<?php
/**
 * Get project pairs by product.
 *
 * @param  int    product
 * @param  int    branch
 * @access public
 * @return array
 */
public function getPairsByProduct($productID, $branch)
{
    $projectIdList = $this->dao->select('project')->from('zt_projectproduct')->where
    ('product')->eq($productID)->andWhere('branch')->eq($branch)->fetchPairs('project');

    return $this->dao->select('id, name')->from(TABLE_PROJECT)
        ->where('type')->eq('project')
        ->andWhere('deleted')->eq(0)
        ->andWhere('model')->ne('kanban')
        ->andWhere('id')->in($projectIdList)
        ->fetchPairs('id', 'name');;
}

/**
 * Judge an action is clickable or not.
 *
 * @param  object    $project
 * @param  string    $action
 * @access public
 * @return bool
 */
public static function isClickable($project, $action)
{
    $action = strtolower($action);

    if(empty($project)) return true;
    if(!isset($project->type)) return true;

    if($action == 'start')    return $project->status == 'wait' or $project->status == 'suspended';
    if($action == 'finish')   return $project->status == 'wait' or $project->status == 'doing';
    if($action == 'close')    return $project->status != 'closed' and $project->projectType == '1';
    if($action == 'suspend')  return $project->status == 'wait' or $project->status == 'doing';
    if($action == 'activate') return $project->status == 'done' or $project->status == 'closed';
    if($action == 'whitelist') return $project->acl != 'open';
    if($action == 'group') return $project->model != 'kanban';

    return true;
}

/**
 * Process business.
 *
 * @param array $dataList
 */
public function processBusiness($dataList)
{
    foreach($dataList as $key => $data)
    {
        $dataList[$key] = $this->processBusinessData($data);
    }

    return $dataList;
}

/**
 * Process business data.
 * @param object $dataList
 * @param string $type
 */
public function processBusinessData($data, $type = 'browse')
{
    $requirements = $this->dao->select('id,title,estimate')->from(TABLE_STORY)->where('business')->eq($data->id)->andWhere('type')->eq('requirement')->andWhere('deleted')->eq(0)->fetchAll();

    if($type == 'view') $projectID = $this->dao->select('project')->from('zt_flow_projectbusiness')->where('business')->eq($data->id)->andWhere('deleted')->eq(0)->fetch('project');

    $data->requirement = '';
    $data->estimate    = $data->developmentBudget;

    foreach($requirements as $requirement)
    {
        if($type == 'businessview')
        {
            $data->requirement .= common::hasPriv('story', 'view') ? html::a(helper::createLink('story', 'view', "storyID=$requirement->id&version=0&param=&storyType=requirement"), $requirement->title, '', "title='{$requirement->title}' data-app='product'") . ',' : $requirement->title . ',';
        }
        else
        {
            $data->requirement .= $type == 'browse' ?  $requirement->title . ',' : html::a(helper::createLink('projectstory', 'view', 'storyID=' . $requirement->id . '&project=' . $projectID), $requirement->title, '', "title='{$requirement->title}' data-app='project'") . ',';
        }
        $data->estimate     = bcsub($data->estimate, $requirement->estimate, '2');
    }

    $data->requirement = trim($data->requirement, ',');

    return $data;
}

public function getStoryIdAndName()
{
    return $this->dao->select("t1.story, GROUP_CONCAT(DISTINCT t2.name ORDER BY t2.name ASC SEPARATOR ', ') AS projectNames")->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin("(SELECT id, name,deleted FROM zt_project WHERE `type` = 'project')")->alias('t2')->on('t1.project = t2.id')
        ->where('t2.deleted')->eq('0')
        ->groupBy('t1.story')
        ->fetchAll();
}

/**
 * Get project by instance
 *
 * @param  int    $instance
 * @access public
 * @return mixed
 */
public function getByInstance($instance)
{
    return $this->dao->select("*")->from('zt_project')
        ->where('instance')->eq($instance)
        ->fetch();
}

/**
 * Get dataList.
 *
 * @param  int    $flow
 * @param  string $mode
 * @param  int    $label
 * @param  string $categoryQuery
 * @param  int    $parentID
 * @param  string $orderBy
 * @param  int    $pager
 * @param  string $extraQuery
 * @access public
 * @return mixed
 */
