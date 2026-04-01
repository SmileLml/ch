<?php
$config->group->package->projectapprovalother = new stdclass();
$config->group->package->projectapprovalother->order  = 5;
$config->group->package->projectapprovalother->subset = 'projectapproval';
$config->group->package->projectapprovalother->privs  = array();
$config->group->package->projectapprovalother->privs['projectapproval-business']         = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,or', 'order' => 10, 'depend' => array(), 'recommend' => array());
$config->group->package->projectapprovalother->privs['projectapproval-linkBusiness']     = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,or', 'order' => 15, 'depend' => array(), 'recommend' => array());
$config->group->package->projectapprovalother->privs['projectapproval-finishReport']     = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,or', 'order' => 20, 'depend' => array(), 'recommend' => array());
$config->group->package->projectapprovalother->privs['projectapproval-exportReportWord'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,or', 'order' => 20, 'depend' => array(), 'recommend' => array());

$config->group->package->manageTesttask->privs['testtask-batchConfirmChange'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,or', 'order' => 100, 'depend' => array(), 'recommend' => array());

$config->group->subset->monitoring = new stdclass();
$config->group->subset->monitoring->order = 30;
$config->group->subset->monitoring->nav   = 'my';

$config->group->package->browseMonitoring = new stdclass();
$config->group->package->browseMonitoring->order  = 5;
$config->group->package->browseMonitoring->subset = 'monitoring';
$config->group->package->browseMonitoring->privs  = array();
$config->group->package->browseMonitoring->privs['monitoring-browse'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite,or', 'order' => 1, 'depend' => array(), 'recommend' => array());
$config->group->package->browseMonitoring->privs['monitoring-export'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite,or', 'order' => 0, 'depend' => array(), 'recommend' => array());
