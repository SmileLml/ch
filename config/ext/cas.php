<?php
$config->cas             = new stdclass();
$config->cas->server     = '192.168.210.229';
$config->cas->port       = 80;
$config->cas->path       = 'scas-server';
$config->cas->compulsory = false; //强制CAS登录,测试环境

//<?php
//$config->cas             = new stdclass();
//$config->cas->server     = 'cas.9cair.com';
//$config->cas->port       = 443;
//$config->cas->path       = '';
//$config->cas->compulsory = false; //强制CAS登录,生产环境
