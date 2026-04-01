<?php
$config->group->subset->chteam = new stdclass();
$config->group->subset->chteam->nav = 'chteam';

$config->group->package->browseChteam = new stdclass();
$config->group->package->browseChteam->order  = 5;
$config->group->package->browseChteam->subset = 'chteam';
$config->group->package->browseChteam->privs  = array();
$config->group->package->browseChteam->privs['chteam-browse'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());

$config->group->package->manageChteam = new stdclass();
$config->group->package->manageChteam->order  = 5;
$config->group->package->manageChteam->subset = 'chteam';
$config->group->package->manageChteam->privs  = array();
$config->group->package->manageChteam->privs['chteam-create'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->manageChteam->privs['chteam-edit']   = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());

$config->group->package->deleteChteam = new stdclass();
$config->group->package->deleteChteam->order  = 5;
$config->group->package->deleteChteam->subset = 'chteam';
$config->group->package->deleteChteam->privs  = array();
$config->group->package->deleteChteam->privs['chteam-delete'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());

$config->group->subset->chproject = new stdclass();
$config->group->subset->chproject->nav = 'chteam';

$config->group->package->browseChproject = new stdclass();
$config->group->package->browseChproject->order  = 5;
$config->group->package->browseChproject->subset = 'chproject';
$config->group->package->browseChproject->privs  = array();
$config->group->package->browseChproject->privs['chproject-browse']     = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-task']       = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-story']      = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-burn']       = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-bug']        = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-testcase']   = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-testtask']   = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-testreport'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-kanban']     = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-cfd']        = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-gantt']      = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-grouptask']  = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-tree']       = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->browseChproject->privs['chproject-taskeffort'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());

$config->group->package->manageChproject = new stdclass();
$config->group->package->manageChproject->order  = 5;
$config->group->package->manageChproject->subset = 'chproject';
$config->group->package->manageChproject->privs  = array();
$config->group->package->manageChproject->privs['chproject-create']   = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->manageChproject->privs['chproject-edit']     = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->manageChproject->privs['chproject-close']    = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());
$config->group->package->manageChproject->privs['chproject-activate'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());

$config->group->package->deleteChproject = new stdclass();
$config->group->package->deleteChproject->order  = 5;
$config->group->package->deleteChproject->subset = 'chproject';
$config->group->package->deleteChproject->privs  = array();
$config->group->package->deleteChproject->privs['chproject-delete'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 5, 'depend' => array(), 'recommend' => array());

$config->group->package->user->privs['company-syncInfo'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite,or', 'order' => 0, 'depend' => array('admin-index', 'admin-register'), 'recommend' => array('user-view'));

$config->group->package->manageTask->privs['task-batchChangeStory']     = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 3, 'depend' => array('execution-task'), 'recommend' => array('task-edit'));
$config->group->package->manageTask->privs['task-batchChangeExecution'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd,lite', 'order' => 3, 'depend' => array('execution-task'), 'recommend' => array());

$config->group->package->manageCase->privs['testcase-batchClone'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd', 'order' => 3, 'depend' => array(), 'recommend' => array());
$config->group->package->manageCase->privs['testcase-syncEdit'] = array('edition' => 'open,biz,max,ipd', 'vision' => 'rnd', 'order' => 3, 'depend' => array(), 'recommend' => array());