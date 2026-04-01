<?php
$this->view->version = $data->version;

$businessstakeholders = $this->dao->select('*')->from('zt_flow_businessstakeholder')->where('zt_flow_businessstakeholder.parent')->eq($data->id)->andWhere('stakeholder')->ne('')->fetchAll();
$depts                = $this->loadModel('dept')->getOptionMenuByGrade(0, 3);

if(isset($_COOKIE['businessVersion']) && !empty($_COOKIE['businessVersion']))
{
    $version = $_COOKIE['businessVersion'];

    setcookie('businessVersion', '');

    $object  = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($data->id)->andWhere('objectType')->eq('business')->andWhere('version')->eq($version)->fetch('element');

    if($object)
    {
        $object   = json_decode($object);
        $children = $object->children;
        unset($object->children);
        unset($object->uid);
        unset($object->version);
        unset($object->status);
        unset($object->reviewStatus);
        unset($object->reviewers);

        foreach($object as $key => $value) if(property_exists($data, $key)) $data->$key = $value;

        $businessstakeholders = $children->sub_businessstakeholder;
    }

    $this->view->version = $version;
}

$userAccount     = $this->app->user->account;
$architect       = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->architect);
$PMO             = $this->loadModel('user')->getUsersByUserGroupName('PMO');
$seniorExecutive = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->seniorExecutive);

$infoAttache     = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoAttache);
$infoLeqader     = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoLeqader);

$isProjectapprovalView = false;
$projectapproval     = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($data->project)->fetch();

if(isset($architect[$userAccount]) || isset($PMO[$userAccount]) || isset($seniorExecutive[$userAccount])  || $this->app->user->admin)
{
    $isProjectapprovalView = true;
}

if(isset($infoLeqader[$userAccount]) || isset($infoAttache[$userAccount]))
{
    $allDept = $this->loadModel('dept')->getAllChildId($this->app->user->dept);
    if(empty($allDept))
    {
        $isProjectapprovalView = true;
    }
    else
    {
        if(in_array($projectapproval->responsibleDept, $allDept)) $isProjectapprovalView = true;
    }
}

$projectmembers = $this->dao->select('*')->from('zt_flow_projectmembers')->where('account')->eq($userAccount)->andWhere('parent')->eq($data->project)->fetch();
if($projectmembers) $isProjectapprovalView = true;

if($isProjectapprovalView && $data->project)
{
    $fields['project']->control = 'input';
    $data->project = html::a($this->createlink('projectapproval', 'view', "dataID=$data->project", $projectapproval->name), $projectapproval->name);
}

$linkedDemand = $this->dao->select("zt_demand.id,CONCAT(zt_demandpool.name, '-', zt_demand.name) as name")
    ->from('zt_demand')
    ->leftJoin('zt_demandpool')->on('zt_demand.pool=zt_demandpool.id')
    ->where('zt_demand.id')->in($data->demand)
    ->fetchPairs('id', 'name');

$fields['demand']->options += $linkedDemand;

$stakeholdersHtml = '';
if(!empty($businessstakeholders))
{
    $stakeholdersHtml .= '<div class="panel panel-block">';
    $stakeholdersHtml .= '<div class="panel-heading">';
    $stakeholdersHtml .= "<strong>{$this->lang->flow->stakeholderInfo}</strong>";
    $stakeholdersHtml .= '</div>';
    $stakeholdersHtml .= '<div class="panel-body scroll">';
    foreach($businessstakeholders as $businessstakeholder)
    {
        $deptName = zget($depts, $businessstakeholder->dept, '');
        $stakeholdersHtml .= "<p><strong>{$this->lang->flow->stakeholder}($deptName)</strong>:{$businessstakeholder->stakeholder}</p>";
    }
    $stakeholdersHtml .= '</div>';
    $stakeholdersHtml .= '</div>';
}

$this->view->stakeholdersHtml = $stakeholdersHtml;

$architect = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->architect);

if($this->app->user->admin || in_array($this->app->user->account, array_keys($architect)))
{
    $fields['project']->options = $this->dao->select('id, name')->from('zt_flow_projectapproval')
        ->where('deleted')->eq('0')
        ->andWhere('status')->eq('approvedProject')
        ->orderBy('id_desc')
        ->fetchPairs('id', 'name');
}
if($data->project)
{
    $fields['project']->options += $this->dao->select('id, name')->from('zt_flow_projectapproval')
        ->where('id')->eq($data->project)
        ->fetchPairs('id', 'name');
}
$this->loadModel('business');
