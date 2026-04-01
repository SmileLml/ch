<?php
/**
 * The control file of chproject module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     chproject
 * @version     $Id: control.php 5094 2013-07-10 08:46:15Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class chproject extends control
{
    /**
     * Ch team project list.
     *
     * @param  int    $teamID
     * @param  int    $intanceProjectID
     * @param  string $status
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function browse($teamID = 0, $intanceProjectID = 0, $status = 'all', $orderBy = 'order_asc', $recTotal = 0, $recPerPage = 100, $pageID = 1)
    {
        $this->loadModel('project');

        $this->session->set('chprojectList', $this->app->getURI(true), 'chproject');

        $this->loadModel('program')->refreshStats(); // Refresh stats fields of projects.

        $teamID = $this->loadModel('chteam')->setMenu($teamID);

        /* Load pager and get tasks. */
        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        $executionStats = $this->chproject->getStatData($teamID, $intanceProjectID, $status, '', $orderBy, $pager);

        $this->view->title            = $this->lang->chproject->common;
        $this->view->position[]       = $this->lang->chproject->common;
        $this->view->executionStats   = $executionStats;
        $this->view->intanceProjectID = $intanceProjectID;
        $this->view->intanceProjects  = $this->project->getPairsByModel('agileplus,agileplus'); // Use "agileplus,agileplus" to avoid get projects of ['scrum', 'agileplus'] model.
        $this->view->teamID           = $teamID;
        $this->view->pager            = $pager;
        $this->view->orderBy          = $orderBy;
        $this->view->users            = $this->loadModel('user')->getPairs('noletter');
        $this->view->status           = $status;

        $this->display();
    }

    /**
     * Create a ch project.
     *
     * @param int    $teamID
     * @access public
     * @return void
     */
    public function create($teamID)
    {
        $this->loadModel('chteam');
        $this->loadModel('execution');
        $this->loadModel('product');
        $this->loadModel('project');
        $this->app->loadLang('program');
        $this->app->loadLang('stage');
        $this->app->loadLang('programplan');

        $teamID = $this->chteam->setMenu($teamID);

        if(!empty($_POST))
        {
            /* Filter empty plans. */
            if(!empty($_POST['plans']))
            {
                foreach($_POST['plans'] as $key => $planItem) $_POST['plans'][$key] = array_filter($_POST['plans'][$key]);
                $_POST['plans'] = array_filter($_POST['plans']);
            }

            $projectID = $this->chproject->create($teamID);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->loadModel('action')->create('chproject', $projectID, 'opened');

            if($this->viewType == 'json') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess));

            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => inlink('browse', "teamID=$teamID") . '#app=chteam'));
        }

        $this->loadModel('user');
        $poUsers = $this->user->getPairs('noclosed|nodeleted|pofirst', '', $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["PM"] = $this->config->user->moreLink;

        $pmUsers = $this->user->getPairs('noclosed|nodeleted|pmfirst', '', $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["PO"] = $this->config->user->moreLink;

        $qdUsers = $this->user->getPairs('noclosed|nodeleted|qdfirst', '', $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["QD"] = $this->config->user->moreLink;

        $rdUsers = $this->user->getPairs('noclosed|nodeleted|devfirst', '', $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["RD"] = $this->config->user->moreLink;

        $this->view->title               = $this->app->tab == 'execution' ? $this->lang->execution->createExec : $this->lang->execution->create;
        $this->view->position[]          = $this->view->title;
        $this->view->allProducts         = array(0 => '') + $this->product->getPairs();
        $this->view->allProjects         = array(0 => '') + $this->project->getPairsByModel('agileplus,agileplus', 0, 'noclosed,multiple');
        $this->view->team                = $this->chteam->getByID($teamID);
        $this->view->multiBranchProducts = $this->product->getMultiBranchPairs();
        $this->view->poUsers             = $poUsers;
        $this->view->pmUsers             = $pmUsers;
        $this->view->qdUsers             = $qdUsers;
        $this->view->rdUsers             = $rdUsers;
        $this->view->users               = $this->loadModel('user')->getPairs('nodeleted|noclosed');
        $this->view->isStage             = false; // 团队迭代只支持敏捷和融合敏捷模型，不存在阶段型执行。

        $this->display();
    }

    /**
     * Edit a ch project.
     *
     * @param  int    $projectID
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function edit($projectID, $teamID = 0)
    {
        $this->loadModel('user');
        $this->loadModel('chteam');
        $this->loadModel('project');
        $this->loadModel('product');
        $this->loadModel('execution');
        $this->loadModel('productplan');

        if(!$teamID) $teamID = $this->dao->select('team')->from(TABLE_CHPROJECTTEAM)->where('project')->eq($projectID)->fetch('team');

        /* Set menu. */
        $this->chteam->setMenu($teamID);

        if(!empty($_POST))
        {
            $changes = $this->chproject->update($projectID);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            if($changes)
            {
                $actionID = $this->loadModel('action')->create('chproject', $projectID, 'edited');
                $this->action->logHistory($actionID, $changes);
            }

            /* If link from no head then reload. */
            $locate = isonlybody() ? 'parent' : $this->session->chprojectList . '#app=chteam';

            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $locate));
        }

        $project             = $this->chproject->getById($projectID);
        $intances            = $this->chproject->getIntances($projectID, true);
        $branches            = $this->chproject->getBranchesByProject($projectID);
        $linkedProductIdList = empty($branches) ? '' : array_keys($branches);

        $allProducts = $this->product->getPairs();
        $allProducts = array(0 => '') + $allProducts;

        $linkedProducts   = $intances ? $this->product->getProducts($intances, 'all', '', true, $linkedProductIdList, false) : [];
        $plans            = $this->productplan->getGroupByProduct(array_keys($linkedProducts), 'skipParent|unexpired');
        $executionStories = $this->project->getStoriesByProject($executionID);

        list($productPlans, $linkedBranchList, $linkedBranches, $unmodifiableProducts, $unmodifiableBranches, $linkedStoryIdList) = $this->chproject->getLinkedBranchList($linkedProducts, $branches, $plans, $executionStories);

        $poUsers = $this->user->getPairs('noclosed|nodeleted|pofirst', $project->PO, $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["PM"] = $this->config->user->moreLink;

        $pmUsers = $this->user->getPairs('noclosed|nodeleted|pmfirst',  $project->PM, $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["PO"] = $this->config->user->moreLink;

        $qdUsers = $this->user->getPairs('noclosed|nodeleted|qdfirst',  $project->QD, $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["QD"] = $this->config->user->moreLink;

        $rdUsers = $this->user->getPairs('noclosed|nodeleted|devfirst', $project->RD, $this->config->maxCount);
        if(!empty($this->config->user->moreLink)) $this->config->moreLinks["RD"] = $this->config->user->moreLink;

        $intanceProjectIDList = $this->chproject->getIntanceProjectPairs($projectID, 'id,project');
        $intanceProjects      = $this->dao->select('id,name')->from(TABLE_PROJECT)->where('id')->in($intanceProjectIDList)->fetchPairs();
        $project->projectName = implode(',', $intanceProjects);

        $title      = $this->lang->execution->edit . $this->lang->colon . $project->name;
        $position[] = html::a($browseProjectLink, $project->name);
        $position[] = $this->lang->execution->edit;

        $projects = $this->project->getPairsByModel('agileplus,agileplus', 0, 'noclosed,multiple');

        $this->view->title          = $title;
        $this->view->position       = $position;
        $this->view->project        = $project;
        $this->view->poUsers        = $poUsers;
        $this->view->pmUsers        = $pmUsers;
        $this->view->qdUsers        = $qdUsers;
        $this->view->rdUsers        = $rdUsers;
        $this->view->users          = $this->user->getPairs('nodeleted|noclosed');
        $this->view->allProducts    = $allProducts;
        $this->view->linkedProducts = $linkedProducts;
        $this->view->linkedBranches = $linkedBranches;
        $this->view->branches       = $branches;
        $this->view->productPlans   = $productPlans;
        $this->view->branchGroups   = $this->execution->getBranchByProduct(array_keys($linkedProducts), 0, 'noclosed', $linkedBranchList);
        $this->view->teamMembers    = $this->chproject->getProjectTeam($projectID, 'execution');
        $this->view->newProjects    = array(0 => '') + array_diff($projects, $intanceProjects);

        $this->display();
    }

    /**
     * Tasks of a chproject.
     *
     * @param  int    $projectID
     * @param  int    $intanceProjectID
     * @param  string $status
     * @param  string $param
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function task($projectID = 0, $intanceProjectID = 0, $status = 'unclosed', $param = 0, $orderBy = '', $recTotal = 0, $recPerPage = 100, $pageID = 1)
    {
        $this->loadModel('tree');
        $this->loadModel('search');
        $this->loadModel('teamtask');
        $this->loadModel('datatable');
        $this->loadModel('setting');
        $this->loadModel('product');
        $this->loadModel('user');
        $this->loadModel('execution');

        // @Todo check the projectID is valid or not.

        /* Set browse type. */
        $browseType = strtolower($status);

        $projectID = $this->chproject->setMenu($projectID);
        $project   = $this->chproject->getById($projectID);

        /* Get products by project. */
        $products = $this->chproject->getIntanceProductPairs($projectID);
        setcookie('preProjectID', $projectID, $this->config->cookieLife, $this->config->webRoot, '', false, true);

        /* Save the recently five executions visited in the cookie. */
        $recentProjects = isset($this->config->chproject->recentProjects) ? explode(',', $this->config->chproject->recentProjects) : [];
        array_unshift($recentProjects, $projectID);
        $recentProjects = array_unique($recentProjects);
        $recentProjects = array_slice($recentProjects, 0, 5);
        $recentProjects = join(',', $recentProjects);
        if(!isset($this->config->chproject->recentProjects) or $this->config->chproject->recentProjects != $recentProjects) $this->setting->updateItem($this->app->user->account . 'common.chproject.recentProjects', $recentProjects);
        if(!isset($this->config->chproject->lastProject)    or $this->config->chproject->lastProject    != $projectID)      $this->setting->updateItem($this->app->user->account . 'common.chproject.lastProject', $projectID);

        $this->setTaskCookie($projectID, $browseType, $param);

        if($browseType == 'bymodule' and $this->session->taskBrowseType == 'bysearch') $this->session->set('taskBrowseType', 'unclosed');

        /* Set queryID, moduleID and productID. */
        $queryID   = ($browseType == 'bysearch')  ? (int)$param : 0;
        $moduleID  = ($browseType == 'bymodule')  ? (int)$param : (($browseType == 'bysearch' or $browseType == 'byproduct') ? 0 : $this->cookie->moduleBrowseParam);
        $productID = ($browseType == 'byproduct') ? (int)$param : (($browseType == 'bysearch' or $browseType == 'bymodule')  ? 0 : $this->cookie->productBrowseParam);

        /* Save to session. */
        $this->session->set('teamTaskList', $this->app->getURI(true) . "#app={$this->app->tab}", 'chproject');
        $this->session->set('chproject', $projectID, 'chproject');

        /* Process the order by field. */
        if(!$orderBy) $orderBy = $this->cookie->teamTaskOrder ? $this->cookie->teamTaskOrder : 'status,id_asc';
        setcookie('teamTaskOrder', $orderBy, 0, $this->config->webRoot, '', false, true);

        /* Append id for secend sort. */
        $sort = common::appendOrder($orderBy);

        /* Header and position. */
        $this->view->title = $project->name . $this->lang->colon . $this->lang->execution->task;

        /* Load pager and get tasks. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'mhtml' || $this->app->getViewType() == 'xhtml') $recPerPage = 10;
        $pager = new pager($recTotal, $recPerPage, $pageID);

        /* Get tasks. */
        $tasks = $this->chproject->getTasks($productID, $projectID, $intanceProjectID, $browseType, $queryID, $moduleID, $sort, $pager);

        /* Get product. */
        $product = $this->product->getById($productID);

        /* team member pairs. */
        $memberPairs = $this->chproject->getMemberPairsByProject($projectID);
        $memberPairs = $this->user->processAccountSort($memberPairs);

        $showAllModule = isset($this->config->execution->task->allModule) ? $this->config->execution->task->allModule : '';
        $extra         = (isset($this->config->execution->task->allModule) && $this->config->execution->task->allModule == 1) ? 'allModule' : '';
        $showModule    = !empty($this->config->datatable->executionTask->showModule) ? $this->config->datatable->executionTask->showModule : '';
        $this->view->modulePairs = $showModule ? $this->tree->getModulePairs($projectID, 'task', $showModule) : array();

        $modules = $this->chproject->getModulePairs($projectID, $showAllModule);

        /* Build the search form. */
        $actionURL = $this->createLink('chproject', 'task', "projectID=$projectID&intanceProjectID=$intanceProjectID&status=bySearch&param=myQueryID");
        $this->config->execution->search['onMenuBar'] = 'yes';
        $this->execution->buildTaskSearchForm($projectID, $this->executions, $queryID, $actionURL, $modules);

        $intanceProjectPairs = $this->chproject->getIntanceProjectPairs($projectID, 'id,project');

        $linkParams = ['chproject' => $projectID, 'intanceProjectID' => $intanceProjectID];
        $moduleTree = $this->tree->getTeamTreeMenu('task', $projectID, 0, $linkParams);

        $intances = $this->chproject->getIntances($projectID);

        /* Assign. */
        $this->view->tasks            = $tasks;
        $this->view->summary          = $this->execution->summary($tasks);
        $this->view->tabID            = 'task';
        $this->view->pager            = $pager;
        $this->view->recTotal         = $pager->recTotal;
        $this->view->recPerPage       = $pager->recPerPage;
        $this->view->orderBy          = $orderBy;
        $this->view->browseType       = $browseType;
        $this->view->status           = $status;
        $this->view->users            = $this->user->getPairs('noletter|all');
        $this->view->param            = $param;
        $this->view->projectID        = $projectID;
        $this->view->project          = $project;
        $this->view->productID        = $productID;
        $this->view->product          = $product;
        $this->view->moduleID         = $moduleID;
        $this->view->memberPairs      = $memberPairs;
        $this->view->branchGroups     = $this->loadModel('branch')->getByProducts($products);
        $this->view->setModule        = true;
        $this->view->showAllModule    = !$project->multiple && !$project->hasProduct ? false : true;
        $this->view->canBeChanged     = common::canModify('execution', $project); // Determines whether an object is editable.
        $this->view->moduleTree       = $moduleTree;
        $this->view->intanceProjects  = $this->loadModel('project')->getPairsByIdList($intanceProjectPairs);
        $this->view->intanceProjectID = $intanceProjectID;
        $this->view->intance          = array_keys($intances)[0];

        $this->display();
    }

    /**
     * Browse stories of a ch project.
     *
     * @param  int    $projectID
     * @param  int    $intanceProjectID
     * @param  string $storyType
     * @param  string $orderBy
     * @param  string $type
     * @param  string $param
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function story($projectID = 0, $intanceProjectID = 0, $storyType = 'story', $orderBy = 'order_desc', $type = 'all', $param = 0, $recTotal = 0, $recPerPage = 50, $pageID = 1)
    {
        /* Load these models. */
        $this->loadModel('story');
        $this->loadModel('user');
        $this->loadModel('product');
        $this->loadModel('datatable');
        $this->loadModel('execution');
        $this->loadModel('project');
        $this->app->loadLang('datatable');
        $this->app->loadLang('testcase');

        $projectID = $this->chproject->setMenu($projectID);
        $project   = $this->chproject->getById($projectID);

        $type      = strtolower($type);
        $queryID   = ($type == 'bysearch') ? $param : 0;
        $productID = $this->fixProductID($type, $param); // Fix productID by param.

        setcookie('storyPreExecutionID', $projectID, $this->config->cookieLife, $this->config->webRoot, '', false, true);

        $this->setStoryCookie($projectID, $type, $param);

        /* Save session. */
        $this->session->set('chproject', $projectID);
        $this->session->set('teamStoryList', $this->app->getURI(true), $this->app->tab);

        /* Process the order by field. */
        if(!$orderBy) $orderBy = $this->cookie->teamStoryOrder ? $this->cookie->teamStoryOrder : 'pri';
        setcookie('teamStoryOrder', $orderBy, 0, $this->config->webRoot, '', false, true);

        /* Append id for secend sort. */
        $sort = common::appendOrder($orderBy);
        if(strpos($sort, 'pri_') !== false) $sort = str_replace('pri_', 'priOrder_', $sort);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'xhtml') $recPerPage = 10;
        $pager = new pager($recTotal, $recPerPage, $pageID);

        $intances   = $this->chproject->getIntances($projectID);
        $executions = $intanceProjectID ? $this->execution->getPairsByProjectID($intanceProjectID, 'id') : [];
        $stories    = $this->story->getExecutionStories($executions ? array_intersect($intances, $executions) : $intances, 0, 0, $sort, $type, $param, $storyType, '', '', $pager);

        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'story', false);

        if(!empty($stories))
        {
            $stories = $this->story->mergeReviewer($stories);
            $stories = $this->story->appendChproject($stories, $projectID);
        }

        $products = $this->product->getProducts($projectID);

        /* Build search form. */
        $this->buildStorySearchForm($projectID, $productID, $products, $queryID, $storyType, $orderBy, $intanceProjectID);

        /* Get related tasks, bugs, cases count of each story. */
        $storyIdList = array();
        foreach($stories as $story)
        {
            $storyIdList[$story->id] = $story->id;
            if(!empty($story->children))
            {
                foreach($story->children as $child) $storyIdList[$child->id] = $child->id;
            }
        }

        if($this->cookie->storyModuleParam) $this->view->module = $this->loadModel('tree')->getById($this->cookie->storyModuleParam);
        if($this->cookie->storyProductParam) $this->view->product = $this->loadModel('product')->getById($this->cookie->storyProductParam);
        if($this->cookie->storyBranchParam)
        {
            $branchID = $this->cookie->storyBranchParam;
            if(strpos($branchID, ',') !== false) list($productID, $branchID) = explode(',', $branchID);
            $this->view->branch  = $this->loadModel('branch')->getById($branchID, $productID);
        }

        /* Display of branch label. */
        $this->view->showBranch = $this->loadModel('branch')->showBranch($this->cookie->storyProductParam, $this->cookie->storyModuleParam, $projectID);

        /* Get execution's product. */
        $productPairs = $this->chproject->getIntanceProductsPairs($projectID);

        if(empty($productID)) $productID = key($productPairs);
        $showModule = !empty($this->config->datatable->executionStory->showModule) ? $this->config->datatable->executionStory->showModule : '';
        $this->view->modulePairs = $showModule ? $this->tree->getModulePairs($type == 'byproduct' ? $param : 0, 'story', $showModule) : array();

        $multiBranch          = false;
        $executionProductList = $this->loadModel('product')->getProducts($projectID);
        foreach($executionProductList as $executionProduct)
        {
            if($executionProduct->type != 'normal')
            {
                $multiBranch = true;
                break;
            }
        }

        if($this->config->edition == 'ipd') $stories = $this->loadModel('story')->getAffectObject($stories, 'story');

        $intanceProjectPairs = $this->chproject->getIntanceProjectPairs($projectID, 'id,project');

        $linkParams = ['chproject' => $projectID, 'intanceProjectID' => $intanceProjectID, 'storyType' => $storyType, 'orderBy' => $orderBy];
        $this->view->moduleTree = $this->tree->getTeamTreeMenu('story', $projectID, 0, $linkParams);

        /* Assign. */
        $this->view->title                 = $project->name . $this->lang->colon . $this->lang->execution->story;
        $this->view->position[]            = $this->lang->execution->story;
        $this->view->productID             = $productID;
        $this->view->projectID             = $projectID;
        $this->view->project               = $project;
        $this->view->stories               = $stories;
        $this->view->linkedTaskStories     = $this->story->getIdListWithTask($projectID);
        $this->view->summary               = $this->product->summary($stories, $storyType);
        $this->view->orderBy               = $orderBy;
        $this->view->storyType             = $storyType;
        $this->view->type                  = $this->session->executionStoryBrowseType;
        $this->view->param                 = $param;
        $this->view->isAllProduct          = ($this->cookie->storyProductParam or $this->cookie->storyModuleParam or $this->cookie->storyBranchParam) ? false : true;
        $this->view->tabID                 = 'story';
        $this->view->storyTasks            = $this->loadModel('task')->getStoryTaskCounts($storyIdList);
        $this->view->storyBugs             = $this->loadModel('bug')->getStoryBugCounts($storyIdList);
        $this->view->storyCases            = $this->loadModel('testcase')->getStoryCaseCounts($storyIdList);
        $this->view->users                 = $this->user->getPairs('noletter');
        $this->view->pager                 = $pager;
        $this->view->setModule             = true;
        $this->view->canBeChanged          = common::canModify('execution', $execution); // Determines whether an object is editable.
        $this->view->storyStages           = $this->product->batchGetStoryStage($stories);
        $this->view->multiBranch           = $multiBranch;
        $this->view->products              = $products;
        $this->view->intanceProjectID      = $intanceProjectID;
        $this->view->intanceProjects       = $this->project->getPairsByIdList($intanceProjectPairs);
        $this->view->executionProjectPairs = $this->chproject->getIntancesProjectOptions($projectID, 'executionID', 'projectName');

        $this->display();
    }

    /**
     * Browse burndown chart of a execution.
     *
     * @param  int       $executionID
     * @param  string    $type
     * @param  int       $interval
     * @param  int       $zentaoProject
     * @access public
     * @return void
     */
    public function burn($projectID, $type = 'noweekend', $interval = 0, $burnBy = 'left' , $zentaoProject = '')
    {
        $this->loadModel('report');
        $this->loadModel('execution');
        $this->loadModel('holiday');

        $projectID   = $this->chproject->setMenu($projectID);
        $project     = $this->chproject->getById($projectID);
        $holidayList = $this->holiday->getList('', 'holiday');

        $intanceProjectIDList = $this->chproject->getIntanceProjectPairs($projectID, 'id,project');
        $executionIds         = $this->dao->select('zentao')->from(TABLE_CHPROJECTINTANCES)->where('ch')->eq($projectID)->fetchPairs();
        if($zentaoProject && $zentaoProject != "-1")
        {
            foreach($intanceProjectIDList as $key => $value)
            {
                if($value == $zentaoProject)
                {
                    $executionID = $key;
                    break;
                }
            }
            $execution = $this->execution->getById($executionID);
        }
        else
        {
            // default all project
            $executionID = array_keys($executionIds)[0];
            $execution = $this->execution->getById($executionID);
        }

        $burnBy = $this->cookie->chburnBy ? $this->cookie->chburnBy : $burnBy;

        $intanceProjectIDList = $this->dao->select('project')->from(TABLE_PROJECT)->where('id')->in(array_keys($executionIds))->fetchPairs();
        $intanceProjects      = $this->dao->select('id,name')->from(TABLE_PROJECT)->where('id')->in($intanceProjectIDList)->fetchPairs();

        $intanceProjects = array("-1" => $this->lang->chproject->allProject) + $intanceProjects;

        /* Header and position. */
        $title = $project->name . $this->lang->colon . $this->lang->execution->burn;

        /* Get date list. */
        if(((strpos('closed,suspended', $execution->status) === false and helper::today() > $execution->end)
                or ($execution->status == 'closed' and substr($execution->closedDate, 0, 10) > $execution->end)
                or ($execution->status == 'suspended' and $execution->suspendedDate > $execution->end))
            and strpos($type, 'delay') === false)
            $type .= ',withdelay';

        $deadline = $execution->status == 'closed' ? substr($execution->closedDate, 0, 10) : $execution->suspendedDate;
        $deadline = strpos('closed,suspended', $execution->status) === false ? helper::today() : $deadline;
        $endDate  = strpos($type, 'withdelay') !== false ? $deadline : $execution->end;
        list($dateList, $interval) = $this->execution->getDateList($execution->begin, $endDate, $type, $interval, 'Y-m-d', $execution->end);

        if($type == "noweekend")
        {
            foreach($dateList as $index => $date)
            {
                $flag = false;
                foreach($holidayList as $holiday)
                {
                    if($date >= $holiday->begin && $date <= $holiday->end)
                    {
                        $flag = true;
                    }
                }
                if($flag) unset($dateList[$index]);
            }
            $dateList = array_values($dateList);
        }


        if($zentaoProject && $zentaoProject != "-1")
        {
            $executionEnd = strpos($type, 'withdelay') !== false ? $execution->end : '';
            $chartData    = $this->execution->buildBurnData($executionID, $dateList, $type, $burnBy, $executionEnd);
        }
        else
        {
            $executionEnd = strpos($type, 'withdelay') !== false ? $execution->end : '';
            $chartData    = $this->execution->buildExecutionBurnData(array_values($executionIds), $dateList, $type, $burnBy, $executionEnd);
        }

        $dayList = array_fill(1, floor((int)$execution->days / $this->config->execution->maxBurnDay) + 5, '');
        foreach($dayList as $key => $val) $dayList[$key] = $this->lang->execution->interval . ($key + 1) . $this->lang->day;

        /* Assign. */
        $this->view->title           = $title;
        $this->view->tabID           = 'burn';
        $this->view->burnBy          = $burnBy;
        $this->view->executionID     = $executionID;
        $this->view->executionName   = $execution->name;
        $this->view->type            = $type;
        $this->view->interval        = $interval;
        $this->view->chartData       = $chartData;
        $this->view->dayList         = array('full' => $this->lang->execution->interval . '1' . $this->lang->day) + $dayList;
        $this->view->projectID       = $projectID;
        $this->view->intanceProjects = $intanceProjects;
        $this->view->zentaoProject   = $zentaoProject;

        unset($this->lang->TRActions);
        $this->display();
    }

    /**
     * Browse bugs of a execution.
     *
     * @param  int    $projectID
     * @param  int    $productID
     * @param  int    $branchID
     * @param  string $orderBy
     * @param  int    $build
     * @param  string $type
     * @param  int    $param
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function bug($projectID = 0, $intanceProjectID = 0, $productID = 0, $branch = 'all', $orderBy = 'status,id_desc', $build = 0, $type = 'all', $param = 0, $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        /* Load these two models. */
        $this->loadModel('bug');
        $this->loadModel('user');
        $this->loadModel('product');
        $this->loadModel('datatable');
        $this->loadModel('tree');
        $this->loadModel('execution');

        $this->chproject->setMenu($projectID);

        $project            = $this->chproject->getById($projectID);
        $intances           = $this->chproject->getIntances($projectID);
        $hasProductIntances = $this->chproject->getIntances($projectID, true);

        /* Save session. */
        $this->session->set('teamBugList', $this->app->getURI(true), $this->app->tab);

        $type     = strtolower($type);
        $queryID  = ($type == 'bysearch') ? (int)$param : 0;
        $products = $hasProductIntances ? $this->product->getProducts($hasProductIntances) : [];

        $productPairs = array('0' => $this->lang->product->all);
        foreach($products as $productData) $productPairs[$productData->id] = $productData->name;

        unset($this->config->bug->search['fields']['product']);
        unset($this->config->bug->search['fields']['plan']);

        $this->session->set('intanceProjectID', $intanceProjectID, $this->app->tab);
        $this->lang->modulePageNav = $this->product->select($hasProductIntances ? $productPairs : [], $productID, 'chproject', 'bug', implode(',', $hasProductIntances), $branch, '', 'bug');

        /* Header and position. */
        $title      = $project->name . $this->lang->colon . $this->lang->execution->bug;
        $position[] = html::a($this->createLink('chproject', 'browse', "projectID=$projectID"), $project->name);
        $position[] = $this->lang->execution->bug;

        /* Load pager and get bugs, user. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'xhtml') $recPerPage = 10;
        $pager      = new pager($recTotal, $recPerPage, $pageID);
        $sort       = common::appendOrder($orderBy);
        $executions = $intanceProjectID ? $this->execution->getPairsByProjectID($intanceProjectID, 'id') : [];
        $bugs       = $this->bug->getExecutionBugs($executions ? array_intersect($intances, $executions) : $intances, $productID, $branch, $build, $type, $param, $sort, '', $pager, 'chproject');
        $bugs       = $this->bug->checkDelayedBugs($bugs);
        $users      = $this->user->getPairs('noletter');

        /* team member pairs. */
        $memberPairs = array();
        $teamMembers = $this->execution->getTeamMembers(current($intances));

        foreach($teamMembers as $index => $member) $memberPairs[$index] = $member->realname;
        $memberPairs = $this->user->processAccountSort($memberPairs);

        $intanceProjects = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        /* Build the search form. */
        $actionURL = $this->createLink('chproject', 'bug', "projectID=$projectID&intanceProjectID=$intanceProjectID&productID=$productID&branch=$branch&orderBy=$orderBy&build=$build&type=bysearch&queryID=myQueryID");
        $this->chproject->buildBugSearchForm($products, $intanceProjects, $queryID, $actionURL, $intances, $projectID);

        $product = $this->product->getById($productID);
        $showBranch      = false;
        $branchOption    = array();
        $branchTagOption = array();
        if($product and $product->type != 'normal')
        {
            /* Display of branch label. */
            $showBranch = $this->loadModel('branch')->showBranch($productID);

            /* Display status of branch. */
            $branches = $this->branch->getList($productID, $executionID, 'all');
            foreach($branches as $branchInfo)
            {
                $branchOption[$branchInfo->id]    = $branchInfo->name;
                $branchTagOption[$branchInfo->id] = $branchInfo->name . ($branchInfo->status == 'closed' ? ' (' . $this->lang->branch->statusList['closed'] . ')' : '');
            }
        }

        /* Get story and task id list. */
        $storyIdList = $taskIdList = array();
        foreach($bugs as $bug)
        {
            if($bug->story)  $storyIdList[$bug->story] = $bug->story;
            if($bug->task)   $taskIdList[$bug->task]   = $bug->task;
            if($bug->toTask) $taskIdList[$bug->toTask] = $bug->toTask;
        }
        $storyList = $storyIdList ? $this->loadModel('story')->getByList($storyIdList) : array();
        $taskList  = $taskIdList  ? $this->loadModel('task')->getByList($taskIdList)   : array();

        /* Process the openedBuild and resolvedBuild fields. */
        $bugs = $this->bug->processBuildForBugs($bugs);

        $moduleID = $type != 'bysearch' ? $param : 0;
        $modules  = $this->tree->getAllModulePairs('bug');

        /* Get module tree.*/
        $tree       = $moduleID ? $this->tree->getByID($moduleID) : '';
        $showModule = !empty($this->config->datatable->executionBug->showModule) ? $this->config->datatable->executionBug->showModule : '';
        $linkParams = ['chproject' => $projectID, 'intanceProjectID' => $intanceProjectID,'productID' => $productID, 'orderBy' => $orderBy, 'type' => $type, 'build' => $build, 'branch' => $branch];
        $moduleTree = $this->tree->getTeamTreeMenu('bug', $projectID, 0, $linkParams);

        $allProducts = $intances ? $this->product->getProducts($intances) : [];

        /* Assign. */
        $this->view->title            = $title;
        $this->view->position         = $position;
        $this->view->bugs             = $bugs;
        $this->view->tabID            = 'bug';
        $this->view->build            = $this->loadModel('build')->getById($build);
        $this->view->buildID          = $this->view->build ? $this->view->build->id : 0;
        $this->view->pager            = $pager;
        $this->view->orderBy          = $orderBy;
        $this->view->users            = $users;
        $this->view->projectID        = $projectID;
        $this->view->productID        = $productID;
        $this->view->project          = $project;
        $this->view->branchID         = empty($this->view->build->branch) ? $branch : $this->view->build->branch;
        $this->view->memberPairs      = $memberPairs;
        $this->view->type             = $type;
        $this->view->summary          = $this->bug->summary($bugs);
        $this->view->param            = $param;
        $this->view->defaultProduct   = (empty($productID) and !empty($allProducts)) ? current(array_keys($allProducts)) : $productID;
        $this->view->builds           = $this->build->getBuildPairs($productID);
        $this->view->branchOption     = $branchOption;
        $this->view->plans            = $this->loadModel('productplan')->getPairs($productID ? $productID : array_keys($products));
        $this->view->projectPairs     = $this->loadModel('project')->getPairsByProgram();
        $this->view->stories          = $storyList;
        $this->view->tasks            = $taskList;
        $this->view->intanceProjects  = $intanceProjects;
        $this->view->intanceProjectID = $intanceProjectID;
        $this->view->moduleTree       = $moduleTree;
        $this->view->moduleID         = $moduleID;
        $this->view->moduleName       = $moduleID ? $tree->name : $this->lang->tree->all;
        $this->view->products         = $productPairs;
        $this->view->setModule        = true;
        $this->view->showBranch       = false;
        $this->view->executions       = $this->execution->getPairs(0, 'all', "nocode");
        $this->view->intanceExecution = key($intances);

        $this->display();
    }

    /**
     * Build story search form.
     *
     * @param  int    $projectID
     * @param  int    $productID
     * @param  array  $products
     * @param  int    $queryID
     * @param  string $storyType
     * @param  string $orderBy
     * @param  int    $intanceProjectID
     * @access private
     * @return viod
     */
    private function buildStorySearchForm($projectID, $productID, $products, $queryID, $storyType, $orderBy, $intanceProjectID = 0)
    {
        /* Build the search form. */
        $modules          = array();
        $productModules   = array();
        $executionModules = $this->loadModel('tree')->getTaskTreeModules($projectID, true);
        if($productID)
        {
            $product = $products[$productID];
            $productModules = $this->tree->getOptionMenu($productID, 'story', 0, $product->branches);
        }
        else
        {
            foreach($products as $product) $productModules += $this->tree->getOptionMenu($product->id, 'story', 0, $product->branches);
        }

        foreach($productModules as $moduleList)
        {
            foreach($moduleList as $moduleID => $moduleName)
            {
                if($moduleID and !isset($executionModules[$moduleID])) continue;
                $modules[$moduleID] = ((count($products) >= 2 and $moduleID) ? $product->name : '') . $moduleName;
            }
        }

        $actionURL    = $this->createLink('chproject', 'story', "projectID=$projectID&intanceProjectID=$intanceProjectID&storyType=$storyType&orderBy=$orderBy&type=bysearch&queryID=myQueryID");
        $branchGroups = $this->loadModel('branch')->getByProducts(array_keys($products));
        $branchOption = array();
        foreach($branchGroups as $branches)
        {
            foreach($branches as $branchID => $name) $branchOption[$branchID] = $name;
        }

        $modules = $this->chproject->getModulePairs($projectID, $showAllModule);

        $this->execution->buildStorySearchForm($products, $branchGroups, $modules, $queryID, $actionURL, 'chprojectStory', $execution);

        $this->view->branchOption = $branchOption;
        $this->view->branchGroups = $branchGroups;

        $plans    = (array)$this->execution->getPlans($products, 'skipParent|withMainPlan|unexpired|noclosed', $projectID);
        $allPlans = array('' => '');
        foreach($plans as $plan) $allPlans += $plan;

        $this->view->allPlans = $allPlans;
    }

    /**
     * Fix productID by param.
     *
     * @param  string $type
     * @param  int    $param
     * @access private
     * @return int
     */
    private function fixProductID($type, $param)
    {
        if($type == 'byproduct') return $param;

        if($type == 'bymodule')
        {
            $module = $this->loadModel('tree')->getByID($param);
            return isset($module->root) ? $module->root : 0;
        }

        return 0;
    }

    /**
     * Set story cookie.
     *
     * @param  int    $projectID
     * @param  string $type
     * @param  int    $param
     * @access private
     * @return viod
     */
    private function setStoryCookie($projectID, $type, $param)
    {
        if($this->cookie->storyPreExecutionID != $projectID)
        {
            $_COOKIE['storyModuleParam'] = $_COOKIE['storyProductParam'] = $_COOKIE['storyBranchParam'] = 0;
            setcookie('storyModuleParam',  0, 0, $this->config->webRoot, '', false, true);
            setcookie('storyProductParam', 0, 0, $this->config->webRoot, '', false, true);
            setcookie('storyBranchParam',  0, 0, $this->config->webRoot, '', false, true);
        }

        if($type == 'bymodule')
        {
            $_COOKIE['storyModuleParam']  = $param;
            $_COOKIE['storyProductParam'] = 0;
            $_COOKIE['storyBranchParam']  = 0;
            setcookie('storyModuleParam', $param, 0, $this->config->webRoot, '', false, true);
            setcookie('storyProductParam', 0, 0, $this->config->webRoot, '', false, true);
            setcookie('storyBranchParam',  0, 0, $this->config->webRoot, '', false, true);
        }
        elseif($type == 'byproduct')
        {
            $_COOKIE['storyModuleParam']  = 0;
            $_COOKIE['storyProductParam'] = $param;
            $_COOKIE['storyBranchParam']  = 0;
            setcookie('storyModuleParam',  0, 0, $this->config->webRoot, '', false, true);
            setcookie('storyProductParam', $param, 0, $this->config->webRoot, '', false, true);
            setcookie('storyBranchParam',  0, 0, $this->config->webRoot, '', false, true);
        }
        elseif($type == 'bybranch')
        {
            $_COOKIE['storyModuleParam']  = 0;
            $_COOKIE['storyProductParam'] = 0;
            $_COOKIE['storyBranchParam']  = $param;
            setcookie('storyModuleParam',  0, 0, $this->config->webRoot, '', false, true);
            setcookie('storyProductParam', 0, 0, $this->config->webRoot, '', false, true);
            setcookie('storyBranchParam',  $param, 0, $this->config->webRoot, '', false, true);
        }
        else
        {
            $this->session->set('executionStoryBrowseType', $type);
            $this->session->set('storyBrowseType', $type, 'execution');
        }
    }

    private function setTaskCookie($projectID, $browseType, $param)
    {
        if($this->cookie->preProjectID != $projectID)
        {
            $_COOKIE['moduleBrowseParam'] = $_COOKIE['productBrowseParam'] = 0;
            setcookie('moduleBrowseParam',  0, 0, $this->config->webRoot, '', false, false);
            setcookie('productBrowseParam', 0, 0, $this->config->webRoot, '', false, true);
        }
        if($browseType == 'bymodule')
        {
            setcookie('moduleBrowseParam',  (int)$param, 0, $this->config->webRoot, '', false, false);
            setcookie('productBrowseParam', 0, 0, $this->config->webRoot, '', false, true);
        }
        elseif($browseType == 'byproduct')
        {
            setcookie('moduleBrowseParam',  0, 0, $this->config->webRoot, '', false, false);
            setcookie('productBrowseParam', (int)$param, 0, $this->config->webRoot, '', false, true);
        }
        else
        {
            $this->session->set('taskBrowseType', $browseType);
        }

        setcookie('preProjectID', $projectID, $this->config->cookieLife, $this->config->webRoot, '', false, true);
    }

    /**
     * Drop menu page.
     *
     * @param  int    $projectID
     * @param  string $module
     * @param  string $method
     * @access public
     * @return void
     */
    public function ajaxGetDropMenu($projectID, $module, $method)
    {
        $this->loadModel('execution');

        $this->view->link      = $this->chproject->getLink($module, $method, $projectID);
        $this->view->module    = $module;
        $this->view->method    = $method;
        $this->view->projectID = $projectID;

        $projectIDList     = $this->chproject->getObtainPermissionIntances();
        $teamProjectIdList = $this->dao->select('project')->from(TABLE_CHPROJECTTEAM)->fetchPairs();

        $projects = $this->dao->select('id,parent,project,grade,status,name,type,PM,path')->from(TABLE_CHPROJECT)
            ->where('deleted')->eq(0)
            ->andWhere('multiple')->eq('1')
            ->andWhere('type')->in('sprint,stage,kanban')
            ->beginIF(!$this->app->user->admin)->andWhere('id')->in(array_keys($projectIDList))->fi()
            ->andWhere('id')->in($teamProjectIdList)->fi()
            ->orderBy('order_asc')
            ->fetchAll('id');

        $nameList = array();
        foreach($projects as $projectID => $project)
        {
            if($project->grade <= 1) $nameList[$projectID] = $project->name;
        }

        $this->view->projects       = $projects;
        $this->view->projectsPinYin = common::convert2Pinyin($nameList);

        $this->display();
    }

    /**
     * Delete a chproject.
     *
     * @param  int    $projectID
     * @param  string $confirm   yes|no
     * @access public
     * @return void
     */
    public function delete($projectID, $confirm = 'no')
    {
        $this->loadModel('execution');

        $project  = $this->chproject->getByID($projectID);
        $intances = $this->chproject->getIntances($projectID);

        if($confirm == 'no')
        {
            $tips = $this->chproject->getDeletedTips($intances);

            return print(js::confirm($tips . sprintf($this->lang->execution->confirmDelete, $project->name), $this->createLink('chproject', 'delete', "projectID=$projectID&confirm=yes")));
        }
        else
        {
            $this->chproject->delete($projectID, $intances);

            return print(js::reload('parent'));
        }
    }

    /**
     * Close ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function close($projectID)
    {
        $this->loadModel('execution');

        if(!empty($_POST))
        {
            $this->chproject->close($projectID);

            if(dao::isError()) return print(js::error(dao::getError()));

            return print(js::closeModal('parent.parent'));
        }

        $project = $this->chproject->getByID($projectID);

        $this->view->title      = $this->view->execution->name . $this->lang->colon .$this->lang->execution->close;
        $this->view->position[] = html::a($this->createLink('chproject', 'browse', "projectID=$projectID"), $this->view->execution->name);
        $this->view->position[] = $this->lang->execution->close;
        $this->view->users      = $this->loadModel('user')->getPairs('noletter');
        $this->view->project    = $project;
        $this->display();
    }

    /**
     *
     * Activate ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function activate($projectID)
    {
        $project = $this->chproject->getByID($projectID);

        if(!empty($_POST))
        {
            $this->chproject->activate($projectID);
            if(dao::isError()) return print(js::error(dao::getError()));

            return print(js::closeModal('parent.parent'));
        }

        $newBegin = date('Y-m-d');
        $dateDiff = helper::diffDate($newBegin, $project->begin);
        $newEnd   = date('Y-m-d', strtotime($project->end) + $dateDiff * 24 * 3600);

        $this->view->title      = $this->lang->colon .$this->lang->execution->activate;
        $this->view->position[] = $this->lang->execution->activate;
        $this->view->users      = $this->loadModel('user')->getPairs('noletter');
        $this->view->actions    = $this->loadModel('action')->getList($this->objectType, $projectID);
        $this->view->newBegin   = $newBegin;
        $this->view->newEnd     = $newEnd;
        $this->view->project    = $project;

        $this->display();
    }

    /**
     * Kanban CFD.
     *
     * @param  int    $projectID
     * @param  string $type
     * @param  string $withWeekend
     * @param  string $begin
     * @param  string $end
     * @param  int    $intanceProjectID
     * @access public
     * @return void
     */
    public function cfd($projectID = 0, $type = 'story', $withWeekend = 'false', $begin = '', $end = '', $intanceProjectID = 0)
    {
        $project = $this->chproject->getByID($projectID);
        $this->chproject->setMenu($projectID);

        $this->loadModel('kanban');
        $this->loadModel('project');
        $this->loadModel('execution');
        $this->app->loadClass('date');

        $minDate = !helper::isZeroDate($project->openedDate) ? date('Y-m-d', strtotime($project->openedDate)) : date('Y-m-d', strtotime($project->begin));
        $maxDate = !helper::isZeroDate($project->closedDate) ? date('Y-m-d', strtotime($project->closedDate)) : helper::today();
        if($project->lifetime == 'ops' or in_array($project->attribute, array('request', 'review'))) $type = 'task';

        $instances = $this->chproject->getIntances($projectID);

        if(!empty($_POST))
        {
            $begin = htmlspecialchars($this->post->begin, ENT_QUOTES);
            $end   = htmlspecialchars($this->post->end, ENT_QUOTES);

            $dateError = array();

            if(empty($begin)) $dateError[] = sprintf($this->lang->error->notempty, $this->lang->execution->charts->cfd->begin);
            if(empty($end))   $dateError[] = sprintf($this->lang->error->notempty, $this->lang->execution->charts->cfd->end);

            if(empty($dateError))
            {
                if($begin < $minDate) $dateError[] = sprintf($this->lang->error->gt, $this->lang->execution->charts->cfd->begin, $minDate);
                if($begin > $maxDate) $dateError[] = sprintf($this->lang->error->lt, $this->lang->execution->charts->cfd->begin, $maxDate);
                if($end < $minDate)   $dateError[] = sprintf($this->lang->error->gt, $this->lang->execution->charts->cfd->end, $minDate);
                if($end > $maxDate)   $dateError[] = sprintf($this->lang->error->lt, $this->lang->execution->charts->cfd->end, $maxDate);
            }

            if(!empty($dateError))
            {
                foreach($dateError as $index => $error) $dateError[$index] = str_replace(array('。', '.'), array('', ''), $error) . '<br/>';
                return $this->sendError($dateError);
            }

            if($begin >= $end) return $this->sendError($this->lang->execution->charts->cfd->errorBegin);
            if(date("Y-m-d", strtotime("-3 months", strtotime($end))) > $begin) return $this->sendError($this->lang->execution->charts->cfd->errorDateRange);

            $this->execution->computeCFD($instances, 'chproject');

            foreach($instances as $instance) $this->execution->checkCFDData($instance, $begin);

            return $this->send(array('result' => 'success', 'locate' => $this->createLink('chproject', 'cfd', "projectID=$projectID&type=$type&withWeekend=$withWeekend&begin=" . helper::safe64Encode(urlencode($begin)) . "&end=" . helper::safe64Encode(urlencode($end))) . '#app=chteam'));
        }

        if($begin and $end)
        {
            $begin = urldecode(helper::safe64Decode($begin));
            $end   = urldecode(helper::safe64Decode($end));
        }
        else
        {
            list($begin, $end) = $this->execution->getBeginEnd4CFD($project);
        }

        $dateList = date::getDateList($begin, $end, 'Y-m-d', $withWeekend == 'false'? 'noweekend' : '');

        if($intanceProjectID) $instances = $this->chproject->getIntancesProjectOptions($projectID, 'executionID', 'executionID', $intanceProjectID);

        $chartData = $this->execution->buildCFDData($instances, $dateList, $type);
        if(isset($chartData['line'])) $chartData['line'] = array_reverse($chartData['line']);

        $this->view->title            = $this->lang->execution->CFD;
        $this->view->type             = $type;
        $this->view->project          = $project;
        $this->view->withWeekend      = $withWeekend;
        $this->view->projectName      = $project->name;
        $this->view->projectID        = $projectID;
        $this->view->chartData        = $chartData;
        $this->view->features         = $this->execution->getExecutionFeatures($project);
        $this->view->begin            = $begin;
        $this->view->end              = $end;
        $this->view->minDate          = $minDate;
        $this->view->maxDate          = $maxDate;
        $this->view->intanceProjectID = $intanceProjectID;
        $this->view->intanceProjects  = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');

        $this->display();
    }

    /**
     * Task kanban.
     *
     * @param  int    $projectID
     * @param  string $browseType story|bug|task|all
     * @param  string $orderBy
     * @param  string $groupBy
     * @param  int    $intanceProjectID
     *
     * @access public
     * @return void
     */
    public function kanban($projectID, $browseType = 'all', $orderBy = 'order_asc', $groupBy = 'default', $intanceProjectID = 0)
    {
        $this->loadModel('execution');
        $this->loadModel('project');

        $this->chproject->setMenu($projectID);
        $project = $this->chproject->getById($projectID);

        /* Load language. */
        $this->app->loadLang('task');
        $this->app->loadLang('bug');
        $this->loadModel('kanban');

        /* Compatibility IE8. */
        if(strpos($this->server->http_user_agent, 'MSIE 8.0') !== false) header("X-UA-Compatible: IE=EmulateIE7");

        $this->session->set('intanceProject', $intanceProjectID);
        $this->session->set('chprojectID', $projectID);
        $this->session->set('execLaneType', $browseType);

        if($groupBy == 'story' and $browseType == 'task' and !isset($this->lang->kanban->orderList[$orderBy])) $orderBy = 'id_asc';

        $intances    = $this->chproject->getIntances($projectID);
        $kanbanGroup = $this->kanban->getExecutionKanban($projectID, $browseType, $groupBy, '', $orderBy);

        if(empty($kanbanGroup))
        {
            foreach($intances as $intance) $this->kanban->createExecutionLane($intance, $browseType);
            $kanbanGroup = $this->kanban->getExecutionKanban($projectID, $browseType, $groupBy, '', $orderBy);
        }

        /* Show lanes of the attribute: no story&bug in request, no bug in design. */
        if(!isset($this->lang->execution->menu->story)) unset($kanbanGroup['story']);
        if(!isset($this->lang->execution->menu->qa))    unset($kanbanGroup['bug']);

        /* Determines whether an object is editable. */
        $canBeChanged = common::canModify('execution', $project);

        /* Get execution's product. */
        $productID    = 0;
        $productNames = array();
        $productNames = $this->chproject->getIntanceProductPairs($projectID);

        if($productNames) $productID = key($productNames);

        $userList = $this->dao->select('account, realname, avatar')->from(TABLE_USER)->where('deleted')->eq(0)->fetchAll('account');
        $userList['closed'] = new stdclass();
        $userList['closed']->account  = 'Closed';
        $userList['closed']->realname = 'Closed';
        $userList['closed']->avatar   = '';

        $hiddenPlan       = $project->model !== 'scrum';
        $defaultExecution = $this->execution->getById(key($intances));

        $this->view->title               = $this->lang->execution->kanban;
        $this->view->realnames           = $this->loadModel('user')->getPairs('noletter');
        $this->view->storyOrder          = $orderBy;
        $this->view->orderBy             = 'id_asc';
        $this->view->projectID           = $projectID;
        $this->view->productID           = $productID;
        $this->view->productNames        = $productNames;
        $this->view->productNum          = count($productNames);
        $this->view->allPlans            = [];
        $this->view->browseType          = $browseType;
        $this->view->features            = $this->execution->getExecutionFeatures($defaultExecution);
        $this->view->kanbanGroup         = $kanbanGroup;
        $this->view->project             = $project;
        $this->view->groupBy             = $groupBy;
        $this->view->canBeChanged        = $canBeChanged;
        $this->view->userList            = $userList;
        $this->view->hiddenPlan          = $hiddenPlan;
        $this->view->intanceProjectPairs = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $this->view->intanceProjectID    = $intanceProjectID;
        $this->view->defaultExecution    = $defaultExecution;

        $this->display();
    }

    /**
     * Browse tasks in group.
     *
     * @param  int    $projectID
     * @param  string $groupBy    the field to group by
     * @param  string $filter
     * @param  int    $intanceProjectID
     * @access public
     * @return void
     */
    public function grouptask($projectID = 0, $groupBy = 'story', $filter = '', $intanceProjectID = 0)
    {
        $this->loadModel('execution');

        $this->chproject->setMenu($projectID);

        /* Save session. */
        $this->app->session->set('teamTaskList',  $this->app->getURI(true), 'chproject');

        $project = $this->chproject->getById($projectID);

        /* Header and session. */
        $this->view->title      = $project->name . $this->lang->colon . $this->lang->execution->task;
        $this->view->position[] = $this->lang->execution->task;

        /* Get tasks and group them. */
        if(empty($groupBy))$groupBy = 'story';
        if($groupBy == 'story' and $project->lifetime == 'ops')
        {
            $groupBy = 'status';
            unset($this->lang->execution->groups['story']);
        }

        $intances = $this->chproject->getIntancesProjectOptions($projectID, 'executionID', 'executionID', $intanceProjectID);

        $sort        = common::appendOrder($groupBy);
        $tasks       = $this->loadModel('task')->getExecutionTasks($intances, $productID = 0, $status = 'all', $modules = 0, $sort);
        $groupBy     = str_replace('`', '', $groupBy);
        $taskLang    = $this->lang->task;
        $groupByList = array();
        $groupTasks  = array();

        $groupTasks = array();
        $allCount   = 0;
        foreach($tasks as $task)
        {
            if($task->mode == 'multi') $task->assignedToRealName = $this->lang->task->team;

            $groupTasks[] = $task;
            $allCount++;
            if(isset($task->children))
            {
                foreach($task->children as $child)
                {
                    $groupTasks[] = $child;
                    $allCount++;
                }
                $task->children = true;
                unset($task->children);
            }
        }

        /* Get users. */
        $users = $this->loadModel('user')->getPairs('noletter');
        $tasks = $groupTasks;
        $groupTasks = array();
        foreach($tasks as $task)
        {
            if($groupBy == 'story')
            {
                $groupTasks[$task->story][] = $task;
                $groupByList[$task->story]  = $task->storyTitle;
            }
            elseif($groupBy == 'status')
            {
                $groupTasks[$taskLang->statusList[$task->status]][] = $task;
            }
            elseif($groupBy == 'assignedTo')
            {
                if(isset($task->team))
                {
                    foreach($task->team as $team)
                    {
                        $cloneTask = clone $task;
                        $cloneTask->assignedTo = $team->account;
                        $cloneTask->estimate   = $team->estimate;
                        $cloneTask->consumed   = $team->consumed;
                        $cloneTask->left       = $team->left;
                        if($team->left == 0) $cloneTask->status = 'done';

                        $realname = zget($users, $team->account);
                        $cloneTask->assignedToRealName = $realname;
                        $groupTasks[$realname][] = $cloneTask;
                    }
                }
                else
                {
                    $groupTasks[$task->assignedToRealName][] = $task;
                }
            }
            elseif($groupBy == 'finishedBy')
            {
                if(isset($task->team))
                {
                    $task->consumed = $task->estimate = $task->left = 0;
                    foreach($task->team as $team)
                    {
                        if($team->left != 0)
                        {
                            $task->estimate += $team->estimate;
                            $task->consumed += $team->consumed;
                            $task->left     += $team->left;
                            continue;
                        }

                        $cloneTask = clone $task;
                        $cloneTask->finishedBy = $team->account;
                        $cloneTask->estimate   = $team->estimate;
                        $cloneTask->consumed   = $team->consumed;
                        $cloneTask->left       = $team->left;
                        $cloneTask->status     = 'done';
                        $realname = zget($users, $team->account);
                        $groupTasks[$realname][] = $cloneTask;
                    }
                    if(!empty($task->left)) $groupTasks[$users[$task->finishedBy]][] = $task;
                }
                else
                {
                    $groupTasks[$users[$task->finishedBy]][] = $task;
                }
            }
            elseif($groupBy == 'closedBy')
            {
                $groupTasks[$users[$task->closedBy]][] = $task;
            }
            elseif($groupBy == 'type')
            {
                $groupTasks[$taskLang->typeList[$task->type]][] = $task;
            }
            else
            {
                $groupTasks[$task->$groupBy][] = $task;
            }
        }
        /* Process closed data when group by assignedTo. */
        if($groupBy == 'assignedTo' and isset($groupTasks['Closed']))
        {
            $closedTasks = $groupTasks['Closed'];
            unset($groupTasks['Closed']);
            $groupTasks['closed'] = $closedTasks;
        }

        /* Remove task by filter and group. */
        $filter = (empty($filter) and isset($this->lang->execution->groupFilter[$groupBy])) ? key($this->lang->execution->groupFilter[$groupBy]) : $filter;
        if($filter != 'all')
        {
            if($groupBy == 'story' and $filter == 'linked' and isset($groupTasks[0]))
            {
                $allCount -= count($groupTasks[0]);
                unset($groupTasks[0]);
            }
            elseif($groupBy == 'pri' and $filter == 'noset')
            {
                foreach($groupTasks as $pri => $tasks)
                {
                    if($pri)
                    {
                        $allCount -= count($tasks);
                        unset($groupTasks[$pri]);
                    }
                }
            }
            elseif($groupBy == 'assignedTo' and $filter == 'undone')
            {
                $multiTaskCount = array();
                foreach($groupTasks as $assignedTo => $tasks)
                {
                    foreach($tasks as $i => $task)
                    {
                        if($task->status != 'wait' and $task->status != 'doing')
                        {
                            if($task->mode == 'multi')
                            {
                                if(!isset($multiTaskCount[$task->id]))
                                {
                                    $multiTaskCount[$task->id] = true;
                                    $allCount -= 1;
                                }
                            }
                            else
                            {
                                $allCount -= 1;
                            }

                            unset($groupTasks[$assignedTo][$i]);
                        }
                    }
                }
            }
            elseif(($groupBy == 'finishedBy' or $groupBy == 'closedBy') and isset($tasks['']))
            {
                $allCount -= count($tasks['']);
                unset($tasks['']);
            }
        }

        /* Assign. */
        $this->app->loadLang('tree');
        $this->view->members             = $this->execution->getTeamMembers($projectID);
        $this->view->tasks               = $groupTasks;
        $this->view->tabID               = 'task';
        $this->view->groupByList         = $groupByList;
        $this->view->browseType          = 'group';
        $this->view->groupBy             = $groupBy;
        $this->view->orderBy             = $groupBy;
        $this->view->projectID           = $projectID;
        $this->view->users               = $users;
        $this->view->moduleID            = 0;
        $this->view->moduleName          = $this->lang->tree->all;
        $this->view->features            = $this->execution->getExecutionFeatures($project);
        $this->view->filter              = $filter;
        $this->view->allCount            = $allCount;
        $this->view->intanceProjectPairs = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $this->view->intanceProjectID    = $intanceProjectID;

        $this->display();
    }

    /**
     * Task effort.
     *
     * @param  int    $projectID
     * @param  string $groupBy
     * @param  string $type         noweekend|withweekend
     * @param  int    $intanceProjectID
     * @access public
     * @return void
     */
    public function taskEffort($projectID, $groupBy = 'story', $type = 'noweekend', $intanceProjectID = 0)
    {
        $this->loadModel('execution');

        $this->app->session->set('teamTaskList',  $this->app->getURI(true), 'chproject');

        $this->chproject->setMenu($projectID);

        $this->app->loadLang('task');
        $taskLang = $this->lang->task;
        $users    = $this->loadModel('user')->getPairs('noletter');
        $today    = helper::today();
        $project  = $this->chproject->getByID($projectID);

        if($project->lifetime == 'ops' or in_array($project->attribute, array('request', 'review')))
        {
            if($groupBy == 'story') $groupBy = 'status';
            unset($this->lang->execution->groups['story']);
        }

        $this->app->loadClass('date');

        $intances = $this->chproject->getIntancesProjectOptions($projectID, 'executionID', 'executionID', $intanceProjectID);
        $tasks    = $this->execution->getTaskEffort($intances);

        $groupTasks  = array();
        $taskAll     = array();
        $groupByList = array();
        $counts      = $tasks['count'];
        unset($tasks['count']);

        foreach($tasks as $task)
        {
            if(isset($task->children))
            {
                foreach($task->children as $child) $groupTasks[] = $child;
            }
            else
            {
                $groupTasks[] = $task;
            }
        }

        $tasks      = $groupTasks;
        $groupTasks = array();

        if(!empty($tasks))
        {
            foreach($tasks as $taskKey)
            {
                $taskAll[] = $taskKey->id;
            }
        }

        $efforts     = $this->loadModel('task')->getTaskDateRecord($taskAll);
        $effortsDate = helper::arrayColumn($efforts, 'date');

        if(!empty($effortsDate))
        {
            $project->begin = $effortsDate[0] <= $project->begin ? $effortsDate[0] : $project->begin;
            $lastDate         = end($effortsDate);
            $project->end   = $lastDate >= $project->end ? $lastDate : $project->end;
        }

        $dateList = date::getDateList($project->begin, ($today >= $project->end ? $project->end : $today), 'Y-m-d', $type, $this->config->execution->weekend);
        foreach($tasks as $task)
        {
            if($groupBy == 'story')
            {
                $groupTasks[$task->story][] = $task;
                $groupByList[$task->story]  = $task->storyTitle;
            }
            elseif($groupBy == 'status')
            {
                $groupTasks[$taskLang->statusList[$task->status]][] = $task;
            }
            elseif($groupBy == 'assignedTo')
            {
                if(isset($task->team) and is_array($task->team))
                {
                    foreach($task->team as $team)
                    {
                        $cloneTask = clone $task;
                        $cloneTask->assignedTo = $team->account;
                        $cloneTask->estimate   = $team->estimate;
                        $cloneTask->consumed   = $team->consumed;
                        $cloneTask->left       = $team->left;
                        if($team->left == 0) $cloneTask->status = 'done';

                        $realname = zget($users, $team->account);
                        $cloneTask->assignedToRealName = $realname;
                        $groupTasks[$realname][] = $cloneTask;
                    }
                }
                else
                {
                    $groupTasks[$task->assignedToRealName][] = $task;
                }
            }
            elseif($groupBy == 'finishedBy')
            {
                if(isset($task->team) and is_array($task->team))
                {
                    $task->consumed = $task->estimate = $task->left = 0;
                    foreach($task->team as $team)
                    {
                        if($team->left != 0)
                        {
                            $task->estimate += $team->estimate;
                            $task->consumed += $team->consumed;
                            $task->left     += $team->left;
                            continue;
                        }

                        $cloneTask = clone $task;
                        $cloneTask->finishedBy = $team->account;
                        $cloneTask->estimate   = $team->estimate;
                        $cloneTask->consumed   = $team->consumed;
                        $cloneTask->left       = $team->left;
                        $cloneTask->status     = 'done';
                        $realname = zget($users, $team->account);
                        $groupTasks[$realname][] = $cloneTask;
                    }
                    if(!empty($task->left)) $groupTasks[$users[$task->finishedBy]][] = $task;
                }
                else
                {
                    $groupTasks[$users[$task->finishedBy]][] = $task;
                }
            }
            elseif($groupBy == 'closedBy')
            {
                $groupTasks[$users[$task->closedBy]][] = $task;
            }
            elseif($groupBy == 'type')
            {
                $groupTasks[$taskLang->typeList[$task->type]][] = $task;
            }
            else
            {
                $groupTasks[$task->$groupBy][] = $task;
            }
        }
        /* Process closed data when group by assignedTo. */
        if($groupBy == 'assignedTo' and isset($groupTasks['Closed']))
        {
            $closedTasks = $groupTasks['Closed'];
            unset($groupTasks['Closed']);
            $groupTasks['closed'] = $closedTasks;
        }

        /* Assign. */
        $this->view->title               = $project->name . $this->lang->colon . $this->lang->execution->taskEffort;
        $this->view->position[]          = html::a($this->createLink('chproject', 'browse', "projectID=$projectID"), $project->name);
        $this->view->position[]          = $this->lang->execution->taskEffort;
        $this->view->project             = $project;
        $this->view->projectID           = $projectID;
        $this->view->groupBy             = $groupBy;
        $this->view->groupByList         = $groupByList;
        $this->view->tasks               = $groupTasks;
        $this->view->type                = $type;
        $this->view->dateList            = $dateList;
        $this->view->counts              = $counts;
        $this->view->intanceProjectPairs = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $this->view->intanceProjectID    = $intanceProjectID;

        $this->display();
    }
}
