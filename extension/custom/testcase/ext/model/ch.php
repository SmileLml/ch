<?php
/**
 * Get case info by ID.
 *
 * @param  int    $caseID
 * @param  int    $version
 * @access public
 * @return object|bool
 */
public function getById($caseID, $version = 0)
{
    $case = $this->dao->findById($caseID)->from(TABLE_CASE)->fetch();
    if(!$case) return false;
    foreach($case as $key => $value) if(strpos($key, 'Date') !== false and helper::isZeroDate($value)) $case->$key = '';

    /* Get project and execution. */
    if($this->app->tab == 'project')
    {
        $case->project = $this->session->project;
    }
    elseif($this->app->tab == 'execution')
    {
        $case->execution = $this->session->execution;
        $case->project   = $this->dao->select('project')->from(TABLE_PROJECT)->where('id')->eq($case->execution)->fetch('project');
    }
    elseif($this->app->tab == 'chteam')
    {
        $case->project   = $this->dao->select('project')->from(TABLE_PROJECT)->where('id')->eq($case->execution)->fetch('project');
    }
    else
    {
        $objects = $this->dao->select('t1.*, t1.project as objectID, t2.type')->from(TABLE_PROJECTCASE)->alias('t1')
            ->leftJoin(TABLE_EXECUTION)->alias('t2')->on('t1.project=t2.id')
            ->where('t1.case')->eq($caseID)
            ->fetchAll('objectID');

        foreach($objects as $objectID => $object)
        {
            if($object->type == 'project') $case->project = $objectID;
            if(in_array($object->type, array('sprint', 'stage', 'kanban'))) $case->execution = $objectID;
        }
    }

    if($case->story)
    {
        $story = $this->dao->findById($case->story)->from(TABLE_STORY)->fields('title, status, version')->fetch();
        $case->storyTitle         = $story->title;
        $case->storyStatus        = $story->status;
        $case->latestStoryVersion = $story->version;
    }
    if($case->fromBug) $case->fromBugTitle      = $this->dao->findById($case->fromBug)->from(TABLE_BUG)->fields('title')->fetch('title');

    $case->toBugs = array();
    $toBugs       = $this->dao->select('id, title')->from(TABLE_BUG)->where('`case`')->eq($caseID)->fetchAll();
    foreach($toBugs as $toBug) $case->toBugs[$toBug->id] = $toBug->title;

    if($case->linkCase or $case->fromCaseID) $case->linkCaseTitles = $this->dao->select('id,title')->from(TABLE_CASE)->where('id')->in($case->linkCase)->orWhere('id')->eq($case->fromCaseID)->fetchPairs();
    if($version == 0) $version = $case->version;
    $case->files = $this->loadModel('file')->getByObject('testcase', $caseID);
    $case->currentVersion = $version ? $version : $case->version;

    $case->steps = $this->dao->select('*')->from(TABLE_CASESTEP)->where('`case`')->eq($caseID)->andWhere('version')->eq($version)->orderBy('id')->fetchAll('id');
    foreach($case->steps as $key => $step)
    {
        $step->desc   = html_entity_decode($step->desc);
        $step->expect = html_entity_decode($step->expect);
    }

    return $case;
}

/**
 * Get execution cases.
 *
 * @param  int    $executionID
 * @param  int    $productID
 * @param  int    $branchID
 * @param  int    $moduleID
 * @param  string $orderBy
 * @param  object $pager
 * @param  string $browseType   all|wait|needconfirm
 * @access public
 * @return array
 */
