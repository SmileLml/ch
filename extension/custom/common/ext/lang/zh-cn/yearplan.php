<?php
$lang->yearplan = new stdclass();
$lang->yearplan->common = '年度计划';

$lang->navIcons['yearplan'] = "<i class='icon icon-stack'></i>";

$lang->mainNav->yearplan = "{$lang->navIcons['yearplan']} {$lang->yearplan->common}|yearplan|browse|";

$lang->mainNav->menuOrder[11] = 'yearplan';

$lang->navGroup->yearplan       = 'yearplan';
$lang->navGroup->yearplandemand = 'yearplan';

$lang->yearplan->homeMenu = new stdclass();
$lang->yearplan->homeMenu->browse = array('link' => "年度计划需求池|yearplan|browse|", 'alias' => 'create,edit');

$lang->yearplan->menu = new stdclass();
$lang->yearplan->menu->browse = array('link' => "需求|yearplandemand|browse|yearplanID=%s", 'alias' => 'create,edit,integration,view,confirm');
$lang->yearplan->menu->view   = array('link' => "概况|yearplan|view|yearplanID=%s");