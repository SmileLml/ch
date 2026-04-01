<?php
$config->projectapproval->exportApprovalReportFields = 'desc,strategic,target,projectvalue,buildMethod,buildSpec,projectbusiness,ba,busiProcess,aa,integrate,projectmembers,projectcost,netInfoSafe,deduction,risk,meeting';
$config->projectapproval->exportReviewWordFields     = 'reviewDate,reviewLocation,participant,absentee,projectreviewdetails,remark,reviewResult,businessUnit,businessInto,itPlanInto,purchasingBudget,itCost';

$config->projectapproval->exportChildFields = [];
$config->projectapproval->exportChildFields['projectvalue']         = 'valueDesc,valueType,verification,reachedDate';
$config->projectapproval->exportChildFields['projectbusiness']      = 'PRDdate,goLiveDate,acceptanceDate,developmentBudget,headBusiness,outsourcingBudget,businessDesc,desc,businessObjective';
$config->projectapproval->exportChildFields['projectcost']          = 'costType,costDesc,costBudget,costUnit,costDept,addBudget,descComment';
$config->projectapproval->exportChildFields['projectmembers']       = 'account,projectRole,description';
$config->projectapproval->exportChildFields['projectreviewdetails'] = 'questioner,problemDesc,comment';

$config->projectapproval->headerTitleStyle = [];
$config->projectapproval->headerTitleStyle['name'] = 'Heading 1';
$config->projectapproval->headerTitleStyle['size'] = 16;
$config->projectapproval->headerTitleStyle['bold'] = true;

$config->projectapproval->tableStyle = [];
$config->projectapproval->tableStyle['width']       = 100 * 50;
$config->projectapproval->tableStyle['borderSize']  = 1;
$config->projectapproval->tableStyle['borderColor'] = 'CCCCCC';
$config->projectapproval->tableStyle['cellMargin']  = 50;

$config->projectapproval->titleCellStyle = [];
$config->projectapproval->titleCellStyle['bgColor'] = 'DAEEF3';
$config->projectapproval->titleCellStyle['valign']  = 'center';

$config->projectapproval->contentCellStyle = [];
$config->projectapproval->contentCellStyle['valign'] = 'top';

$config->projectapproval->subTableStyle = [];
$config->projectapproval->subTableStyle['borderSize']  = 1;
$config->projectapproval->subTableStyle['borderColor'] = 'CCCCCC';
$config->projectapproval->subTableStyle['cellMargin']  = 50;
$config->projectapproval->subTableStyle['width']       = 100 * 50;

$config->projectapproval->subTitleStyle = [];
$config->projectapproval->subTitleStyle['name'] = 'Heading 1';
$config->projectapproval->subTitleStyle['size'] = 14;
$config->projectapproval->subTitleStyle['bold'] = true;

$config->projectapproval->headerCellStyle = [];
$config->projectapproval->headerCellStyle['bgcolor'] = 'DAEEF3';