<?php
$config->demand = new stdclass();
$config->demand->create      = new stdclass();
$config->demand->edit        = new stdclass();
$config->demand->review      = new stdclass();
$config->demand->integration = new stdclass();

$config->demand->create->requiredFields    = 'name,businessDesc,businessObjective,severity,deadline,desc';
$config->demand->edit->requiredFields      = $config->demand->create->requiredFields;
$config->demand->review->requiredFields    = 'result';
$config->demand->integration->syncedFields = 'desc,businessDesc,businessObjective';

$config->demand->editor = new stdclass();
$config->demand->editor->create    = array('id' => 'desc,businessDesc,businessObjective', 'tools' => 'simpleTools');
$config->demand->editor->edit      = array('id' => 'desc,businessDesc,businessObjective', 'tools' => 'simpleTools');
$config->demand->editor->view      = array('id' => 'comment', 'tools' => 'simpleTools');
$config->demand->editor->review    = array('id' => 'comment', 'tools' => 'simpleTools');
$config->demand->editor->tostory   = array('id' => 'spec,verify', 'tools' => 'simpleTools');
$config->demand->editor->assignto  = array('id' => 'comment', 'tools' => 'simpleTools');
$config->demand->editor->submit    = array('id' => 'comment', 'tools' => 'simpleTools');
$config->demand->editor->close     = array('id' => 'comment', 'tools' => 'simpleTools');

$config->demand->export = new stdclass();
$config->demand->import = new stdclass();
$config->demand->export->listFields     = explode(',', "severity,status,project,dept");
$config->demand->listFields             = "severity,status,project,dept,stage";
$config->demand->export->templateFields = explode(',', "severity,project,dept,name,deadline,businessDesc,desc,businessObjective");
$config->demand->templateFields         = "project,dept,name,deadline,businessDesc,desc,businessObjective";

$config->demand->list = new stdclass();
$config->demand->list->exportFields = 'id, stage, project, dept, name, status, deadline, businessDesc, desc, businessObjective, createdBy, createdDate, stage, demandSource';

/* Search. */
global $lang;
$config->demand->search['module'] = 'demand';
$config->demand->search['fields']['id']                = $lang->demand->id;
//$config->demand->search['fields']['pri']               = $lang->demand->pri;
$config->demand->search['fields']['severity']          = $lang->demand->severity;
$config->demand->search['fields']['name']              = $lang->demand->name;
//$config->demand->search['fields']['assignedTo']        = $lang->demand->assignedTo;
$config->demand->search['fields']['status']            = $lang->demand->status;
$config->demand->search['fields']['pool']              = $lang->demand->demand;
$config->demand->search['fields']['deadline']          = $lang->demand->deadline;
$config->demand->search['fields']['createdBy']         = $lang->demand->createdBy;
$config->demand->search['fields']['createdDate']       = $lang->demand->createdDate;
$config->demand->search['fields']['mailto']            = $lang->demand->mailto;
$config->demand->search['fields']['desc']              = $lang->demand->desc;
$config->demand->search['fields']['businessDesc']      = $lang->demand->businessDesc;
$config->demand->search['fields']['businessObjective'] = $lang->demand->businessObjective;
$config->demand->search['fields']['project']           = $lang->demand->project;
$config->demand->search['fields']['dept']              = $lang->demand->dept;
$config->demand->search['fields']['stage']             = $lang->demand->stage;

$config->demand->search['params']['id']                = array('operator' => '=', 'control' => 'input', 'values' => '');
//$config->demand->search['params']['pri']               = array('operator' => '=', 'control' => 'select',  'values' => $lang->demand->priList);
$config->demand->search['params']['severity']          = array('operator' => '=', 'control' => 'select',  'values' => $lang->demand->severityList);
$config->demand->search['params']['name']              = array('operator' => 'include', 'control' => 'input',  'values' => '');
$config->demand->search['params']['status']            = array('operator' => '=', 'control' => 'select', 'values' => $lang->demand->statusList);
$config->demand->search['params']['pool']              = array('operator' => '=', 'control' => 'select', 'values' => '');
//$config->demand->search['params']['assignedTo']        = array('operator' => '=', 'control' => 'select', 'values' => 'users');
$config->demand->search['params']['deadline']          = array('operator' => '=', 'control' => 'input', 'class' => 'date', 'values' => '');
$config->demand->search['params']['createdBy']         = array('operator' => '=', 'control' => 'select', 'values' => 'users');
$config->demand->search['params']['createdDate']       = array('operator' => '=', 'control' => 'input', 'values' => '', 'class' => 'date');
$config->demand->search['params']['feedbackBy']        = array('operator' => 'include', 'control' => 'input', 'values' => '');
$config->demand->search['params']['desc']              = array('operator' => 'include', 'control' => 'input', 'values' => '');
$config->demand->search['params']['businessDesc']      = array('operator' => 'include', 'control' => 'input', 'values' => '');
$config->demand->search['params']['businessObjective'] = array('operator' => 'include', 'control' => 'input', 'values' => '');
$config->demand->search['params']['project']           = array('operator' => '=', 'control' => 'select', 'values' => '');
$config->demand->search['params']['dept']              = array('operator' => 'include', 'control' => 'select', 'values' => '');
$config->demand->search['params']['stage']             = array('operator' => '=', 'control' => 'select', 'values' => $lang->demand->stageList);

