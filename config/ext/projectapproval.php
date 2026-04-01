<?php
define('TABLE_OBJECTVERSION', '`' . $config->db->prefix . 'objectversion`');
define('TABLE_CHILDHISTORY',  '`' . $config->db->prefix . 'childhistory`');

$config->openMethods[] = 'project.selectproduct';
$config->openMethods[] = 'diff.childdiff';
