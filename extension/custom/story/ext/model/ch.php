<?php
/**
 * Get stories pairs of a execution.
 *
 * @param  int           $executionID
 * @param  int           $productID
 * @param  int           $branch
 * @param  array|string  $moduleIdList
 * @param  string        $type full|short
 * @param  string        $status all|unclosed|review
 * @param  string        $storyType story|requirement
 * @access public
 * @return array
 */
public function getExecutionStoryPairs($executionID = 0, $productID = 0, $branch = 'all', $moduleIdList = 0, $type = 'full', $status = 'all', $storyType = 'story')
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getExecutionStoryPairs();

    $stories = $this->dao->select('t2.id, t2.title, t2.module, t2.pri, t2.estimate, t3.name AS product')
        ->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t1.product = t3.id')
        ->where('t1.project')->in($executionID)
        ->andWhere('t2.deleted')->eq(0)
        ->andWhere('t2.type')->eq($storyType)
        ->beginIF($productID)->andWhere('t2.product')->eq((int)$productID)->fi()
        ->beginIF($branch !== 'all')->andWhere('t2.branch')->in("0,$branch")->fi()
        ->beginIF($moduleIdList)->andWhere('t2.module')->in($moduleIdList)->fi()
        ->beginIF($status == 'unclosed')->andWhere('t2.status')->ne('closed')->fi()
        ->beginIF($status == 'review')->andWhere('t2.status')->in('draft,changing')->fi()
        ->beginIF($status == 'active')->andWhere('t2.status')->eq('active')->fi()
        ->orderBy('t1.`order` desc, t1.`story` desc')
        ->fetchAll('id');

    return empty($stories) ? array() : $this->formatStories($stories, $type);
}

/**
 * Get stories list of a execution.
 *
 * @param  int    $executionID
 * @param  int    $productID
 * @param  int    $branch
 * @param  string $orderBy
 * @param  string $type
 * @param  int    $param
 * @param  string $storyType
 * @param  string $excludeStories
 * @param  string $excludeStatus
 * @param  object $pager
 * @access public
 * @return array
 */
