<?php
$requirements = $this->dao->select('id,status')->from('zt_story')->where('business')->eq($dataID)->andWhere('deleted')->eq(0)->fetchAll();

$isBeOnline = true;
foreach($requirements as $requirement)
{
    if(!in_array($requirement->status, array('beOnline', 'closed'))) $isBeOnline = false;
}
if($isBeOnline)
{
    $business = $this->dao->select('*')->from('zt_flow_business')->where('id')->eq($dataID)->fetch();

    if($business->status != 'beOnline')
    {
        $this->dao->update('zt_flow_business')->set('status')->eq('beOnline')->set('realGoLiveDate')->eq(helper::now())->where('id')->eq($dataID)->exec();
        $this->loadModel('flow')->mergeVersionByObjectType($dataID, 'business');
        $actionID = $this->loadModel('action')->create('business', $dataID, 'changebeonline');
        $result['changes']   = array();
        $result['changes'][] = ['field' => 'status', 'old' => $business->status, 'new' => 'beOnline'];
        $this->loadModel('action')->logHistory($actionID, $result['changes']);
    }
}

$projectapprovalID     = $this->dao->select('parent')->from('zt_flow_projectbusiness')->where('business')->eq($dataID)->andWhere('deleted')->eq(0)->fetch('parent');
$projectapprovalStatus = $this->dao->select('status')->from('zt_flow_projectapproval')->where('id')->eq($projectapprovalID)->fetch('status');
$noChangeStatus        = array('cancelled', 'finished', 'cancelReview', 'finishReview', 'devTest', 'closure', 'changeReview');

if(!in_array($projectapprovalStatus, $noChangeStatus))
{
    $this->dao->update('zt_flow_projectapproval')->set('status')->eq('devTest')->where('id')->eq($projectapprovalID)->exec();
    $this->loadModel('flow')->mergeVersionByObjectType($projectapprovalID, 'projectapproval');
    $actionID = $this->loadModel('action')->create('projectapproval', $projectapprovalID, 'changedevtest');
    $result['changes']   = array();
    $result['changes'][] = ['field' => 'status', 'old' => $projectapprovalStatus, 'new' => 'devTest'];
    $this->loadModel('action')->logHistory($actionID, $result['changes']);
}


if($isBeOnline)
{
    $businessIdList     = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq(0)->fetchPairs('business', 'business');
    $businessStatusList = $this->dao->select('status')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll('status');

    $isClosure = true;
    foreach($businessStatusList as $businessStatus)
    {
        if($businessStatus->status != 'beOnline' and $businessStatus->status != 'closed' and $businessStatus->status != 'cancelled') $isClosure = false;
    }

    $noChangeStatus        = array('cancelled', 'finished', 'cancelReview', 'finishReview', 'closure', 'changeReview');
    $projectapprovalStatus = $this->dao->select('status')->from('zt_flow_projectapproval')->where('id')->eq($projectapprovalID)->fetch('status');

    if($isClosure && !in_array($projectapprovalStatus, $noChangeStatus))
    {
        $this->dao->update('zt_flow_projectapproval')->set('status')->eq('closure')->where('id')->eq($projectapprovalID)->exec();
        $actionID = $this->loadModel('action')->create('projectapproval', $projectapprovalID, 'changeclosure');
        $result['changes']   = array();
        $result['changes'][] = ['field' => 'status', 'old' => $projectapprovalStatus, 'new' => 'closure'];
        $this->loadModel('action')->logHistory($actionID, $result['changes']);
    }
}
