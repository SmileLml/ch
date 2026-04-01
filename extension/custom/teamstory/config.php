<?php
global $lang, $app;
$config->teamstory->datatable = new stdclass();

$config->teamstory->datatable->defaultField = array('id', 'title', 'pri', 'project', 'plan', 'status', 'estimate', 'reviewedBy', 'stage', 'assignedTo', 'openedBy', 'openedDate', 'actions');

$config->teamstory->datatable->fieldList['id']['title']    = 'idAB';
$config->teamstory->datatable->fieldList['id']['fixed']    = 'left';
$config->teamstory->datatable->fieldList['id']['width']    = '70';
$config->teamstory->datatable->fieldList['id']['required'] = 'yes';
$config->teamstory->datatable->fieldList['id']['type']     = 'checkID';
$config->teamstory->datatable->fieldList['id']['sortType'] = true;
$config->teamstory->datatable->fieldList['id']['checkbox'] = true;

$config->teamstory->datatable->fieldList['title']['title']        = 'title';
$config->teamstory->datatable->fieldList['title']['width']        = 'auto';
$config->teamstory->datatable->fieldList['title']['required']     = 'yes';
$config->teamstory->datatable->fieldList['title']['type']         = 'html';
$config->teamstory->datatable->fieldList['title']['fixed']        = 'left';
$config->teamstory->datatable->fieldList['title']['sortType']     = true;
$config->teamstory->datatable->fieldList['title']['nestedToggle'] = true;
$config->teamstory->datatable->fieldList['title']['iconRender']   = true;

$config->teamstory->datatable->fieldList['pri']['title']    = 'priAB';
$config->teamstory->datatable->fieldList['pri']['fixed']    = 'left';
$config->teamstory->datatable->fieldList['pri']['type']     = 'html';
$config->teamstory->datatable->fieldList['pri']['sortType'] = true;
$config->teamstory->datatable->fieldList['pri']['width']    = '45';
$config->teamstory->datatable->fieldList['pri']['required'] = 'no';
$config->teamstory->datatable->fieldList['pri']['name']     = $this->lang->story->pri;

$config->teamstory->datatable->fieldList['project']['title']    = 'projectName';
$config->teamstory->datatable->fieldList['project']['type']     = 'html';
$config->teamstory->datatable->fieldList['project']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['project']['width']    = '120';
$config->teamstory->datatable->fieldList['project']['sortType'] = true;
$config->teamstory->datatable->fieldList['project']['required'] = 'no';

$config->teamstory->datatable->fieldList['plan']['title']      = 'planAB';
$config->teamstory->datatable->fieldList['plan']['fixed']      = 'no';
$config->teamstory->datatable->fieldList['plan']['width']      = '90';
$config->teamstory->datatable->fieldList['plan']['type']       = 'html';
$config->teamstory->datatable->fieldList['plan']['sortType']   = true;
$config->teamstory->datatable->fieldList['plan']['required']   = 'no';
$config->teamstory->datatable->fieldList['plan']['control']    = 'select';
$config->teamstory->datatable->fieldList['plan']['dataSource'] = array('module' => 'productplan', 'method' => 'getPairs', 'params' => '$productID');

$config->teamstory->datatable->fieldList['status']['title']    = 'statusAB';
$config->teamstory->datatable->fieldList['status']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['status']['width']    = '70';
$config->teamstory->datatable->fieldList['status']['type']     = 'html';
$config->teamstory->datatable->fieldList['status']['sortType'] = true;
$config->teamstory->datatable->fieldList['status']['required'] = 'no';

$config->teamstory->datatable->fieldList['openedBy']['title']    = 'openedByAB';
$config->teamstory->datatable->fieldList['openedBy']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['openedBy']['type']     = 'user';
$config->teamstory->datatable->fieldList['openedBy']['sortType'] = true;
$config->teamstory->datatable->fieldList['openedBy']['width']    = '70';
$config->teamstory->datatable->fieldList['openedBy']['required'] = 'no';

