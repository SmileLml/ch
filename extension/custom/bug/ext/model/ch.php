<?php
/**
 * Get bugs of a execution.
 *
 * @param  int|array    $executionID
 * @param  int          $productID
 * @param  int          $branchID
 * @param  string|array $builds
 * @param  string       $type
 * @param  int          $param
 * @param  string       $orderBy
 * @param  string       $excludeBugs
 * @param  object       $pager
 * @access public
 * @return array
 */
public function getExecutionBugs($executionID, $productID = 0, $branchID = 'all', $builds = 0, $type = '', $param = 0, $orderBy = 'id_desc', $excludeBugs = '', $pager = null, $from = 'execution')
{
    $type = strtolower($type);
    if(strpos($orderBy, 'pri_') !== false) $orderBy = str_replace('pri_', 'priOrder_', $orderBy);
    if(strpos($orderBy, 'severity_') !== false) $orderBy = str_replace('severity_', 'severityOrder_', $orderBy);

    if($type == 'bysearch')
    {
        $queryID = (int)$param;

        $sessionQuery = $from == 'chproject' ? 'chprojectBugQuery' : 'executionBugQuery';
        $sessionForm  = $from == 'chproject' ? 'chprojectBugForm'  : 'executionBugForm';

        if($this->session->$sessionQuery == false) $this->session->set($sessionQuery, ' 1 = 1');
        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                $this->session->set($sessionQuery, $query->sql);
                $this->session->set($sessionForm, $query->form);
            }
        }

        $bugQuery = $this->getBugQuery($this->session->$sessionQuery);

        $bugs = $this->dao->select("*, IF(`pri` = 0, {$this->config->maxPriValue}, `pri`) as priOrder, IF(`severity` = 0, {$this->config->maxPriValue}, `severity`) as severityOrder")->from(TABLE_BUG)
            ->where($bugQuery)
            ->andWhere('execution')->in($executionID)
            ->andWhere('deleted')->eq(0)
            ->beginIF($excludeBugs)->andWhere('id')->notIN($excludeBugs)->fi()
            ->beginIF(!empty($productID) and strpos($bugQuery, 'product') === false and strpos($bugQuery, '`product` IN') === false)->andWhere('product')->eq($productID)->fi()
            ->beginIF(!empty($productID) and $branchID !== 'all' and strpos($bugQuery, 'product') === false and strpos($bugQuery, '`product` IN') === false)->andWhere('branch')->eq($branchID)->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');
    }
    else
    {
        $condition = '';
        if($builds)
        {
            if(!is_array($builds)) $builds = explode(',', $builds);

            $conditions = array();
            foreach($builds as $build)
            {
                if($build) $conditions[] = "FIND_IN_SET('$build', t1.openedBuild)";
            }
            $condition = join(' OR ', $conditions);
            $condition = "($condition)";
        }
        $bugs = $this->dao->select("t1.*, IF(t1.`pri` = 0, {$this->config->maxPriValue}, t1.`pri`) as priOrder, IF(t1.`severity` = 0, {$this->config->maxPriValue}, t1.`severity`) as severityOrder")->from(TABLE_BUG)->alias('t1')
            ->leftJoin(TABLE_MODULE)->alias('t2')->on('t1.module=t2.id')
            ->where('t1.deleted')->eq(0)
            ->beginIF(!empty($productID) and $branchID !== 'all')->andWhere('t1.branch')->eq($branchID)->fi()
            ->beginIF(empty($builds))->andWhere('t1.execution')->in($executionID)->fi()
            ->beginIF(!empty($productID))->andWhere('t1.product')->eq($productID)->fi()
            ->beginIF($type == 'unresolved')->andWhere('t1.status')->eq('active')->fi()
            ->beginIF($type == 'noclosed')->andWhere('t1.status')->ne('closed')->fi()
            ->beginIF($condition)->andWhere("$condition")->fi()
            ->beginIF(!empty($param))->andWhere('t2.path')->like("%,$param,%")->andWhere('t2.deleted')->eq(0)->fi()
            ->beginIF($excludeBugs)->andWhere('t1.id')->notIN($excludeBugs)->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');
    }

    $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'bug', false);

    return $bugs;
}