public function getExecutionStories($executionID = 0, $productID = 0, $branch = 0, $orderBy = 't1.`order`_desc', $type = 'byModule', $param = 0, $storyType = 'story', $excludeStories = '', $excludeStatus = '', $pager = null)
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getExecutionStories();

    if(!$executionID) return array();
    $executions = $this->dao->select('*')->from(TABLE_PROJECT)->where('id')->in($executionID)->fetchAll('id');
    $hasProject = false;
    $hasExecution = false;
    foreach($executions as $execution)
    {
        if($execution->type == 'project') $hasProject   = true;
        if($execution->type != 'project') $hasExecution = true;
    }

    $orderBy = str_replace('branch_', 't2.branch_', $orderBy);
    $orderBy = str_replace('version_', 't2.version_', $orderBy);
    $type    = strtolower($type);

    $products = $this->loadModel('product')->getProducts($executionID);
    if($type == 'bysearch')
    {
        $queryID = (int)$param;

        if($this->session->executionStoryQuery == false) $this->session->set('executionStoryQuery', ' 1 = 1');
        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                if($this->app->rawModule == 'projectstory')
                {
                    $this->session->set('projectstoryQuery', $query->sql);
                    $this->session->set('projectstoryForm', $query->form);
                }
                elseif($this->app->rawModule == 'chproject')
                {
                    $this->session->set('chprojectStoryQuery', $query->sql);
                    $this->session->set('chprojectStoryForm', $query->form);
                }
                else
                {
                    $this->session->set('executionStoryQuery', $query->sql);
                    $this->session->set('executionStoryForm', $query->form);
                }
            }
        }

        if(in_array($this->app->rawModule, array('projectstory', 'chproject')))
        {
            $searchQuery = $this->session->{$this->app->rawModule . 'Query'};
            if($this->app->rawModule == 'chproject') $searchQuery = $this->session->chprojectStoryQuery;
            $this->session->executionStoryQuery = $searchQuery;
        }

        $allProduct = "`product` = 'all'";
        $storyQuery = $this->session->executionStoryQuery;
        if(strpos($this->session->executionStoryQuery, $allProduct) !== false)
        {
            $storyQuery = str_replace($allProduct, '1', $this->session->executionStoryQuery);
        }
        $storyQuery = preg_replace('/`(\w+)`/', 't2.`$1`', $storyQuery);

        if($this->app->rawModule != 'projectstory' and $products) $productID = key($products);
        $review = $this->getRevertStoryIDList($productID);

        if(strpos($storyQuery, 'result') !== false)
        {
            if(strpos($storyQuery, 'revert') !== false)
            {
                $storyQuery  = str_replace("AND t2.`result` = 'revert'", '', $storyQuery);
                $storyQuery .= " AND t2.`id` " . helper::dbIN($review);
            }
            else
            {
                $storyQuery = str_replace(array('t2.`result`'), array('t4.`result`'), $storyQuery);
            }
        }

        $stories = $this->dao->select("distinct t1.*, t2.*, IF(t2.`pri` = 0, {$this->config->maxPriValue}, t2.`pri`) as priOrder, t3.type as productType, t2.version as version")->from(TABLE_PROJECTSTORY)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t2.product = t3.id')
            ->beginIF(strpos($storyQuery, 'result') !== false)->leftJoin(TABLE_STORYREVIEW)->alias('t4')->on('t2.id = t4.story and t2.version = t4.version')->fi()
            ->where('t2.type')->eq($storyType)
            ->andWhere("($storyQuery)")
            ->andWhere('t1.project')->in($executionID)
            ->andWhere('t2.deleted')->eq(0)
            ->andWhere('t3.deleted')->eq(0)
            ->beginIF($excludeStories)->andWhere('t2.id')->notIN($excludeStories)->fi()
            ->orderBy($orderBy)
            ->page($pager, 't2.id')
            ->fetchAll('id');
    }
    else
    {
        $productParam = ($type == 'byproduct' and $param) ? $param : $this->cookie->storyProductParam;
        $branchParam  = ($type == 'bybranch'  and $param !== '') ? $param : $this->cookie->storyBranchParam;
        $moduleParam  = ($type == 'bymodule'  and $param !== '') ? $param : $this->cookie->storyModuleParam;

        $modules = array();
        if(!empty($moduleParam) or strpos('allstory,unclosed,bymodule', $type) !== false)
        {
            $modules = $this->dao->select('id')->from(TABLE_MODULE)->where('path')->like("%,$moduleParam,%")->andWhere('type')->eq('story')->andWhere('deleted')->eq(0)->fetchPairs();
        }

        if(strpos($branchParam, ',') !== false) list($productParam, $branchParam) = explode(',', $branchParam);

        $unclosedStatus = $this->lang->story->statusList;
        unset($unclosedStatus['closed']);

        /* Get story id list of linked executions. */
        $storyIdList = array();
        if($type == 'linkedexecution' or $type == 'unlinkedexecution')
        {
            $executions  = $this->loadModel('execution')->getPairs($executionID);
            $storyIdList = $this->dao->select('story')->from(TABLE_PROJECTSTORY)->where('project')->in(array_keys($executions))->fetchPairs();
        }

        $type = (strpos('bymodule|byproduct', $type) !== false and $this->session->storyBrowseType) ? $this->session->storyBrowseType : $type;

        $stories = $this->dao->select("distinct t1.*, t2.*, IF(t2.`pri` = 0, {$this->config->maxPriValue}, t2.`pri`) as priOrder, t3.type as productType, t2.version as version")->from(TABLE_PROJECTSTORY)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t2.product = t3.id')
            ->where('t1.project')->in($executionID)
            ->andWhere('t2.type')->eq($storyType)
            ->beginIF($excludeStories)->andWhere('t2.id')->notIN($excludeStories)->fi()
            ->beginIF($hasProject)
            ->beginIF(!empty($productID))->andWhere('t1.product')->eq($productID)->fi()
            ->beginIF($type == 'bybranch' and $branchParam !== '')->andWhere('t2.branch')->in("0,$branchParam")->fi()
            ->beginIF($type == 'linkedexecution')->andWhere('t2.id')->in($storyIdList)->fi()
            ->beginIF($type == 'unlinkedexecution')->andWhere('t2.id')->notIn($storyIdList)->fi()
            ->fi()
            ->beginIF($hasExecution)
            ->beginIF(!empty($productParam))->andWhere('t1.product')->eq($productParam)->fi()
            ->beginIF($this->session->executionStoryBrowseType and strpos('changing|', $this->session->executionStoryBrowseType) !== false)->andWhere('t2.status')->in(array_keys($unclosedStatus))->fi()
            ->fi()
            ->beginIF(strpos('draft|reviewing|changing|closed', $type) !== false)->andWhere('t2.status')->eq($type)->fi()
            ->beginIF($type == 'unclosed')->andWhere('t2.status')->in(array_keys($unclosedStatus))->fi()
            ->beginIF($excludeStatus)->andWhere('t2.status')->notIN($excludeStatus)->fi()
            ->beginIF($this->session->storyBrowseType and strpos('changing|', $this->session->storyBrowseType) !== false)->andWhere('t2.status')->in(array_keys($unclosedStatus))->fi()
            ->beginIF($modules)->andWhere('t2.module')->in($modules)->fi()
            ->andWhere('t2.deleted')->eq(0)
            ->andWhere('t3.deleted')->eq(0)
            ->orderBy($orderBy)
            ->page($pager, 't2.id')
            ->fetchAll('id');
    }

    $query = $this->dao->get();

    /* Get the stories of main branch. */
    $branchStoryList = $this->dao->select('t1.*,t2.branch as productBranch')->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_PROJECTPRODUCT)->alias('t2')->on('t1.project = t2.project')
        ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t1.product = t3.id')
        ->where('t1.story')->in(array_keys($stories))
        ->andWhere('t1.branch')->eq(BRANCH_MAIN)
        ->andWhere('t3.type')->ne('normal')
        ->fetchAll();

    $branches       = array();
    $stageOrderList = 'wait,planned,projected,developing,developed,testing,tested,verified,released,closed';

    foreach($branchStoryList as $story) $branches[$story->productBranch][$story->story] = $story->story;

    /* Set up story stage. */
    foreach($branches as $branchID => $storyIdList)
    {
        $stages = $this->dao->select('*')->from(TABLE_STORYSTAGE)->where('story')->in($storyIdList)->andWhere('branch')->eq($branchID)->fetchPairs('story', 'stage');

        /* Take the earlier stage. */
        foreach($stages as $storyID => $stage) if(strpos($stageOrderList, $stories[$storyID]->stage) > strpos($stageOrderList, $stage)) $stories[$storyID]->stage = $stage;
    }

    $this->dao->sqlobj->sql = $query;
    return $this->mergePlanTitle($productID, $stories, $branch, $storyType);
}

