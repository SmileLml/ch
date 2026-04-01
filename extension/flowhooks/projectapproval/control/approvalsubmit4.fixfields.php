<?php
$this->session->set('isApprovalsubmit4', true);

$data->changeType    = '';
$data->changeReason  = '';
$data->changeContent = '';

$this->dao->delete()->from('zt_copyflow_business')->where('project')->eq($data->id)->andWhere('operator')->eq($this->app->user->account)->exec();

if(!empty($this->session->changeBusiness)) $this->session->set('changeBusiness', '');

$allCostBudget = $this->dao->select('IFNULL(sum(costBudget), 0) as costBudget')->from('zt_flow_projectcost')
    ->where('parent')->eq($dataID)
    ->andWhere('costType')->eq('itPlanInto')
    ->andWhere('deleted')->eq(0)
    ->fetch('costBudget');

setcookie('projectCostBudget', $allCostBudget, 0, $this->config->webRoot);