/**
 * Print cell data.
 *
 * @param  object $col
 * @param  object $bug
 * @param  array  $users
 * @param  array  $builds
 * @param  array  $branches
 * @param  array  $modulePairs
 * @param  array  $executions
 * @param  array  $plans
 * @param  array  $stories
 * @param  array  $tasks
 * @param  string $mode
 * @param  array  $projectPairs
 * @param  array  $products
 *
 * @access public
 * @return void
 */
public function printCell($col, $bug, $users, $builds, $branches, $modulePairs, $executions = array(), $plans = array(), $stories = array(), $tasks = array(), $mode = 'datatable', $projectPairs = array(), $products = array())
{
    /* Check the product is closed. */
    $canBeChanged = common::canBeChanged('bug', $bug);

    $canBatchEdit         = ($canBeChanged and common::hasPriv('bug', 'batchEdit'));
    $canBatchConfirm      = ($canBeChanged and common::hasPriv('bug', 'batchConfirm'));
    $canBatchClose        = common::hasPriv('bug', 'batchClose');
    $canBatchActivate     = ($canBeChanged and common::hasPriv('bug', 'batchActivate'));
    $canBatchChangeBranch = ($canBeChanged and common::hasPriv('bug', 'batchChangeBranch'));
    $canBatchChangeModule = ($canBeChanged and common::hasPriv('bug', 'batchChangeModule'));
    $canBatchResolve      = ($canBeChanged and common::hasPriv('bug', 'batchResolve'));
    $canBatchAssignTo     = ($canBeChanged and common::hasPriv('bug', 'batchAssignTo'));

    $canBatchAction = ($canBatchEdit or $canBatchConfirm or $canBatchClose or $canBatchActivate or $canBatchChangeBranch or $canBatchChangeModule or $canBatchResolve or $canBatchAssignTo);

    $canView = common::hasPriv('bug', 'view');

    $hasCustomSeverity = false;
    foreach($this->lang->bug->severityList as $severityKey => $severityValue)
    {
        if(!empty($severityKey) and (string)$severityKey != (string)$severityValue)
        {
            $hasCustomSeverity = true;
            break;
        }
    }

    $bugLink     = helper::createLink('bug', 'view', "bugID=$bug->id");
    $account     = $this->app->user->account;
    $id          = $col->id;
    $os          = '';
    $browser     = '';
    $osList      = explode(',', $bug->os);
    $browserList = explode(',', $bug->browser);
    foreach($osList as $value)
    {
        if(empty($value)) continue;
        $os .= $this->lang->bug->osList[$value] . ',';
    }
    foreach($browserList as $value)
    {
        if(empty($value)) continue;
        $browser .= zget($this->lang->bug->browserList, $value) . ',';
    }
    $os      = trim($os, ',');
    $browser = trim($browser, ',');
    if($col->show)
    {
        $class = "c-$id";
        $title = '';
        switch($id)
        {
            case 'id':
                $class .= ' cell-id';
                break;
            case 'status':
                $class .= ' bug-' . $bug->status;
                $title  = "title='" . $this->processStatus('bug', $bug) . "'";
                break;
            case 'confirmed':
                $class .= ' text-center';
                break;
            case 'title':
                $class .= ' text-left';
                $title  = "title='{$bug->title}'";
                break;
            case 'type':
                $title  = "title='" . zget($this->lang->bug->typeList, $bug->type) . "'";
                break;
            case 'assignedTo':
                $class .= ' has-btn text-left';
                if($bug->assignedTo == $account) $class .= ' red';
                break;
            case 'resolvedBy':
                $class .= ' c-user';
                $title  = "title='" . zget($users, $bug->resolvedBy) . "'";
                break;
            case 'openedBy':
                $class .= ' c-user';
                $title  = "title='" . zget($users, $bug->openedBy) . "'";
                break;
            case 'project':
                $title = "title='" . zget($projectPairs, $bug->project, '') . "'";
                break;
            case 'product':
                $title = "title='" . zget($products, $bug->product, '') . "'";
                break;
            case 'plan':
                $title = "title='" . zget($plans, $bug->plan, '') . "'";
                break;
            case 'execution':
                $title = "title='" . zget($executions, $bug->execution) . "'";
                break;
            case 'resolvedBuild':
                $class .= ' text-ellipsis';
                $title  = "title='" . $bug->resolvedBuild . "'";
                break;
            case 'os':
                $class .= ' text-ellipsis';
                $title  = "title='" . $os . "'";
                break;
            case 'keywords':
                $class .= ' text-left';
                $title  = "title='{$bug->keywords}'";
                break;
            case 'browser':
                $class .= ' text-ellipsis';
                $title  = "title='" . $browser . "'";
                break;
            case 'deadline':
                $class .= ' text-center';
                break;
        }

        if($id == 'deadline' && isset($bug->delay) && $bug->status == 'active') $class .= ' delayed';
        if(strpos(',type,execution,story,plan,task,openedBuild,', ",{$id},") !== false) $class .= ' text-ellipsis';

        echo "<td class='" . $class . "' $title>";
        if($this->config->edition != 'open') $this->loadModel('flow')->printFlowCell('bug', $bug, $id);
        switch($id)
        {
        case 'id':
            if($canBatchAction)
            {
                echo html::checkbox('bugIDList', array($bug->id => '')) . html::a(helper::createLink('bug', 'view', "bugID=$bug->id"), sprintf('%03d', $bug->id), '', "data-app='{$this->app->tab}'");
            }
            else
            {
                printf('%03d', $bug->id);
            }
            break;
        case 'severity':
            $severityValue     = zget($this->lang->bug->severityList, $bug->severity);
            $hasCustomSeverity = !is_numeric($severityValue);
            if($hasCustomSeverity)
            {
                echo "<span class='label-severity-custom' data-severity='{$bug->severity}' title='" . $severityValue . "'>" . $severityValue . "</span>";
            }
            else
            {
                echo "<span class='label-severity' data-severity='{$bug->severity}' title='" . $severityValue . "'></span>";
            }
            break;
        case 'pri':
            if($bug->pri)
            {
                echo "<span class='label-pri label-pri-" . $bug->pri . "' title='" . zget($this->lang->bug->priList, $bug->pri, $bug->pri) . "'>";
                echo zget($this->lang->bug->priList, $bug->pri, $bug->pri);
                echo "</span>";
            }
            break;
        case 'confirmed':
            $class = 'confirm' . $bug->confirmed;
            echo "<span class='$class' title='" . zget($this->lang->bug->confirmedList, $bug->confirmed, $bug->confirmed) . "'>" . zget($this->lang->bug->confirmedList, $bug->confirmed, $bug->confirmed) . "</span> ";
            break;
        case 'title':
            $showBranch = isset($this->config->bug->browse->showBranch) ? $this->config->bug->browse->showBranch : 1;
            if(isset($branches[$bug->branch]) and $showBranch) echo "<span class='label label-outline label-badge' title={$branches[$bug->branch]}>{$branches[$bug->branch]}</span> ";
            if($bug->module and isset($modulePairs[$bug->module])) echo "<span class='label label-gray label-badge'>{$modulePairs[$bug->module]}</span> ";
            echo $canView ? html::a($bugLink, $bug->title, null, "style='color: $bug->color' data-app={$this->app->tab}") : "<span style='color: $bug->color'>{$bug->title}</span>";
            if($bug->case)
            {
                $caseLink = helper::createLink('testcase', 'view', "caseID=$bug->case&version=$bug->caseVersion");
                if($this->app->tab == 'chteam') $caseLink = helper::createLink('testcase', 'view', "caseID=$bug->case&version=$bug->caseVersion") . '#app=chteam';
                echo html::a($caseLink, "[" . $this->lang->testcase->common  . "#$bug->case]", '', "class='bug' title='$bug->case'");
            }
            break;
        case 'branch':
            echo zget($branches, $bug->branch, '');
            break;
        case 'project':
            echo zget($projectPairs, $bug->project, '');
            break;
        case 'product':
            echo zget($products, $bug->product, '');
            break;
        case 'execution':
            echo zget($executions, $bug->execution, '');
            break;
        case 'plan':
            echo zget($plans, $bug->plan, '');
            break;
        case 'story':
            if(isset($stories[$bug->story]))
            {
                $story = $stories[$bug->story];
                echo common::hasPriv('story', 'view') ? html::a(helper::createLink('story', 'view', "storyID=$story->id", 'html', true), $story->title, '', "class='iframe'") : $story->title;
            }
            break;
        case 'task':
            if(isset($tasks[$bug->task]))
            {
                $task = $tasks[$bug->task];
                echo common::hasPriv('task', 'view') ? html::a(helper::createLink('task', 'view', "taskID=$task->id", 'html', true), $task->name, '', "class='iframe'") : $task->name;
            }
            break;
        case 'toTask':
            if(isset($tasks[$bug->toTask]))
            {
                $task = $tasks[$bug->toTask];
                echo common::hasPriv('task', 'view') ? html::a(helper::createLink('task', 'view', "taskID=$task->id", 'html', true), $task->name, '', "class='iframe'") : $task->name;
            }
            break;
        case 'type':
            echo zget($this->lang->bug->typeList, $bug->type);
            break;
        case 'status':
            echo "<span class='status-bug status-{$bug->status}'>";
            echo $this->processStatus('bug', $bug);
            echo  '</span>';
            break;
        case 'activatedCount':
            echo $bug->activatedCount;
            break;
        case 'activatedDate':
            echo helper::isZeroDate($bug->activatedDate) ? '' : substr($bug->activatedDate, 5, 11);
            break;
        case 'keywords':
            echo $bug->keywords;
            break;
        case 'os':
            echo $os;
            break;
        case 'browser':
            echo $browser;
            break;
        case 'mailto':
            $mailto = explode(',', $bug->mailto);
            foreach($mailto as $account)
            {
                $account = trim($account);
                if(empty($account)) continue;
                echo zget($users, $account) . " &nbsp;";
            }
            break;
        case 'found':
            echo zget($users, $bug->found);
            break;
        case 'openedBy':
            echo zget($users, $bug->openedBy);
            break;
        case 'openedDate':
            echo helper::isZeroDate($bug->openedDate) ? '' : substr($bug->openedDate, 5, 11);
            break;
        case 'openedBuild':
            echo $bug->openedBuild;
            break;
        case 'assignedTo':
            $this->printAssignedHtml($bug, $users);
            break;
        case 'assignedDate':
            echo helper::isZeroDate($bug->assignedDate) ? '' : substr($bug->assignedDate, 5, 11);
            break;
        case 'deadline':
            echo helper::isZeroDate($bug->deadline) ? '' : '<span>' . substr($bug->deadline, 5, 11) . '</span>';
            break;
        case 'resolvedBy':
            echo zget($users, $bug->resolvedBy, $bug->resolvedBy);
            break;
        case 'resolution':
            echo zget($this->lang->bug->resolutionList, $bug->resolution);
            break;
        case 'resolvedDate':
            echo helper::isZeroDate($bug->resolvedDate) ? '' : substr($bug->resolvedDate, 5, 11);
            break;
        case 'resolvedBuild':
            echo $bug->resolvedBuild;
            break;
        case 'closedBy':
            echo zget($users, $bug->closedBy);
            break;
        case 'closedDate':
            echo helper::isZeroDate($bug->closedDate) ? '' : substr($bug->closedDate, 5, 11);
            break;
        case 'lastEditedBy':
            echo zget($users, $bug->lastEditedBy);
            break;
        case 'lastEditedDate':
            echo helper::isZeroDate($bug->lastEditedDate) ? '' : substr($bug->lastEditedDate, 5, 11);
            break;
        case 'actions':
            echo $this->buildOperateMenu($bug, 'browse');
            break;
        }
        echo '</td>';
    }
}

