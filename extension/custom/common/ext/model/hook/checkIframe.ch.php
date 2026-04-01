<?php
$module = $this->app->getModuleName();
$method = $this->app->getMethodName();

if($module == 'ehr' and $method == 'sync') return true;
if($module == 'user' and $method == 'syncallusers') return true;
