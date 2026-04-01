<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Start a task.
     *
     * @param  int    $taskID
     * @param  string $extra
     * @access public
     * @return void
     */
    public function start($taskID, $extra = '')
    {
        $this->commonAction($taskID);

        $extra = str_replace(array(',', ' '), array('&', ''), $extra);
        parse_str($extra, $output);

        $task = $this->task->getById($taskID);

        if(!empty($_POST))
        {
            $this->loadModel('action');
            $changes = $this->task->start($taskID, $extra);

            if(dao::isError())
            {
                if($this->viewType == 'json' or (defined('RUN_MODE') && RUN_MODE == 'api')) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
                return print(js::error(dao::getError()));
            }

            if($task->story)
            {
                $linkRequirementIds = $this->dao->select('id,AID')->from(TABLE_RELATION)->where('BID')->eq($task->story)->andWhere('BType')->eq('story')->fetchPairs('id', 'AID');
                if($linkRequirementIds)
                {
                    $activeRequirementIds = $this->dao->select('id')->from(TABLE_STORY)->where('status')->eq('active')->andWhere('id')->in($linkRequirementIds)->andWhere('deleted')->eq(0)->fetchPairs('id', 'id');
                    if($activeRequirementIds)
                    {
                        $this->dao->update('zt_story')->set('status')->eq('devInProgress')->where('id')->in($activeRequirementIds)->exec();
                        foreach($linkRequirementIds as $linkRequirementID)
                        {
                            $actionID = $this->loadModel('action')->create('story', $linkRequirementID, 'changedevinprogress');
                            $result['changes']   = array();
                            $result['changes'][] = ['field' => 'status', 'old' => 'active', 'new' => 'devInProgress'];
                            $this->loadModel('action')->logHistory($actionID, $result['changes']);
                        }
                    }
                }
            }

            $act = $this->post->left == 0 ? 'Finished' : 'Started';
            $actionID = $this->action->create('task', $taskID, $act, $this->post->comment);
            if(!empty($changes)) $this->action->logHistory($actionID, $changes);

            $this->executeHooks($taskID);
            $this->loadModel('common')->syncPPEStatus($taskID);

            /* Remind whether to update status of the bug, if task which from that bug has been finished. */
            if($changes and $this->task->needUpdateBugStatus($task))
            {
                foreach($changes as $change)
                {
                    if($change['field'] == 'status' and $change['new'] == 'done')
                    {
                        $confirmURL = $this->createLink('bug', 'view', "id=$task->fromBug");
                        unset($_GET['onlybody']);
                        $cancelURL  = $this->createLink('task', 'view', "taskID=$taskID");
                        return print(js::confirm(sprintf($this->lang->task->remindBug, $task->fromBug), $confirmURL, $cancelURL, 'parent', 'parent.parent'));
                    }
                }
            }

            if($this->viewType == 'json' or (defined('RUN_MODE') && RUN_MODE == 'api')) return $this->send(array('result' => 'success'));

            if(isonlybody())
            {
                $execution    = $this->execution->getByID($task->execution);
                $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                if(($this->app->tab == 'execution' or ($this->config->vision == 'lite' and $this->app->tab == 'project' and $this->session->kanbanview == 'kanban')) and $execution->type == 'kanban')
                {
                    $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                    $regionID      = !empty($output['regionID']) ? $output['regionID'] : 0;
                    $kanbanData    = $this->loadModel('kanban')->getRDKanban($task->execution, $execLaneType, 'id_desc', $regionID, $execGroupBy, $rdSearchValue);
                    $kanbanData    = json_encode($kanbanData);

                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban($kanbanData, $regionID)"));
                }
                if(isset($output['from']) and $output['from'] == 'taskkanban')
                {
                    $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                    $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($task->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                    $kanbanType      = $execLaneType == 'all' ? 'task' : key($kanbanData);
                    $kanbanData      = $kanbanData[$kanbanType];
                    $kanbanData      = json_encode($kanbanData);

                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban(\"task\", $kanbanData)"));
                }
                return print(js::closeModal('parent.parent', 'this', "function(){parent.parent.location.reload();}"));
            }
            return print(js::locate($this->createLink('task', 'view', "taskID=$taskID"), 'parent'));
        }

        $assignedTo = empty($task->assignedTo) ? $this->app->user->account : $task->assignedTo;
        if(!empty($task->team)) $assignedTo = $this->task->getAssignedTo4Multi($task->team, $task);

        $this->view->title      = $this->view->execution->name . $this->lang->colon .$this->lang->task->start;
        $this->view->position[] = $this->lang->task->start;

        $this->view->users      = $this->loadModel('user')->getPairs('noletter');
        $this->view->members    = $this->loadModel('user')->getTeamMemberPairs($task->execution, 'execution', 'nodeleted');
        $this->view->assignedTo = $assignedTo;
        $this->display();
    }
}