/**
 * Build bug menu.
 *
 * @param  object $bug
 * @param  string $type
 * @access public
 * @return string
 */
public function buildOperateMenu($bug, $type = 'view')
{
    $menu          = '';
    $params        = "bugID=$bug->id";
    $extraParams   = "extras=bugID=$bug->id";
    if($this->app->tab == 'project')   $extraParams .= ",projectID={$bug->project}";
    if($this->app->tab == 'execution') $extraParams .= ",executionID={$bug->execution}";
    $copyParams    = "productID=$bug->product&branch=$bug->branch&$extraParams&chprojectID={$this->session->chproject}";
    $convertParams = "productID=$bug->product&branch=$bug->branch&moduleID=0&from=bug&bugID=$bug->id";
    $toStoryParams = "product=$bug->product&branch=$bug->branch&module=0&story=0&execution=0&bugID=$bug->id";

    $menu .= $this->buildMenu('bug', 'confirmBug', $params, $bug, $type, 'ok', '', "iframe", true);
    if($type == 'view' and $bug->status != 'closed') $menu .= $this->buildMenu('bug', 'assignTo', $params, $bug, $type, '', '', "iframe", true);
    $menu .= $this->buildMenu('bug', 'resolve', $params, $bug, $type, 'checked', '', "iframe showinonlybody", true);
    $menu .= $this->buildMenu('bug', 'close', $params, $bug, $type, '', '', "text-danger iframe showinonlybody", true);
    if($type == 'view') $menu .= $this->buildMenu('bug', 'activate', $params, $bug, $type, '', '', "text-success iframe showinonlybody", true);
    if($type == 'view' && $this->app->tab != 'product')
    {
        $tab   = $this->app->tab == 'qa' ? 'product' : $this->app->tab;
        if($tab == 'product')
        {
            $product = $this->loadModel('product')->getByID($bug->product);
            if(!empty($product->shadow)) $tab = 'project';
        }
        $menu .= $this->buildMenu('bug', 'toStory', $toStoryParams, $bug, $type, $this->lang->icons['story'], '', '', '', "data-app='$tab' id='tostory'", $this->lang->bug->toStory);
        if(common::hasPriv('task', 'create') and !isonlybody()) $menu .= html::a('#toTask', "<i class='icon icon-check'></i><span class='text'>{$this->lang->bug->toTask}</span>", '', "data-app='qa' data-toggle='modal' class='btn btn-link'");
        $menu .= $this->buildMenu('bug', 'createCase', $convertParams, $bug, $type, 'sitemap');
    }
    if($type == 'view')
    {
        $menu .= "<div class='divider'></div>";
        $menu .= $this->buildFlowMenu('bug', $bug, $type, 'direct');
        $menu .= "<div class='divider'></div>";
    }

    $editParams = $params;
    if($this->app->tab == 'chteam') $editParams .= "&comment=&kanbanGroup=&chprojectID={$this->session->chproject}";
    $menu .= $this->buildMenu('bug', 'edit', $editParams, $bug, $type);
    if($this->app->tab != 'product') $menu .= $this->buildMenu('bug', 'create', $copyParams, $bug, $type, 'copy');

    if($type == 'view')
    {
        $deleteParams = $params;
        if($this->app->tab == 'chteam') $deleteParams .= "&confirm=no&from=chproject";
        $menu .= $this->buildMenu('bug', 'delete', $deleteParams, $bug, $type, 'trash', 'hiddenwin', "showinonlybody");
    }

    return $menu;
}

