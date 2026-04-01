<?php
$lang->business->confirmClose  = '您确定要执行关闭操作吗？';
$lang->business->confirmCancel = '您确定要执行取消操作吗？';
$lang->business->prdReview     = 'PRD评审确认';


$lang->business->action = new stdclass();
$lang->business->action->changeportionprd     = '$date，状态发生变更，变更为“PRD部分通过”';
$lang->business->action->changebeonline       = '$date，状态发生变更，变更为“已上线”';
$lang->business->action->changebusinessstatus = '$date，状态发生变更';
$lang->business->action->changedeclined       = '$date，项目管理已关闭，状态发生变更';
$lang->business->action->syncdatebystory      = '$date，史诗的日期同步到业务需求';
$lang->business->action->changebusinessdate   = '$date，通过接口更新业务日期';
$lang->business->action->evaluationfeedback   = '$date，<strong>$actor</strong> 意见反馈通过。';
$lang->business->action->projectsubmit1       = '$date，<strong>$actor</strong> 发起初审。';
$lang->business->action->projectsubmit2       = '$date，<strong>$actor</strong> 发起复审。';
$lang->business->action->projectsubmit3       = '$date，<strong>$actor</strong> 发起变更。';
$lang->business->action->projectcancel1       = '$date，<strong>$actor</strong> 撤回初审。';
$lang->business->action->projectcancel2       = '$date，<strong>$actor</strong> 撤回复审。';
$lang->business->action->projectcancel3       = '$date，<strong>$actor</strong> 撤回变更。';
$lang->business->action->projectreviewreject1 = '$date，<strong>$actor</strong> 初审待调整。';
$lang->business->action->projectreviewreject2 = '$date，<strong>$actor</strong> 复审待调整。';
$lang->business->action->projectreviewreject3 = '$date，<strong>$actor</strong> 变更不通过。';
$lang->business->action->projectreview2       = '$date，<strong>$actor</strong> 复审通过。';
$lang->business->action->projectreview3       = '$date，<strong>$actor</strong> 变更通过。';
$lang->business->action->projectreview4       = '$date，<strong>$actor</strong> 项目管理取消评审通过。';
$lang->business->action->projectapproval      = '$date，<strong>$actor</strong> 项目管理操作。';
$lang->business->action->projectedit          = '$date，<strong>$actor</strong> 项目管理编辑。';

$lang->business->actionList = array();
$lang->business->actionList['approvalreview1'] = '审批中';
$lang->business->actionList['approvalreview2'] = '变更审批中';
$lang->business->actionList['prdreview']    = 'prd评审中';

$lang->business->disableAfterClickList = array();
$lang->business->disableAfterClickList['approvalcancel1'] = '撤回';
$lang->business->disableAfterClickList['approvalcancel2'] = '撤回变更';