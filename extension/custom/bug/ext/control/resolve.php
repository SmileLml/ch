<?php
helper::importControl('bug');
class mybug extends bug
{
    /**
     * Resolve a bug.
     *
     * @param  int    $bugID
     * @param  string $extra
     * @param  string $from taskkanban
     * @access public
     * @return void
     */
    public function resolve($bugID, $extra = '', $from = '')
    {
        $bug = $this->bug->getById($bugID);
        if($bug->execution) $execution = $this->loadModel('execution')->getByID($bug->execution);

        if(!empty($_POST))
        {
            $changes = $this->bug->resolve($bugID, $extra);
            if(dao::isError()) return print(js::error(dao::getError()));
            $files = $this->loadModel('file')->saveUpload('bug', $bugID);

            $fileAction = !empty($files) ? $this->lang->addFiles . join(',', $files) . "\n" : '';
            $actionID = $this->action->create('bug', $bugID, 'Resolved', $fileAction . $this->post->comment, $this->post->resolution . ($this->post->duplicateBug ? ':' . filter_var($this->post->duplicateBug, FILTER_SANITIZE_NUMBER_INT) : ''));
            $this->action->logHistory($actionID, $changes);

            $bug = $this->bug->getById($bugID);

            $this->executeHooks($bugID);

            if($bug->toTask != 0)
            {
                /* If task is not finished, update it's status. */
                $task = $this->task->getById($bug->toTask);
                if($task->status != 'done')
                {
                    $confirmURL = $this->createLink('task', 'view', "taskID=$bug->toTask");
                    unset($_GET['onlybody']);
                    $cancelURL  = $this->createLink('bug', 'view', "bugID=$bugID");
                    return print(js::confirm(sprintf($this->lang->bug->remindTask, $bug->toTask), $confirmURL, $cancelURL, 'parent', 'parent.parent'));
                }
            }

            $extra = str_replace(array(',', ' '), array('&', ''), $extra);
            parse_str($extra, $output);
            if(isonlybody())
            {
                $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                if($this->app->tab == 'execution' and isset($execution->type) and $execution->type == 'kanban')
                {
                    $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                    $regionID      = !empty($output['regionID']) ? $output['regionID'] : 0;
                    $kanbanData    = $this->loadModel('kanban')->getRDKanban($bug->execution, $execLaneType, 'id_desc', $regionID, $execGroupBy, $rdSearchValue);
                    $kanbanData    = json_encode($kanbanData);
                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban($kanbanData, $regionID)"));
                }
                elseif($from == 'taskkanban')
                {
                    $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                    $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($bug->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                    $kanbanType      = $execLaneType == 'all' ? 'bug' : key($kanbanData);
                    $kanbanData      = $kanbanData[$kanbanType];
                    $kanbanData      = json_encode($kanbanData);
                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban(\"bug\", $kanbanData)"));
                }
                else
                {
                    return print(js::closeModal('parent.parent', 'this', "typeof(parent.parent.setTitleWidth) == 'function' ? parent.parent.setTitleWidth() : parent.parent.location.reload()"));
                }
            }
            if(defined('RUN_MODE') && RUN_MODE == 'api')
            {
                return $this->send(array('status' => 'success', 'data' => $bugID));
            }
            else
            {
                return print(js::locate($this->createLink('bug', 'view', "bugID=$bugID"), 'parent'));
            }
        }

        $projectID  = $bug->project;
        $productID  = $bug->product;
        $users      = $this->user->getPairs('noclosed');
        $assignedTo = $bug->openedBy;
        if(!isset($users[$assignedTo])) $assignedTo = $this->bug->getModuleOwner($bug->module, $productID);
        unset($this->lang->bug->resolutionList['tostory']);

        $this->bug->checkBugExecutionPriv($bug);
        $this->qa->setMenu($this->products, $productID, $bug->branch);

        $this->config->moreLinks['duplicateBug'] = inlink('ajaxGetProductBugs', "productID={$productID}&bugID={$bugID}&type=json");

        $builds = $this->loadModel('build')->getBuildPairs($productID, $bug->branch, 'withbranch,noreleased');
        if($this->app->tab == 'chteam') $builds = $this->loadModel('build')->getBuildPairs($productID, $bug->branch, 'withbranch,noreleased', $bug->execution, 'execution');

        $this->view->title          = $this->products[$productID] . $this->lang->colon . $this->lang->bug->resolve;
        $this->view->bug            = $bug;
        $this->view->users          = $users;
        $this->view->assignedTo     = $assignedTo;
        $this->view->executions     = $this->loadModel('product')->getExecutionPairsByProduct($productID, $bug->branch ? "0,{$bug->branch}" : 0, 'id_desc', $projectID, 'stagefilter');
        $this->view->builds         = $builds;
        $this->view->actions        = $this->action->getList('bug', $bugID);
        $this->view->execution      = isset($execution) ? $execution : '';
        $this->display();
    }
}
