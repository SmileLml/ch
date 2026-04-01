<?php
$childFields = $this->view->childFields;
$childDatas  = $this->view->childDatas;

$oldBusinessIds    = array_column($childDatas['sub_projectbusiness'], 'business');
$oldBusinessList   = $this->dao->select('id,name')->from('zt_flow_business')->where('id')->in($oldBusinessIds)->fetchPairs();

$childFields['sub_projectbusiness']['business']->options += $oldBusinessList;


$businessIdList = array();
foreach($childDatas['sub_projectbusiness'] as $sub_projectbusiness) $businessIdList[] = $sub_projectbusiness->business;
$linkedBusinesses = $this->dao->select('t1.*, t2.id as projectbusinessID')->from('zt_flow_business')->alias('t1')
    ->leftJoin('zt_flow_projectbusiness')->alias('t2')->on('t1.id = t2.business')
    ->where('t1.id')->in($businessIdList)
    ->andWhere('t1.deleted')->eq(0)
    ->andWhere('t2.deleted')->eq(0)
    ->fetchAll('id');

foreach($linkedBusinesses as $linkedBusiness)
{
    $childDatas['sub_projectbusiness'][$linkedBusiness->projectbusinessID]->status = $linkedBusiness->status;

    if($linkedBusiness->status == 'closed' or $linkedBusiness->status == 'cancelled')
    {
        $tempBusiness = $childDatas['sub_projectbusiness'][$linkedBusiness->projectbusinessID];
        unset($childDatas['sub_projectbusiness'][$linkedBusiness->projectbusinessID]);
        $childDatas['sub_projectbusiness'][$linkedBusiness->projectbusinessID] = $tempBusiness;
    }
}

$this->view->childFields = $childFields;
$this->view->childDatas  = $childDatas;
