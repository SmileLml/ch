<?php
$lang->custom->object['demand'] = '需求池';

$lang->custom->menuOrder[22] = 'demand';

$lang->custom->demand = new stdClass();
$lang->custom->demand->fields['priList']      = '优先级';
$lang->custom->demand->fields['categoryList'] = '类别';
$lang->custom->demand->fields['severityList'] = '严重程度';
$lang->custom->demand->fields['sourceList']   = '需求来源';

$lang->custom->projectrole                        = new stdclass();
$lang->custom->projectrole->fields['rolelist']    = '项目角色';
$lang->custom->projectrole->fields['unitprice']   = '单价';
$lang->custom->projectrole->fields['projectcost'] = '项目成本';

$lang->custom->story = new stdClass();
$lang->custom->story->fields['required']     = $lang->custom->required;
$lang->custom->story->fields['categoryList'] = '类型';
$lang->custom->story->fields['priList']      = '优先级';
$lang->custom->story->fields['sourceList']   = '来源';
$lang->custom->story->fields['reasonList']   = '关闭原因';
$lang->custom->story->fields['stageList']    = '阶段';
$lang->custom->story->fields['statusList']   = '状态';
