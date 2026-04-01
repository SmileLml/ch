<?php
$lang->linkBusiness = '关联业务需求';

$lang->projectapproval->exportPDF             = '导出PDF';
$lang->projectapproval->close                 = '验收';
$lang->projectapproval->noAccessFinishReport  = '无权限访问结项报告！';
$lang->projectapproval->finishMailSubject     = '『%s』项目的结项提醒';
$lang->projectapproval->finishMailContent     = '『%s』项目下的业务需求均已取消或验收完毕，可进行项目结项，%s。';
$lang->projectapproval->prdsubmit             = 'PRD评审确认';
$lang->projectapproval->confirmMessage        = '您确认要执行${title}操作吗?';
$lang->projectapproval->architect             = '业务架构师';
$lang->projectapproval->PMO                   = 'PMO';
$lang->projectapproval->leader                = '信息化领导';
$lang->projectapproval->finishFiles           = '结项附件';
$lang->projectapproval->businessList          = '业务需求';
$lang->projectapproval->linkBusiness          = '关联业务需求';
$lang->projectapproval->finishReport          = '结项报告';
$lang->projectapproval->changeHistoryDetail   = '变更信息详情';
$lang->projectapproval->cancelHistoryDetail   = '取消信息详情';
$lang->projectapproval->changeApplicant       = '变更申请人';
$lang->projectapproval->changeApplicationDate = '变更申请日期';
$lang->projectapproval->changeContent         = '变更内容';
$lang->projectapproval->changeReason          = '变更原因';
$lang->projectapproval->changeType            = '变更类型';
$lang->projectapproval->finishInfo            = '结项信息';
$lang->projectapproval->projectapproval       = '项目管理';
$lang->projectapproval->approvalReport        = '立项报告';
$lang->projectapproval->exportReportWord      = '导出立项报告';
$lang->projectapproval->approvalReviewReport  = '立项评审报告';
$lang->projectapproval->exportReviewWord      = '导出立项评审报告';
$lang->projectapproval->actualCost            = '实际成本（人天）';
$lang->projectapproval->businessLine          = '业务条线';

$lang->projectapproval->progressSituation     = '进展情况';
$lang->projectapproval->progress              = '进度百分比';
$lang->projectapproval->progressDeviation     = '整体进度偏差';
$lang->projectapproval->practicalBegin        = '实际开始时间';
$lang->projectapproval->practicalEnd          = '实际完成时间';
$lang->projectapproval->percentageDifference  = '差值百分比';
$lang->projectapproval->projectcostInfo       = '成本数据';
$lang->projectapproval->projectvalueInfo      = '项目价值目标信息';
$lang->projectapproval->progressDeviationTips = '项目计划完成时间 和 最晚的需求实际验收时间 ，去除休息、节假日的偏差天数。';
$lang->projectapproval->practicalBeginTips    = '关联项目下第一个史诗的创建时间。';
$lang->projectapproval->practicalEndTips      = '关联业务需求最晚的验收时间。';

$lang->projectapproval->qualitySituation      = '质量情况';
$lang->projectapproval->allBugNum             = 'Bug总数';
$lang->projectapproval->onlineBugNum          = '上线后Bug数';
$lang->projectapproval->onlineBugprogress     = '上线后Bug占比';
$lang->projectapproval->unsolvedBugNum        = '未解决Bug数';
$lang->projectapproval->onlineBugprogressTips = '上线Bug数 / 项目关联史诗的预计总人天。';

$lang->projectapproval->changeNumStatistics   = '变更次数统计';
$lang->projectapproval->allChangeNum          = '变更总次数';
$lang->projectapproval->changeNum             = '变更次数';

$lang->projectapproval->reviewConclusion      = '评审结论';
$lang->projectapproval->reviewOpinion         = '评审意见';

$lang->projectapproval->business = new stdClass();

$lang->projectapproval->business->PRDdate             = '计划PRD完成日期';
$lang->projectapproval->business->goLiveDate          = '计划上线日期';
$lang->projectapproval->business->acceptanceDate      = '计划验收日期';
$lang->projectapproval->business->developmentBudget   = '研发预算(人天)';
$lang->projectapproval->business->headBusiness        = '需求负责人';
$lang->projectapproval->business->outsourcingBudget   = '外购预算金额(元)';
$lang->projectapproval->business->emptyPRDdate        = '计划PRD完成日期不能为空';
$lang->projectapproval->business->emptyGoLiveDate     = '计划上线日期不能为空';
$lang->projectapproval->business->emptyAcceptanceDate = '计划验收日期不能为空';

