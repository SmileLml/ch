<?php
$currentData    = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($result['recordID'])->fetch();
$newBusinessID  = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($result['recordID'])->andWhere('deleted')->eq('0')->fetchPairs('business');
$diffBusinessID = array_filter($oldBusiness) ? array_diff($oldBusiness, $newBusinessID) : [];

$this->loadModel('flow');
$this->loadModel('action');
if($diffBusinessID)
{
    $this->dao->update('zt_flow_business')->set('status')->eq('activate')->where('id')->in($diffBusinessID)->exec();

    foreach($diffBusinessID as $tempBusinessID)
    {
        if($currentData->status != 'pendingReview') continue;
        $actionID = $this->action->create('business', $tempBusinessID, 'projectedit');
        $change = array();
        $change[] = array('field' => 'status', 'old' => 'projecting', 'new' => 'activate');
        $this->action->logHistory($actionID, $change);
    }


    foreach($diffBusinessID as $businessID) $this->flow->mergeVersionByObjectType($businessID, 'business');
}

if($newBusinessID)
{
    $newBusinessList = $this->dao->select('id,status')->from('zt_flow_business')->where('id')->in($newBusinessID)->fetchPairs('id');
    $this->dao->update('zt_flow_business')->set('status')->eq('projecting')->where('id')->in($newBusinessID)->exec();
    foreach($newBusinessID as $tempBusinessID)
    {
        if($currentData->status != 'pendingReview') continue;
        $actionID = $this->action->create('business', $tempBusinessID, 'projectedit');
        $change = array();
        $change[] = array('field' => 'status', 'old' => $newBusinessList[$tempBusinessID], 'new' => 'projecting');
        $this->action->logHistory($actionID, $change);
    }
}