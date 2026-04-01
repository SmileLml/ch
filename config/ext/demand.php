<?php

define('SX_ENABLE',false); //闪信开关

$filter->demand = new stdclass();
$filter->demand->browse = new stdclass();
$filter->demand->export = new stdclass();

$filter->demand->browse->cookie['requirementModule'] = 'int';
$filter->demand->export->cookie['checkedItem']       = 'reg::checked';


