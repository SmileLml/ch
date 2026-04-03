<?php
$config->installed       = true;
$config->debug           = false;
$config->requestType     = 'PATH_INFO';
$config->timezone        = 'Asia/Shanghai';
$config->db->driver      = 'mysql';
$config->db->host        = '127.0.0.1';
$config->db->port        = '3306';
$config->db->name        = 'ch';
$config->db->user        = 'root';
$config->db->encoding    = 'UTF8';
$config->db->password    = '122112';
$config->db->prefix      = 'zt_';
$config->webRoot         = getWebRoot();
$config->default->lang   = 'zh-cn';
$config->customSession = true;
$config->debug           = 6;