/**
 * Update a bug.
 *
 * @param  int    $bugID
 * @access public
 * @return void
 */
public function update($bugID)
{
    $oldBug = $this->getById($bugID);
    if(!empty($_POST['lastEditedDate']) and $oldBug->lastEditedDate != $this->post->lastEditedDate)
    {
        dao::$errors[] = $this->lang->error->editedByOther;
        return false;
    }
    $now = helper::now();
    $bug = fixer::input('post')
        ->add('id', $bugID)
        ->cleanInt('product,module,severity,project,execution,story,task,branch,duplicateBug')
        ->stripTags($this->config->bug->editor->edit['id'], $this->config->allowedTags)
        ->setDefault('module,execution,story,task,duplicateBug,branch', 0)
        ->setDefault('product', $oldBug->product)
        ->setDefault('openedBuild', '')
        ->setDefault('os', '')
        ->setDefault('browser', '')
        ->setDefault('plan', 0)
        ->setDefault('deadline', '0000-00-00')
        ->setDefault('resolvedDate', '')
        ->setDefault('lastEditedBy',   $this->app->user->account)
        ->setDefault('mailto', '')
        ->setDefault('deleteFiles', array())
        ->add('lastEditedDate', $now)
        ->setIF(strpos($this->config->bug->edit->requiredFields, 'deadline') !== false, 'deadline', $this->post->deadline)
        ->join('openedBuild', ',')
        ->join('mailto', ',')
        ->join('linkBug', ',')
        ->join('os', ',')
        ->join('browser', ',')
        ->setIF($this->post->assignedTo  != $oldBug->assignedTo, 'assignedDate', $now)
        ->setIF($this->post->resolvedBy  != '' and $this->post->resolvedDate == '', 'resolvedDate', $now)
        ->setIF($this->post->resolution  != '' and $this->post->resolvedDate == '', 'resolvedDate', $now)
        ->setIF($this->post->resolution  != '' and $this->post->resolvedBy   == '', 'resolvedBy',   $this->app->user->account)
        ->setIF($this->post->closedBy    != '' and $this->post->closedDate   == '', 'closedDate',   $now)
        ->setIF($this->post->closedDate  != '' and $this->post->closedBy     == '', 'closedBy',     $this->app->user->account)
        ->setIF($this->post->closedBy    != '' or  $this->post->closedDate   != '', 'assignedTo',   'closed')
        ->setIF($this->post->closedBy    != '' or  $this->post->closedDate   != '', 'assignedDate', $now)
        ->setIF($this->post->resolution  != '' or  $this->post->resolvedDate != '', 'status',       'resolved')
        ->setIF($this->post->closedBy    != '' or  $this->post->closedDate   != '', 'status',       'closed')
        ->setIF(($this->post->resolution != '' or  $this->post->resolvedDate != '') and $this->post->assignedTo == '', 'assignedTo', $oldBug->openedBy)
        ->setIF(($this->post->resolution != '' or  $this->post->resolvedDate != '') and $this->post->assignedTo == '', 'assignedDate', $now)
        ->setIF($this->post->assignedTo  == '' and $oldBug->status           == 'closed', 'assignedTo', 'closed')
        ->setIF($this->post->resolution  == '' and $this->post->resolvedDate =='', 'status', 'active')
        ->setIF($this->post->resolution  != '', 'confirmed', 1)
        ->setIF($this->post->resolution  != '' and $this->post->resolution != 'duplicate', 'duplicateBug', 0)
        ->setIF($this->post->story != false and $this->post->story != $oldBug->story, 'storyVersion', $this->loadModel('story')->getVersion($this->post->story))
        ->setIF(!$this->post->linkBug, 'linkBug', '')
        ->setIF($this->post->case === '', 'case', 0)
        ->setIF($this->post->testtask === '', 'testtask', 0)
        ->remove('comment,files,labels,uid,contactListMenu')
        ->get();

    $bug = $this->loadModel('file')->processImgURL($bug, $this->config->bug->editor->edit['id'], $this->post->uid);
    $this->dao->update(TABLE_BUG)->data($bug, 'deleteFiles')
        ->autoCheck()
        ->batchCheck($this->config->bug->edit->requiredFields, 'notempty')
        ->checkIF($bug->resolvedBy, 'resolution',  'notempty')
        ->checkIF($bug->closedBy,   'resolution',  'notempty')
        ->checkIF($bug->notifyEmail, 'notifyEmail', 'email')
        ->checkIF($bug->resolution == 'duplicate', 'duplicateBug', 'notempty')
        ->checkIF($bug->resolution == 'fixed',     'resolvedBuild','notempty')
        ->checkFlow()
        ->where('id')->eq((int)$bugID)
        ->exec();

    if(!dao::isError())
    {
        /* Link bug to build and release. */
        if($bug->resolution == 'fixed' and !empty($bug->resolvedBuild) and $oldBug->resolvedBuild != $bug->resolvedBuild)
        {
            if(!empty($oldBug->resolvedBuild)) $this->loadModel('build')->unlinkBug($oldBug->resolvedBuild, (int)$bugID);
            $this->linkBugToBuild($bugID, $bug->resolvedBuild);
        }

        if($bug->plan != $oldBug->plan)
        {
            $this->loadModel('action');
            if(!empty($oldBug->plan)) $this->action->create('productplan', $oldBug->plan, 'unlinkbug', '', $bugID);
            if(!empty($bug->plan)) $this->action->create('productplan', $bug->plan, 'linkbug', '', $bugID);
        }

        $linkBugs    = explode(',', $bug->linkBug);
        $oldLinkBugs = explode(',', $oldBug->linkBug);
        $addBugs     = array_diff($linkBugs, $oldLinkBugs);
        $removeBugs  = array_diff($oldLinkBugs, $linkBugs);
        $changeBugs  = array_merge($addBugs, $removeBugs);
        $changeBugs  = $this->dao->select('id,linkbug')->from(TABLE_BUG)->where('id')->in(array_filter($changeBugs))->fetchPairs();
        foreach($changeBugs as $changeBugID => $changeBug)
        {
            if(in_array($changeBugID, $addBugs) and empty($changeBug))  $this->dao->update(TABLE_BUG)->set('linkBug')->eq($bugID)->where('id')->eq((int)$changeBugID)->exec();
            if(in_array($changeBugID, $addBugs) and !empty($changeBug)) $this->dao->update(TABLE_BUG)->set('linkBug')->eq("$changeBug,$bugID")->where('id')->eq((int)$changeBugID)->exec();
            if(in_array($changeBugID, $removeBugs))
            {
                $linkBugs = explode(',', $changeBug);
                unset($linkBugs[array_search($bugID, $linkBugs)]);
                $this->dao->update(TABLE_BUG)->set('linkBug')->eq(implode(',', $linkBugs))->where('id')->eq((int)$changeBugID)->exec();
            }
        }

        if(!empty($bug->resolvedBy)) $this->loadModel('score')->create('bug', 'resolve', $bugID);

        if($this->app->tab == 'chteam' && ($bug->execution != $oldBug->execution))
        {
            $cell = $this->dao->select('id,cards')->from(TABLE_KANBANCELL)
                ->where('kanban')->eq($oldBug->execution)
                ->andWhere('type')->eq('bug')
                ->andWhere('cards')->like("%$bugID%")
                ->fetch();

            $cards = str_replace(",$bugID,", ',', $cell->cards);

            $this->dao->update(TABLE_KANBANCELL)->set('cards')->eq($cards)->where('id')->eq($cell->id)->exec();
        }

        if($bug->execution && (($bug->status != $oldBug->status) || ($bug->execution != $oldBug->execution))) $this->loadModel('kanban')->updateLane($bug->execution, 'bug');

        if($this->config->edition != 'open' && $oldBug->feedback) $this->loadModel('feedback')->updateStatus('bug', $oldBug->feedback, $bug->status, $oldBug->status);

        $this->file->processFile4Object('bug', $oldBug, $bug);
        return common::createChanges($oldBug, $bug);
    }
}
