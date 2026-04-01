<?php
$lang->monitoring = new stdClass();
$lang->monitoring->common                 = '过程监控';
$lang->monitoring->projectNumber          = '项目编号';
$lang->monitoring->projectName            = '项目名称';
$lang->monitoring->projectpri             = '项目级别';
$lang->monitoring->responsibleDept        = '业务部门';
$lang->monitoring->businessPM             = '业务项目经理';
$lang->monitoring->businessDept           = '项目主责事业处';
$lang->monitoring->itPM                   = 'IT项目群经理';
$lang->monitoring->productManager         = '产品经理';
$lang->monitoring->itDevM                 = 'IT研发经理';
$lang->monitoring->beginDate              = '计划立项时间';
$lang->monitoring->projectReviewDate      = '实际立项时间';
$lang->monitoring->businessID             = '需求编号';
$lang->monitoring->businessTitle          = '需求主题';
$lang->monitoring->businessStatus         = '需求状态';
$lang->monitoring->PRDdate                = '计划PRD时间';
$lang->monitoring->PRDconfirmDate         = 'PRD确认时间';
$lang->monitoring->PRDwarning             = 'PRD逾期预警';
$lang->monitoring->goLiveDate             = '计划上线时间';
$lang->monitoring->goLiveConfirmDate      = '上线确认时间';
$lang->monitoring->goLiveWarning          = '上线逾期预警';
$lang->monitoring->acceptanceDate         = '计划验收时间';
$lang->monitoring->acceptanceConfirmDate  = '验收确认时间';
$lang->monitoring->acceptanceWarning      = '验收逾期预警';
$lang->monitoring->terminationDate        = '计划结项时间';
$lang->monitoring->terminationConfirmDate = '结项确认时间';
$lang->monitoring->terminationWarning     = '结项逾期预警';
$lang->monitoring->projectMonitoring      = '项目过程监控';
$lang->monitoring->completed              = '已完成';
$lang->monitoring->deferredType           = '延期类型';
$lang->monitoring->PRDWarning             = 'PRD逾期预警';
$lang->monitoring->goLiveWarning          = '上线逾期预警';
$lang->monitoring->acceptanceWarning      = '验收逾期预警';
$lang->monitoring->terminationWarning     = '项目结项逾期预警';
$lang->monitoring->browse                 = '列表';
$lang->monitoring->export                 = '导出';

$lang->monitoring->deferredTypeList = [];

$lang->monitoring->deferredTypeList['PRD']         = 'PRD逾期';
$lang->monitoring->deferredTypeList['goLive']      = '上线逾期';
$lang->monitoring->deferredTypeList['acceptance']  = '验收逾期';
$lang->monitoring->deferredTypeList['termination'] = '结项逾期';

$lang->monitoring->overdueReminder = [];

$lang->monitoring->overdueReminder['PRD']         = '【PRD逾期预警】[项目管理]%s 的 [业务需求] %s 已产生PRD逾期，请及时进行处理。';
$lang->monitoring->overdueReminder['goLive']      = '【上线逾期预警】[项目管理]%s 的 [业务需求] %s 已产生上线逾期，请及时进行处理。';
$lang->monitoring->overdueReminder['acceptance']  = '【验收逾期预警】[项目管理]%s 的 [业务需求] %s 已产生验收逾期，请及时进行处理。';
$lang->monitoring->overdueReminder['termination'] = '【项目结项逾期预警】[项目管理]%s 已逾期 %s 天，请及时进行处理。';
