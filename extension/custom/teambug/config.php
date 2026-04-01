<?php
$config->teambug->datatable = new stdclass();
$config->teambug->datatable->defaultField = array('id', 'title', 'project', 'product', 'severity', 'pri', 'status', 'openedBy', 'openedDate', 'confirmed', 'assignedTo', 'resolution', 'actions');

global $lang;
$config->teambug->datatable->fieldList['id']['title']    = 'idAB';
$config->teambug->datatable->fieldList['id']['type']     = 'checkID';
$config->teambug->datatable->fieldList['id']['fixed']    = 'left';
$config->teambug->datatable->fieldList['id']['width']    = '70';
$config->teambug->datatable->fieldList['id']['checkbox'] = true;
$config->teambug->datatable->fieldList['id']['sortType'] = true;
$config->teambug->datatable->fieldList['id']['required'] = 'yes';

$config->teambug->datatable->fieldList['module']['control']    = 'select';
$config->teambug->datatable->fieldList['module']['title']      = 'module';
$config->teambug->datatable->fieldList['module']['dataSource'] = array('module' => 'tree', 'method' => 'getOptionMenu', 'params' => '$productID&bug&0&all');
$config->teambug->datatable->fieldList['module']['display']    = false; // The configuration of datatable also used to export data, the false value means the field won't display in datatable.

$config->teambug->datatable->fieldList['title']['title']    = 'title';
$config->teambug->datatable->fieldList['title']['type']     = 'html';
$config->teambug->datatable->fieldList['title']['fixed']    = 'left';
$config->teambug->datatable->fieldList['title']['width']    = 'auto';
$config->teambug->datatable->fieldList['title']['minWidth'] = '200';
$config->teambug->datatable->fieldList['title']['sortType'] = true;
$config->teambug->datatable->fieldList['title']['required'] = 'yes';

$config->teambug->datatable->fieldList['project']['title']      = 'project';
$config->teambug->datatable->fieldList['project']['type']       = 'html';
$config->teambug->datatable->fieldList['project']['fixed']      = 'no';
$config->teambug->datatable->fieldList['project']['width']      = '120';
$config->teambug->datatable->fieldList['project']['sortType']   = true;
$config->teambug->datatable->fieldList['project']['control']    = 'hidden';
$config->teambug->datatable->fieldList['project']['dataSource'] = array('module' => 'project', 'method' => 'getPairs');

$config->teambug->datatable->fieldList['product']['title']      = 'product';
$config->teambug->datatable->fieldList['product']['type']       = 'html';
$config->teambug->datatable->fieldList['product']['fixed']      = 'no';
$config->teambug->datatable->fieldList['product']['width']      = '120';
$config->teambug->datatable->fieldList['product']['sortType']   = true;
$config->teambug->datatable->fieldList['product']['control']    = 'hidden';
$config->teambug->datatable->fieldList['product']['dataSource'] = array('module' => 'product', 'method' => 'getPairs', 'params' => '&0&&all');

$config->teambug->datatable->fieldList['severity']['title']    = 'severityAB';
$config->teambug->datatable->fieldList['severity']['type']     = 'html';
$config->teambug->datatable->fieldList['severity']['fixed']    = 'left';
$config->teambug->datatable->fieldList['severity']['width']    = '60';
$config->teambug->datatable->fieldList['severity']['sortType'] = true;
$config->teambug->datatable->fieldList['severity']['show']     = true;

$config->teambug->datatable->fieldList['pri']['title']    = 'P';
$config->teambug->datatable->fieldList['pri']['type']     = 'html';
$config->teambug->datatable->fieldList['pri']['fixed']    = 'left';
$config->teambug->datatable->fieldList['pri']['width']    = '50';
$config->teambug->datatable->fieldList['pri']['sortType'] = true;
$config->teambug->datatable->fieldList['pri']['show']     = true;

$config->teambug->datatable->fieldList['status']['title']    = 'statusAB';
$config->teambug->datatable->fieldList['status']['type']     = 'html';
$config->teambug->datatable->fieldList['status']['fixed']    = 'left';
$config->teambug->datatable->fieldList['status']['width']    = '80';
$config->teambug->datatable->fieldList['status']['sortType'] = true;
$config->teambug->datatable->fieldList['status']['show']     = true;

$config->teambug->datatable->fieldList['branch']['title']      = 'branch';
$config->teambug->datatable->fieldList['branch']['type']       = 'html';
$config->teambug->datatable->fieldList['branch']['fixed']      = 'left';
$config->teambug->datatable->fieldList['branch']['width']      = '100';
$config->teambug->datatable->fieldList['branch']['sortType']   = true;
$config->teambug->datatable->fieldList['branch']['control']    = 'select';
$config->teambug->datatable->fieldList['branch']['dataSource'] = array('module' => 'branch', 'method' => 'getPairs', 'params' => '$productID');

