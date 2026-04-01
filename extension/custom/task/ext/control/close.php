<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Close a task.
     *
     * @param  int    $taskID
     * @param  string $extra
     * @access public
     * @return void
     */
    public function close($taskID, $extra = '')
    {
        $this->commonAction($taskID);

        $extra = str_replace(array(',', ' '), array('&', ''), $extra);
        parse_str($extra, $output);

        if(!empty($_POST))
        {
            $this->loadModel('action');
            $changes = $this->task->close($taskID, $extra);

            if(dao::isError()) return print(js::error(dao::getError()));

            if($this->post->comment != '' or !empty($changes))
            {
                $actionID = $this->action->create('task', $taskID, 'Closed', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $this->executeHooks($taskID);

            if(isonlybody())
            {
                $task      = $this->task->getById($taskID);
                $execution = $this->execution->getByID($task->execution);

                if(isset($task->fromIssue) and $task->fromIssue > 0)
                {
                    $fromIssue = $this->loadModel('issue')->getByID($task->fromIssue);
                    if($fromIssue->status != 'closed')
                    {
                        $confirmURL = $this->createLink('issue', 'close', "id=$task->fromIssue");
                        unset($_GET['onlybody']);
                        $cancelURL  = $this->createLink('task', 'view', "taskID=$taskID");
                        return print(js::confirm(sprintf($this->lang->task->remindIssue, $task->fromIssue), $confirmURL, $cancelURL, 'parent', 'parent.parent'));
                    }
                }

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
                if(isset($output['from']) && $output['from'] == 'taskkanban')
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

            if(defined('RUN_MODE') && RUN_MODE == 'api')
            {
                return $this->send(array('status' => 'success', 'data' => $taskID));
            }
            else
            {
                return print(js::locate($this->createLink('task', 'view', "taskID=$taskID"), 'parent'));
            }
        }

        $this->view->title      = $this->view->execution->name . $this->lang->colon .$this->lang->task->finish;
        $this->view->position[] = $this->lang->task->finish;
        $this->view->users      = $this->loadModel('user')->getPairs('noletter');

        $this->display();
    }
}