/**
 * Get project pairs by story id.
 *
 * @param  int    $storyID
 * @param  string $fields
 * @param  string $type
 * @access public
 * @return array
 */
public function getProjectPairsByID($storyID, $fields, $type = 'all')
{
    return $this->dao->select($fields)->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->where('t1.story')->eq($storyID)
        ->andWhere('t2.deleted')->eq('0')
        ->beginIF($type != 'all')->andWhere('t2.type')->in($type)->fi()
        ->fetchPairs();
}

/**
 * Get stories list of a product.
 *
 * @param  int          $productID
 * @param  int          $branch
 * @param  array|string $moduleIdList
 * @param  string       $status
 * @param  string       $type    requirement|story
 * @param  string       $orderBy
 * @param  array|string $excludeStories
 * @param  object       $pager
 * @param  bool         $hasParent
 * @param  int          $objectID
 *
 * @access public
 * @return array
 */
public function getProductStories($productID = 0, $branch = 0, $moduleIdList = 0, $status = 'all', $type = 'story', $orderBy = 'id_desc', $hasParent = true, $excludeStories = '', $pager = null, $objectID = 0)
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getStories();

    $stories        = array();
    $branchProducts = array();
    $normalProducts = array();
    $productList    = $this->dao->select('*')->from(TABLE_PRODUCT)->where('id')->in($productID)->fetchAll('id');
    foreach($productList as $product)
    {
        if($product->type != 'normal')
        {
            $branchProducts[$product->id] = $product->id;
            continue;
        }

        $normalProducts[$product->id] = $product->id;
    }

    $productQuery = '(';
    if(!empty($normalProducts)) $productQuery .= '`product` ' . helper::dbIN(array_keys($normalProducts));
    if(!empty($branchProducts))
    {
        if(!empty($normalProducts)) $productQuery .= " OR ";
        $productQuery .= "(`product` " . helper::dbIN(array_keys($branchProducts));

        if($branch !== 'all')
        {
            if(is_array($branch)) $branch = join(',', $branch);
            $productQuery .= " AND `branch` " . helper::dbIN($branch);
        }
        $productQuery .= ')';
    }
    if(empty($normalProducts) and empty($branchProducts)) $productQuery .= '1 = 1';
    $productQuery .= ') ';

    $storyIdList = [];

    $stories = $this->dao->select("*, IF(`pri` = 0, {$this->config->maxPriValue}, `pri`) as priOrder")->from(TABLE_STORY)
        ->where('product')->in($productID)
        ->andWhere($productQuery)
        ->beginIF(!$hasParent)->andWhere("parent")->ge(0)->fi()
        ->beginIF(!empty($moduleIdList))->andWhere('module')->in($moduleIdList)->fi()
        ->beginIF(!empty($excludeStories))->andWhere('id')->notIN($excludeStories)->fi()
        ->beginIF($status and $status != 'all')->andWhere('status')->in($status)->fi()
        ->andWhere("FIND_IN_SET('{$this->config->vision}', vision)")
        ->andWhere('type')->eq($type)
        ->andWhere('deleted')->eq(0)
        ->beginIF($storyIdList)->andWhere('id')->in($storyIdList)->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');

    return $this->mergePlanTitle($productID, $stories, $branch, $type);
}

/**
 * Get stories through search.
 *
 * @access public
 * @param  int         $productID
 * @param  int|string  $branch
 * @param  int         $queryID
 * @param  string      $orderBy
 * @param  string      $executionID
 * @param  string      $type requirement|story
 * @param  string      $excludeStories
 * @param  string      $excludeStatus
 * @param  object      $pager
 * @access public
 * @return array
 */