$config->teambug->datatable->fieldList['confirmed']['title']    = 'confirmedAB';
$config->teambug->datatable->fieldList['confirmed']['type']     = 'html';
$config->teambug->datatable->fieldList['confirmed']['fixed']    = 'no';
$config->teambug->datatable->fieldList['confirmed']['width']    = '70';
$config->teambug->datatable->fieldList['confirmed']['sortType'] = true;
$config->teambug->datatable->fieldList['confirmed']['show']     = true;

$config->teambug->datatable->fieldList['type']['title']    = 'type';
$config->teambug->datatable->fieldList['type']['type']     = 'html';
$config->teambug->datatable->fieldList['type']['fixed']    = 'no';
$config->teambug->datatable->fieldList['type']['width']    = '90';
$config->teambug->datatable->fieldList['type']['sortType'] = true;

$config->teambug->datatable->fieldList['project']['title']      = 'project';
$config->teambug->datatable->fieldList['project']['type']       = 'html';
$config->teambug->datatable->fieldList['project']['fixed']      = 'no';
$config->teambug->datatable->fieldList['project']['width']      = '120';
$config->teambug->datatable->fieldList['project']['sortType']   = true;
$config->teambug->datatable->fieldList['project']['control']    = 'hidden';
$config->teambug->datatable->fieldList['project']['dataSource'] = array('module' => 'project', 'method' => 'getPairs');

$config->teambug->datatable->fieldList['plan']['title']    = 'plan';
$config->teambug->datatable->fieldList['plan']['type']     = 'html';
$config->teambug->datatable->fieldList['plan']['fixed']    = 'no';
$config->teambug->datatable->fieldList['plan']['width']    = '120';
$config->teambug->datatable->fieldList['plan']['sortType'] = true;

$config->teambug->datatable->fieldList['openedBy']['title']    = 'openedByAB';
$config->teambug->datatable->fieldList['openedBy']['type']     = 'html';
$config->teambug->datatable->fieldList['openedBy']['fixed']    = 'no';
$config->teambug->datatable->fieldList['openedBy']['width']    = '80';
$config->teambug->datatable->fieldList['openedBy']['sortType'] = true;
$config->teambug->datatable->fieldList['openedBy']['show']     = true;

$config->teambug->datatable->fieldList['openedDate']['title']    = 'openedDateAB';
$config->teambug->datatable->fieldList['openedDate']['type']     = 'html';
$config->teambug->datatable->fieldList['openedDate']['fixed']    = 'no';
$config->teambug->datatable->fieldList['openedDate']['width']    = '90';
$config->teambug->datatable->fieldList['openedDate']['sortType'] = true;
$config->teambug->datatable->fieldList['openedDate']['show']     = true;

$config->teambug->datatable->fieldList['openedBuild']['title']      = 'openedBuild';
$config->teambug->datatable->fieldList['openedBuild']['type']       = 'html';
$config->teambug->datatable->fieldList['openedBuild']['fixed']      = 'no';
$config->teambug->datatable->fieldList['openedBuild']['width']      = '120';
$config->teambug->datatable->fieldList['openedBuild']['sortType']   = true;
$config->teambug->datatable->fieldList['openedBuild']['control']    = 'multiple';
$config->teambug->datatable->fieldList['openedBuild']['dataSource'] = array('module' => 'build', 'method' =>'getBuildPairs', 'params' => '$productID&$branch&noempty,noterminate,nodone,withbranch');

$config->teambug->datatable->fieldList['assignedTo']['title']      = 'assignedTo';
$config->teambug->datatable->fieldList['assignedTo']['type']       = 'html';
$config->teambug->datatable->fieldList['assignedTo']['fixed']      = 'no';
$config->teambug->datatable->fieldList['assignedTo']['width']      = '90';
$config->teambug->datatable->fieldList['assignedTo']['sortType']   = true;
$config->teambug->datatable->fieldList['assignedTo']['show']       = true;
$config->teambug->datatable->fieldList['assignedTo']['dataSource'] = array('module' => 'user', 'method' =>'getPairs', 'params' => 'noclosed|noletter');

$config->teambug->datatable->fieldList['assignedDate']['title']    = 'assignedDate';
$config->teambug->datatable->fieldList['assignedDate']['type']     = 'html';
$config->teambug->datatable->fieldList['assignedDate']['fixed']    = 'no';
$config->teambug->datatable->fieldList['assignedDate']['width']    = '90';
$config->teambug->datatable->fieldList['assignedDate']['sortType'] = true;

