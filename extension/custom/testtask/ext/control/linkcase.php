<?php
class mytesttask extends testtask
{
    /**
     * Link cases to a test task.
     *
     * @param  int    $taskID
     * @param  string $type
     * @param  int    $param
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function linkCase($taskID, $type = 'all', $param = 0, $recTotal = 0, $recPerPage = 20, $pageID = 1, $chproject = 0)
    {
        if(!empty($_POST))
        {
            $localUrl = inlink('cases', "taskID=$taskID");
            if($this->app->tab == 'chteam') $localUrl = inlink('cases', "taskID=$taskID&browseType=all&param=0&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&chproject=$chproject");

            $this->testtask->linkCase($taskID, $type);
            $this->locate($localUrl);
        }

        /* Save session. */
        $this->session->set('caseList', $this->app->getURI(true), 'qa');

        /* Get task and product id. */
        $task      = $this->testtask->getById($taskID);
        $productID = $this->product->saveState($task->product, $this->products);
        $product   = $this->product->getByID($productID);

        if(!isset($this->products[$productID])) $this->products[$productID] = $product->name;
        $this->checkAccess($task);

        /* Save session. */
        if($this->app->tab == 'project')
        {
            $this->loadModel('project')->setMenu($task->project);
            $this->lang->modulePageNav = $this->testtask->select($productID, $taskID, 'project', $task->project);
        }
        elseif($this->app->tab == 'execution')
        {
            $this->loadModel('execution')->setMenu($task->execution);
            $this->lang->modulePageNav = $this->testtask->select($productID, $taskID, 'execution', $task->execution);
        }
        elseif($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($chproject);
            $this->view->chprojectID = $chproject;
        }
        else
        {
            $this->testtask->setMenu($this->products, $productID, $task->branch, $taskID);
        }

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Build the search form. */
        $this->loadModel('testcase');
        $this->config->testcase->search['module']                      = 'linkcase';
        $this->config->testcase->search['params']['product']['values'] = array($productID => $this->products[$productID]);
        $this->config->testcase->search['params']['module']['values']  = $this->loadModel('tree')->getOptionMenu($productID, 'case', 0, $task->branch);
        $this->config->testcase->search['actionURL']                   = inlink('linkcase', "taskID=$taskID&type=$type&param=$param");
        $this->config->testcase->search['params']['scene']['values']   = $this->testcase->getSceneMenu($productID, 0, $viewType = 'case', $startSceneID = 0,  0);
        $this->config->testcase->search['style']                       = 'simple';

        if($this->app->tab == 'chteam') $this->config->testcase->search['actionURL'] = inlink('linkcase', "taskID=$taskID&type=$type&param=$param&recTotal=0&recPerPage=20&pageID=1&chproject=$chproject");

        $build   = $this->loadModel('build')->getByID($task->build);
        $stories = array();
        if($build)
        {
            $stories = $this->dao->select('id,title')->from(TABLE_STORY)->where('id')->in($build->stories)->fetchPairs();
            $this->config->testcase->search['params']['story']['values'] = $stories;
            $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'story');
        }

        if($product->shadow) unset($this->config->testcase->search['fields']['product']);
        if($type != 'bystory')
        {
            unset($this->config->testcase->search['fields']['story']);
            unset($this->config->testcase->search['params']['story']);
        }
        if($task->productType == 'normal')
        {
            unset($this->config->testcase->search['fields']['branch']);
            unset($this->config->testcase->search['params']['branch']);
        }
        else
        {
            $this->config->testcase->search['fields']['branch'] = sprintf($this->lang->product->branch, $this->lang->product->branchName[$task->productType]);
            $branchName = $this->loadModel('branch')->getById($task->branch);
            $branches   = array('' => '', BRANCH_MAIN => $this->lang->branch->main, $task->branch => $branchName);
            $this->config->testcase->search['params']['branch']['values'] = $branches;
        }

        if(!$this->config->testcase->needReview) unset($this->config->testcase->search['params']['status']['values']['wait']);
        $this->loadModel('search')->setSearchParams($this->config->testcase->search);

        $this->view->title      = $task->name . $this->lang->colon . $this->lang->testtask->linkCase;
        $this->view->position[] = html::a($this->createLink('testtask', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->testtask->common;
        $this->view->position[] = $this->lang->testtask->linkCase;

        $testTask = $this->testtask->getRelatedTestTasks($productID, $taskID);

        if($this->app->tab == 'chteam')
        {
            $intanceExecutionIDList = $this->chproject->getIntances($chproject);
            $tasks                  = $this->testtask->getExecutionTasks($intanceExecutionIDList, 'execution');
            $taskIdList             = array_keys($tasks);
            foreach($testTask as $key => $val)
            {
                if(!in_array($key, $taskIdList)) unset($testTask[$key]);
            }
        }

        /* Get cases. */
        $cases = $this->testtask->getLinkableCases($productID, $task, $taskID, $type, $param, $pager);
        if($this->app->tab == 'chteam') $cases = $this->testtask->getChLinkableCase($task->execution, $productID, $taskID, $pager);

        $this->view->users     = $this->loadModel('user')->getPairs('noletter');
        $this->view->cases     = $cases;
        $this->view->taskID    = $taskID;
        $this->view->testTask  = $testTask;
        $this->view->pager     = $pager;
        $this->view->task      = $task;
        $this->view->type      = $type;
        $this->view->param     = $param;
        $this->view->suiteList = $this->loadModel('testsuite')->getSuites($task->product);

        $this->display();
    }
    /**
     * Check access.
     *
     * @param  object $testtask
     * @access private
     * @return bool
     */
    private function checkAccess($testtask)
    {
        $canAccess = true;

        $view = $this->app->user->view;

        if(!$this->app->user->admin)
        {
            if($testtask->product   && strpos(",{$view->products},", ",$testtask->product,") === false)   $canAccess = false;
            if($testtask->project   && strpos(",{$view->projects},", ",$testtask->project,") === false)   $canAccess = false;
            if($testtask->execution && strpos(",{$view->sprints},",  ",$testtask->execution,") === false) $canAccess = false;
        }

        if($canAccess) return true;

        echo(js::alert($this->lang->testtask->accessDenied));
        echo js::locate(helper::createLink('testtask', 'browse'));

        return false;
    }
}
