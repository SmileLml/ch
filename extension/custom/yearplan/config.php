<?php
$config->yearplan = new stdclass();
$config->yearplan->create = new stdclass();
$config->yearplan->edit   = new stdclass();

$config->yearplan->create->requiredFields = 'name,createdBy,createdDate,owner';
$config->yearplan->edit->requiredFields   = $config->yearplan->create->requiredFields;

$config->yearplan->editor = new stdclass();
$config->yearplan->editor->create    = array('id' => 'desc', 'tools' => 'simpleTools');
$config->yearplan->editor->edit      = array('id' => 'desc', 'tools' => 'simpleTools');
$config->yearplan->editor->view      = array('id' => 'comment', 'tools' => 'simpleTools');
$config->yearplan->editor->close     = array('id' => 'comment', 'tools' => 'simpleTools');

$config->yearplan->export = new stdclass();
$config->yearplan->import = new stdclass();
$config->yearplan->export->listFields     = explode(',', "status,owner");
$config->yearplan->export->templateFields = explode(',', "name,owner,desc");

$config->yearplan->list = new stdclass();
$config->yearplan->list->exportFields = 'id, name, status, owner, desc, createdBy, createdDate';

/* Search. */
global $lang;
$config->yearplan->search['module'] = 'yearplan';
$config->yearplan->search['fields']['id']          = $lang->yearplan->id;
$config->yearplan->search['fields']['name']        = $lang->yearplan->name;
$config->yearplan->search['fields']['owner']       = $lang->yearplan->owner;
$config->yearplan->search['fields']['status']      = $lang->yearplan->status;
$config->yearplan->search['fields']['participant'] = $lang->yearplan->participant;
$config->yearplan->search['fields']['createdBy']   = $lang->yearplan->createdBy;
$config->yearplan->search['fields']['createdDate'] = $lang->yearplan->createdDate;
$config->yearplan->search['fields']['desc']        = $lang->yearplan->desc;

$config->yearplan->search['params']['id']           = array('operator' => '=', 'control' => 'input', 'values' => '');
$config->yearplan->search['params']['name']         = array('operator' => 'include', 'control' => 'input',  'values' => '');
$config->yearplan->search['params']['status']       = array('operator' => '=', 'control' => 'select', 'values' => $lang->yearplan->statusList);
$config->yearplan->search['params']['owner']        = array('operator' => '=', 'control' => 'select', 'values' => 'users');
$config->yearplan->search['params']['participant']  = array('operator' => 'include', 'control' => 'select', 'values' => 'users');
$config->yearplan->search['params']['createdBy']    = array('operator' => '=', 'control' => 'select', 'values' => 'users');
$config->yearplan->search['params']['createdDate']  = array('operator' => '=', 'control' => 'select', 'values' => '', 'class' => 'date');
$config->yearplan->search['params']['desc']         = array('operator' => 'include', 'control' => 'input', 'values' => '');
