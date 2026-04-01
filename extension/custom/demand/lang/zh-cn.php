<?php
$lang->demand->common         = '需求池需求';
$lang->demand->create         = '提交需求';
$lang->demand->batchCreate    = '批量提交需求';
$lang->demand->subdivide      = '拆分需求';
$lang->demand->browse         = '需求列表';
$lang->demand->edit           = '编辑需求';
$lang->demand->view           = '需求详情';
$lang->demand->delete         = '删除需求';
$lang->demand->close          = '关闭需求';
$lang->demand->review         = '评审需求';
$lang->demand->track          = '需求矩阵';
$lang->demand->submit         = '提交评审';
$lang->demand->export         = '导出数据';
$lang->demand->tostory        = '转需求';
$lang->demand->import         = '导入';
$lang->demand->comment        = '备注';
$lang->demand->exportTemplate = '导出模板';
$lang->demand->showImport     = '从模板导入';
$lang->demand->integration    = '整合业务需求';
$lang->demand->business       = '关联的业务需求';

$lang->demand->id                = '编号';
$lang->demand->idAB              = 'ID';
$lang->demand->code              = '序号';
$lang->demand->category          = '需求类别';
$lang->demand->pri               = '优先级';
$lang->demand->priAB             = 'P';
$lang->demand->severityAB        = 'S';
$lang->demand->severity          = '业务重要程度';
$lang->demand->demand            = '所属需求池';
$lang->demand->status            = '状态';
$lang->demand->name              = '需求名称';
$lang->demand->nameAB            = '名称';
$lang->demand->module            = '所属模块';
$lang->demand->union             = '需求单位';
$lang->demand->source            = '需求来源';
$lang->demand->sourceNote        = '来源备注';
$lang->demand->synUnion          = '同步单位';
$lang->demand->date              = '需求提出时间';
$lang->demand->createdBy         = '由谁创建';
$lang->demand->createdDate       = '创建时间';
$lang->demand->contact           = '需求联系人';
$lang->demand->contactInfo       = '联系方式';
$lang->demand->group             = '需求负责小组';
$lang->demand->owner             = '需求负责人';
$lang->demand->deadline          = '期望上线日期';
$lang->demand->desc              = '需求描述';
$lang->demand->num               = '需求记录数';
$lang->demand->new               = '新增';
$lang->demand->assignedTo        = '指派给';
$lang->demand->workload          = '工作量(小时)';
$lang->demand->mailto            = '通知人';
$lang->demand->feedbackBy        = '反馈者';
$lang->demand->email             = '反馈邮箱';
$lang->demand->mobile            = '手机';
$lang->demand->reviewer          = '评审人';
$lang->demand->result            = '评审结果';
$lang->demand->allModule         = '所有模块';
$lang->demand->noModule          = '暂无模块';
$lang->demand->manageTree        = '维护模块';
$lang->demand->manageChild       = '维护子模块';
$lang->demand->chooseType        = '请选择转化对象类型';
$lang->demand->next              = '下一步';
$lang->demand->storyType         = '需求类型';
$lang->demand->to                = '转';
$lang->demand->URS               = "转化的{$lang->URCommon}";
$lang->demand->SRS               = "转化的{$lang->SRCommon}";
$lang->demand->businessDesc      = "业务现状";
$lang->demand->businessObjective = "期望达成的业务效果";
$lang->demand->project           = "归属项目";
$lang->demand->projectApproval   = '归属预立项';
$lang->demand->dept              = "干系部门";
$lang->demand->businessUnit      = "业务单元";
$lang->demand->stage             = "所属阶段";
$lang->demand->demandSource      = "需求来源部门";

$lang->demand->storyTypeList['requirement'] = $lang->URCommon;
$lang->demand->storyTypeList['story']       = $lang->SRCommon;

$lang->demand->ditto = '同上';

$lang->demand->info      = '信息';
$lang->demand->basicInfo = '基本信息';

$lang->demand->importNotice     = '请先导出模板，按照模板格式填写数据后再导入。';
$lang->demand->noRequire        = '%s行的“%s”是必填字段，不能为空';
$lang->demand->integratedDemand = '原始需求已整合';

