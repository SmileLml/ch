<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * View a task.
     *
     * @param  int    $taskID
     * @access public
     * @return void
     */
    public function view($taskID)
    {
        $taskID = (int)$taskID;
        $task   = $this->task->getById($taskID, true);
        if(!$task)
        {
            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'fail', 'code' => 404, 'message' => '404 Not found'));
            return print(js::error($this->lang->notFound) . js::locate($this->createLink('execution', 'all')));
        }
        if(!$this->loadModel('common')->checkPrivByObject('execution', $task->execution)) return print(js::error($this->lang->execution->accessDenied) . js::locate($this->createLink('execution', 'all')));

        $this->session->set('executionList', $this->app->getURI(true), 'execution');

        $this->commonAction($taskID);
        if($this->app->tab == 'project') $this->loadModel('project')->setMenu($this->session->project);

        $from = '';
        if($this->app->tab == 'chteam')
        {
            $from        = 'chteam';
            $chprojectID = $this->dao->select('ch')->from(TABLE_CHPROJECTINTANCES)->where('zentao')->eq($task->execution)->fetch('ch');

            $this->loadModel('chproject')->setMenu($chprojectID);

            $this->session->set('chproject', $chprojectID);
        }

        $execution = $this->execution->getById($task->execution);
        if(!isonlybody() and $execution->type == 'kanban')
        {
            setcookie('taskToOpen', $taskID, 0, $this->config->webRoot, '', false, true);
            return print(js::locate($this->createLink('execution', 'kanban', "executionID=$execution->id")));
        }

        $this->session->project = $task->project;

        if($task->fromBug != 0)
        {
            $bug = $this->loadModel('bug')->getById($task->fromBug);
            $task->bugSteps = '';
            if($bug)
            {
                $task->bugSteps = $this->loadModel('file')->setImgSize($bug->steps);
                foreach($bug->files as $file) $task->files[] = $file;
            }
            $this->view->fromBug = $bug;
        }
        else
        {
            $story = $this->story->getById($task->story, $task->storyVersion);
            $task->storySpec   = empty($story) ? '' : $this->loadModel('file')->setImgSize($story->spec);
            $task->storyVerify = empty($story) ? '' : $this->loadModel('file')->setImgSize($story->verify);
            $task->storyFiles  = zget($story, 'files', array());
        }

        $task->linkedBranch = $this->task->getLinkedBranch($taskID);
        if($task->team) $this->lang->task->assign = $this->lang->task->transfer;

        /* Update action. */
        if($task->assignedTo == $this->app->user->account) $this->loadModel('action')->read('task', $taskID);

        $this->executeHooks($taskID);

        if($this->config->edition == 'ipd') $task = $this->loadModel('story')->getAffectObject('', 'task', $task);

        $title      = "TASK#$task->id $task->name / $execution->name";
        $position[] = html::a($this->createLink('execution', 'browse', "executionID=$task->execution"), $execution->name);
        $position[] = $this->lang->task->common;
        $position[] = $this->lang->task->view;

        $this->view->title        = $title;
        $this->view->position     = $position;
        $this->view->execution    = $execution;
        $this->view->task         = $task;
        $this->view->actions      = $this->loadModel('action')->getList('task', $taskID);
        $this->view->users        = $this->loadModel('user')->getPairs('noletter');
        $this->view->preAndNext   = $this->loadModel('common')->getPreAndNextObject('task', $taskID);
        $this->view->product      = $this->tree->getProduct($task->module);
        $this->view->modulePath   = $this->tree->getParents($task->module);
        $this->view->linkMRTitles = $this->loadModel('mr')->getLinkedMRPairs($taskID, 'task');
        $this->view->linkCommits  = $this->loadModel('repo')->getCommitsByObject($taskID, 'task');
        $this->view->projects     = $this->loadModel('project')->getPairs();
        $this->view->from         = $from;
        $this->display();
    }
}