$config->teambug->datatable->fieldList['deadline']['title']    = 'deadline';
$config->teambug->datatable->fieldList['deadline']['type']     = 'html';
$config->teambug->datatable->fieldList['deadline']['fixed']    = 'no';
$config->teambug->datatable->fieldList['deadline']['width']    = '90';
$config->teambug->datatable->fieldList['deadline']['sortType'] = true;

$config->teambug->datatable->fieldList['resolvedBy']['title']    = 'resolvedBy';
$config->teambug->datatable->fieldList['resolvedBy']['type']     = 'html';
$config->teambug->datatable->fieldList['resolvedBy']['fixed']    = 'no';
$config->teambug->datatable->fieldList['resolvedBy']['width']    = '100';
$config->teambug->datatable->fieldList['resolvedBy']['sortType'] = true;

$config->teambug->datatable->fieldList['resolution']['title']    = 'resolutionAB';
$config->teambug->datatable->fieldList['resolution']['type']     = 'html';
$config->teambug->datatable->fieldList['resolution']['fixed']    = 'no';
$config->teambug->datatable->fieldList['resolution']['width']    = '110';
$config->teambug->datatable->fieldList['resolution']['sortType'] = true;
$config->teambug->datatable->fieldList['resolution']['show']     = true;

$config->teambug->datatable->fieldList['resolvedDate']['title']    = 'resolvedDateAB';
$config->teambug->datatable->fieldList['resolvedDate']['type']     = 'html';
$config->teambug->datatable->fieldList['resolvedDate']['fixed']    = 'no';
$config->teambug->datatable->fieldList['resolvedDate']['width']    = '120';
$config->teambug->datatable->fieldList['resolvedDate']['sortType'] = true;

$config->teambug->datatable->fieldList['resolvedBuild']['title']      = 'resolvedBuild';
$config->teambug->datatable->fieldList['resolvedBuild']['type']       = 'html';
$config->teambug->datatable->fieldList['resolvedBuild']['fixed']      = 'no';
$config->teambug->datatable->fieldList['resolvedBuild']['width']      = '120';
$config->teambug->datatable->fieldList['resolvedBuild']['sortType']   = true;
$config->teambug->datatable->fieldList['resolvedBuild']['control']    = 'select';
$config->teambug->datatable->fieldList['resolvedBuild']['dataSource'] = array('module' => 'bug', 'method' =>'getRelatedObjects', 'params' => 'resolvedBuild&id,name');

$config->teambug->datatable->fieldList['activatedCount']['title']    = 'activatedCountAB';
$config->teambug->datatable->fieldList['activatedCount']['type']     = 'html';
$config->teambug->datatable->fieldList['activatedCount']['fixed']    = 'no';
$config->teambug->datatable->fieldList['activatedCount']['width']    = '80';
$config->teambug->datatable->fieldList['activatedCount']['sortType'] = true;

$config->teambug->datatable->fieldList['activatedDate']['title']    = 'activatedDate';
$config->teambug->datatable->fieldList['activatedDate']['type']     = 'html';
$config->teambug->datatable->fieldList['activatedDate']['fixed']    = 'no';
$config->teambug->datatable->fieldList['activatedDate']['width']    = '90';
$config->teambug->datatable->fieldList['activatedDate']['sortType'] = true;

$config->teambug->datatable->fieldList['story']['title']      = 'story';
$config->teambug->datatable->fieldList['story']['type']       = 'html';
$config->teambug->datatable->fieldList['story']['fixed']      = 'no';
$config->teambug->datatable->fieldList['story']['width']      = '120';
$config->teambug->datatable->fieldList['story']['sortType']   = true;
$config->teambug->datatable->fieldList['story']['control']    = 'select';
$config->teambug->datatable->fieldList['story']['dataSource'] = array('module' => 'story', 'method' =>'getProductStoryPairs', 'params' => '$productID');

$config->teambug->datatable->fieldList['task']['title']      = 'task';
$config->teambug->datatable->fieldList['task']['type']       = 'html';
$config->teambug->datatable->fieldList['task']['fixed']      = 'no';
$config->teambug->datatable->fieldList['task']['width']      = '120';
$config->teambug->datatable->fieldList['task']['sortType']   = true;
$config->teambug->datatable->fieldList['task']['dataSource'] = array('module' => 'bug', 'method' =>'getRelatedObjects', 'params' => 'task&id,name');

