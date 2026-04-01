<?php
$this->loadModel('projectapproval');
$demand = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($dataID)->fetch('demand');

$projectapprovalID = $this->dao->select('parent')->from('zt_flow_projectbusiness')->where('business')->eq($dataID)->andWhere('deleted')->eq(0)->fetch('parent');
if($projectapprovalID)
{
    $projectBusinessIds      = $this->dao->select('id, business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq(0)->fetchPairs('id');
    $noCloseOrCancelBusiness = $this->dao->select('*')->from('zt_flow_business')->where('id')->in($projectBusinessIds)->andWhere('status')->notin(array('closed', 'cancelled'))->fetchAll();
    if(empty($noCloseOrCancelBusiness)) 
    {
        $projectapproval = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($projectapprovalID)->fetch();
        $this->loadModel('mail')->send($projectapproval->businessPM, sprintf($this->lang->projectapproval->finishMailSubject, $projectapproval->name), sprintf($this->lang->projectapproval->finishMailContent, $projectapproval->name, html::a(rtrim(common::getSysURL(), '/') .helper::createLink('projectapproval', 'view', "dataID={$projectapproval->id}"), $projectapproval->name)), "", true);
    }
}

if($demand)
{
    $this->dao->update(TABLE_DEMAND)->set('status')->eq('closed')->where('id')->in($demand)->exec();
    foreach(explode(',', $demand) as $demandID) $this->loadModel('action')->create('demand', $demandID, 'closedByBusiness', '', $dataID);
}