$config->teamstory->datatable->fieldList['estimate']['title']    = 'estimateAB';
$config->teamstory->datatable->fieldList['estimate']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['estimate']['sortType'] = true;
$config->teamstory->datatable->fieldList['estimate']['width']    = '50';
$config->teamstory->datatable->fieldList['estimate']['required'] = 'no';

$config->teamstory->datatable->fieldList['reviewer']['title']      = 'reviewer';
$config->teamstory->datatable->fieldList['reviewer']['control']    = 'multiple';
$config->teamstory->datatable->fieldList['reviewer']['display']    = false;
$config->teamstory->datatable->fieldList['reviewer']['dataSource'] = array('module' => 'story', 'method' => 'getStoriesReviewer', 'params' => '$productID');

$config->teamstory->datatable->fieldList['reviewedBy']['title']      = 'reviewedBy';
$config->teamstory->datatable->fieldList['reviewedBy']['fixed']      = 'no';
$config->teamstory->datatable->fieldList['reviewedBy']['type']       = 'user';
$config->teamstory->datatable->fieldList['reviewedBy']['sortType']   = true;
$config->teamstory->datatable->fieldList['reviewedBy']['width']      = '100';
$config->teamstory->datatable->fieldList['reviewedBy']['required']   = 'no';
$config->teamstory->datatable->fieldList['reviewedBy']['control']    = 'multiple';

$config->teamstory->datatable->fieldList['reviewedDate']['title']    = 'reviewedDate';
$config->teamstory->datatable->fieldList['reviewedDate']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['reviewedDate']['type']     = 'html';
$config->teamstory->datatable->fieldList['reviewedDate']['sortType'] = true;
$config->teamstory->datatable->fieldList['reviewedDate']['width']    = '95';
$config->teamstory->datatable->fieldList['reviewedDate']['required'] = 'no';

$config->teamstory->datatable->fieldList['stage']['title']    = 'stageAB';
$config->teamstory->datatable->fieldList['stage']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['stage']['type']     = 'html';
$config->teamstory->datatable->fieldList['stage']['sortType'] = true;
$config->teamstory->datatable->fieldList['stage']['width']    = '85';
$config->teamstory->datatable->fieldList['stage']['required'] = 'no';

$config->teamstory->datatable->fieldList['assignedTo']['title']    = 'assignedTo';
$config->teamstory->datatable->fieldList['assignedTo']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['assignedTo']['type']     = 'html';
$config->teamstory->datatable->fieldList['assignedTo']['sortType'] = true;
$config->teamstory->datatable->fieldList['assignedTo']['width']    = '90';
$config->teamstory->datatable->fieldList['assignedTo']['required'] = 'no';

$config->teamstory->datatable->fieldList['assignedDate']['title']    = 'assignedDate';
$config->teamstory->datatable->fieldList['assignedDate']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['assignedDate']['type']     = 'html';
$config->teamstory->datatable->fieldList['assignedDate']['sortType'] = true;
$config->teamstory->datatable->fieldList['assignedDate']['width']    = '95';
$config->teamstory->datatable->fieldList['assignedDate']['required'] = 'no';

$config->teamstory->datatable->fieldList['product']['title']      = 'product';
$config->teamstory->datatable->fieldList['product']['control']    = 'hidden';
$config->teamstory->datatable->fieldList['product']['display']    = false;
$config->teamstory->datatable->fieldList['product']['dataSource'] = array('module' => 'transfer', 'method' => 'getRelatedObjects', 'params' => 'story&product&id,name');

$config->teamstory->datatable->fieldList['branch']['title']      = 'branch';
$config->teamstory->datatable->fieldList['branch']['control']    = 'select';
$config->teamstory->datatable->fieldList['branch']['display']    = false;
$config->teamstory->datatable->fieldList['branch']['dataSource'] = array('module' => 'branch', 'method' => 'getPairs', 'params' => '$productID&active');

$config->teamstory->datatable->fieldList['module']['title']      = 'module';
$config->teamstory->datatable->fieldList['module']['control']    = 'select';
$config->teamstory->datatable->fieldList['module']['display']    = false;
$config->teamstory->datatable->fieldList['module']['dataSource'] = array('module' => 'tree', 'method' => 'getOptionMenu', 'params' => '$productID&story&0&all');