public function getExecutionCases($executionID, $productID = 0, $branchID = 0, $moduleID = 0, $orderBy = 'id_desc', $pager = null, $browseType = '')
{
    if($browseType == 'needconfirm')
    {
        return $this->dao->select('distinct t1.*, t2.*')->from(TABLE_PROJECTCASE)->alias('t1')
            ->leftJoin(TABLE_CASE)->alias('t2')->on('t1.case=t2.id')
            ->leftJoin(TABLE_STORY)->alias('t3')->on('t2.story = t3.id')
            ->leftJoin(TABLE_MODULE)->alias('t4')->on('t2.module=t4.id')
            ->where('t1.project')->in($executionID)
            ->beginIF(!empty($productID))->andWhere('t1.product')->eq($productID)->fi()
            ->beginIF(!empty($moduleID))->andWhere('t4.path')->like("%,$moduleID,%")->fi()
            ->beginIF(!empty($productID) and $branchID !== 'all')->andWhere('t2.branch')->eq($branchID)->fi()
            ->andWhere('t2.deleted')->eq('0')
            ->andWhere('t3.version > t2.storyVersion')
            ->andWhere("t3.status")->eq('active')
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');
    }

    return $this->dao->select('distinct t1.*, t2.*,t5.id as projectID, t1.product as productID')->from(TABLE_PROJECTCASE)->alias('t1')
        ->leftJoin(TABLE_CASE)->alias('t2')->on('t1.case=t2.id')
        ->leftJoin(TABLE_MODULE)->alias('t3')->on('t2.module=t3.id')
        ->leftJoin(TABLE_EXECUTION)->alias('t4')->on('t2.execution=t4.id')
        ->leftJoin(TABLE_PROJECT)->alias('t5')->on('t4.project=t5.id')
        ->where('t1.project')->in($executionID)
        ->beginIF($browseType != 'all' and $browseType != 'byModule')->andWhere('t2.status')->eq($browseType)->fi()
        ->beginIF(!empty($productID))->andWhere('t1.product')->eq($productID)->fi()
        ->beginIF(!empty($moduleID))->andWhere('t3.path')->like("%,$moduleID,%")->fi()
        ->beginIF(!empty($productID) and $branchID !== 'all')->andWhere('t2.branch')->eq($branchID)->fi()
        ->andWhere('t2.deleted')->eq('0')
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');
}

/**
 * Sync case to project.
 *
 * @param  object $case
 * @param  int    $caseID
 * @access public
 * @return void
 */
public function syncCase2Project($case, $caseID)
{
    $projects = array();
    if(!empty($case->story))
    {
        $projects = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($case->story)->fetchPairs();
    }
    elseif($this->app->tab == 'project' and empty($case->story))
    {
        $projects = array($this->session->project);
    }
    elseif($this->app->tab == 'execution' and empty($case->story))
    {
        $projects = array($this->session->execution);
    }
    elseif($this->app->tab == 'chteam' and empty($case->story))
    {
        $projects = array($case->execution);
    }

    if(empty($projects)) return;

    $this->loadModel('action');
    $objectInfo = $this->dao->select('*')->from(TABLE_PROJECT)->where('id')->in($projects)->fetchAll('id');

    foreach($projects as $projectID)
    {
        $lastOrder = (int)$this->dao->select('*')->from(TABLE_PROJECTCASE)->where('project')->eq($projectID)->orderBy('order_desc')->limit(1)->fetch('order');
        $data = new stdclass();
        $data->project = $projectID;
        $data->product = $case->product;
        $data->case    = $caseID;
        $data->version = 1;
        $data->order   = ++ $lastOrder;
        $this->dao->insert(TABLE_PROJECTCASE)->data($data)->exec();

        $object     = $objectInfo[$projectID];
        $objectType = $object->type;
        if($objectType == 'project') $this->action->create('case', $caseID, 'linked2project', '', $projectID);
        if(in_array($objectType, array('sprint', 'stage')) and $object->multiple) $this->action->create('case', $caseID, 'linked2execution', '', $projectID);
    }
}

/**
 * Build test case view menu.
 *
 * @param  object $case
 * @access public
 * @return string
 */
