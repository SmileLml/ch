<?php
class mytesttask extends testtask
{
    /**
     * Edit a test task.
     *
     * @param  int    $taskID
     * @param  int    $chprojectID
     * @access public
     * @return void
     */
    public function edit($taskID, $chprojectID = 0)
    {
        /* Get task info. */
        $task      = $this->testtask->getById($taskID);
        $productID = $this->loadModel('product')->saveState($task->product, $this->products);

        if(!empty($_POST))
        {
            if($chprojectID)
            {
                $projectProducts = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($_POST['execution'])->fetchPairs();

                if(!in_array($_POST['product'], $projectProducts)) $_POST['product'] = array_keys($projectProducts)[0];
            }

            $changes = $this->testtask->update($taskID);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            if($changes or $this->post->comment)
            {
                $actionID = $this->loadModel('action')->create('testtask', $taskID, 'edited', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $message = $this->executeHooks($taskID);
            if($message) $this->lang->saveSuccess = $message;

            if($this->app->tab == 'chteam')
            {
                $link = isonlybody() ? 'parent' : $this->session->teamTesttaskList;
            }
            else
            {
                $link = isonlybody() ? 'parent' : $this->session->testtaskList;
            }

            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $link));
        }

        $this->loadModel('project');

        /* Set menu. */
        if($this->app->tab == 'project')
        {
            $this->project->setMenu($task->project);
        }
        elseif($this->app->tab == 'execution')
        {
            $this->loadModel('execution')->setMenu($task->execution);
        }
        elseif($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($chprojectID);

            $this->view->chprojectID = $chprojectID;
        }
        else
        {
            $this->loadModel('qa')->setMenu($this->products, $productID, $task->branch, $taskID);
        }

        if(!isset($this->products[$productID]))
        {
            $product = $this->product->getByID($productID);
            $this->products[$productID] = $product->name;
        }

        /* Create testtask from testtask of test.*/
        $productID = $productID ? $productID : key($this->products);
        $projectID = $this->lang->navGroup->testtask == 'qa' ? 0 : $this->session->project;
        if($this->app->tab == 'chteam')
        {
            $executions = $this->loadModel('chproject')->getIntancesProjectOptions($chprojectID);
        }
        else
        {
            $executions = empty($productID) ? array() : $this->product->getExecutionPairsByProduct($productID, 0, 'id_desc', $projectID);
        }
        $executionID = $task->execution;
        if($executionID)
        {
            $execution = $this->loadModel('execution')->getById($executionID);
            if(!isset($executions[$executionID]))
            {
                $executions[$executionID] = $execution->name;
                if(empty($execution->multiple))
                {
                    $project = $this->loadModel('project')->getById($execution->project);
                    $executions[$executionID] = $project->name . "({$this->lang->project->disableExecution})";
                }
            }
            if($this->app->tab == 'chteam')
            {
                $project = $this->loadModel('project')->getById($execution->project);

                if($project->hasProduct == 0)
                {
                    $linkProductID = $this->loadModel('product')->getProductIDByProject($project->id);
                    $linkProduct   = $this->loadModel('product')->getById($linkProductID);

                    if($linkProduct->shadow) $productID = $linkProductID;
                }
            }

            $builds = $this->loadModel('build')->getBuildPairs($productID, 'all', 'noempty,notrunk,withexecution', $executionID, 'execution', $task->build, false);
        }
        else
        {
            $builds = $this->loadModel('build')->getBuildPairs($productID, 'all', 'noempty,notrunk,withexecution', $task->project, 'project', $task->build, false);
        }

        $this->view->title        = $this->products[$productID] . $this->lang->colon . $this->lang->testtask->edit;
        $this->view->task         = $task;
        $this->view->project      = $this->project->getByID($projectID);
        $this->view->executions   = $executions;
        $this->view->builds       = empty($productID) ? array() : $builds;
        $this->view->testreports  = $this->loadModel('testreport')->getPairs($task->product, $task->testreport);
        $this->view->users        = $this->loadModel('user')->getPairs('nodeleted|noclosed', $task->owner);
        $this->view->contactLists = $this->user->getContactLists($this->app->user->account, 'withnote');

        $this->display();
    }
}
