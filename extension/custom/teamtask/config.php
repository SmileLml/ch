<?php
$config->teamtask->datatable = new stdclass();
$config->teamtask->datatable->defaultField = array('id', 'name', 'pri', 'project', 'assignedTo', 'status', 'finishedBy', 'deadline', 'estimate', 'consumed', 'left', 'progress', 'actions');

global $app, $lang;
$config->teamtask->datatable->fieldList['id']['title']    = 'idAB';
$config->teamtask->datatable->fieldList['id']['fixed']    = 'left';
$config->teamtask->datatable->fieldList['id']['minWidth'] = '70';
$config->teamtask->datatable->fieldList['id']['required'] = 'yes';
$config->teamtask->datatable->fieldList['id']['type']     = 'checkID';
$config->teamtask->datatable->fieldList['id']['sortType'] = true;
$config->teamtask->datatable->fieldList['id']['checkbox'] = true;

$config->teamtask->datatable->fieldList['name']['title']        = 'name';
$config->teamtask->datatable->fieldList['name']['required']     = 'yes';
$config->teamtask->datatable->fieldList['name']['width']        = 'auto';
$config->teamtask->datatable->fieldList['name']['type']         = 'html';
$config->teamtask->datatable->fieldList['name']['fixed']        = 'left';
$config->teamtask->datatable->fieldList['name']['sortType']     = true;
$config->teamtask->datatable->fieldList['name']['nestedToggle'] = true;
$config->teamtask->datatable->fieldList['name']['iconRender']   = true;

$config->teamtask->datatable->fieldList['pri']['title']    = 'priAB';
$config->teamtask->datatable->fieldList['pri']['fixed']    = 'left';
$config->teamtask->datatable->fieldList['pri']['type']     = 'html';
$config->teamtask->datatable->fieldList['pri']['width']    = '45';
$config->teamtask->datatable->fieldList['pri']['required'] = 'no';
$config->teamtask->datatable->fieldList['pri']['sortType'] = true;
$config->teamtask->datatable->fieldList['pri']['name']     = $lang->task->pri;

$config->teamtask->datatable->fieldList['project']['title']    = 'project';
$config->teamtask->datatable->fieldList['project']['type']     = 'html';
$config->teamtask->datatable->fieldList['project']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['project']['width']    = '100';
$config->teamtask->datatable->fieldList['project']['sortType'] = true;
$config->teamtask->datatable->fieldList['project']['required'] = 'no';

$config->teamtask->datatable->fieldList['assignedTo']['title']       = 'assignedTo';
$config->teamtask->datatable->fieldList['assignedTo']['fixed']       = 'no';
$config->teamtask->datatable->fieldList['assignedTo']['width']       = '100';
$config->teamtask->datatable->fieldList['assignedTo']['type']        = 'html';
$config->teamtask->datatable->fieldList['assignedTo']['required']    = 'no';
$config->teamtask->datatable->fieldList['assignedTo']['currentUser'] = $app->user->account;
$config->teamtask->datatable->fieldList['assignedTo']['assignLink']  = array('module' => 'task', 'method' => 'assignTo', 'params' => 'executionID={execution}&taskID={id}');
$config->teamtask->datatable->fieldList['assignedTo']['sortType']    = true;
$config->teamtask->datatable->fieldList['assignedTo']['control']     = 'select';
$config->teamtask->datatable->fieldList['assignedTo']['dataSource']  = array('module' => 'user', 'method' => 'getTeamMemberPairs', 'params' => '$executionID&execution');

