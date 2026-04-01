<?php
$config->project->create->subRequiredFields = new stdclass();
$config->project->create->subRequiredFields->projectmembers  = 'account,projectRole,description';
$config->project->create->subRequiredFields->projectcost     = 'costBudget,costDept,costDesc,costType,costUnit';
$config->project->create->subRequiredFields->projectbusiness = 'PRDdate,acceptanceDate,goLiveDate';

$config->project->edit->subRequiredFields = new stdclass();
$config->project->edit->subRequiredFields->projectmembers  = 'account,projectRole,description';
$config->project->edit->subRequiredFields->projectcost     = 'costBudget,costDept,costDesc,costType,costUnit';
$config->project->edit->subRequiredFields->projectbusiness = 'PRDdate,acceptanceDate,goLiveDate';