public function getBySearch($productID, $branch = '', $queryID = 0, $orderBy = '', $executionID = '', $type = 'story', $excludeStories = '', $excludeStatus = '', $pager = null)
{
    $this->loadModel('product');
    $executionID = empty($executionID) ? 0 : $executionID;
    $products    = empty($executionID) ? $this->product->getList($programID = 0, $status = 'all', $limit = 0, $line = 0, $shadow = 'all') : $this->product->getProducts($executionID);

    $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';

    /* Get the sql and form status from the query. */
    if($query)
    {
        $this->session->set('storyQuery', $query->sql);
        $this->session->set('storyForm', $query->form);
    }
    if($this->session->storyQuery == false) $this->session->set('storyQuery', ' 1 = 1');

    $allProduct     = "`product` = 'all'";
    $storyQuery     = $this->session->storyQuery;
    $queryProductID = $productID;
    if(strpos($storyQuery, $allProduct) !== false)
    {
        $storyQuery     = str_replace($allProduct, '1', $storyQuery);
        $queryProductID = 'all';
    }

    $storyQuery = $storyQuery . ' AND `product` ' . helper::dbIN(array_keys($products));

    if($excludeStories) $storyQuery = $storyQuery . ' AND `id` NOT ' . helper::dbIN($excludeStories);
    if($excludeStatus)  $storyQuery = $storyQuery . ' AND `status` NOT ' . helper::dbIN($excludeStatus);
    if($this->app->moduleName == 'productplan') $storyQuery .= " AND `status` NOT IN ('closed') AND `parent` >= 0 ";
    if(in_array($this->app->moduleName, array('build', 'projectrelease', 'release'))) $storyQuery .= "AND `parent` >= 0 ";
    $allBranch = "`branch` = 'all'";
    if(!empty($executionID))
    {
        $normalProducts = array();
        $branchProducts = array();
        foreach($products as $product)
        {
            if($product->type != 'normal')
            {
                $branchProducts[$product->id] = $product;
                continue;
            }

            $normalProducts[$product->id] = $product;
        }

        $storyQuery .= ' AND (';
        if(!empty($normalProducts)) $storyQuery .= '`product` ' . helper::dbIN(array_keys($normalProducts));
        if(!empty($branchProducts))
        {
            $branches = array(BRANCH_MAIN => BRANCH_MAIN);
            if($branch === '')
            {
                foreach($branchProducts as $product)
                {
                    foreach($product->branches as $branchID) $branches[$branchID] = $branchID;
                }
            }
            else
            {
                $branches[$branch] = $branch;
            }

            $branches    = join(',', $branches);
            if(!empty($normalProducts)) $storyQuery .= " OR ";
            $storyQuery .= "(`product` " . helper::dbIN(array_keys($branchProducts)) . " AND `branch` " . helper::dbIN($branches) . ")";
        }
        if(empty($normalProducts) and empty($branchProducts)) $storyQuery .= '1 = 1';
        $storyQuery .= ') ';

        if($this->app->moduleName == 'release' or $this->app->moduleName == 'build')
        {
            $storyQuery .= " AND `status` NOT IN ('draft')"; // Fix bug #990.
        }
        else
        {
            $storyQuery .= " AND `status` NOT IN ('draft', 'reviewing', 'changing', 'closed')";
        }

        if($this->app->rawModule == 'build' and $this->app->rawMethod == 'linkstory') $storyQuery .= " AND `parent` != '-1'";
    }
    elseif(strpos($storyQuery, $allBranch) !== false)
    {
        $storyQuery = str_replace($allBranch, '1', $storyQuery);
    }
    elseif($branch !== 'all' and $branch !== '' and strpos($storyQuery, '`branch` =') === false and $queryProductID != 'all')
    {
        if($branch and strpos($storyQuery, '`branch` =') === false) $storyQuery .= " AND `branch` " . helper::dbIN($branch);
    }

    $storyQuery = preg_replace("/`plan` +LIKE +'%([0-9]+)%'/i", "CONCAT(',', `plan`, ',') LIKE '%,$1,%'", $storyQuery);

    return $this->getBySQL($queryProductID, $storyQuery, $orderBy, $pager, $type);
}

/**
 * Merge project name.
 *
 * @param  array $stories
 * @param  int   $chproject
 * @access public
 * @return array
 */
public function appendChproject($stories, $chproject = 0)
{
    $linkedExcutionProjects = $chproject ? $this->loadModel('chproject')->getIntancesProjectOptions($chproject, 'executionID', 'projectName') : [];
    $executionIdList        = $chproject ? $this->dao->select('story,project')->from(TABLE_PROJECTSTORY)->where('story')->in(array_column($stories, 'id'))->fetchGroup('story') : [];

    foreach($stories as $story)
    {
        if($chproject)
        {
            $story->linkedProjects = array();

            foreach($executionIdList[$story->id] as $execution)
            {
                if(isset($linkedExcutionProjects[$execution->project])) $story->linkedProjects[$execution->project] = $linkedExcutionProjects[$execution->project];
            }
        }

        $projects = isset($story->linkedProjects) ? array_values($story->linkedProjects) : $this->getProjectPairsByID($story->id, 'id,name', 'project');

        $story->projectName = implode(', ', $projects);
    }

    return $stories;
}

/**
 * Build operate menu.
 *
 * @param  object $story
 * @param  string $type
 * @param  object $execution
 * @param  string $storyType story|requirement
 * @access public
 * @return string
 */
public function buildOperateMenu($story, $type = 'view', $execution = '', $storyType = 'story')
{
    if($this->app->tab == 'chteam' && $type == 'browse') $type = 'execution';

    $params = "storyID=$story->id";

    if($type == 'browse')    $menu = $this->buildBrowseOperateMenu($story, $type, $execution, $storyType, $params);
    if($type == 'view')      $menu = $this->buildViewOperateMenu($story, $type, $execution, $storyType, $params);
    if($type == 'execution') $menu = $this->buildExecutionOperateMenu($story, $execution, $storyType, $params);

    return $menu;
}

/**
 * Build execution operate menu.
 *
 * @param  int    $story
 * @param  int    $execution
 * @param  string $storyType
 * @param  string $params
 * @access private
 * @return string
 */
