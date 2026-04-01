<?php
$lang->project->business            = '业务需求列表';
$lang->project->product             = '所属产品';
$lang->project->selectProduct       = '选择产品';
$lang->project->splitEpic           = '拆分史诗';
$lang->project->prdsubmit           = '发起PRD评审';
$lang->project->prdcancel           = '撤销PRD评审';
$lang->project->confirmMessage      = '您确认要执行${title}操作吗?';
$lang->project->requirement         = '关联的产品需求/史诗';
$lang->project->estimate            = '剩余拆分人天';
$lang->project->cannotBeZero        = '『%s』需要大于0。';
$lang->project->acceptanceDateError = "计划验收日期『%s』应小于等于计划完成日期『%s』。";

$lang->project->requiredFieldList = array();
$lang->project->requiredFieldList['costBudget']     = '成本预算';
$lang->project->requiredFieldList['costDept']       = '成本部门';
$lang->project->requiredFieldList['costDesc']       = '成本描述';
$lang->project->requiredFieldList['costType']       = '成本类型';
$lang->project->requiredFieldList['costUnit']       = '成本单价';
$lang->project->requiredFieldList['account']        = '姓名';
$lang->project->requiredFieldList['projectRole']    = '项目角色';
$lang->project->requiredFieldList['description']    = '职责描述';
$lang->project->requiredFieldList['PRDdate']        = '计划PRD完成日期';
$lang->project->requiredFieldList['acceptanceDate'] = '计划验收日期';
$lang->project->requiredFieldList['goLiveDate']     = '计划上线日期';


$lang->project->action->changewaitstatus  = array('main' => '$date, 由 <strong>$actor</strong> 切换任务的所属项目或所属执行后，更新状态为未开始。');
$lang->project->action->changedoingstatus = array('main' => '$date, 由 <strong>$actor</strong> 切换任务的所属项目或所属执行后，更新状态为进行中。');