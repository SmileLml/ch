<?php
$config->chproject->editor = new stdclass();
$config->chproject->editor->create    = array('id' => 'desc',    'tools' => 'simpleTools');
$config->chproject->editor->edit      = array('id' => 'desc',    'tools' => 'simpleTools');
$config->chproject->editor->close     = array('id' => 'comment', 'tools' => 'simpleTools');
$config->chproject->editor->activate  = array('id' => 'comment', 'tools' => 'simpleTools');

$config->chproject->create = new stdclass();
$config->chproject->edit   = new stdclass();
$config->chproject->create->requiredFields = 'project,name,code,begin,end';
$config->chproject->edit->requiredFields   = 'project,name,code,begin,end';