$lang->projectapproval->objectVersion = new stdClass();
$lang->projectapproval->objectVersion->create             = '创建';
$lang->projectapproval->objectVersion->edit               = '修改';
$lang->projectapproval->objectVersion->approvalsubmit1    = '初审';
$lang->projectapproval->objectVersion->evaluationfeedback = '意见反馈';
$lang->projectapproval->objectVersion->approvalsubmit2    = '复审';
$lang->projectapproval->objectVersion->approvalsubmit3    = '第%s次变更';
$lang->projectapproval->objectVersion->approvalsubmit4    = '取消';
$lang->projectapproval->objectVersion->approvalsubmit5    = '结项';

$lang->projectapproval->businessInto     = '项目业务部门投入人天';
$lang->projectapproval->itPlanInto       = '项目IT自有开发人力';
$lang->projectapproval->purchasingBudget = '项目IT外购预算';
$lang->projectapproval->itCost           = '项目IT运维初步报价';

$lang->projectapproval->disableAfterClickList = array();
$lang->projectapproval->disableAfterClickList['approvalcancel1'] = '撤回初审';
$lang->projectapproval->disableAfterClickList['approvalcancel2'] = '撤回复审';
$lang->projectapproval->disableAfterClickList['approvalcancel3'] = '撤回变更';
$lang->projectapproval->disableAfterClickList['approvalcancel4'] = '撤回取消';
$lang->projectapproval->disableAfterClickList['approvalcancel4'] = '撤回结项';

$lang->projectapproval->actionList = array();
$lang->projectapproval->actionList['approvalreview1'] = '立项审批中';
$lang->projectapproval->actionList['approvalreview2'] = '立项审批中';
$lang->projectapproval->actionList['approvalreview3'] = '变更审批中';
$lang->projectapproval->actionList['approvalreview4'] = '取消评审中';
$lang->projectapproval->actionList['approvalreview5'] = '结项评审中';

$lang->projectapproval->richtextDefault = array();
$lang->projectapproval->richtextDefault['desc']        = '请在此处简述项目背景需求、业务痛点。';
$lang->projectapproval->richtextDefault['target']      = "提供项目目标的简要概述，目标达成后成功的样子描述。\n例如：25年底建设时速500公里总长1200公里的京沪高铁；建设年储水量400亿立方米、年发电量1000亿千瓦时三峡大坝；2030年实现5nm国产芯片量产。";
$lang->projectapproval->richtextDefault['ba']          = "描述现有的业务架构和目标的业务架构差异，建议以业务架构图方式体现。如业务架构无变化，直接描述现有业务架构即可。";
$lang->projectapproval->richtextDefault['busiProcess'] = "描述现有的业务模式（业务流程），例如：航班计划的制定，飞行任务的安排，航班计划的变更等等。\n描述现有业务模式中所存在的问题，基于现有的业务模式，总结现有问题，以及这些问题所带来的后果及风险。\n提出目标业务模式（业务流程）。";
$lang->projectapproval->richtextDefault['aa']          = "描述本次项目涉及的应用系统功能架构，建议以上（渠道）、中（应用功能）、下（支持功能）、右（接口）方式进行展示。把此次项目实现功能标记清楚。";
$lang->projectapproval->richtextDefault['integrate']   = "说明本次项目涉及到哪些系统，系统之间的集成关系，并以数据流的方式描述主要应用系统之间的数据依赖和流转关系。";
$lang->projectapproval->richtextDefault['meeting']     = "请在此处说明项目例会的频率、状态报告发布的频率，任务反馈方式等";

