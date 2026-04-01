<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Create a task.
     *
     * @param  int    $executionID
     * @param  int    $storyID
     * @param  int    $moduleID
     * @param  int    $taskID
     * @param  int    $todoID
     * @param  string $extra
     * @param  int    $bugID
     * @param  int    $chProjectID
     * @param  int    $formRelation
     * @access public
     * @return void
     */
    public function create($executionID = 0, $storyID = 0, $moduleID = 0, $taskID = 0, $todoID = 0, $extra = '', $bugID = 0, $chProjectID = 0, $formRelation = 0)
    {
        if(empty($this->app->user->view->sprints) and !$executionID) $this->locate($this->createLink('execution', 'create'));
        $extra = str_replace(array(',', ' '), array('&', ''), $extra);
        parse_str($extra, $output);

        $this->loadModel('chproject');

        if($this->app->tab == 'chteam')
        {
            $executionIdList = $this->loadModel('chproject')->getIntances($chProjectID);
            $executionID     = $executionID ? $executionID : $this->dao->select('id')->from(TABLE_EXECUTION)->where('id')->in($executionIdList)->fetch('id');
        }

        if(!empty($executionID)) $execution = $this->execution->getById($executionID);
        $executions  = $this->execution->getPairs(0, 'all', isset($execution) ? (!common::canModify('execution', $execution) ? 'noclosed' : '') : 'noclosed');
        $executionID = $this->execution->saveState($executionID, $executions);
        $execution   = $this->execution->getById($executionID);

        $this->execution->setMenu($executionID);
        if($this->app->tab == 'project') $this->loadModel('project')->setMenu($this->session->project);
        if($this->app->tab == 'chteam')  $this->chproject->setMenu($chProjectID);

        $this->execution->getLimitedExecution();
        $limitedExecutions = !empty($_SESSION['limitedExecutions']) ? $_SESSION['limitedExecutions'] : '';
        if(strpos(",{$limitedExecutions},", ",$executionID,") !== false)
        {
            echo js::alert($this->lang->task->createDenied);
            return print(js::locate($this->createLink('execution', 'task', "executionID=$executionID")));
        }

        $task = new stdClass();
        $task->module     = $moduleID;
        $task->mode       = '';
        $task->assignedTo = '';
        $task->name       = '';
        $task->story      = $storyID;
        $task->type       = '';
        $task->pri        = '3';
        $task->estimate   = '';
        $task->desc       = '';
        $task->estStarted = '';
        $task->deadline   = '';
        $task->mailto     = '';
        $task->color      = '';
        if($taskID > 0)
        {
            $task        = $this->task->getByID($taskID);
            $executionID = $task->execution;

            /* Emptying consumed hours when copy task. */
            if($task->mode == 'multi')
            {
                foreach($task->team as $teamMember) $teamMember->consumed = 0;
            }
        }

        if($todoID > 0)
        {
            $todo = $this->loadModel('todo')->getById($todoID);
            $task->name = $todo->name;
            $task->pri  = $todo->pri;
            $task->desc = $todo->desc;
        }

        if($bugID > 0)
        {
            $bug = $this->loadModel('bug')->getById($bugID);
            $task->name       = $bug->title;
            $task->pri        = $bug->pri;
            $task->pri        = !empty($bug->pri) ? $bug->pri : '3';
            $task->assignedTo = array($bug->assignedTo);
        }

        $taskLink  = $this->createLink('execution', 'browse', "executionID=$executionID&tab=task");

        $this->loadModel('kanban');
        if($execution->type == 'kanban')
        {
            $regionPairs = $this->kanban->getRegionPairs($execution->id, 0, 'execution');
            $regionID    = !empty($output['regionID']) ? $output['regionID'] : key($regionPairs);
            $lanePairs   = $this->kanban->getLanePairsByRegion($regionID, 'task');
            $laneID      = isset($output['laneID']) ? $output['laneID'] : key($lanePairs);

            $this->view->regionID    = $regionID;
            $this->view->laneID      = $laneID;
            $this->view->regionPairs = $regionPairs;
            $this->view->lanePairs   = $lanePairs;
        }

        if(!empty($_POST))
        {
            $response['result'] = 'success';

            setcookie('lastTaskModule', (int)$this->post->module, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, false);
            if($this->post->execution) $executionID = (int)$this->post->execution;

            /* Create task here. */
            $tasksID = $this->task->create($executionID, $bugID);
            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }

            /* if the count of tasksID is 1 then check exists. */
            if(count($tasksID) == 1)
            {
                $taskID = current($tasksID);
                if($taskID['status'] == 'exists')
                {
                    $response['locate']  = $this->createLink('task', 'view', "taskID={$taskID['id']}");
                    $response['message'] = sprintf($this->lang->duplicate, $this->lang->task->common);
                    return $this->send($response);
                }
            }

            /* Create actions. */
            $this->loadModel('action');
            foreach($tasksID as $taskID)
            {
                /* if status is exists then this task has exists not new create. */
                if($taskID['status'] == 'exists') continue;

                $taskID = $taskID['id'];
                $this->action->create('task', $taskID, 'Opened', '');
            }

            /* Create task in kanban. */
            $kanbanID = $execution->type == 'kanban' ? $executionID : $_POST['execution'];

            $laneID = isset($output['laneID']) ? $output['laneID'] : 0;
            if(!empty($_POST['lane'])) $laneID = $_POST['lane'];

            $columnID = $this->kanban->getColumnIDByLaneID($laneID, 'wait');
            if(empty($columnID)) $columnID = isset($output['columnID']) ? $output['columnID'] : 0;

            if(!empty($laneID) and !empty($columnID)) $this->kanban->addKanbanCell($kanbanID, $laneID, $columnID, 'task', $taskID);
            if(empty($laneID) or empty($columnID)) $this->kanban->updateLane($kanbanID, 'task');

            /* To do status. */
            if($todoID > 0)
            {
                $this->dao->update(TABLE_TODO)->set('status')->eq('done')->where('id')->eq($todoID)->exec();
                $this->action->create('todo', $todoID, 'finished', '', "TASK:$taskID");

                if(($this->config->edition == 'biz' || $this->config->edition == 'max') && $todo->type == 'feedback' && $todo->idvalue) $this->loadModel('feedback')->updateStatus('todo', $todo->idvalue, 'done');
            }

            $message = $this->executeHooks($taskID);
            if($message) $this->lang->saveSuccess = $message;
            $response['message'] = $this->lang->saveSuccess;

            /* Return task id when call the API. */
            if($this->viewType == 'json' or (defined('RUN_MODE') && RUN_MODE == 'api')) return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'id' => $taskID));

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

                        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.updateKanban($kanbanData, 0)"));
                    }
                    else
                    {
                        $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                        $kanbanData      = $this->kanban->getExecutionKanban($executionID, $execLaneType, $execGroupBy, $taskSearchValue);
                        $kanbanType      = $execLaneType == 'all' ? 'task' : key($kanbanData);
                        $kanbanData      = $kanbanData[$kanbanType];
                        $kanbanData      = json_encode($kanbanData);

                        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.updateKanban(\"task\", $kanbanData)"));
                    }
                }
                else
                {
                    return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'parent'));
                }
            }

            /* Locate the browser. */
            if($this->app->getViewType() == 'xhtml')
            {
                $taskLink  = $this->createLink('task', 'view', "taskID=$taskID", 'html');
                $response['locate'] = $taskLink;
                return $this->send($response);
            }

            if($this->post->after == 'continueAdding')
            {
                $storyParam = $this->post->story ? $this->post->story : '';

                $response['message'] = $this->lang->task->successSaved . $this->lang->task->afterChoices['continueAdding'];
                $response['locate']  = $this->createLink('task', 'create', "executionID=$executionID&storyID={$storyParam}&moduleID=$moduleID&taskID=0&todoID=0&extra=&bugID=0&chProjectID=$chProjectID");
                if($this->app->tab == 'project') $response['locate'] = 'reload';

                if($this->app->tab == 'chteam' && ($storyParam == $storyID || empty($storyParam))) $response['locate'] = 'reload';

                return $this->send($response);
            }
            elseif($this->post->after == 'toTaskList')
            {
                setcookie('moduleBrowseParam',  0, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);

                $taskLink = $this->createLink('execution', 'task', "executionID=$executionID&status=unclosed&param=0&orderBy=id_desc");

                if($this->app->tab == 'chteam') $taskLink = $this->createLink('chproject', 'task', "projectID=$chProjectID");

                $response['locate'] = $taskLink;
                return $this->send($response);
            }
            elseif($this->post->after == 'toStoryList')
            {
                $response['locate'] = $this->createLink('execution', 'story', "executionID=$executionID");
                if($this->config->vision == 'lite')
                {
                    $projectID = $this->dao->select('project')->from(TABLE_EXECUTION)->where('id')->eq($executionID)->fetch('project');
                    $response['locate'] = $this->createLink('projectstory', 'story', "projectID=$projectID");
                }

                if($this->app->tab == 'chteam') $response['locate'] = $this->createLink('chproject', 'story', "projectID=$chProjectID");

                return $this->send($response);
            }
            else
            {
                if($this->app->tab == 'chteam') $taskLink = $this->session->teamTaskList;

                $response['locate'] = $taskLink;
                return $this->send($response);
            }
        }

        $users            = $this->loadModel('user')->getPairs('noclosed|nodeleted');
        $members          = $this->loadModel('user')->getTeamMemberPairs($executionID, 'execution', 'nodeleted');
        $showAllModule    = true;
        $moduleOptionMenu = $this->tree->getTaskOptionMenu($executionID, 0, 0, $showAllModule ? 'allModule' : '');

        /* Fix bug #3381. When the story module is the root module. */
        if($storyID)
        {
            $task->module = $this->dao->findByID($storyID)->from(TABLE_STORY)->fetch('module');
        }
        else
        {
            $task->module = $task->module ? $task->module : (int)$this->cookie->lastTaskModule;
            if(!isset($moduleOptionMenu[$task->module])) $task->module = 0;
        }

        /* Get block id of assinge to me. */
        $blockID = 0;
        if(isonlybody())
        {
            $blockID = $this->dao->select('id')->from(TABLE_BLOCK)
                ->where('block')->eq('assingtome')
                ->andWhere('module')->eq('my')
                ->andWhere('account')->eq($this->app->user->account)
                ->orderBy('order_desc')
                ->fetch('id');
        }

        $title      = $execution->name . $this->lang->colon . $this->lang->task->create;
        $position[] = html::a($taskLink, $execution->name);
        $position[] = $this->lang->task->common;
        $position[] = $this->lang->task->create;

        $projectID = $execution ? $execution->project : 0;

        /* Set Custom*/
        foreach(explode(',', $this->config->task->customCreateFields) as $field) $customFields[$field] = $this->lang->task->$field;

        if(!empty($projectID))
        {
            $executions = $this->execution->getByProject($projectID, 'all', 0, true);

            $executionKey = 0;
            $executionModifyList = $this->execution->getByIdList(array_keys($executions));
            foreach($executionModifyList as $modifykey)
            {
                if(!common::canModify('execution', $modifykey)) $executionKey = $modifykey->id;
                if($executionKey) unset($executions[$executionKey]);
            }
        }

        if($chProjectID) $executions = $this->chproject->getIntancesProjectOptions($chProjectID);

        $stories = $this->story->getExecutionStoryPairs(array_keys($executions), 0, 'all', '', '', 'active');

        $lifetimeList  = array();
        $attributeList = array();
        $executionList = $this->execution->getByIdList(array_keys($executions));
        foreach($executionList as $id => $object)
        {
            $lifetimeList[$id]  = $object->lifetime;
            $attributeList[$id] = $object->attribute;
        }

        $testStoryIdList = $this->loadModel('story')->getTestStories(array_keys($stories), $execution->id);
        /* Stories that can be used to create test tasks. */
        $testStories     = array();
        foreach($stories as $storyID => $storyTitle)
        {
            if(empty($storyID) or isset($testStoryIdList[$storyID])) continue;
            $testStories[$storyID] = $storyTitle;
        }

        $this->view->customFields  = $customFields;
        $this->view->showFields    = $this->config->task->custom->createFields;
        $this->view->showAllModule = $showAllModule;

        $gobackLink = (isset($output['from']) and $output['from'] == 'global') ? $this->createLink('execution', 'task', "executionID=$executionID") : '';

        if($this->app->tab == 'chteam')
        {
            $from = (isset($output['from']) && $output['from'] == 'story') ? 'story' : 'task';
            $gobackLink = $this->createLink('chproject', $from, "projectID=$chProjectID");
        }

        $this->view->title            = $title;
        $this->view->testStories      = $testStories;
        $this->view->position         = $position;
        $this->view->gobackLink       = $gobackLink;
        $this->view->execution        = $execution;
        $this->view->executions       = $executions;
        $this->view->lifetimeList     = $lifetimeList;
        $this->view->attributeList    = $attributeList;
        $this->view->task             = $task;
        $this->view->users            = $users;
        $this->view->storyID          = $storyID;
        $this->view->stories          = $stories;
        $this->view->testStoryIdList  = $testStoryIdList;
        $this->view->members          = $members;
        $this->view->blockID          = $blockID;
        $this->view->moduleOptionMenu = $moduleOptionMenu;
        $this->view->projectID        = $projectID;
        $this->view->formRelation     = $formRelation;
        $this->view->productID        = $this->loadModel('product')->getProductIDByProject($projectID);;
        $this->view->features         = $this->execution->getExecutionFeatures($execution);

        $this->display();
    }
}