private function buildExecutionOperateMenu($story, $execution, $storyType = 'story', $params)
{
    $menu = '';

    static $taskGroups = array();

    $hasDBPriv    = common::hasDBPriv($execution, 'execution');
    $canBeChanged = common::canModify('execution', $execution);
    if($canBeChanged)
    {
        $executionID = empty($execution) ? $this->session->execution : $execution->id;
        $param       = "executionID=$executionID&story={$story->id}&moduleID={$story->module}";

        $story->reviewer  = isset($story->reviewer)  ? $story->reviewer  : array();
        $story->notReview = isset($story->notReview) ? $story->notReview : array();

        $canSubmitReview    = (strpos('draft,changing', $story->status) !== false and common::hasPriv('story', 'submitReview'));
        $canReview          = (strpos('draft,changing', $story->status) === false and common::hasPriv('story', 'review'));
        $canRecall          = common::hasPriv('story', 'recall');
        $canCreateTask      = common::hasPriv('task', 'create');
        $canBatchCreateTask = common::hasPriv('task', 'batchCreate');
        $canCreateCase      = ($hasDBPriv and common::hasPriv('testcase', 'create') and $this->app->tab != 'chteam');
        $canEstimate        = common::hasPriv('execution', 'storyEstimate', $execution);
        $canUnlinkStory     = (common::hasPriv('execution', 'unlinkStory', $execution) and ($execution->hasProduct or $execution->multiple));

        if(strpos('draft,changing', $story->status) !== false)
        {
            if($canSubmitReview) $menu .= common::buildIconButton('story', 'submitReview', "storyID=$story->id&from=story", $story, 'list', 'confirm', '', 'iframe', true, "data-width='50%'");
        }
        else
        {
            if($canReview)
            {
                $reviewDisabled = in_array($this->app->user->account, $story->notReview) and ($story->status == 'draft' or $story->status == 'changing') ? '' : 'disabled';
                $story->from = 'execution';
                $menu .= common::buildIconButton('story', 'review', "story={$story->id}&from=execution", $story, 'list', 'search', '', $reviewDisabled, false, "data-group=execution");
            }
        }

        if($canRecall)
        {
            $recallDisabled = empty($story->reviewedBy) and strpos('draft,changing', $story->status) !== false and !empty($story->reviewer) ? '' : 'disabled';
            $title  = $story->status == 'changing' ? $this->lang->story->recallChange : $this->lang->story->recall;
            $menu  .= common::buildIconButton('story', 'recall', "story={$story->id}", $story, 'list', 'undo', 'hiddenwin', $recallDisabled, '', '', $title);
        }
        if(!$execution->hasProduct && $this->app->tab != 'chteam') $menu .= common::buildIconButton('story', 'edit', $params . "&kanbanGroup=default&storyType=$story->type", $story, 'list', '', '', 'showinonlybody');

        $this->lang->task->create = $this->lang->execution->wbs;
        $toTaskDisabled = $story->status == 'active' ? '' : 'disabled';
        if(commonModel::isTutorialMode())
        {
            $wizardParams = helper::safe64Encode($param);
            $menu .=  html::a(helper::createLink('tutorial', 'wizard', "module=task&method=create&params=$wizardParams"), "<i class='icon-plus'></i>",'', "class='btn btn-task-create' title='{$this->lang->execution->wbs}' data-app='{$this->app->tab}'");
        }
        else
        {
            $chProjectID = 0;
            $executionID = 0;
            if($this->app->tab == 'chteam' && $this->session->chproject)
            {
                $chProjectID = $this->session->chproject;
                $intances    = $this->loadModel('chproject')->getIntances($chProjectID);
                $executionID = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('project')->in($intances)->andWhere('story')->eq($story->id)->fetch('project');
            }

            $taskParam = "executionID=$executionID&story={$story->id}&moduleID={$story->module}&taskID=0&todoID=0&extra=from=story&bugID=0&chProjectID=$chProjectID";
            if($hasDBPriv and $storyType == 'story') $menu .= common::buildIconButton('task', 'create', $taskParam, '', 'list', 'plus', '', 'btn-task-create ' . $toTaskDisabled);
        }

        $this->lang->task->batchCreate = $this->lang->execution->batchWBS;
        if($hasDBPriv and $storyType == 'story' and $this->app->tab != 'chteam') $menu .= common::buildIconButton('task', 'batchCreate', "executionID=$executionID&story={$story->id}", '', 'list', 'pluses', '', $toTaskDisabled);

        if(($canSubmitReview or $canReview or $canRecall or $canCreateTask or $canBatchCreateTask) and ($canCreateCase or $canEstimate or $canUnlinkStory)) $menu .= "<div class='dividing-line'></div>";
        if($canEstimate and $storyType == 'story') $menu .= common::buildIconButton('execution', 'storyEstimate', "executionID=$executionID&storyID=$story->id", '', 'list', 'estimate', '', 'iframe', true, "data-width='470px'");

        $this->lang->testcase->batchCreate = $this->lang->testcase->create;
        if($canCreateCase and $storyType == 'story') $menu .= common::buildIconButton('testcase', 'create', "productID=$story->product&branch=$story->branch&moduleID=$story->module&form=&param=0&storyID=$story->id", '', 'list', 'sitemap', '', 'iframe', true, "data-app='{$this->app->tab}'");

        if(($canEstimate or $canCreateCase) and $canUnlinkStory) $menu .= "<div class='dividing-line'></div>";

        $executionID = empty($execution) ? 0 : $execution->id;

        /* Adjust code, hide split entry. */
        if(common::hasPriv('story', 'batchCreate') and !$execution->multiple and !$execution->hasProduct and $this->app->tab != 'chteam')
        {
            if(empty($taskGroups[$story->id])) $taskGroups[$story->id] = $this->dao->select('id')->from(TABLE_TASK)->where('story')->eq($story->id)->fetch('id');

            $isClick = $this->isClickable($story, 'batchcreate');
            $title   = $story->type == 'story' ? $this->lang->story->subdivideSR : $this->lang->story->subdivide;
            if(!$isClick and $story->status != 'closed')
            {
                if($story->parent > 0)
                {
                    $title = $this->lang->story->subDivideTip['subStory'];
                }
                else
                {
                    if($story->status != 'active') $title = sprintf($this->lang->story->subDivideTip['notActive'], $story->type == 'story' ? $this->lang->SRCommon : $this->lang->URCommon);
                    if($story->status == 'active' and $story->stage != 'wait') $title = sprintf($this->lang->story->subDivideTip['notWait'], zget($this->lang->story->stageList, $story->stage));
                    if($story->status == 'active' and !empty($taskGroups[$story->id])) $title = sprintf($this->lang->story->subDivideTip['notWait'], $this->lang->story->hasDividedTask);
                }
            }

            $menu .= $this->buildMenu('story', 'batchCreate', "productID=$story->product&branch=$story->branch&module=$story->module&$params&executionID=$executionID&plan=0&storyType=story", $story, 'browse', 'split', '', 'showinonlybody', '', '', $title);
        }

        if(common::hasPriv('story', 'close', "storyType={$story->type}") and !$execution->multiple and !$execution->hasProduct) $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, 'browse', '', '', 'iframe', true);

        if($canUnlinkStory and $this->app->tabe != 'chteam') $menu .= common::buildIconButton('execution', 'unlinkStory', "executionID=$executionID&storyID=$story->id&confirm=no", '', 'list', 'unlink', 'hiddenwin');

        if(common::hasPriv('execution', 'unlinkStory', $execution) && $this->app->tab == 'chteam')
        {
            $menu .= "<div class='btn-group dropdown'>";
            $menu .= "<button type='button' class='btn dropdown-toggle' data-toggle='context-dropdown' title='{$this->lang->story->unlinkStory}' style='border-radius: 4px;'><i class='icon-unlink'></i></button>";
            $menu .= "<ul class='dropdown-menu pull-right text-left' role='menu'>";

            foreach($story->linkedProjects as $linkedExcutionID => $projectName)
            {
                $title = sprintf($this->lang->story->unlinkStoryFrom, $projectName);
                $menu .= '<li>' . html::a(helper::createLink('execution', 'unlinkStory', "executionID=$linkedExcutionID&storyID={$story->id}&confirm=no", '', true), '<i class="icon-unlink"></i> ' . $projectName, '', "class='btn-link' title='$title' target='hiddenwin'") . "</li>";
            }

            $menu .= '</ul></div>';
        }
    }

    return $menu;
}