$config->teambug->datatable->fieldList['toTask']['title']    = 'toTask';
$config->teambug->datatable->fieldList['toTask']['type']     = 'html';
$config->teambug->datatable->fieldList['toTask']['fixed']    = 'no';
$config->teambug->datatable->fieldList['toTask']['width']    = '120';
$config->teambug->datatable->fieldList['toTask']['sortType'] = true;

$config->teambug->datatable->fieldList['keywords']['title']    = 'keywords';
$config->teambug->datatable->fieldList['keywords']['type']     = 'html';
$config->teambug->datatable->fieldList['keywords']['fixed']    = 'no';
$config->teambug->datatable->fieldList['keywords']['width']    = '100';
$config->teambug->datatable->fieldList['keywords']['sortType'] = true;

$config->teambug->datatable->fieldList['os']['title']    = 'os';
$config->teambug->datatable->fieldList['os']['type']     = 'html';
$config->teambug->datatable->fieldList['os']['fixed']    = 'no';
$config->teambug->datatable->fieldList['os']['width']    = '80';
$config->teambug->datatable->fieldList['os']['sortType'] = true;
$config->teambug->datatable->fieldList['os']['control']  = 'multiple';

$config->teambug->datatable->fieldList['browser']['title']    = 'browser';
$config->teambug->datatable->fieldList['browser']['type']     = 'html';
$config->teambug->datatable->fieldList['browser']['fixed']    = 'no';
$config->teambug->datatable->fieldList['browser']['width']    = '80';
$config->teambug->datatable->fieldList['browser']['sortType'] = true;
$config->teambug->datatable->fieldList['browser']['control']  = 'multiple';

$config->teambug->datatable->fieldList['mailto']['title']    = 'mailto';
$config->teambug->datatable->fieldList['mailto']['type']     = 'html';
$config->teambug->datatable->fieldList['mailto']['fixed']    = 'no';
$config->teambug->datatable->fieldList['mailto']['width']    = '100';
$config->teambug->datatable->fieldList['mailto']['sortType'] = true;

$config->teambug->datatable->fieldList['closedBy']['title']    = 'closedBy';
$config->teambug->datatable->fieldList['closedBy']['type']     = 'html';
$config->teambug->datatable->fieldList['closedBy']['fixed']    = 'no';
$config->teambug->datatable->fieldList['closedBy']['width']    = '80';
$config->teambug->datatable->fieldList['closedBy']['sortType'] = true;

$config->teambug->datatable->fieldList['closedDate']['title']    = 'closedDate';
$config->teambug->datatable->fieldList['closedDate']['type']     = 'html';
$config->teambug->datatable->fieldList['closedDate']['fixed']    = 'no';
$config->teambug->datatable->fieldList['closedDate']['width']    = '90';
$config->teambug->datatable->fieldList['closedDate']['sortType'] = true;

$config->teambug->datatable->fieldList['lastEditedBy']['title']    = 'lastEditedBy';
$config->teambug->datatable->fieldList['lastEditedBy']['type']     = 'html';
$config->teambug->datatable->fieldList['lastEditedBy']['fixed']    = 'no';
$config->teambug->datatable->fieldList['lastEditedBy']['width']    = '90';
$config->teambug->datatable->fieldList['lastEditedBy']['sortType'] = true;

$config->teambug->datatable->fieldList['lastEditedDate']['title']    = 'lastEditedDateAB';
$config->teambug->datatable->fieldList['lastEditedDate']['type']     = 'html';
$config->teambug->datatable->fieldList['lastEditedDate']['fixed']    = 'no';
$config->teambug->datatable->fieldList['lastEditedDate']['width']    = '90';
$config->teambug->datatable->fieldList['lastEditedDate']['sortType'] = true;

$config->teambug->datatable->fieldList['steps']['title']   = 'steps';
$config->teambug->datatable->fieldList['steps']['control'] = 'textarea';
$config->teambug->datatable->fieldList['steps']['display'] = false; // The configuration of datatable also used to export data, the false value means the field won't display in datatable.

$config->teambug->datatable->fieldList['case']['title']      = 'case';
$config->teambug->datatable->fieldList['case']['dataSource'] = array('module' => 'bug', 'method' =>'getRelatedObjects', 'params' => 'case&id,title');
$config->teambug->datatable->fieldList['case']['display']    = false; // The configuration of datatable also used to export data, the false value means the field won't display in datatable.

$config->teambug->datatable->fieldList['actions']['title']    = 'actions';
$config->teambug->datatable->fieldList['actions']['type']     = 'html';
$config->teambug->datatable->fieldList['actions']['width']    = '150';
$config->teambug->datatable->fieldList['actions']['fixed']    = 'right';
$config->teambug->datatable->fieldList['actions']['required'] = 'yes';
