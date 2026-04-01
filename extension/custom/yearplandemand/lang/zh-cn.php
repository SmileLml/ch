<?php
$lang->yearplandemand->common         = '年度计划需求';
$lang->yearplandemand->create         = '创建年度计划需求';
$lang->yearplandemand->browse         = '年度计划需求列表';
$lang->yearplandemand->edit           = '编辑年度计划需求';
$lang->yearplandemand->view           = '年度计划需求详情';
$lang->yearplandemand->delete         = '删除年度计划需求';
$lang->yearplandemand->confirm        = '确认';
$lang->yearplandemand->export         = '导出';
$lang->yearplandemand->integration    = '合并';
$lang->yearplandemand->cancel         = '取消';
$lang->yearplandemand->restore        = '还原';
$lang->yearplandemand->sendback       = '退回';
$lang->yearplandemand->exportTemplate = '导出模板';
$lang->yearplandemand->submit         = '提交';
$lang->yearplandemand->update         = '修改';
$lang->yearplandemand->import         = '导入';
$lang->yearplandemand->showImport     = '从模板导入';

$lang->yearplandemand->infoManger    = '信息管理部';
$lang->yearplandemand->infoTechnical = '信息技术部';
$lang->yearplandemand->chteamdept    = '春秋航空';

$lang->yearplandemand->confirmDelete   = '您确认删除该年度计划需求吗？';
$lang->yearplandemand->confirmCancel   = '您确认取消该年度计划需求吗？';
$lang->yearplandemand->confirmRestore  = '您确认还原该年度计划需求吗？';
$lang->yearplandemand->confirmSendback = '您确认退回该年度计划需求吗？';
$lang->yearplandemand->confirmSubmit   = '您确认提交该年度计划需求吗？';

$lang->yearplandemand->integrationCountError  = '需求数量不可少于2条';
$lang->yearplandemand->integrationStatusError = '【%s】需求，属于不可合并状态';

$lang->yearplandemand->requiredError             = '【%s】为空，请补充信息后%s。';
$lang->yearplandemand->dateError                 = '需求【%s】需早于【%s】，请修改信息后%s。';
$lang->yearplandemand->milestoneError            = '里程碑【%s】需早于【%s】，请修改信息后%s。';
$lang->yearplandemand->milestoneConfirmDateError = '里程碑【计划方案确认时间】不可超出需求【计划立项时间 】、【计划方案确认时间】区间，请修改信息后%s。';
$lang->yearplandemand->milestoneGoLiveDateError  = '里程碑【计划上线时间】不可超出需求【计划立项时间】、【计划上线时间】区间，请修改信息后%s。';
$lang->yearplandemand->milestoneRequired         = '请补充完整里程碑信息。';

$lang->yearplandemand->id                 = '编号';
$lang->yearplandemand->name               = '项目名称';
$lang->yearplandemand->desc               = '需求内容';
$lang->yearplandemand->level              = '项目级别';
$lang->yearplandemand->category           = '三化分类';
$lang->yearplandemand->initDept           = '业务负责部门';
$lang->yearplandemand->dept               = '干系部门';
$lang->yearplandemand->approvalDate       = '计划立项时间';
$lang->yearplandemand->planConfirmDate    = '计划方案确认时间';
$lang->yearplandemand->goliveDate         = '计划上线时间';
$lang->yearplandemand->itPlanInto         = 'IT计划投入研发人天';
$lang->yearplandemand->itPlanIntoSimplify = '研发人天';
$lang->yearplandemand->itPM               = 'IT项目群经理';
$lang->yearplandemand->businessArchitect  = '业务架构师';
$lang->yearplandemand->businessManager    = '业务项目群经理';
$lang->yearplandemand->isPurchased        = '是否涉及外购软件';
$lang->yearplandemand->purchasedContents  = '涉及外购内容';
$lang->yearplandemand->status             = '状态';
$lang->yearplandemand->createdBy          = '创建人';
$lang->yearplandemand->createdDate        = '创建时间';
$lang->yearplandemand->milestone          = '年度计划里程碑';
$lang->yearplandemand->basicInfo          = '基本信息';
$lang->yearplandemand->mergeTo            = '合并到';
$lang->yearplandemand->mergeSources       = '合并需求';
$lang->yearplandemand->confirmResult      = '确认结论';
$lang->yearplandemand->confirmComment     = '确认意见';
$lang->yearplandemand->importNotice       = '请先导出模板，按照模板格式填写数据后再导入。';
$lang->yearplandemand->noRequire          = '%s行的“%s”是必填字段，不能为空';


$lang->yearplandemand->milestoneFields = array();
$lang->yearplandemand->milestoneFields['batch']           = '批次';
$lang->yearplandemand->milestoneFields['name']            = '批次名称';
$lang->yearplandemand->milestoneFields['planConfirmDate'] = '计划方案确认时间';
$lang->yearplandemand->milestoneFields['goliveDate']      = '计划上线时间';

$lang->yearplandemand->confirmResultList['']     = '';
$lang->yearplandemand->confirmResultList['pass'] = '通过';
$lang->yearplandemand->confirmResultList['back'] = '退回';

$lang->yearplandemand->levelList[0] = '';
$lang->yearplandemand->levelList[1] = '公司重点项目';
$lang->yearplandemand->levelList[2] = '部门重点项目';
$lang->yearplandemand->levelList[3] = '临时项目';

$lang->yearplandemand->categoryList[0] = '';
$lang->yearplandemand->categoryList[1] = '信息化';
$lang->yearplandemand->categoryList[2] = '数字化';
$lang->yearplandemand->categoryList[3] = '智能化';

$lang->yearplandemand->labelList = array();
$lang->yearplandemand->labelList['bydept']        = '本部门';
$lang->yearplandemand->labelList['all']           = '所有';
$lang->yearplandemand->labelList['draft']         = '草稿';
$lang->yearplandemand->labelList['merged']        = '已合并';
$lang->yearplandemand->labelList['confirmed']     = '已确认';
$lang->yearplandemand->labelList['tobeevaluated'] = '待评估';

$lang->yearplandemand->statusList = array();
$lang->yearplandemand->statusList['']              = '';
$lang->yearplandemand->statusList['draft']         = '草稿';
$lang->yearplandemand->statusList['merged']        = '已合并';
$lang->yearplandemand->statusList['confirmed']     = '已确认';
$lang->yearplandemand->statusList['cancelled']     = '已取消';
$lang->yearplandemand->statusList['tobeevaluated'] = '待评估';

$lang->yearplandemand->isPurchasedList = array();
$lang->yearplandemand->isPurchasedList[0] = '否';
$lang->yearplandemand->isPurchasedList[1] = '是';

$lang->yearplandemand->action = new stdClass();
$lang->yearplandemand->action->confirm  = array('main' => '$date, 由 <strong>$actor</strong> 确认。');
$lang->yearplandemand->action->cancel   = array('main' => '$date, 由 <strong>$actor</strong> 取消。');
$lang->yearplandemand->action->restore  = array('main' => '$date, 由 <strong>$actor</strong> 还原。');
$lang->yearplandemand->action->sendback = array('main' => '$date, 由 <strong>$actor</strong> 退回。');
$lang->yearplandemand->action->submit   = array('main' => '$date, 由 <strong>$actor</strong> 提交。');