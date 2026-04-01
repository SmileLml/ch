<?php
$lang->approvalflow->warningList['selectGroupMembers'] = '请选择权限分组成员';

$lang->approvalflow->reviewerTypeList['groupMember']        = array('name' => '权限分组成员', 'options' => 'groupMembers',        'tips' => '选择权限分组成员');
$lang->approvalflow->reviewerTypeList['permissionGrouping'] = array('name' => '权限分组'    , 'options' => 'permissionGroupings', 'tips' => '选择权限分组');

$lang->approvalflow->groupMemberList = array();
$lang->approvalflow->groupMemberList['businessManager']   = '业务项目经理';
$lang->approvalflow->groupMemberList['businessArchitect'] = '业务架构师';
$lang->approvalflow->groupMemberList['PMO']               = 'PMO';
$lang->approvalflow->groupMemberList['itPM']              = 'IT项目群经理';
$lang->approvalflow->groupMemberList['businessPM']        = '业务项目群经理';
$lang->approvalflow->groupMemberList['foundingMember']    = '发起人';
$lang->approvalflow->groupMemberList['productManager']    = '产品经理';

$lang->approvalflow->permissionGroupingList = [];
$lang->approvalflow->permissionGroupingList['PMO']       = 'PMO';
$lang->approvalflow->permissionGroupingList['architect'] = '架构师';

$lang->approvalflow->noticeTypeList['onlyByType'] = '仅通过';
$lang->approvalflow->noticeTypeList['onlyByYes']  = '是';
$lang->approvalflow->noticeTypeList['onlyByNo']   = '否';

$lang->approvalflow->warningList['oneGroupMember'] = '只能有一个类型是 "发起人自选","权限分组"或者"权限分组成员"';

$lang->approvalflow->noticeTypeList['needReviewer']    = '审批人是否必填';
$lang->approvalflow->noticeTypeList['needReviewerYes'] = '是';
$lang->approvalflow->noticeTypeList['needReviewerNo']  = '否';

$lang->approvalflow->errorList['nodeNeedReviewer'] = '『%s』节点的审批人不能为空！';

$lang->approvalflow->systemUser = '系统成员';