<?php
class mytesttask extends testtask
{
    public function cases($taskID, $browseType = 'all', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1, $chproject = 0)
    {
        /* Load modules. */
        $this->loadModel('datatable');
        $this->loadModel('testcase');
        $this->loadModel('execution');

        /* Save the session. */
        $this->session->set('caseList', $this->app->getURI(true), 'qa');

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Set the browseType and moduleID. */
        $browseType = strtolower($browseType);

        /* Get task and product info, set menu. */
        $task = $this->testtask->getById($taskID);
        if(!$task) return print(js::error($this->lang->testtask->checkLinked) . js::locate('back'));

        $this->checkAccess($task);

        $productID = $task->product;
        $product   = $this->product->getByID($productID);
        if(!isset($this->products[$productID])) $this->products[$productID] = $product->name;

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
        setcookie('preTaskID', $taskID, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, true);

        /* Determines whether an object is editable. */
        $canBeChanged = common::canBeChanged('testtask', $task);

        if($this->cookie->preTaskID != $taskID)
        {
            $_COOKIE['taskCaseModule'] = 0;
            setcookie('taskCaseModule', 0, 0, $this->config->webRoot, '', $this->config->cookieSecure, true);
        }

        if($browseType == 'bymodule') setcookie('taskCaseModule', (int)$param, 0, $this->config->webRoot, '', $this->config->cookieSecure, true);
        if($browseType != 'bymodule') $this->session->set('taskCaseBrowseType', $browseType);
        if($browseType == 'bysuite')  $suiteName = $this->loadModel('testsuite')->getById($param)->name;

        /* Set the browseType, moduleID and queryID. */
        $moduleID   = ($browseType == 'bymodule') ? (int)$param : ($browseType == 'bysearch' ? 0 : ($this->cookie->taskCaseModule ? $this->cookie->taskCaseModule : 0));
        $queryID    = ($browseType == 'bysearch' or $browseType == 'bysuite') ? (int)$param : 0;

        /* Get execution type and set assignedToList. */
        $execution = $this->execution->getById($task->execution);
        if($execution and $execution->acl == 'private')
        {
            $assignedToList = $this->loadModel('user')->getTeamMemberPairs($execution->id, 'execution', 'nodeleted');
        }
        else
        {
            $assignedToList = $this->loadModel('user')->getPairs('noclosed|noletter|nodeleted|qafirst');
        }

        /* Append id for secend sort. */
        $sort = common::appendOrder($orderBy, 't2.id');

        /* Get test cases. */
        $runs = $this->testtask->getTaskCases($productID, $browseType, $queryID, $moduleID, $sort, $pager, $task);
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', false);

        if($this->config->edition == 'ipd') $runs = $this->loadModel('story')->getAffectObject($runs, 'case');
        $runs = $this->loadModel('story')->checkNeedConfirm($runs);

        $case2RunMap = array();
        foreach($runs as $run) $case2RunMap[$run->case] = $run->id;

        $scenesGroup = $this->testtask->getSceneCases($productID, $runs);
        $runs        = !empty($scenesGroup['runs']) ? $scenesGroup['runs'] : array();
        $scenes      = !empty($scenesGroup['scenes']) ? $scenesGroup['scenes'] : array();

        /* Build the search form. */
        $this->loadModel('testcase');
        $this->config->testcase->search['module']                      = 'testcase';
        $this->config->testcase->search['params']['product']['values'] = array($productID => $this->products[$productID], 'all' => $this->lang->testcase->allProduct);
        $this->config->testcase->search['params']['module']['values']  = $this->loadModel('tree')->getOptionMenu($productID, $viewType = 'case');
        $this->config->testcase->search['params']['status']['values']  = array('' => '') + $this->lang->testcase->statusList;
        $this->config->testcase->search['params']['lib']['values']     = $this->loadModel('caselib')->getLibraries();
        $this->config->testcase->search['params']['scene']['values']   = $this->testcase->getSceneMenu($productID, $moduleID, 'case', 0,  0);

        $this->config->testcase->search['queryID']              = $queryID;
        $this->config->testcase->search['fields']['assignedTo'] = $this->lang->testtask->assignedTo;
        $this->config->testcase->search['params']['assignedTo'] = array('operator' => '=', 'control' => 'select', 'values' => 'users');

        if($this->app->tab == 'chteam')
        {
            $this->config->testcase->search['actionURL'] = inlink('cases', "taskID=$taskID&browseType=bySearch&queryID=myQueryID&orderBy=id_desc&recTotal=0&recPerPage=20&pageID=1&chproject=$chproject");
        }
        else
        {
            $this->config->testcase->search['actionURL'] = inlink('cases', "taskID=$taskID&browseType=bySearch&queryID=myQueryID");
        }
        if(!$this->config->testcase->needReview) unset($this->config->testcase->search['params']['status']['values']['wait']);
        if($product->shadow) unset($this->config->testcase->search['fields']['product']);
        unset($this->config->testcase->search['fields']['branch']);
        unset($this->config->testcase->search['params']['branch']);
        $this->loadModel('search')->setSearchParams($this->config->testcase->search);

        $showModule = $this->loadModel('setting')->getItem("owner={$this->app->user->account}&module=datatable&section=testtaskCases&key=showModule");

        $this->view->title      = $this->products[$productID] . $this->lang->colon . $this->lang->testtask->cases;
        $this->view->position[] = html::a($this->createLink('testtask', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->testtask->common;
        $this->view->position[] = $this->lang->testtask->cases;

        $this->view->productID      = $productID;
        $this->view->productName    = $this->products[$productID];
        $this->view->task           = $task;
        $this->view->runs           = array_merge($scenes, $runs);
        $this->view->case2RunMap    = $case2RunMap;
        $this->view->users          = $this->loadModel('user')->getPairs('noclosed|qafirst|noletter');
        $this->view->assignedToList = $assignedToList;
        $this->view->moduleTree     = $this->loadModel('tree')->getTreeMenu($productID, 'case', 0, array('treeModel', 'createTestTaskLink'), $taskID, $task->branch);
        $this->view->browseType     = $browseType;
        $this->view->param          = $param;
        $this->view->orderBy        = $orderBy;
        $this->view->taskID         = $taskID;
        $this->view->moduleID       = $moduleID;
        $this->view->moduleName     = $moduleID ? $this->tree->getById($moduleID)->name : $this->lang->tree->all;
        $this->view->treeClass      = $browseType == 'bymodule' ? '' : 'hidden';
        $this->view->pager          = $pager;
        $this->view->branches       = $this->loadModel('branch')->getPairs($productID);
        $this->view->setModule      = true;
        $this->view->showBranch     = false;
        $this->view->suites         = $this->loadModel('testsuite')->getSuitePairs($productID);
        $this->view->suiteName      = isset($suiteName) ? $suiteName : $this->lang->testtask->browseBySuite;
        $this->view->canBeChanged   = $canBeChanged;
        $this->view->automation     = $this->loadModel('zanode')->getAutomationByProduct($productID);
        $this->view->modulePairs    = $showModule ? $this->tree->getModulePairs($productID, 'case', $showModule) : array();

        $this->display();
    }

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
