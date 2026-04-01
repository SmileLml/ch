<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Batch change the story of task.
     *
     * @param  int    $storyID
     * @access public
     * @return void
     */
    public function batchChangeStory($storyID)
    {
        if($this->post->taskIDList)
        {
            $taskIDList = $this->post->taskIDList;
            $taskIDList = array_unique($taskIDList);
            unset($_POST['taskIDList']);
            $storyEstimate   = $this->dao->select('estimate')->from('zt_story')->where('id')->eq($storyID)->fetch('estimate');
            $projectNum      = $this->dao->select('id, project')->from('zt_task')->where('id')->in($taskIDList)->fetchGroup('project', 'project');
            if(count($projectNum) > 1) return print(js::error($this->lang->task->diffProjectError));

            $isCheckEstimate = $this->task->checkEstimateByStory($taskIDList, $storyID, $storyEstimate);
            if($isCheckEstimate) return print(js::error($this->lang->task->exceedEstimateError));

            $allChanges = $this->task->batchChangeStory($taskIDList, $storyID);
            if(dao::isError()) return print(js::error(dao::getError()));
            foreach($allChanges as $taskID => $changes)
            {
                $this->loadModel('action');
                $actionID = $this->action->create('task', $taskID, 'Edited');
                $this->action->logHistory($actionID, $changes);
            }
            if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchOther');
        }
        return print(js::reload('parent'));
    }
}