$lang->demand->priList[''] = '';
$lang->demand->priList[1]  = '1';
$lang->demand->priList[2]  = '2';
$lang->demand->priList[3]  = '3';
$lang->demand->priList[4]  = '4';

$lang->demand->severityList[0] = '';
$lang->demand->severityList[1] = '紧急';
$lang->demand->severityList[2] = '中等';
$lang->demand->severityList[3] = '一般';
$lang->demand->severityList[4] = '不重要';

$lang->demand->categoryList['feature']     = '功能';
$lang->demand->categoryList['interface']   = '接口';
$lang->demand->categoryList['performance'] = '性能';
$lang->demand->categoryList['safe']        = '安全';
$lang->demand->categoryList['experience']  = '体验';
$lang->demand->categoryList['improve']     = '改进';
$lang->demand->categoryList['other']       = '其他';

$lang->demand->sourceList['']           = '';
$lang->demand->sourceList['customer']   = '客户';
$lang->demand->sourceList['user']       = '用户';
$lang->demand->sourceList['po']         = '产品经理';
$lang->demand->sourceList['market']     = '市场';
$lang->demand->sourceList['service']    = '客服';
$lang->demand->sourceList['operation']  = '运营';
$lang->demand->sourceList['support']    = '技术支持';
$lang->demand->sourceList['competitor'] = '竞争对手';
$lang->demand->sourceList['partner']    = '合作伙伴';
$lang->demand->sourceList['dev']        = '开发人员';
$lang->demand->sourceList['tester']     = '测试人员';
$lang->demand->sourceList['bug']        = 'Bug';
$lang->demand->sourceList['other']      = '其他';

$lang->demand->statusList[''] = '';
//$lang->demand->statusList['wait']   = '待评审';
//$lang->demand->statusList['refuse'] = '已驳回';
$lang->demand->statusList['active'] = '激活';
$lang->demand->statusList['closed'] = '已关闭';

$lang->demand->resultList['pass']   = '确认通过';
$lang->demand->resultList['refuse'] = '拒绝';

$lang->demand->stageList['0'] = '未整合';
$lang->demand->stageList['1'] = '已整合';

$lang->demand->projectTypeList[1] = '预立项';
$lang->demand->projectTypeList[2] = '项目';

$lang->demand->labelList = array();
$lang->demand->labelList['all']           = '所有';
$lang->demand->labelList['assigntome']    = '指派给我';
//$lang->demand->labelList['wait']          = '待评审';
//$lang->demand->labelList['refuse']        = '已驳回';
$lang->demand->labelList['openedbyme']    = '由我创建';
$lang->demand->labelList['closed']        = '已关闭';
$lang->demand->labelList['integrated']    = '已整合';
$lang->demand->labelList['notintegrated'] = '未整合';

$lang->demand->errorEmptyProduct = '『所属产品』不能为空';

$lang->demand->action = new stdclass();
$lang->demand->action->reviewed                = array('main' => '$date, 由 <strong>$actor</strong> 评审， 结果为 $extra。', 'extra' => 'resultList');
$lang->demand->action->submited                = array('main' => '$date, 由 <strong>$actor</strong> 提交评审。');
$lang->demand->action->tostory                 = array('main' => '$date, 由 <strong>$actor</strong> 转为需求 $extra。');
$lang->demand->action->closedbybusiness        = array('main' => '$date, 由 <strong>$actor</strong> 关闭业务需求 <strong>#$extra</strong>。导致该需求被关闭。');
$lang->demand->action->integrationintobusiness = array('main' => '$date, 由 <strong>$actor</strong> 整合为业务需求 <strong>#$extra</strong>。');

$lang->demand->confirmDelete        = '您确认删除该需求吗？';
$lang->demand->confirmSub           = '执行此操作会在对应产品下创建一条同名用户需求，并且该需求状态变为已拆分，您确认吗？';
$lang->demand->integrationError     = 'ID为 %s 的原始需求已被整合，剔除出当前选择。';
$lang->demand->integrationPrivError = 'ID为 %s 的原始需求不可被当前账户整合，将自动为您剔除。';

$lang->demand->projectName    = '归属项目';
$lang->demand->originalDemand = '原始需求';

$lang->demand->pool        = '需求池';
$lang->demand->deleted     = '逻辑删除';
$lang->demand->projectType = '项目类型';
