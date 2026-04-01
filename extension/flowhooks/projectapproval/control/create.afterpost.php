<?php
$productPlans[]['products'] = $this->dao->select('id')->from(TABLE_PRODUCT)->where('name')->eq($this->lang->defaultProductTitle)->fetch('id');

$projectApproval = new stdclass();
$projectApproval->model       = 'agileplus';
$projectApproval->hasproduct  = 1;
$projectApproval->acl         = 'private';
$projectApproval->auth        = 'extend';
$projectApproval->productPlan = json_encode($productPlans);

$this->loadModel('flow');

$this->dao->update('zt_flow_projectapproval')->data($projectApproval)->where('id')->eq($result['recordID'])->exec();
$this->flow->mergeVersionByObjectType($result['recordID'], 'projectapproval');

$projectBusiness = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($result['recordID'])->andWhere('deleted')->eq('0')->fetchPairs('business', 'business');

if($projectBusiness) $this->dao->update('zt_flow_business')->set('status')->eq('projecting')->where('id')->in(array_keys($projectBusiness))->exec();

if($result['result'] == 'success') $this->flow->createVersionByObjectType($result['recordID'], 'projectapproval');