$config->demand->datatable = new stdclass();
$config->demand->datatable->defaultField = array('id', 'name', 'stage', 'status', 'project', 'businessUnit', 'dept', 'createdBy', 'createdDate', 'actions');

$config->demand->datatable->fieldList['id']['title']    = 'idAB';
$config->demand->datatable->fieldList['id']['fixed']    = 'left';
$config->demand->datatable->fieldList['id']['width']    = '70';
$config->demand->datatable->fieldList['id']['required'] = 'yes';

$config->demand->datatable->fieldList['severity']['title']    = 'severity';
$config->demand->datatable->fieldList['severity']['fixed']    = 'left';
$config->demand->datatable->fieldList['severity']['width']    = '120';
$config->demand->datatable->fieldList['severity']['required'] = 'no';

//$config->demand->datatable->fieldList['pri']['title']    = 'priAB';
//$config->demand->datatable->fieldList['pri']['fixed']    = 'left';
//$config->demand->datatable->fieldList['pri']['width']    = '70';
//$config->demand->datatable->fieldList['pri']['required'] = 'no';

$config->demand->datatable->fieldList['name']['title']    = 'name';
$config->demand->datatable->fieldList['name']['fixed']    = 'left';
$config->demand->datatable->fieldList['name']['width']    = 'auto';
$config->demand->datatable->fieldList['name']['required'] = 'yes';

$config->demand->datatable->fieldList['stage']['title']    = 'stage';
$config->demand->datatable->fieldList['stage']['fixed']    = 'left';
$config->demand->datatable->fieldList['stage']['width']    = '120';
$config->demand->datatable->fieldList['stage']['required'] = 'no';

$config->demand->datatable->fieldList['project']['title']    = 'project';
$config->demand->datatable->fieldList['project']['fixed']    = 'left';
$config->demand->datatable->fieldList['project']['width']    = '150';
$config->demand->datatable->fieldList['project']['required'] = 'no';

$config->demand->datatable->fieldList['dept']['title']    = 'dept';
$config->demand->datatable->fieldList['dept']['fixed']    = 'left';
$config->demand->datatable->fieldList['dept']['width']    = '150';
$config->demand->datatable->fieldList['dept']['required'] = 'no';

$config->demand->datatable->fieldList['status']['title']    = 'status';
$config->demand->datatable->fieldList['status']['fixed']    = 'no';
$config->demand->datatable->fieldList['status']['width']    = '100';
$config->demand->datatable->fieldList['status']['required'] = 'no';

$config->demand->datatable->fieldList['deadline']['title']    = 'deadline';
$config->demand->datatable->fieldList['deadline']['fixed']    = 'no';
$config->demand->datatable->fieldList['deadline']['width']    = '120';
$config->demand->datatable->fieldList['deadline']['required'] = 'no';

//$config->demand->datatable->fieldList['assignedTo']['title']    = 'assignedTo';
//$config->demand->datatable->fieldList['assignedTo']['fixed']    = 'no';
//$config->demand->datatable->fieldList['assignedTo']['width']    = '120';
//$config->demand->datatable->fieldList['assignedTo']['required'] = 'no';

//$config->demand->datatable->fieldList['reviewer']['title']    = 'reviewer';
//$config->demand->datatable->fieldList['reviewer']['fixed']    = 'no';
//$config->demand->datatable->fieldList['reviewer']['width']    = '120';
//$config->demand->datatable->fieldList['reviewer']['required'] = 'no';

$config->demand->datatable->fieldList['createdBy']['title']    = 'createdBy';
$config->demand->datatable->fieldList['createdBy']['fixed']    = 'no';
$config->demand->datatable->fieldList['createdBy']['width']    = '120';
$config->demand->datatable->fieldList['createdBy']['required'] = 'no';

$config->demand->datatable->fieldList['createdDate']['title']    = 'createdDate';
$config->demand->datatable->fieldList['createdDate']['fixed']    = 'no';
$config->demand->datatable->fieldList['createdDate']['width']    = '120';
$config->demand->datatable->fieldList['createdDate']['required'] = 'no';

$config->demand->datatable->fieldList['actions']['title']    = 'actions';
$config->demand->datatable->fieldList['actions']['fixed']    = 'right';
$config->demand->datatable->fieldList['actions']['width']    = '125';
$config->demand->datatable->fieldList['actions']['required'] = 'yes';
