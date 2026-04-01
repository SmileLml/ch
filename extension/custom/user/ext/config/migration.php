<?php
$config->user->migrateFields = new stdClass();

$config->user->migrateFields->projectapproval = 'name,program,projectNumber,begin,end,days,desc,businessPM,pri,businessLine,responsibleDept,relevantDept,businessUnit,projectApprovalDate,totalCost,createdBy,createdDate';
$config->user->migrateFields->projectcost     = 'costBudget,costDept,costDesc,costType,descComment';
$config->user->migrateFields->projectmembers  = 'account,projectRole,description';
$config->user->migrateFields->projectvalue    = 'valueType,valueName,valueDesc,isQuantifiable,dataSources,formula,reachedDate';
$config->user->migrateFields->business        = 'name,status,severity,deadline,reasonType,dept,businessDesc,businessObjective,createdDept,developmentBudget,outsourcingBudget,processChange,processName,REQid,businessUnit,createdBy,createdDate';
$config->user->migrateFields->story           = 'product,title,source,category,pri,sourceNote,assignedTo,estimate,openedBy,openedDate,reviewer';