public function buildOperateViewMenu($case)
{
    if($case->deleted) return '';

    $menu        = '';
    $params      = "caseID=$case->id";
    $extraParams = "runID=$case->runID&$params";
    if(!$case->needconfirm)
    {
        if(!$case->isLibCase)
        {
            if($this->app->getViewType() == 'xhtml')
            {
                $menu .= $this->buildMenu('testtask', 'runCase', "$extraParams&version=$case->currentVersion", $case, 'view', 'play', '', 'showinonlybody', false, "data-width='95%'");
                $menu .= $this->buildMenu('testtask', 'results', "$extraParams&version=$case->version",        $case, 'view', '', '', 'showinonlybody', false, "data-width='95%'");
                if(!isonlybody()) $menu .= $this->buildMenu('testcase', 'importToLib', $params,                $case, 'view', 'assets', '', 'showinonlybody iframe', true, "data-width='500px'");
            }
            else
            {
                if($this->app->tab == 'chteam')
                {
                    $menu .= $this->buildMenu('testtask', 'runCase', "$extraParams&version=$case->currentVersion&confirm=&chprojectID={$this->session->chproject}", $case, 'view', 'play', '', 'showinonlybody iframe', false, "data-width='95%'");
                    $menu .= $this->buildMenu('testtask', 'results', "$extraParams&version=$case->version&status=done&chprojectID={$this->session->chproject}",        $case, 'view', '', '', 'showinonlybody iframe', false, "data-width='95%'");
                }
                else
                {
                    $menu .= $this->buildMenu('testtask', 'runCase', "$extraParams&version=$case->currentVersion", $case, 'view', 'play', '', 'showinonlybody iframe', false, "data-width='95%'");
                    $menu .= $this->buildMenu('testtask', 'results', "$extraParams&version=$case->version",        $case, 'view', '', '', 'showinonlybody iframe', false, "data-width='95%'");
                }
                if(!isonlybody()) $menu .= $this->buildMenu('testcase', 'importToLib', $params,                $case, 'view', 'assets', '', 'showinonlybody iframe', true, "data-width='500px'");
            }
            if($case->caseFails > 0)
            {
                if($this->app->tab == 'chteam')
                {
                    $menu .= $this->buildMenu('testcase', 'createBug', "product=$case->product&branch=$case->branch&extra=$params,version=$case->version,runID=$case->runID&chprojectID={$this->session->chproject}", $case, 'view', 'bug', '', 'iframe', '', "data-width='90%'");
                }
                else
                {
                    $menu .= $this->buildMenu('testcase', 'createBug', "product=$case->product&branch=$case->branch&extra=$params,version=$case->version,runID=$case->runID", $case, 'view', 'bug', '', 'iframe', '', "data-width='90%'");
                }
            }
        }
        if($this->config->testcase->needReview || !empty($this->config->testcase->forceReview))
        {
            $menu .= $this->buildMenu('testcase', 'review', $params, $case, 'view', '', '', 'showinonlybody iframe', '', '', $this->lang->testcase->reviewAB);
        }
    }
    else
    {
        $menu .= $this->buildMenu('testcase', 'confirmstorychange', $params, $case, 'view', 'confirm', 'hiddenwin', '', '', '', $this->lang->confirm);
    }

    $menu .= "<div class='divider'></div>";
    $menu .= $this->buildFlowMenu('testcase', $case, 'view', 'direct');
    $menu .= "<div class='divider'></div>";

    if(!$case->needconfirm)
    {
        if(!isonlybody())
        {
            $editParams = $params;
            if($this->app->tab == 'project')   $editParams .= "&comment=false&projectID={$this->session->project}";
            if($this->app->tab == 'execution') $editParams .= "&comment=false&executionID={$this->session->execution}";
            if($this->app->tab == 'chteam')    $editParams .= "&comment=false&executionID=0&chprojectID={$this->session->chproject}";
            $menu .= $this->buildMenu('testcase', 'edit', $editParams, $case, 'view', '', '', 'showinonlybody');
        }
        if(!$case->isLibCase && $case->auto != 'unit')
        {
            if($this->app->tab == 'chteam')
            {
                $menu .= $this->buildMenu('testcase', 'create', "productID=$case->product&branch=$case->branch&moduleID=$case->module&from=testcase&param=$case->id&storyID=0&extras=executionID=$case->execution&chprojectID={$this->session->chproject}", $case, 'view', 'copy');
            }
            else
            {
                $menu .= $this->buildMenu('testcase', 'create', "productID=$case->product&branch=$case->branch&moduleID=$case->module&from=testcase&param=$case->id", $case, 'view', 'copy');
            }
        }
        if($case->isLibCase && common::hasPriv('caselib', 'createCase'))
        {
            echo html::a(helper::createLink('caselib', 'createCase', "libID=$case->lib&moduleID=$case->module&param=$case->id"), "<i class='icon-copy'></i>", '', "class='btn' title='{$this->lang->testcase->copy}'");
        }

        $menu .= $this->buildMenu('testcase', 'delete', $params, $case, 'view', 'trash', 'hiddenwin', '');
    }

    return $menu;
}

/**
 * Build test case browse menu.
 *
 * @param  object $case
 * @access public
 * @return string
 */
