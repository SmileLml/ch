<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Batch stories convert to tasks.
     *
     * @param  int    $executionID
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function batchToTask($executionID = 0, $projectID = 0)
    {
        if($this->app->tab == 'execution' and $executionID) $this->loadModel('execution')->setMenu($executionID);
        if($this->app->tab == 'project' and $executionID) $this->loadModel('execution')->setMenu($executionID);

        if($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($this->post->chproject);

            if($this->post->execution) $executionID = $this->post->execution;

            $execution = $this->execution->getByID($executionID);
            $projectID = $execution->project;
        }

        if(!empty($_POST['name']))
        {
            $response['result']  = 'success';
            $response['message'] = $this->lang->story->successToTask;

            $tasks = $this->story->batchToTask($executionID, $projectID);
            if(dao::isError()) return print(js::error(dao::getError()));

            if($this->viewType == 'json') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'idList' => $tasks));

            $link = $this->app->tab == 'chteam' ? $this->createLink('chproject', 'task', "projectID={$this->post->chproject}") : $this->createLink('execution', 'task', "executionID=$executionID");
            return print(js::locate($link, 'parent'));
        }

        $backLink = $this->app->tab == 'chteam' ? $this->session->teamStoryList: $this->session->storyList;
        if(!$this->post->storyIdList) return print(js::locate($backLink, 'parent'));

        $storyGroup       = array();
        $stories          = $this->story->getByList($_POST['storyIdList']);
        $executionStories = $this->story->getExecutionStoryPairs($executionID);
        foreach($stories as $story)
        {
            if(strpos('draft,reviewing,changing,closed', $story->status) !== false)
            {
                unset($stories[$story->id]);
                continue;
            }

            if(!isset($executionStories[$story->id]))
            {
                unset($stories[$story->id]);
                continue;
            }

            if(isset($storyGroup[$story->module])) continue;
            $storyGroup[$story->module] = $this->story->getExecutionStoryPairs($executionID, 0, 'all', $story->module, 'short', 'active');
        }

        if(empty($stories)) return print(js::error($this->lang->story->noStoryToTask) . js::locate($backLink));

        $this->view->title          = $this->lang->story->batchToTask;
        $this->view->executionID    = $executionID;
        $this->view->chproject      = $this->post->chproject;
        $this->view->syncFields     = empty($_POST['fields']) ? array() : $_POST['fields'];
        $this->view->hourPointValue = empty($_POST['hourPointValue']) ? 0 : $_POST['hourPointValue'];
        $this->view->taskType       = empty($_POST['type']) ? '' : $_POST['type'];
        $this->view->stories        = $stories;
        $this->view->storyGroup     = $storyGroup;
        $this->view->modules        = $this->loadModel('tree')->getTaskOptionMenu($executionID, 0, 0, 'allModule');
        $this->view->members        = $this->loadModel('user')->getTeamMemberPairs($executionID, 'execution', 'nodeleted');
        $this->view->storyTasks     = $this->loadModel('task')->getStoryTaskCounts(array_keys($stories), $executionID);

        $this->display();
    }

}