/**
 * Build browse operate menu.
 *
 * @param  object $story
 * @param  string $type
 * @param  string $execution
 * @param  string $storyType
 * @param  string $params
 * @access private
 * @return string
 */
private function buildBrowseOperateMenu($story, $type = 'view', $execution = '', $storyType = 'story', $params)
{
    static $taskGroups = array();

    if(!common::canBeChanged('story', $story)) return $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, 'list', '', '', 'iframe', true);

    $storyReviewer = isset($story->reviewer) ? $story->reviewer : array();
    if($story->URChanged) return $this->buildMenu('story', 'processStoryChange', $params, $story, $type, 'ok', '', 'iframe', true, '', $this->lang->confirm);

    $isClick = $this->isClickable($story, 'change');
    $title   = $isClick ? '' : $this->lang->story->changeTip;
    $menu    = $this->buildMenu('story', 'change', $params . "&from=&storyType=$story->type", $story, $type, 'alter', '', 'showinonlybody', false, '', $title);

    if($story->status != 'reviewing')
    {
        $menu .= $this->buildMenu('story', 'submitReview', "storyID=$story->id&storyType=$story->type", $story, $type, 'confirm', '', 'iframe', true, "data-width='50%'");
    }
    else
    {
        $isClick = $this->isClickable($story, 'review');
        $title   = $this->lang->story->review;
        if(!$isClick and $story->status != 'closed')
        {
            if($story->status == 'active')
            {
                $title = $this->lang->story->reviewTip['active'];
            }
            elseif($storyReviewer and in_array($this->app->user->account, $storyReviewer))
            {
                $title = $this->lang->story->reviewTip['reviewed'];
            }
            elseif($storyReviewer and !in_array($this->app->user->account, $storyReviewer))
            {
                $title = $this->lang->story->reviewTip['notReviewer'];
            }
        }
        $menu .= $this->buildMenu('story', 'review', $params . "&from=&storyType=$story->type", $story, $type, 'search', '', 'showinonlybody', false, '', $title);
    }

    $isClick = $this->isClickable($story, 'recall');
    $title   = $story->status == 'changing' ? $this->lang->story->recallChange : $this->lang->story->recall;
    $title   = $isClick ? $title : $this->lang->story->recallTip['actived'];
    $menu   .= $this->buildMenu('story', 'recall', $params . "&from=list&confirm=no&storyType=$story->type", $story, $type, 'undo', 'hiddenwin', 'showinonlybody', false, '', $title);
    $menu   .= $this->buildMenu('story', 'edit', $params . "&kanbanGroup=default&storyType=$story->type", $story, $type, '', '', 'showinonlybody');

    $vars            = "storyType={$story->type}";
    $canChange       = common::hasPriv('story', 'change', '', $vars);
    $canRecall       = common::hasPriv('story', 'recall', '', $vars);
    $canSubmitReview = (strpos('draft,changing', $story->status) !== false and common::hasPriv('story', 'submitReview', '', $vars));
    $canReview       = (strpos('draft,changing', $story->status) === false and common::hasPriv('story', 'review', '', $vars));
    $canEdit         = common::hasPriv('story', 'edit', '', $vars);
    $canBatchCreate  = ($this->app->tab == 'product' and (common::hasPriv('story', 'batchCreate', '', 'storyType=story')));
    $canCreateCase   = ($story->type == 'story' and common::hasPriv('testcase', 'create'));
    $canClose        = common::hasPriv('story', 'close', '', $vars);
    $canUnlinkStory  = ($this->app->tab == 'project' and common::hasPriv('projectstory', 'unlinkStory'));

    if(in_array($this->app->tab, array('product', 'project')))
    {
        if(($canChange or $canRecall or $canSubmitReview or $canReview or $canEdit) and ($canCreateCase or $canBatchCreate or $canClose or $canUnlinkStory))
        {
            $menu .= "<div class='dividing-line'></div>";
        }
    }

    if($this->app->tab == 'product' and $storyType == 'requirement')
    {
        if($story->status != 'closed')
        {
            $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe', true);
        }
        else
        {
            $menu .= $this->buildMenu('story', 'activate', $params . "&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);
        }

        if($canClose and ($canBatchCreate or $canCreateCase)) $menu .= "<div class='dividing-line'></div>";
    }

    if($story->type != 'requirement' and $this->config->vision != 'lite') $menu .= $this->buildMenu('testcase', 'create', "productID=$story->product&branch=$story->branch&module=0&from=&param=0&$params", $story, $type, 'sitemap', '', 'iframe showinonlybody', true, "data-app='{$this->app->tab}'");

    $shadow = $this->dao->findByID($story->product)->from(TABLE_PRODUCT)->fetch('shadow');
    if($this->app->rawModule != 'projectstory' OR $this->config->vision == 'lite' OR $shadow OR $story->type == 'requirement')
    {
        if($shadow and empty($taskGroups[$story->id])) $taskGroups[$story->id] = $this->dao->select('id')->from(TABLE_TASK)->where('story')->eq($story->id)->fetch('id');

        $isClick = $this->isClickable($story, 'batchcreate');
        $title   = $story->type == 'story' ? $this->lang->story->subdivideSR : $this->lang->story->subdivide;
        $parent  = $story->parent;
        if($storyType == 'requirement' && $story->type == 'story') $story->parent = 0;
        if(!$isClick and $story->status != 'closed')
        {
            if($story->parent > 0)
            {
                $title = $this->lang->story->subDivideTip['subStory'];
            }
            elseif(!empty($story->twins))
            {
                $title = $this->lang->story->subDivideTip['twinsSplit'];
            }
            else
            {
                if($story->status != 'active') $title = sprintf($this->lang->story->subDivideTip['notActive'], $story->type == 'story' ? $this->lang->SRCommon : $this->lang->URCommon);
                if($story->status == 'active' and $story->stage != 'wait') $title = sprintf($this->lang->story->subDivideTip['notWait'], zget($this->lang->story->stageList, $story->stage));
                if($story->status == 'active' and !empty($taskGroups[$story->id])) $title = sprintf($this->lang->story->subDivideTip['notWait'], $this->lang->story->hasDividedTask);
            }
        }

        $executionID = empty($execution) ? 0 : $execution->id;
        if($this->config->vision != 'or') $menu .= $this->buildMenu('story', 'batchCreate', "productID=$story->product&branch=$story->branch&module=$story->module&$params&executionID=$executionID&plan=0&storyType=$storyType", $story, $type, 'split', '', 'showinonlybody', '', '', $title);
        $story->parent = $parent;
    }

    if(($this->app->rawModule == 'projectstory' or ($this->app->tab != 'product' and $storyType == 'requirement')) and $this->config->vision != 'lite')
    {
        if($canClose) $menu .= "<div class='dividing-line'></div>";

        $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe', true);
        if(!empty($execution) and $execution->hasProduct and !($storyType == 'requirement' and $story->type == 'story'))
        {
            $moduleName = $execution->multiple ? 'projectstory' : 'execution';
            $objectID   = $execution->multiple ? $this->session->project : $execution->id;
            $menu .= $this->buildMenu($moduleName, 'unlinkStory', "projectID={$objectID}&$params", $story, $type, 'unlink', 'hiddenwin', 'showinonlybody');
        }
    }

    if($this->app->tab == 'product' and $storyType == 'story')
    {
        if(($canBatchCreate or $canCreateCase) and $canClose) $menu .= "<div class='dividing-line'></div>";

        $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe', true);
    }

    return $menu;
}