public function getDataList($flow, $mode = 'browse', $label = 0, $categoryQuery = '', $parentID = 0, $orderBy = '', $pager = null, $extraQuery = '')
{
    $querySessionName = $flow->module . 'Query';
    if($this->session->$querySessionName == false) $this->session->set($querySessionName, ' 1 = 1');
    $searchQuery = $this->loadModel('search')->replaceDynamic($this->session->$querySessionName);

    $labelQuery = '';
    if($label)
    {
        if($mode == 'bysearch')
        {
            $query = $this->search->getQuery($label);
            $searchQuery  = $query->sql;
            $labelOrderBy = '';
            $this->session->set($flow->module . 'Form', $query->form);
        }
        else
        {
            list($labelQuery, $labelOrderBy) = $this->getLabelQueryAndOrderBy($label);
        }

        if(!$orderBy) $orderBy = $labelOrderBy;
    }

    if(!$orderBy) $orderBy = 'id_desc';

    $productRelatedModules = ",productplan,release,story,build,bug,testcase,testtask,testsuite,feedback,";
    $dataList = $this->dao->select('*')->from($flow->table)
        ->where('deleted')->eq('0')
        ->beginIF(!$flow->buildin && $parentID)->andWhere('project')->eq($parentID)->fi()
        ->beginIF($mode == 'bysearch')->andWhere($searchQuery)->fi()
        ->beginIF($labelQuery)->andWhere($labelQuery)->fi()
        ->beginIF($categoryQuery)->andWhere($categoryQuery)->fi()
        ->beginIF($extraQuery)->andWhere($extraQuery)->fi()
        ->beginIF($flow->module == 'product')->andWhere('id')->in($this->app->user->view->products)->fi()
        ->beginIF($flow->module == 'project')->andWhere('id')->in($this->app->user->view->projects)->fi()
        ->beginIF($flow->module == 'execution')->andWhere('id')->in($this->app->user->view->sprints)->fi()
        ->beginIF($flow->module == 'task')->andWhere('execution')->in($this->app->user->view->sprints)->fi()
        ->beginIF($flow->module == 'caselib')->andWhere('product')->eq('0')->fi()
        ->beginIF(strpos($productRelatedModules, ',' . $flow->module . ',') !== false)->andWhere('product')->in($this->app->user->view->products)->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');

    $this->session->set($flow->module . 'QueryCondition', $this->dao->get());

    foreach($dataList as $data) $data = $this->loadModel('flow')->processDBData($flow->module, $data);

    return $dataList;
}

/**
 * Get business list.
 *
 * @param  int    $projectID
 * @param  string $orderBy
 * @param  int    $pager
 * @access public
 * @return array
 */
public function getBusinessList($projectID, $orderBy, $pager)
{
    $businessIdList = $this->dao->select('business')
        ->from('zt_flow_projectbusiness')
        ->where('project')->eq($projectID)
        ->andWhere('deleted')->eq(0)
        ->fetchPairs();

    $businessList = $this->dao->select('*')->from('zt_flow_business')
        ->where('deleted')->eq('0')
        ->beginIF($projectID)->andWhere('id')->in($businessIdList)->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');

    foreach($businessList as $data) $data = $this->loadModel('flow')->processDBData('business', $data);

    return $businessList;
}

/**
 * Get business pairs.
 *
 * @param  int    $projectID
 * @param  string $orderBy
 * @param  int    $pager
 * @access public
 * @return array
 */
public function getBusinessPairs($projectID, $module = '')
{
    $businessIdList = $this->dao->select('business')
        ->from('zt_flow_projectbusiness')
        ->where('project')->eq($projectID)
        ->andWhere('deleted')->eq(0)
        ->fetchPairs();

    $businessList = $this->dao->select('id, name')->from('zt_flow_business')
        ->where('deleted')->eq('0')
        ->beginIF($projectID)->andWhere('id')->in($businessIdList)->fi()
        ->beginIF($module == 'story')->andWhere('status')->in(array('approvedProject', 'portionPRD'))->fi()
        ->fetchPairs('id');

    return $businessList;
}
