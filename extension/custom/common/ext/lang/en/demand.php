<?php
$lang->demandpool = new stdclass();
$lang->demandpool->common = 'Demand';

$lang->demand = new stdclass();
$lang->demand->common = 'Demand';

$lang->navIcons['demandpool'] = "<i class='icon icon-bars'></i>";

$lang->mainNav->demandpool = "{$lang->navIcons['demandpool']} {$lang->demandpool->common}|demandpool|browse|";

$lang->mainNav->menuOrder[11] = 'demandpool';

$lang->navGroup->demandpool = 'demandpool';
$lang->navGroup->demand     = 'demandpool';

$lang->demandpool->menu = new stdclass();
$lang->demandpool->menu->browse = array('link' => "Demand|demand|browse|poolID=%s", 'alias' => 'create,batchcreate,edit,managetree,view,tostory,showimport');
$lang->demandpool->menu->track  = array('link' => "Track|demand|track|demandID=%s");
$lang->demandpool->menu->view   = array('link' => "View|demandpool|view|poolID=%s");

$lang->demandpool->menuOrder[5]  = 'browse';
$lang->demandpool->menuOrder[10] = 'track';
$lang->demandpool->menuOrder[15] = 'view';