/**
 * Build view operate menu.
 *
 * @param  object $story
 * @param  string $type
 * @param  object $execution
 * @param  string $storyType
 * @param  string $params
 * @access private
 * @return string
 */
private function buildViewOperateMenu($story, $type = 'view', $execution = '', $storyType = 'story', $params)
{
    static $taskGroups = array();

    $menu = $this->buildMenu('story', 'change', $params . "&from=&storyType=$story->type", $story, $type, 'alter', '', 'showinonlybody');
    if($story->status != 'reviewing') $menu .= $this->buildMenu('story', 'submitReview', $params . "&storyType=$story->type", $story, $type, 'confirm', '', 'showinonlybody iframe', true, "data-width='50%'");

    $title = $story->status == 'changing' ? $this->lang->story->recallChange : $this->lang->story->recall;
    $menu .= $this->buildMenu('story', 'recall', $params . "&from=view&confirm=no&storyType=$story->type", $story, $type, 'undo', 'hiddenwin', 'showinonlybody', false, '', $title);

    $menu .= $this->buildMenu('story', 'review', $params . "&from={$this->app->tab}&storyType=$story->type", $story, $type, 'search', '', 'showinonlybody');

    $executionID = empty($execution) ? 0 : $execution->id;
    if(!isonlybody())
    {
        $subdivideTitle = $story->type == 'story' ? $this->lang->story->subdivideSR : $this->lang->story->subdivide;
        if($this->config->vision != 'or') $menu .= $this->buildMenu('story', 'batchCreate', "productID=$story->product&branch=$story->branch&moduleID=$story->module&$params&executionID=$executionID&plan=0&storyType=story", $story, $type, 'split', '', 'divideStory', true, "data-toggle='modal' data-type='iframe' data-width='95%'", $subdivideTitle);

    }

    $menu .= $this->buildMenu('story', 'assignTo', $params . "&kanbanGroup=default&from=&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);
    $menu .= $this->buildMenu('story', 'close',    $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);
    $menu .= $this->buildMenu('story', 'activate', $params . "&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);


    /* Print testcate actions. */
    if($story->parent >= 0 and $story->type != 'requirement' and (common::hasPriv('testcase', 'create', $story) or common::hasPriv('testcase', 'batchCreate', $story)) and $this->app->tab != 'chteam')
    {
        $this->app->loadLang('testcase');
        $menu .= "<div class='btn-group dropup'>";
        $menu .= "<button type='button' class='btn dropdown-toggle' data-toggle='dropdown'><i class='icon icon-sitemap'></i> " . $this->lang->testcase->common . " <span class='caret'></span></button>";
        $menu .= "<ul class='dropdown-menu' id='createCaseActionMenu'>";

        $misc = "data-toggle='modal' data-type='iframe' data-width='95%'";
        if(isonlybody()) $misc = '';

        if(common::hasPriv('testcase', 'create', $story))
        {
            $link  = helper::createLink('testcase', 'create', "productID=$story->product&branch=$story->branch&moduleID=0&from=&param=0&$params", '', true);
            $menu .= "<li>" . html::a($link, $this->lang->testcase->create, '', $misc) . "</li>";
        }

        if(common::hasPriv('testcase', 'batchCreate'))
        {
            $link  = helper::createLink('testcase', 'batchCreate', "productID=$story->product&branch=$story->branch&moduleID=0&$params", '', true);
            $menu .= "<li>" . html::a($link, $this->lang->testcase->batchCreate, '', $misc) . "</li>";
        }

        $menu .= "</ul></div>";
    }

    if($story->parent >= 0 and $story->type != 'requirement' && common::hasPriv('testcase', 'create', $story) && $this->app->tab == 'chteam')
    {
        $menu .= $this->buildMenu('testcase', 'create', "productID=$story->product&branch=$story->branch&moduleID=0&from=&param=0&$params&extras=&chproject={$this->session->chproject}", $story, $type, '', '', 'iframe showinonlybody', true);
    }

    $moreActions      = '';
    $disabledFeatures = ",{$this->config->disabledFeatures},";
    if($story->type != 'requirement' and ($this->config->edition == 'max' or $this->config->edition == 'ipd') and $this->app->tab == 'project' and common::hasPriv('story', 'importToLib') and strpos($disabledFeatures, ',assetlibStorylib,') === false and strpos($disabledFeatures, ',assetlib,') === false)
    {
        $moreActions .= '<li>' . html::a('#importToLib', "<i class='icon icon-assets'></i> " . $this->lang->story->importToLib, '', 'class="btn" data-toggle="modal"') . '</li>';
    }

    if(($this->app->tab == 'execution' or (!empty($execution) and $execution->multiple === '0')) and $story->status == 'active' and $story->type == 'story') $moreActions .= '<li>' . $this->buildMenu('task', 'create', "execution={$this->session->execution}&{$params}&moduleID=$story->module", $story, $type, 'plus', '', 'showinonlybody') . '</li>';

    if($moreActions)
    {
        $menu .= "<div class='btn-group dropup'>";
        $menu .= "<button type='button' class='btn dropdown-toggle' data-toggle='dropdown'>" . $this->lang->more . "<span class='caret'></span></button>";
        $menu .= "<ul class='dropdown-menu' id='moreActions'>";
        $menu .= $moreActions;
        $menu .='</ul></div>';
    }

    $menu .= "<div class='divider'></div>";
    $menu .= $this->buildFlowMenu('story', $story, $type, 'direct');
    $menu .= "<div class='divider'></div>";

    $menu .= $this->buildMenu('story', 'edit', $params . "&kanbanGroup=default&storyType=$story->type", $story, $type);

    $executionIdList = $this->getProjectPairsByID($story->id, 'id,id', 'sprint');
    $executionID     = current($executionIdList);

    $menu .= $this->buildMenu('story', 'create', "productID=$story->product&branch=$story->branch&moduleID=$story->module&{$params}&executionID=$executionID&bugID=0&planID=0&todoID=0&extra=&storyType=$story->type&chproject={$this->session->chproject}", $story, $type, 'copy', '', '', '', "data-width='1050'");
    $menu .= $this->buildMenu('story', 'delete', $params . "&confirm=no&from=&storyType=$story->type", $story, 'button', 'trash', 'hiddenwin', 'showinonlybody');

    return $menu;
}
