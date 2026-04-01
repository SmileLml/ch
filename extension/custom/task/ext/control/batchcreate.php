<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Batch create task.
     *
     * @param int    $executionID
     * @param int    $storyID
     * @param int    $iframe
     * @param int    $taskID
     * @param string $extra
     *
     * @access public
     * @return void
     */
    public function batchCreate($executionID = 0, $storyID = 0, $moduleID = 0, $taskID = 0, $iframe = 0, $extra = '')
    {
        $this->execution->getLimitedExecution();
        $limitedExecutions = !empty($_SESSION['limitedExecutions']) ? $_SESSION['limitedExecutions'] : '';
        if(strpos(",{$limitedExecutions},", ",$executionID,") !== false)
        {
            echo js::alert($this->lang->task->createDenied);
            return print(js::locate($this->createLink('execution', 'task', "executionID=$executionID")));
        }

        $execution = $this->execution->getById($executionID);

        if($this->app->tab == 'my')
        {
            $taskLink = $this->createLink('my', 'work', 'mode=task');
        }
        elseif($this->app->tab == 'project' and $execution->multiple)
        {
            $taskLink = $this->createLink('project', 'execution', "browseType=all&projectID={$execution->project}");
        }
        else
        {
            $taskLink  = $this->createLink('execution', 'browse', "executionID=$executionID");
        }

        /* Set menu. */
        $this->execution->setMenu($execution->id);
        if($this->app->tab == 'project') $this->loadModel('project')->setMenu($this->session->project);

        if($this->app->tab == 'chteam')
        {
            $chProjectID = $this->execution->getChProjectByExecution($executionID);
            $taskLink    = $this->createLink('chproject', 'task', "projectID=$chProjectID");

            $this->loadModel('chproject')->setMenu($chProjectID);

            $this->view->executions = $this->chproject->getIntancesProjectOptions($chProjectID);
        }

        /* When common task are child tasks, query whether common task are consumed. */
        $taskConsumed = 0;
        if($taskID) $taskConsumed = $this->dao->select('consumed')->from(TABLE_TASK)->where('id')->eq($taskID)->andWhere('parent')->eq(0)->fetch('consumed');

        if(!empty($_POST))
        {
            $mails = $this->task->batchCreate($executionID, $extra);
            if(dao::isError()) return print(js::error(dao::getError()));

            $taskIDList = array();
            foreach($mails as $mail) $taskIDList[] = $mail->taskID;

            /* Return task id list when call the API. */
            if($this->viewType == 'json' or (defined('RUN_MODE') && RUN_MODE == 'api')) return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'idList' => $taskIDList));

            /* If link from no head then reload. */
            if(isonlybody())
            {
                if($this->app->tab == 'execution' or $this->config->vision == 'lite')
                {
                    $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                    $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                    if($execution->type == 'kanban')
                    {
                        $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                        $kanbanData    = $this->loadModel('kanban')->getRDKanban($executionID, $execLaneType, 'id_desc', 0, $execGroupBy, $rdSearchValue);
                        $kanbanData    = json_encode($kanbanData);

                        return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban($kanbanData, 0)"));
                    }
                    else
                    {
                        $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                        $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($executionID, $execLaneType, $execGroupBy, $taskSearchValue);
                        $kanbanType      = $execLaneType == 'all' ? 'task' : key($kanbanData);
                        $kanbanData      = $kanbanData[$kanbanType];
                        $kanbanData      = json_encode($kanbanData);

                        return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban(\"task\", $kanbanData)"));
                    }
                }
                else
                {
                    return print(js::reload('parent.parent'));
                }
            }
            return print(js::locate($taskLink, 'parent'));
        }

        $story = $this->story->getByID($storyID);
        if($story)
        {
            $moduleID = $story->module;
            $stories  = $this->story->getExecutionStoryPairs($executionID, 0, 'all', $moduleID, 'short', 'active');
        }
        else
        {
            $stories = $this->story->getExecutionStoryPairs($executionID, 0, 'all', 0, 'short', 'active');
        }

        $members       = $this->loadModel('user')->getTeamMemberPairs($executionID, 'execution', 'nodeleted');
        $showAllModule = isset($this->config->execution->task->allModule) ? $this->config->execution->task->allModule : '';
        $modules       = $this->loadModel('tree')->getTaskOptionMenu($executionID, 0, 0, $showAllModule ? 'allModule' : '');

        /* Set Custom*/
        foreach(explode(',', $this->config->task->customBatchCreateFields) as $field)
        {
            if($execution->type == 'stage' and strpos('estStarted,deadline', $field) !== false) continue;
            $customFields[$field] = $this->lang->task->$field;
        }

        $showFields = $this->config->task->custom->batchCreateFields;
        if($execution->lifetime == 'ops' or $execution->attribute == 'request' or $execution->attribute == 'review')
        {
            unset($customFields['story']);
            $showFields = str_replace(',story,', ',', ",$showFields,");
            $showFields = trim($showFields, ',');
        }

        $this->view->customFields = $customFields;
        $this->view->showFields   = $showFields;

        if($execution->type == 'kanban')
        {
            $extra = str_replace(array(',', ' '), array('&', ''), $extra);
            parse_str($extra, $output);

            $this->loadModel('kanban');
            $regionPairs = $this->kanban->getRegionPairs($executionID, 0, 'execution');
            $regionID    = !empty($output['regionID']) ? $output['regionID'] : key($regionPairs);
            $lanePairs   = $this->kanban->getLanePairsByRegion($regionID, 'task');
            $laneID      = isset($output['laneID']) ? $output['laneID'] : key($lanePairs);

            $this->view->regionID    = $regionID;
            $this->view->laneID      = $laneID;
            $this->view->regionPairs = $regionPairs;
            $this->view->lanePairs   = $lanePairs;
        }

        $title      = $execution->name . $this->lang->colon . $this->lang->task->batchCreate;
        $position[] = html::a($taskLink, $execution->name);
        $position[] = $this->lang->task->common;
        $position[] = $this->lang->task->batchCreate;

        if($taskID) $this->view->parentTitle = $this->dao->select('name')->from(TABLE_TASK)->where('id')->eq($taskID)->fetch('name');
        if($taskID) $this->view->parentPri   = $this->dao->select('pri')->from(TABLE_TASK)->where('id')->eq($taskID)->fetch('pri');

        $this->view->title        = $title;
        $this->view->position     = $position;
        $this->view->execution    = $execution;
        $this->view->stories      = $stories;
        $this->view->modules      = $modules;
        $this->view->parent       = $taskID;
        $this->view->storyID      = $storyID;
        $this->view->story        = $story;
        $this->view->storyTasks   = $this->task->getStoryTaskCounts(array_keys($stories), $executionID);
        $this->view->members      = $members;
        $this->view->moduleID     = $moduleID;
        $this->view->taskConsumed = $taskConsumed;
        $this->view->executionID  = $executionID;

        $this->display();
    }
}