$config->teamtask->datatable->fieldList['assignedDate']['title']    = 'assignedDate';
$config->teamtask->datatable->fieldList['assignedDate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['assignedDate']['type']     = 'date';
$config->teamtask->datatable->fieldList['assignedDate']['width']    = '110';
$config->teamtask->datatable->fieldList['assignedDate']['sortType'] = true;
$config->teamtask->datatable->fieldList['assignedDate']['required'] = 'no';

$config->teamtask->datatable->fieldList['type']['title']    = 'typeAB';
$config->teamtask->datatable->fieldList['type']['type']     = 'category';
$config->teamtask->datatable->fieldList['type']['width']    = '80';
$config->teamtask->datatable->fieldList['type']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['type']['sortType'] = true;
$config->teamtask->datatable->fieldList['type']['required'] = 'no';
$config->teamtask->datatable->fieldList['type']['map']      = $lang->task->typeList;

$config->teamtask->datatable->fieldList['status']['title']    = 'statusAB';
$config->teamtask->datatable->fieldList['status']['type']     = 'html';
$config->teamtask->datatable->fieldList['status']['width']    = '60';
$config->teamtask->datatable->fieldList['status']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['status']['sortType'] = true;
$config->teamtask->datatable->fieldList['status']['required'] = 'no';

$config->teamtask->datatable->fieldList['finishedBy']['title']    = 'finishedByAB';
$config->teamtask->datatable->fieldList['finishedBy']['type']     = 'user';
$config->teamtask->datatable->fieldList['finishedBy']['width']    = '80';
$config->teamtask->datatable->fieldList['finishedBy']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['finishedBy']['sortType'] = true;
$config->teamtask->datatable->fieldList['finishedBy']['required'] = 'no';

$config->teamtask->datatable->fieldList['deadline']['title']    = 'deadlineAB';
$config->teamtask->datatable->fieldList['deadline']['type']     = 'html';
$config->teamtask->datatable->fieldList['deadline']['width']    = '70';
$config->teamtask->datatable->fieldList['deadline']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['deadline']['sortType'] = true;
$config->teamtask->datatable->fieldList['deadline']['required'] = 'no';
$config->teamtask->datatable->fieldList['deadline']['control']  = 'date';

$config->teamtask->datatable->fieldList['estimate']['title']    = 'estimateAB';
$config->teamtask->datatable->fieldList['estimate']['width']    = '65';
$config->teamtask->datatable->fieldList['estimate']['sortType'] = true;
$config->teamtask->datatable->fieldList['estimate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['estimate']['required'] = 'no';

$config->teamtask->datatable->fieldList['consumed']['title']    = 'consumedAB';
$config->teamtask->datatable->fieldList['consumed']['width']    = '65';
$config->teamtask->datatable->fieldList['consumed']['sortType'] = true;
$config->teamtask->datatable->fieldList['consumed']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['consumed']['required'] = 'no';

$config->teamtask->datatable->fieldList['left']['title']    = 'leftAB';
$config->teamtask->datatable->fieldList['left']['width']    = '65';
$config->teamtask->datatable->fieldList['left']['sortType'] = true;
$config->teamtask->datatable->fieldList['left']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['left']['required'] = 'no';

$config->teamtask->datatable->fieldList['progress']['title']    = 'progressAB';
$config->teamtask->datatable->fieldList['progress']['width']    = '75';
$config->teamtask->datatable->fieldList['progress']['type']     = 'progress';
$config->teamtask->datatable->fieldList['progress']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['progress']['required'] = 'no';
$config->teamtask->datatable->fieldList['progress']['sortType'] = false;
$config->teamtask->datatable->fieldList['progress']['name']     = $lang->task->progress;

$config->teamtask->datatable->fieldList['openedBy']['title']    = 'openedByAB';
$config->teamtask->datatable->fieldList['openedBy']['type']     = 'user';
$config->teamtask->datatable->fieldList['openedBy']['width']    = '90';
$config->teamtask->datatable->fieldList['openedBy']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['openedBy']['sortType'] = true;
$config->teamtask->datatable->fieldList['openedBy']['required'] = 'no';

$config->teamtask->datatable->fieldList['openedDate']['title']    = 'openedDate';
$config->teamtask->datatable->fieldList['openedDate']['type']     = 'date';
$config->teamtask->datatable->fieldList['openedDate']['width']    = '110';
$config->teamtask->datatable->fieldList['openedDate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['openedDate']['sortType'] = true;
$config->teamtask->datatable->fieldList['openedDate']['required'] = 'no';

$config->teamtask->datatable->fieldList['estStarted']['title']    = 'estStarted';
$config->teamtask->datatable->fieldList['estStarted']['type']     = 'date';
$config->teamtask->datatable->fieldList['estStarted']['width']    = '90';
$config->teamtask->datatable->fieldList['estStarted']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['estStarted']['sortType'] = true;
$config->teamtask->datatable->fieldList['estStarted']['required'] = 'no';
$config->teamtask->datatable->fieldList['estStarted']['control']  = 'date';

$config->teamtask->datatable->fieldList['realStarted']['title']    = 'realStarted';
$config->teamtask->datatable->fieldList['realStarted']['type']     = 'date';
$config->teamtask->datatable->fieldList['realStarted']['width']    = '95';
$config->teamtask->datatable->fieldList['realStarted']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['realStarted']['sortType'] = true;
$config->teamtask->datatable->fieldList['realStarted']['required'] = 'no';

$config->teamtask->datatable->fieldList['finishedDate']['title']    = 'finishedDateAB';
$config->teamtask->datatable->fieldList['finishedDate']['type']     = 'date';
$config->teamtask->datatable->fieldList['finishedDate']['width']    = '105';
$config->teamtask->datatable->fieldList['finishedDate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['finishedDate']['sortType'] = true;
$config->teamtask->datatable->fieldList['finishedDate']['required'] = 'no';

$config->teamtask->datatable->fieldList['canceledBy']['title']    = 'canceledBy';
$config->teamtask->datatable->fieldList['canceledBy']['type']     = 'user';
$config->teamtask->datatable->fieldList['canceledBy']['width']    = '110';
$config->teamtask->datatable->fieldList['canceledBy']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['canceledBy']['sortType'] = true;
$config->teamtask->datatable->fieldList['canceledBy']['required'] = 'no';

$config->teamtask->datatable->fieldList['canceledDate']['title']    = 'canceledDate';
$config->teamtask->datatable->fieldList['canceledDate']['type']     = 'date';
$config->teamtask->datatable->fieldList['canceledDate']['width']    = '115';
$config->teamtask->datatable->fieldList['canceledDate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['canceledDate']['sortType'] = true;
$config->teamtask->datatable->fieldList['canceledDate']['required'] = 'no';

$config->teamtask->datatable->fieldList['closedBy']['title']    = 'closedBy';
$config->teamtask->datatable->fieldList['closedBy']['type']     = 'user';
$config->teamtask->datatable->fieldList['closedBy']['width']    = '100';
$config->teamtask->datatable->fieldList['closedBy']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['closedBy']['sortType'] = true;
$config->teamtask->datatable->fieldList['closedBy']['required'] = 'no';

$config->teamtask->datatable->fieldList['closedDate']['title']    = 'closedDate';
$config->teamtask->datatable->fieldList['closedDate']['type']     = 'date';
$config->teamtask->datatable->fieldList['closedDate']['width']    = '115';
$config->teamtask->datatable->fieldList['closedDate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['closedDate']['sortType'] = true;
$config->teamtask->datatable->fieldList['closedDate']['required'] = 'no';

$config->teamtask->datatable->fieldList['closedReason']['title']    = 'closedReason';
$config->teamtask->datatable->fieldList['closedReason']['type']     = 'category';
$config->teamtask->datatable->fieldList['closedReason']['width']    = '120';
$config->teamtask->datatable->fieldList['closedReason']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['closedReason']['sortType'] = true;
$config->teamtask->datatable->fieldList['closedReason']['required'] = 'no';
$config->teamtask->datatable->fieldList['closedReason']['map']      = $lang->task->reasonList;

$config->teamtask->datatable->fieldList['lastEditedBy']['title']    = 'lastEditedBy';
$config->teamtask->datatable->fieldList['lastEditedBy']['width']    = '95';
$config->teamtask->datatable->fieldList['lastEditedBy']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['lastEditedBy']['sortType'] = true;
$config->teamtask->datatable->fieldList['lastEditedBy']['required'] = 'no';

$config->teamtask->datatable->fieldList['lastEditedDate']['title']    = 'lastEditedDate';
$config->teamtask->datatable->fieldList['lastEditedDate']['width']    = '120';
$config->teamtask->datatable->fieldList['lastEditedDate']['type']     = 'date';
$config->teamtask->datatable->fieldList['lastEditedDate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['lastEditedDate']['sortType'] = true;
$config->teamtask->datatable->fieldList['lastEditedDate']['required'] = 'no';

$config->teamtask->datatable->fieldList['activatedDate']['title']    = 'activatedDate';
$config->teamtask->datatable->fieldList['activatedDate']['width']    = '90';
$config->teamtask->datatable->fieldList['activatedDate']['type']     = 'date';
$config->teamtask->datatable->fieldList['activatedDate']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['activatedDate']['sortType'] = true;
$config->teamtask->datatable->fieldList['activatedDate']['required'] = 'no';

$config->teamtask->datatable->fieldList['story']['title']      = "storyAB";
$config->teamtask->datatable->fieldList['story']['width']      = '80';
$config->teamtask->datatable->fieldList['story']['fixed']      = 'no';
$config->teamtask->datatable->fieldList['story']['sortType']   = true;
$config->teamtask->datatable->fieldList['story']['required']   = 'no';
$config->teamtask->datatable->fieldList['story']['name']       = $lang->task->story;
$config->teamtask->datatable->fieldList['story']['type']       = 'html';
$config->teamtask->datatable->fieldList['story']['control']    = 'select';
$config->teamtask->datatable->fieldList['story']['dataSource'] = array('module' => 'story', 'method' => 'getExecutionStoryPairs', 'params' => '$executionID&0&all&&&active');

$config->teamtask->datatable->fieldList['mailto']['title']    = 'mailto';
$config->teamtask->datatable->fieldList['mailto']['width']    = '100';
$config->teamtask->datatable->fieldList['mailto']['fixed']    = 'no';
$config->teamtask->datatable->fieldList['mailto']['sortType'] = true;
$config->teamtask->datatable->fieldList['mailto']['required'] = 'no';

$config->teamtask->datatable->fieldList['module']['title']      = 'module';
$config->teamtask->datatable->fieldList['module']['control']    = 'select';
$config->teamtask->datatable->fieldList['module']['dataSource'] = array('module' => 'tree', 'method' => 'getTaskOptionMenu', 'params' => '$executionID');
$config->teamtask->datatable->fieldList['module']['display']    = false;

$config->teamtask->datatable->fieldList['execution']['title']      = 'execution';
$config->teamtask->datatable->fieldList['execution']['control']    = 'hidden';
$config->teamtask->datatable->fieldList['execution']['type']       = 'html';
$config->teamtask->datatable->fieldList['execution']['dataSource'] = array('module' => 'execution', 'method' => 'getPairs');
$config->teamtask->datatable->fieldList['execution']['display']    = false;

$config->teamtask->datatable->fieldList['mode']['title']   = 'mode';
$config->teamtask->datatable->fieldList['mode']['control'] = 'hidden';
$config->teamtask->datatable->fieldList['mode']['display'] = false;

$config->teamtask->datatable->fieldList['desc']['title']   = 'desc';
$config->teamtask->datatable->fieldList['desc']['control'] = 'textarea';
$config->teamtask->datatable->fieldList['desc']['display'] = false;

$config->teamtask->datatable->fieldList['actions']['title']    = 'actions';
$config->teamtask->datatable->fieldList['actions']['type']     = 'html';
$config->teamtask->datatable->fieldList['actions']['fixed']    = 'right';
$config->teamtask->datatable->fieldList['actions']['width']    = '180';
$config->teamtask->datatable->fieldList['actions']['required'] = 'yes';