$config->teamstory->datatable->fieldList['keywords']['title']    = 'keywords';
$config->teamstory->datatable->fieldList['keywords']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['keywords']['type']     = 'html';
$config->teamstory->datatable->fieldList['keywords']['sortType'] = true;
$config->teamstory->datatable->fieldList['keywords']['width']    = '100';
$config->teamstory->datatable->fieldList['keywords']['required'] = 'no';

$config->teamstory->datatable->fieldList['source']['title']    = 'source';
$config->teamstory->datatable->fieldList['source']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['source']['type']     = 'html';
$config->teamstory->datatable->fieldList['source']['sortType'] = true;
$config->teamstory->datatable->fieldList['source']['width']    = '90';
$config->teamstory->datatable->fieldList['source']['required'] = 'no';

$config->teamstory->datatable->fieldList['sourceNote']['title']    = 'sourceNote';
$config->teamstory->datatable->fieldList['sourceNote']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['sourceNote']['type']     = 'html';
$config->teamstory->datatable->fieldList['sourceNote']['sortType'] = true;
$config->teamstory->datatable->fieldList['sourceNote']['width']    = '90';
$config->teamstory->datatable->fieldList['sourceNote']['required'] = 'no';

$config->teamstory->datatable->fieldList['category']['title']    = 'category';
$config->teamstory->datatable->fieldList['category']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['category']['type']     = 'html';
$config->teamstory->datatable->fieldList['category']['sortType'] = true;
$config->teamstory->datatable->fieldList['category']['width']    = '60';
$config->teamstory->datatable->fieldList['category']['required'] = 'no';

$config->teamstory->datatable->fieldList['openedDate']['title']    = 'openedDate';
$config->teamstory->datatable->fieldList['openedDate']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['openedDate']['type']     = 'html';
$config->teamstory->datatable->fieldList['openedDate']['sortType'] = true;
$config->teamstory->datatable->fieldList['openedDate']['width']    = '95';
$config->teamstory->datatable->fieldList['openedDate']['required'] = 'no';

$config->teamstory->datatable->fieldList['closedBy']['title']    = 'closedBy';
$config->teamstory->datatable->fieldList['closedBy']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['closedBy']['type']     = 'user';
$config->teamstory->datatable->fieldList['closedBy']['sortType'] = true;
$config->teamstory->datatable->fieldList['closedBy']['width']    = '80';
$config->teamstory->datatable->fieldList['closedBy']['required'] = 'no';

$config->teamstory->datatable->fieldList['closedDate']['title']    = 'closedDate';
$config->teamstory->datatable->fieldList['closedDate']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['closedDate']['type']     = 'html';
$config->teamstory->datatable->fieldList['closedDate']['sortType'] = true;
$config->teamstory->datatable->fieldList['closedDate']['width']    = '95';
$config->teamstory->datatable->fieldList['closedDate']['required'] = 'no';

$config->teamstory->datatable->fieldList['closedReason']['title']    = 'closedReason';
$config->teamstory->datatable->fieldList['closedReason']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['closedReason']['type']     = 'html';
$config->teamstory->datatable->fieldList['closedReason']['sortType'] = true;
$config->teamstory->datatable->fieldList['closedReason']['width']    = '90';
$config->teamstory->datatable->fieldList['closedReason']['required'] = 'no';

$config->teamstory->datatable->fieldList['lastEditedBy']['title']    = 'lastEditedBy';
$config->teamstory->datatable->fieldList['lastEditedBy']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['lastEditedBy']['type']     = 'user';
$config->teamstory->datatable->fieldList['lastEditedBy']['sortType'] = true;
$config->teamstory->datatable->fieldList['lastEditedBy']['width']    = '80';
$config->teamstory->datatable->fieldList['lastEditedBy']['required'] = 'no';

$config->teamstory->datatable->fieldList['lastEditedDate']['title']    = 'lastEditedDate';
$config->teamstory->datatable->fieldList['lastEditedDate']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['lastEditedDate']['type']     = 'html';
$config->teamstory->datatable->fieldList['lastEditedDate']['sortType'] = true;
$config->teamstory->datatable->fieldList['lastEditedDate']['width']    = '110';
$config->teamstory->datatable->fieldList['lastEditedDate']['required'] = 'no';

