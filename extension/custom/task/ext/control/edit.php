<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Edit a task.
     *
     * @param  int    $taskID
     * @param  string $comment
     * @param  string $kanbanGroup
     * @param  string $from
     * @access public
     * @return void
     */
    public function edit($taskID, $comment = 'false', $kanbanGroup = 'default', $from = '')
    {
        $this->commonAction($taskID);
        $task = $this->task->getById($taskID);
        if($this->app->tab == 'project') $this->loadModel('project')->setMenu($task->project);

        if($this->app->tab == 'chteam')
        {
            $from        = 'chteam';
            $chProjectID = $this->execution->getChProjectByExecution($task->execution);

            $this->loadModel('chproject')->setMenu($chProjectID);
        }

        if(!empty($_POST))
        {
            $this->loadModel('action');
            $changes = array();
            if(!$comment or $comment == 'false')
            {
                $changes = $this->task->update($taskID);
                if(dao::isError()) return print(js::error(dao::getError()));
            }

            if($this->post->comment != '' or !empty($changes))
            {
                $action     = !empty($changes) ? 'Edited' : 'Commented';
                $actionID   = $this->action->create('task', $taskID, $action, $this->post->comment);
                if(!empty($changes)) $this->action->logHistory($actionID, $changes);
            }

            $this->executeHooks($taskID);

            if($_POST['status'] == 'doing') $this->loadModel('common')->syncPPEStatus($taskID);

            if($task->fromBug != 0)
            {
                foreach($changes as $change)
                {
                    if($change['field'] == 'status')
                    {
                        $confirmURL = $this->createLink('bug', 'view', "id=$task->fromBug");
                        $cancelURL  = $this->server->HTTP_REFERER;
                        return print(js::confirm(sprintf($this->lang->task->remindBug, $task->fromBug), $confirmURL, $cancelURL, 'parent', 'parent'));
                    }
                }
            }

            if(isonlybody())
            {
                $execution    = $this->execution->getByID($task->execution);
                $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                if(($this->app->tab == 'execution' or ($this->config->vision == 'lite' and $this->app->tab == 'project' and $this->session->kanbanview == 'kanban')) and $execution->type == 'kanban')
                {
                    $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                    $kanbanData    = $this->loadModel('kanban')->getRDKanban($task->execution, $execLaneType, 'id_desc', 0, $execGroupBy, $rdSearchValue);
                    $kanbanData    = json_encode($kanbanData);

                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban($kanbanData)"));
                }
                if($from == 'taskkanban')
                {
                    $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                    $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($task->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                    $kanbanType      = $execLaneType == 'all' ? 'task' : key($kanbanData);
                    $kanbanData      = $kanbanData[$kanbanType];
                    $kanbanData      = json_encode($kanbanData);

                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban(\"task\", $kanbanData)"));
                }
                return print(js::reload('parent.parent'));
            }

            if(defined('RUN_MODE') && RUN_MODE == 'api')
            {
                return $this->send(array('status' => 'success', 'data' => $taskID));
            }
            else
            {
                return print(js::locate($this->createLink('task', 'view', "taskID=$taskID"), 'parent'));
            }
        }

        $tasks = $this->task->getParentTaskPairs($this->view->execution->id, $this->view->task->parent);
        if(isset($tasks[$taskID])) unset($tasks[$taskID]);

        if(!isset($this->view->members[$this->view->task->assignedTo])) $this->view->members[$this->view->task->assignedTo] = $this->view->task->assignedTo;
        if(isset($this->view->members['closed']) or $this->view->task->status == 'closed') $this->view->members['closed']  = 'Closed';

        $executions = array();
        if(!empty($task->project))      $executions = $this->execution->getByProject($task->project, 'all', 0, true);
        if($this->app->tab == 'chteam') $executions = $this->chproject->getIntancesProjectOptions($chProjectID);

        $this->view->title         = $this->lang->task->edit . 'TASK' . $this->lang->colon . $this->view->task->name;
        $this->view->position[]    = $this->lang->task->common;
        $this->view->position[]    = $this->lang->task->edit;
        $this->view->stories       = $this->story->getExecutionStoryPairs($this->view->execution->id, 0, 'all', '', 'full', 'active');
        $this->view->tasks         = $tasks;
        $this->view->users         = $this->loadModel('user')->getPairs('nodeleted|noclosed', "{$this->view->task->openedBy},{$this->view->task->canceledBy},{$this->view->task->closedBy}");
        $this->view->showAllModule = true;
        $this->view->modules       = $this->tree->getTaskOptionMenu($this->view->task->execution, 0, 0, $this->view->showAllModule ? 'allModule' : '');
        $this->view->executions    = $executions;
        $this->display();
    }
}
