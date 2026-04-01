<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Batch edit task.
     *
     * @param  int    $executionID
     * @access public
     * @return void
     */
    public function batchEdit($executionID = 0)
    {
        if($this->post->names)
        {
            $isBeyondEstimate = $this->task->isBeyondEstimate($_POST, 'edit');
            if(!$isBeyondEstimate) return $this->send(array('result' => 'fail', 'message' => $this->lang->task->beyondEstimateError));
            $allChanges = $this->task->batchUpdate();
            if(dao::isError()) return print(js::error(dao::getError()));

            if(!empty($allChanges))
            {
                /* updateStatus is a description of whether to update the responsibility performance*/
                $waitTaskID = false;
                foreach($allChanges as $taskID => $changes)
                {
                    if(empty($changes)) continue;

                    /* Determine whether the status of a task has been changed, if the status of a task has been changed, set $updateStatus to taskID*/
                    if($waitTaskID == false)
                    {
                        foreach($changes as $changeField)
                        {
                            if($changeField['field'] == 'status' && $changeField['new'] == 'doing')
                            {
                                $waitTaskID = $taskID;
                                break;
                            }
                        }
                    }

                    $actionID = $this->loadModel('action')->create('task', $taskID, 'Edited');
                    $this->action->logHistory($actionID, $changes);

                    $task = $this->task->getById($taskID);
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
                    if($waitTaskID !== false) $this->loadModel('common')->syncPPEStatus($waitTaskID);
                }
            }
            $this->loadModel('score')->create('ajax', 'batchOther');

            $locate = $this->app->tab == 'chteam' ? $this->session->teamTaskList : $this->session->taskList;
            return print(js::locate($locate, 'parent'));
        }

        if(!$this->post->taskIDList) return print(js::locate($this->session->taskList, 'parent'));

        $taskIDList = array_unique($this->post->taskIDList);

        $chProjectID = 0;
        if($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject');

            $chProjectID = $executionID;
            $this->chproject->setMenu($chProjectID);

            $chProject       = $this->chproject->getById($chProjectID);
            $executionIdList = $this->chproject->getIntances($chProjectID);
            $projectIdList   = $this->dao->select('project')->from(TABLE_PROJECT)->where('id')->in($executionIdList)->fetchPairs();
            $projectList     = $this->dao->select('id,name')->from(TABLE_PROJECT)->where('id')->in($projectIdList)->fetchPairs();

            $this->view->title      = $chProject->name . $this->lang->colon . $this->lang->task->batchEdit;
            $this->view->position[] = $chProject->name;
            $this->view->execution  = $chProject;
        }
        /* The tasks of execution. */
        elseif($executionID)
        {
            $execution = $this->execution->getById($executionID);
            $this->execution->setMenu($execution->id);

            /* Set modules and members. */
            $showAllModule = isset($this->config->task->allModule) ? $this->config->task->allModule : '';
            $modules       = $this->tree->getTaskOptionMenu($executionID, 0, 0, $showAllModule ? 'allModule' : '');
            $modules       = array('ditto' => $this->lang->task->ditto) + $modules;

            $this->view->title      = $execution->name . $this->lang->colon . $this->lang->task->batchEdit;
            $this->view->position[] = html::a($this->createLink('execution', 'browse', "executionID=$execution->id"), $execution->name);
            $this->view->execution  = $execution;
            $this->view->modules    = $modules;
        }
        /* The tasks of my. */
        else
        {
            /* Set my menu. */
            $this->loadModel('my');
            $this->lang->my->menu->work['subModule'] = 'task';

            $this->view->position[] = html::a($this->createLink('my', 'task'), $this->lang->my->task);
            $this->view->title      = $this->lang->task->batchEdit;
            $this->view->users      = $this->loadModel('user')->getPairs('noletter');
        }

        /* Get edited tasks. */
        $tasks = $this->dao->select('*')->from(TABLE_TASK)->where('id')->in($taskIDList)->fetchAll('id');
        $teams = $this->dao->select('*')->from(TABLE_TASKTEAM)->where('task')->in($taskIDList)->fetchGroup('task', 'id');

        $noEditTask = '';
        foreach($tasks as $id => $task)
        {
            $isNotCloseProject = true;
            if(!empty($task->project))
            {
                $projectapprovalID = $this->dao->select('instance')->from('zt_project')->where('id')->eq($task->project)->fetch();
                
                $projectapproval = $this->dao->select('status')->from('zt_flow_projectapproval')->where('id')->eq($projectapprovalID->instance)->fetch();
                
                if($projectapproval->status == 'cancelled' || $projectapproval->status == 'finished') $isNotCloseProject = false;
            }
            if(!$isNotCloseProject)
            {
                $noEditTask .= "#$id ";
                unset($tasks[$id]);
            }
        }

        if(!empty($noEditTask)) echo js::alert(sprintf($this->lang->story->batchEditProjectapprovalTip, $noEditTask));
        if(empty($tasks)) return print(js::locate($this->session->taskList));


        /* Get execution teams. */
        $executionIDList = array();
        foreach($tasks as &$task)
        {
            if(!in_array($task->execution, $executionIDList)) $executionIDList[] = $task->execution;
            if($chProjectID) $task->projectName = $projectList[$task->project];
        }

        $executionTeams = $this->dao->select('*')->from(TABLE_TEAM)->where('root')->in($executionIDList)->andWhere('type')->eq('execution')->fetchGroup('root', 'account');

        /* Judge whether the editedTasks is too large and set session. */
        $countInputVars  = count($tasks) * (count(explode(',', $this->config->task->custom->batchEditFields)) + 3);
        $showSuhosinInfo = common::judgeSuhosinSetting($countInputVars);
        if($showSuhosinInfo) $this->view->suhosinInfo = extension_loaded('suhosin') ? sprintf($this->lang->suhosinInfo, $countInputVars) : sprintf($this->lang->maxVarsInfo, $countInputVars);

        /* Set Custom*/
        if(isset($execution))
        {
            foreach(explode(',', $this->config->task->customBatchEditFields) as $field)
            {
                if($execution->type == 'stage' and strpos('estStarted,deadline', $field) !== false) continue;
                $customFields[$field] = $this->lang->task->$field;
            }
        }
        else
        {
            foreach(explode(',', $this->config->task->customBatchEditFields) as $field) $customFields[$field] = $this->lang->task->$field;
        }

        $this->view->customFields = $customFields;
        $this->view->showFields   = $this->config->task->custom->batchEditFields;

        /* Assign. */
        $this->view->position[]     = $this->lang->task->common;
        $this->view->position[]     = $this->lang->task->batchEdit;
        $this->view->executionID    = $executionID;
        $this->view->priList        = array('0' => '', 'ditto' => $this->lang->task->ditto) + $this->lang->task->priList;
        $this->view->statusList     = array('' => '',  'ditto' => $this->lang->task->ditto) + $this->lang->task->statusList;
        $this->view->typeList       = array('' => '',  'ditto' => $this->lang->task->ditto) + $this->lang->task->typeList;
        $this->view->taskIDList     = $taskIDList;
        $this->view->tasks          = $tasks;
        $this->view->teams          = $teams;
        $this->view->executionTeams = $executionTeams;
        $this->view->executionName  = isset($execution) ? $execution->name : '';
        $this->view->executionType  = isset($execution) ? $execution->type : '';
        $this->view->users          = $this->loadModel('user')->getPairs('nodeleted');
        $this->view->chProjectID    = $chProjectID;

        $this->display();
    }
}