$config->teamstory->datatable->fieldList['activatedDate']['title']    = 'activatedDate';
$config->teamstory->datatable->fieldList['activatedDate']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['activatedDate']['type']     = 'html';
$config->teamstory->datatable->fieldList['activatedDate']['sortType'] = true;
$config->teamstory->datatable->fieldList['activatedDate']['width']    = '95';
$config->teamstory->datatable->fieldList['activatedDate']['required'] = 'no';

$config->teamstory->datatable->fieldList['feedbackBy']['title']    = 'feedbackBy';
$config->teamstory->datatable->fieldList['feedbackBy']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['feedbackBy']['type']     = 'user';
$config->teamstory->datatable->fieldList['feedbackBy']['width']    = '100';
$config->teamstory->datatable->fieldList['feedbackBy']['required'] = 'no';

$config->teamstory->datatable->fieldList['notifyEmail']['title']    = 'notifyEmail';
$config->teamstory->datatable->fieldList['notifyEmail']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['notifyEmail']['type']     = 'html';
$config->teamstory->datatable->fieldList['notifyEmail']['width']    = '100';
$config->teamstory->datatable->fieldList['notifyEmail']['required'] = 'no';

$config->teamstory->datatable->fieldList['mailto']['title']    = 'mailto';
$config->teamstory->datatable->fieldList['mailto']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['mailto']['type']     = 'html';
$config->teamstory->datatable->fieldList['mailto']['sortType'] = true;
$config->teamstory->datatable->fieldList['mailto']['width']    = '100';
$config->teamstory->datatable->fieldList['mailto']['required'] = 'no';

$config->teamstory->datatable->fieldList['version']['title']    = 'version';
$config->teamstory->datatable->fieldList['version']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['version']['type']     = 'html';
$config->teamstory->datatable->fieldList['version']['sortType'] = true;
$config->teamstory->datatable->fieldList['version']['width']    = '70';
$config->teamstory->datatable->fieldList['version']['required'] = 'no';

$config->teamstory->datatable->fieldList['taskCount']['title']    = 'T';
$config->teamstory->datatable->fieldList['taskCount']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['taskCount']['width']    = '30';
$config->teamstory->datatable->fieldList['taskCount']['type']     = 'html';
$config->teamstory->datatable->fieldList['taskCount']['required'] = 'no';
$config->teamstory->datatable->fieldList['taskCount']['name']     = $lang->story->taskCount;
$config->teamstory->datatable->fieldList['taskCount']['sort']     = 'no';

$config->teamstory->datatable->fieldList['bugCount']['title']    = 'B';
$config->teamstory->datatable->fieldList['bugCount']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['bugCount']['width']    = '30';
$config->teamstory->datatable->fieldList['bugCount']['required'] = 'no';
$config->teamstory->datatable->fieldList['bugCount']['type']     = 'html';
$config->teamstory->datatable->fieldList['bugCount']['name']     = $lang->story->bugCount;
$config->teamstory->datatable->fieldList['bugCount']['sort']     = 'no';

$config->teamstory->datatable->fieldList['caseCount']['title']    = 'C';
$config->teamstory->datatable->fieldList['caseCount']['fixed']    = 'no';
$config->teamstory->datatable->fieldList['caseCount']['width']    = '30';
$config->teamstory->datatable->fieldList['caseCount']['required'] = 'no';
$config->teamstory->datatable->fieldList['caseCount']['type']     = 'html';
$config->teamstory->datatable->fieldList['caseCount']['name']     = $lang->story->caseCount;
$config->teamstory->datatable->fieldList['caseCount']['sort']     = 'no';

$config->teamstory->datatable->fieldList['actions']['title']    = 'actions';
$config->teamstory->datatable->fieldList['actions']['type']     = 'html';
$config->teamstory->datatable->fieldList['actions']['fixed']    = 'right';
$config->teamstory->datatable->fieldList['actions']['width']    = '180';
$config->teamstory->datatable->fieldList['actions']['required'] = 'yes';