public function buildOperateBrowseMenu($case)
{
    $canBeChanged = common::canBeChanged('case', $case);
    if(!$canBeChanged) return '';

    $menu   = '';
    $params = "caseID=$case->id";

    if($case->needconfirm || $case->browseType == 'needconfirm')
    {
        return $this->buildMenu('testcase', 'confirmstorychange', $params, $case, 'browse', 'ok', 'hiddenwin', '', '', '', $this->lang->confirm);
    }
    if($this->app->tab == 'chteam')
    {
        $menu .= $this->buildMenu('testtask', 'runCase', "runID=0&$params&version=$case->version&confirm=&chprojectID={$this->session->chproject}", $case, 'browse', 'play', '', 'runCase iframe', false, "data-width='95%'");
        $menu .= $this->buildMenu('testtask', 'results', "runID=0&$params&version=0&status=done&chprojectID={$this->session->chproject}", $case, 'browse', '', '', 'iframe', true, "data-width='95%'");
    }
    else
    {
        $menu .= $this->buildMenu('testtask', 'runCase', "runID=0&$params&version=$case->version", $case, 'browse', 'play', '', 'runCase iframe', false, "data-width='95%'");
        $menu .= $this->buildMenu('testtask', 'results', "runID=0&$params", $case, 'browse', '', '', 'iframe', true, "data-width='95%'");
    }

    $editParams = $params;
    if($this->app->tab == 'project')   $editParams .= "&comment=false&projectID={$this->session->project}";
    if($this->app->tab == 'execution') $editParams .= "&comment=false&executionID={$this->session->execution}";
    if($this->app->tab == 'chteam')    $editParams .= "&comment=false&executionID=0&chprojectID={$this->session->chproject}";
    $menu .= $this->buildMenu('testcase', 'edit', $editParams, $case, 'browse');

    if($this->config->testcase->needReview || !empty($this->config->testcase->forceReview))
    {
        common::printIcon('testcase', 'review', $params, $case, 'browse', 'glasses', '', 'showinonlybody iframe');
    }

    if($this->app->tab == 'chteam')
    {
        $menu .= $this->buildMenu('testcase', 'createBug', "product=$case->product&branch=$case->branch&extra=caseID=$case->id,version=$case->version,runID=&chprojectID={$this->session->chproject}", $case, 'browse', 'bug', '', 'iframe', '', "data-width='90%'");

        $menu .= $this->buildMenu('testcase', 'create', "productID=$case->product&branch=$case->branch&moduleID=$case->module&from=testcase&param=$case->id&storyID=0&extras=executionID=$case->execution&chprojectID={$this->session->chproject}", $case, 'browse', 'copy');
    }
    else
    {
        $menu .= $this->buildMenu('testcase', 'createBug', "product=$case->product&branch=$case->branch&extra=caseID=$case->id,version=$case->version,runID=", $case, 'browse', 'bug', '', 'iframe', '', "data-width='90%'");

        $menu .= $this->buildMenu('testcase', 'create', "productID=$case->product&branch=$case->branch&moduleID=$case->module&from=testcase&param=$case->id", $case, 'browse', 'copy');
    }

    if($case->auto == 'auto') $menu .= $this->buildMenu('testcase', 'showScript', $params, $case, 'browse', 'file-code', '', 'runCase iframe', false);

    return $menu;
}

/**
 * Deal with the relationship between the case and project when edit the case.
 *
 * @param  object  $oldCase
 * @param  object  $case
 * @param  int     $caseID
 * @access public
 * @return void
 */
public function updateCase2Project($oldCase, $case, $caseID)
{
    $productChanged = ($oldCase->product != $case->product);
    $storyChanged   = ($oldCase->story   != $case->story);

    if($productChanged)
    {
        $this->dao->update(TABLE_PROJECTCASE)
            ->set('product')->eq($case->product)
            ->set('version')->eq($case->version)
            ->where('`case`')->eq($oldCase->id)
            ->exec();
    }

    if($this->app->tab == 'chteam' && $oldCase->execution != $case->execution) $this->dao->update(TABLE_PROJECTCASE)->set('project')->eq($case->execution)->where('`case`')->eq($oldCase->id)->andWhere('project')->eq($oldCase->execution)->exec();

    /* The related story is changed. */
    if($storyChanged)
    {
        /* If the new related story isn't linked the project, unlink the case. */
        $projects = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($oldCase->story)->fetchAll('project');

        $projectIdList = array_keys($projects);
        $this->dao->delete()->from(TABLE_PROJECTCASE)
            ->where('project')->in()
            ->andWhere('`case`')->eq($oldCase->id)
            ->exec();

        /* If the new related story is not null, make the case link the project which link the new related story. */
        if(!empty($case->story))
        {
            $projects = $this->dao->select('*')->from(TABLE_PROJECTSTORY)->where('story')->eq($case->story)->fetchAll('project');
            if($projects)
            {
                $projects = array_keys($projects);
                foreach($projects as $projectID)
                {
                    $lastOrder = (int)$this->dao->select('*')->from(TABLE_PROJECTCASE)->where('project')->eq($projectID)->orderBy('order_desc')->limit(1)->fetch('order');
                    $data = new stdclass();
                    $data->project = $projectID;
                    $data->product = $case->product;
                    $data->case    = $caseID;
                    $data->version = $oldCase->version;
                    $data->order   = ++ $lastOrder;
                    $this->dao->replace(TABLE_PROJECTCASE)->data($data)->exec();
                }
            }
        }
    }
}
