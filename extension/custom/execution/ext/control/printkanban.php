<?php
helper::importControl('execution');
class myExecution extends execution
{
    /**
     * Print kanban.
     *
     * @param  int    $executionID
     * @param  string $orderBy
     * @access public
     * @return void
     */
    public function printKanban($executionID, $orderBy = 'id_asc')
    {
        $this->view->title = $this->lang->execution->printKanban;
        $contents = array('story', 'wait', 'doing', 'done', 'cancel');

        if($_POST)
        {
            if($this->app->tab == 'chteam') $executionID = $this->loadModel('chproject')->getIntances($executionID);

            $stories    = $this->loadModel('story')->getExecutionStories($executionID, 0, 0, $orderBy);
            $storySpecs = $this->story->getStorySpecs(array_keys($stories));

            $order = 1;
            foreach($stories as $story) $story->order = $order++;

            $kanbanTasks = $this->execution->getKanbanTasks($executionID, "id");
            $kanbanBugs  = $this->loadModel('bug')->getExecutionBugs($executionID);

            $users       = array();
            $taskAndBugs = array();
            foreach($kanbanTasks as $task)
            {
                $storyID = $task->storyID;
                $status  = $task->status;
                $users[] = $task->assignedTo;

                $taskAndBugs[$status]["task{$task->id}"] = $task;
            }
            foreach($kanbanBugs as $bug)
            {
                $storyID = $bug->story;
                $status  = $bug->status;
                $status  = $status == 'active' ? 'wait' : ($status == 'resolved' ? ($bug->resolution == 'postponed' ? 'cancel' : 'done') : $status);
                $users[] = $bug->assignedTo;

                $taskAndBugs[$status]["bug{$bug->id}"] = $bug;
            }

            $datas = array();
            foreach($contents as $content)
            {
                if($content != 'story' and !isset($taskAndBugs[$content])) continue;
                $datas[$content] = $content == 'story' ? $stories : $taskAndBugs[$content];
            }

            unset($this->lang->story->stageList['']);
            unset($this->lang->story->stageList['wait']);
            unset($this->lang->story->stageList['planned']);
            unset($this->lang->story->stageList['projected']);
            unset($this->lang->story->stageList['released']);
            unset($this->lang->task->statusList['']);
            unset($this->lang->task->statusList['wait']);
            unset($this->lang->task->statusList['closed']);
            unset($this->lang->bug->statusList['']);
            unset($this->lang->bug->statusList['closed']);

            $originalDatas = $datas;
            if($this->post->content == 'increment')
            {
                $prevKanbans = $this->execution->getPrevKanban($executionID);
                foreach($datas as $type => $data)
                {
                    if(isset($prevKanbans[$type]))
                    {
                        $prevData = $prevKanbans[$type];
                        foreach($prevData as $id)
                        {
                            if(isset($data[$id])) unset($datas[$type][$id]);
                        }
                    }
                }
            }

            /* Close the page when there is no data. */
            $hasData = false;
            foreach($datas as $data)
            {
                if(!empty($data)) $hasData = true;
            }
            if(!$hasData) return print(js::alert($this->lang->execution->noPrintData) . js::close());

            $this->execution->saveKanbanData($executionID, $originalDatas);

            $hasBurn = $this->post->content == 'all';
            if($hasBurn)
            {
                /* Get date list. */
                $executionInfo    = $this->execution->getByID($executionID);
                list($dateList) = $this->execution->getDateList($executionInfo->begin, $executionInfo->end, 'noweekend');
                $chartData      = $this->execution->buildBurnData($executionID, $dateList, 'noweekend');
            }

            $this->view->hasBurn    = $hasBurn;
            $this->view->datas      = $datas;
            $this->view->chartData  = isset($chartData) ? $chartData : array();
            $this->view->storySpecs = $storySpecs;
            $this->view->realnames  = $this->loadModel('user')->getRealNameAndEmails($users);
            $this->view->executionID  = $executionID;

            return $this->display();
        }

        if($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($executionID);
            $execution = $this->chproject->getById($executionID);
        }
        else
        {
            $this->execution->setMenu($executionID);
            $execution = $this->execution->getById($executionID);
        }

        $this->view->executionID = $executionID;
        $this->display();
    }
}
