<?php
$config->demandpool = new stdclass();
$config->demandpool->create = new stdclass();
$config->demandpool->edit   = new stdclass();

$config->demandpool->create->requiredFields = 'name,createdBy,createdDate,desc';
$config->demandpool->edit->requiredFields   = $config->demandpool->create->requiredFields;

$config->demandpool->editor = new stdclass();
$config->demandpool->editor->create    = array('id' => 'desc', 'tools' => 'simpleTools');
$config->demandpool->editor->edit      = array('id' => 'desc', 'tools' => 'simpleTools');
$config->demandpool->editor->view      = array('id' => 'comment', 'tools' => 'simpleTools');
$config->demandpool->editor->close     = array('id' => 'comment', 'tools' => 'simpleTools');

$config->demandpool->export = new stdclass();
$config->demandpool->import = new stdclass();
$config->demandpool->export->listFields     = explode(',', "dept,status,owner,pri");
$config->demandpool->export->templateFields = explode(',', "dept,name,owner,desc");

$config->demandpool->list = new stdclass();
$config->demandpool->list->exportFields = 'id, dept, name, status, pri, owner, desc, createdBy, createdDate';

/* Search. */
global $lang;
$config->demandpool->search['module'] = 'demandpool';
$config->demandpool->search['fields']['id']          = $lang->demandpool->id;
$config->demandpool->search['fields']['name']        = $lang->demandpool->name;
$config->demandpool->search['fields']['dept']        = $lang->demandpool->dept;
$config->demandpool->search['fields']['owner']       = $lang->demandpool->owner;
$config->demandpool->search['fields']['status']      = $lang->demandpool->status;
$config->demandpool->search['fields']['participant'] = $lang->demandpool->participant;
$config->demandpool->search['fields']['createdBy']   = $lang->demandpool->createdBy;
$config->demandpool->search['fields']['createdDate'] = $lang->demandpool->createdDate;
$config->demandpool->search['fields']['desc']        = $lang->demandpool->desc;

$config->demandpool->search['params']['id']           = array('operator' => '=', 'control' => 'input', 'values' => '');
$config->demandpool->search['params']['name']         = array('operator' => 'include', 'control' => 'input',  'values' => '');
$config->demandpool->search['params']['dept']         = array('operator' => '=', 'control' => 'select', 'values' => '');
$config->demandpool->search['params']['status']       = array('operator' => '=', 'control' => 'select', 'values' => $lang->demandpool->statusList);
$config->demandpool->search['params']['owner']        = array('operator' => '=', 'control' => 'select', 'values' => 'users');
$config->demandpool->search['params']['participant']  = array('operator' => 'include', 'control' => 'select', 'values' => 'users');
$config->demandpool->search['params']['createdBy']    = array('operator' => '=', 'control' => 'select', 'values' => 'users');
$config->demandpool->search['params']['createdDate']  = array('operator' => '=', 'control' => 'select', 'values' => '', 'class' => 'date');
$config->demandpool->search['params']['desc']         = array('operator' => 'include', 'control' => 'input', 'values' => '');
