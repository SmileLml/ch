<?php
$config->chteam->create = new stdclass();
$config->chteam->create->requiredFields = 'name';

$config->chteam->edit = new stdclass();
$config->chteam->edit->requiredFields = 'name';

$config->chteam->editor = new stdclass();
$config->chteam->editor->create     = array('id' => 'desc', 'tools' => 'chteamTools');
$config->chteam->editor->edit       = array('id' => 'desc', 'tools' => 'chteamTools');

global $lang;
$config->chteam->datatable = new stdclass();
$config->chteam->datatable->defaultField = array('id', 'name', 'leader', 'members', 'desc', 'createdBy', 'createdDate', 'actions');

$config->chteam->datatable->fieldList['id']['title']    = 'ID';
$config->chteam->datatable->fieldList['id']['fixed']    = 'left';
$config->chteam->datatable->fieldList['id']['width']    = '60';
$config->chteam->datatable->fieldList['id']['required'] = 'yes';
$config->chteam->datatable->fieldList['id']['pri']      = '1';

$config->chteam->datatable->fieldList['name']['title']    = 'name';
$config->chteam->datatable->fieldList['name']['fixed']    = 'left';
$config->chteam->datatable->fieldList['name']['width']    = '180';
$config->chteam->datatable->fieldList['name']['required'] = 'yes';
$config->chteam->datatable->fieldList['name']['sort']     = 'yes';
$config->chteam->datatable->fieldList['name']['pri']      = '1';

$config->chteam->datatable->fieldList['leader']['title']    = 'leader';
$config->chteam->datatable->fieldList['leader']['fixed']    = 'left';
$config->chteam->datatable->fieldList['leader']['width']    = '100';
$config->chteam->datatable->fieldList['leader']['required'] = 'yes';
$config->chteam->datatable->fieldList['leader']['sort']     = 'yes';
$config->chteam->datatable->fieldList['leader']['pri']      = '1';

$config->chteam->datatable->fieldList['members']['title']    = 'members';
$config->chteam->datatable->fieldList['members']['fixed']    = 'left';
$config->chteam->datatable->fieldList['members']['width']    = '300';
$config->chteam->datatable->fieldList['members']['minWidth'] = '180';
$config->chteam->datatable->fieldList['members']['required'] = 'yes';
$config->chteam->datatable->fieldList['members']['sort']     = 'no';
$config->chteam->datatable->fieldList['members']['pri']      = '1';

$config->chteam->datatable->fieldList['desc']['title']    = 'desc';
$config->chteam->datatable->fieldList['desc']['fixed']    = 'left';
$config->chteam->datatable->fieldList['desc']['width']    = 'auto';
$config->chteam->datatable->fieldList['desc']['minWidth'] = '180';
$config->chteam->datatable->fieldList['desc']['required'] = 'yes';
$config->chteam->datatable->fieldList['desc']['sort']     = 'no';
$config->chteam->datatable->fieldList['desc']['pri']      = '1';

$config->chteam->datatable->fieldList['createdBy']['title']    = 'createdBy';
$config->chteam->datatable->fieldList['createdBy']['fixed']    = 'left';
$config->chteam->datatable->fieldList['createdBy']['width']    = '100';
$config->chteam->datatable->fieldList['createdBy']['required'] = 'yes';
$config->chteam->datatable->fieldList['createdBy']['sort']     = 'yes';
$config->chteam->datatable->fieldList['createdBy']['pri']      = '1';

$config->chteam->datatable->fieldList['createdDate']['title']    = 'createdDate';
$config->chteam->datatable->fieldList['createdDate']['fixed']    = 'left';
$config->chteam->datatable->fieldList['createdDate']['width']    = '140';
$config->chteam->datatable->fieldList['createdDate']['required'] = 'yes';
$config->chteam->datatable->fieldList['createdDate']['sort']     = 'yes';
$config->chteam->datatable->fieldList['createdDate']['pri']      = '1';

$config->chteam->datatable->fieldList['actions']['title']    = 'actions';
$config->chteam->datatable->fieldList['actions']['fixed']    = 'right';
$config->chteam->datatable->fieldList['actions']['width']    = '75';
$config->chteam->datatable->fieldList['actions']['required'] = 'yes';
$config->chteam->datatable->fieldList['actions']['pri']      = '1';

$config->chteam->search['module'] = 'chteam';
$config->chteam->search['fields']['name']      = $lang->chteam->name;
$config->chteam->search['fields']['leader']    = $lang->chteam->leader;
$config->chteam->search['fields']['members']   = $lang->chteam->members;
$config->chteam->search['fields']['createdBy'] = $lang->chteam->createdBy;

$config->chteam->search['params']['name']      = array('operator' => 'include', 'control' => 'input' , 'values' => '');
$config->chteam->search['params']['leader']    = array('operator' => '='      , 'control' => 'select', 'values' => 'users');
$config->chteam->search['params']['members']   = array('operator' => 'include', 'control' => 'select', 'values' => 'users');
$config->chteam->search['params']['createdBy'] = array('operator' => '='      , 'control' => 'select', 'values' => 'users');
