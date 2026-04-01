<?php
if(!defined('TABLE_CHPROJECT'))         define('TABLE_CHPROJECT',         '`' . $config->db->prefix . 'ch_project`');
if(!defined('TABLE_CHPROJECTTEAM'))     define('TABLE_CHPROJECTTEAM',     '`' . $config->db->prefix . 'ch_projectteam`');
if(!defined('TABLE_CHPROJECTINTANCES')) define('TABLE_CHPROJECTINTANCES', '`' . $config->db->prefix . 'ch_projectintances`');
if(!defined('TABLE_CHTEAM'))            define('TABLE_CHTEAM',            '`' . $config->db->prefix . 'ch_team`');
if(!defined('TABLE_YEARPLAN'))          define('TABLE_YEARPLAN',            '`' . $config->db->prefix . 'yearplan`');
if(!defined('TABLE_YEARPLANDEMAND'))    define('TABLE_YEARPLANDEMAND',            '`' . $config->db->prefix . 'yearplandemand`');

$config->objectTables['chproject']      = TABLE_CHPROJECT;
$config->objectTables['chteam']         = TABLE_CHTEAM;
$config->objectTables['yearplan']       = TABLE_YEARPLAN;
$config->objectTables['yearplandemand'] = TABLE_YEARPLANDEMAND;

$filter->task->batchchangeexecution = new stdclass();
$filter->task->batchchangeexecution->cookie['batchChangeTaskIdList'] = 'reg::any';

$config->openMethods[] = 'caselib.batchclone';