$lang->projectapproval->netInfoSafeDefault = <<<EOD
<table class="MsoNormalTable" border="1" cellspacing="0">
  <tbody>
    <tr>
      <td width="129" style="background:#D9D9D9;">
        <p class="MsoHeader" style="text-align:center;"><b><span style="font-family:'微软雅黑';font-size:11pt;">成本项</span></b><b><span style="font-family:'微软雅黑';font-size:11pt;"></span></b></p>
      </td>
      <td width="324">
        <p class="MsoNormal" align="center" style="text-align:center;"><b><span style="font-family:'微软雅黑';font-size:11pt;">金额</span></b><b><span style="font-family:'微软雅黑';font-size:11pt;"></span></b></p>
      </td>
    </tr>
    <tr>
      <td width="129" style="background:#D9D9D9;">
        <p class="MsoHeader"><b><span style="font-family:'微软雅黑';font-size:11pt;">业务部门网络与信息安全投入预算</span></b><b><span style="font-family:'微软雅黑';font-size:11pt;"></span></b></p>
      </td>
      <td width="324">
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">外购系统渗透性测试费用：人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">元</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">信息系统应急预案和应急演练费用：人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">元</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">安全风险评估或外部专业咨询服务费用：人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">元</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoToc1" style="font-family:Calibri;font-weight:bold;font-size:10pt;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-weight:normal;font-size:10pt;"><span style="font-family:'微软雅黑';">其他</span><span style="font-family:'微软雅黑';">:</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-weight:normal;font-size:10pt;">__________</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-weight:normal;font-size:10pt;"><span style="font-family:'微软雅黑';">人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-weight:normal;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-weight:normal;font-size:10pt;">元</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-weight:normal;font-size:10pt;"></span></i></p>
      </td>
    </tr>
    <tr>
      <td width="129" style="background:#D9D9D9;">
        <p class="MsoHeader"><b><span style="font-family:'微软雅黑';font-size:11pt;">IT网络与信息安全投入研发预算</span></b><b><span style="font-family:'微软雅黑';font-size:11pt;"></span></b></p>
      </td>
      <td width="324">
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">对接统一认证费用：人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">元</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">对接统一监控费用：人民币</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-family:'微软雅黑';">元</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoToc1" style="font-family:Calibri;font-weight:bold;font-size:10pt;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">HTTPS协议费用：人民币 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;元</span></i><span style="font-size:10pt;"></span></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">个人敏感信息（姓名、密码、手机号、证件号、银行账号、电子邮件地址）展示及导出功能脱敏费用：人民币</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-family:'微软雅黑';">元</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">审计日志功能（至少应包括事件的日期、时间、发起者信息、类型、描述和结果等）费用：人民币</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-family:'微软雅黑';">元</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">国产加密算法费用：人民币</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-family:'微软雅黑';">元</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">安全测试用例和漏洞修复费用：人民币</span> &nbsp;&nbsp;<span style="font-family:'微软雅黑';"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;元</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">移动终端</span><span style="font-family:'微软雅黑';">APP加固费用：人民币 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;元</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoToc1" style="font-family:Calibri;font-weight:bold;font-size:10pt;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">移动终端</span><span style="font-family:'微软雅黑';">APP签名费用：人民币 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;元</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10.5pt;"><span style="font-family:'微软雅黑';">其他</span><span style="font-family:'微软雅黑';">:</span></span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10.5pt;">__________</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10.5pt;">&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">元</span></i><span style="font-size:10.5pt;"></span></p>
      </td>
    </tr>
    <tr>
      <td width="129" style="background:#D9D9D9;">
        <p class="MsoHeader"><b><span style="font-family:'微软雅黑';font-size:11pt;">IT网络与信息安全投入运维安全费用</span></b><b><span style="font-family:'微软雅黑';font-size:11pt;"></span></b></p>
      </td>
      <td width="324">
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">元</span></i><b><span style="font-family:'微软雅黑';font-size:11pt;"></span></b></p>
      </td>
    </tr>
    <tr>
      <td width="129" style="background:#D9D9D9;">
        <p class="MsoHeader"><b><span style="font-family:'微软雅黑';font-size:10pt;">总投入</span></b><b><span style="font-family:'微软雅黑';font-size:11pt;"></span></b></p>
      </td>
      <td width="324">
        <p class="MsoNormal" style="text-align:justify;"><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"><span style="font-family:'微软雅黑';">人民币</span> </span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;">元</span></i><i><span style="font-family:'微软雅黑';color:#0070C0;font-size:10pt;"></span></i></p>
      </td>
    </tr>
  </tbody>
</table>
EOD;
$lang->projectapproval->riskDefault = <<<EOD
<p>请在此处对识别出的项目风险进行说明并说明应对方案和责任人。</p>
<p>
  <table class="table table-kindeditor ke-select-col" style="width:100%;">
    <tbody>
      <tr>
        <th style="text-align:center;border:1px solid #ddd;">风险项</th>
        <th style="text-align:center;border:1px solid #ddd;">应对方案</th>
        <th style="text-align:center;border:1px solid #ddd;">责任人</th>
      </tr>
      <tr>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
      </tr>
      <tr>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
      </tr>
      <tr>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
      </tr>
      <tr>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
      </tr>
      <tr>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
        <td style="border:1px solid #ddd;"><br /></td>
      </tr>
    </tbody>
  </table>
<br />
</p>
EOD;