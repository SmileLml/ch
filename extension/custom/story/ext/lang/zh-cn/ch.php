<?php
$lang->story->affectedStory               = '影响的' . $lang->story->common;
$lang->story->affectedName                = $lang->story->common . '名称';
$lang->story->affectedTask                = '影响的任务';
$lang->story->execution                   = '所属执行';
$lang->story->business                    = '所属业务需求';
$lang->story->project                     = '所属项目';
$lang->story->residueEstimate             = '剩余拆分人天';
$lang->story->unlinkStoryFrom             = "与执行: %s 解除关联";
$lang->story->day                         = "天";
$lang->story->businessError               = "业务需求的剩余人天不足。";
$lang->story->requirementError            = "产品需求/史诗的剩余人天不足";
$lang->story->planonlinedateover          = "用户需求的计划上线日期，不得超过 业务需求的计划上线日期 。";
$lang->story->projectRequire              = "所属项目不能为空。";
$lang->story->batchcreateNotDraft         = "史诗prd评审过程中 和 通过后,不可进行细分";
$lang->story->submitprdreview             = '提交了PRD评审';
$lang->story->submitbusinessreview        = '提交了业务确认评审';
$lang->story->batchEditProjectapprovalTip = '%s关联项目管理已取消或关闭,将自动忽略';
$lang->story->taskEstimateExceedError     = '故事预计不能小于关联任务预计之和';
$lang->story->reviewDuplicate             = '请勿重复评审!';
$lang->story->confirmUnlink               = '您确认要取消关联吗？';

$lang->story->statusList['']              = '';
$lang->story->statusList['draft']         = '草稿';
$lang->story->statusList['reviewing']     = '评审中';
$lang->story->statusList['PRDReviewing']  = 'PRD评审中';
$lang->story->statusList['PRDReviewed']   = 'PRD评审通过';
$lang->story->statusList['confirming']    = '业务确认中';
$lang->story->statusList['active']        = '激活';
$lang->story->statusList['closed']        = '已验收';
$lang->story->statusList['changing']      = '变更中';
$lang->story->statusList['devInProgress'] = '开发进行中';
$lang->story->statusList['beOnline']      = '已上线';
$lang->story->statusList['cancelled']     = '已取消';

$lang->story->beOnlineStatusList['closed']        = '已验收';
$lang->story->beOnlineStatusList['beOnline']      = '已上线';

$lang->story->action->submitprdreview      = '$date，由<strong>$actor</strong> 提交PRD评审。';
$lang->story->action->submitbusinessreview = '$date，由<strong>$actor</strong> 提交业务确认评审。';
$lang->story->action->changedevinprogress  = '$date，状态发生变更，变更为“开发进行中”';
$lang->story->action->changebeonline       = '$date，状态发生变更，变更为“已上线”';

$lang->story->openMessageTemplate = "您有待审批的[史诗]，请前往%s 进行确认。编号：%s ，主题： %s ，创建人：%s";

foreach($lang->story->statusList as $status => $statusName)
{
    $changeStatus = 'changestatus' . strtolower($status);
    $lang->story->action->$changeStatus = '$date，状态发生变更，变更为“' . $statusName . '”';
}

foreach($lang->story->stageList as $stage => $stageName)
{
    $changeStage = 'changestage' . strtolower($stage);
    $lang->story->action->$changeStage = '$date，阶段发生变更，变更为“' . $stageName . '”';
}

$lang->story->relatedRequirement = '所属史诗';
$lang->story->actualConsumed     = '实际消耗人天';
