<?php
$lang->demandpool = new stdclass();
$lang->demandpool->common = '需求池';

$lang->demand = new stdclass();
$lang->demand->common = '需求';

$lang->navIcons['demandpool'] = "<i class='icon icon-bars'></i>";

$lang->mainNav->demandpool = "{$lang->navIcons['demandpool']} {$lang->demandpool->common}|demandpool|browse|";

$lang->mainNav->menuOrder[12] = 'demandpool';

$lang->navGroup->demandpool = 'demandpool';
$lang->navGroup->demand     = 'demandpool';

$lang->demandpool->menu = new stdclass();
$lang->demandpool->menu->browse = array('link' => "需求|demand|browse|poolID=%s", 'alias' => 'create,batchcreate,edit,managetree,view,tostory,showimport,integration', 'subModule' => 'business');
$lang->demandpool->menu->track  = array('link' => "矩阵|demand|track|demandID=%s");
$lang->demandpool->menu->view   = array('link' => "概况|demandpool|view|poolID=%s");

$lang->demandpool->menuOrder[5]  = 'browse';
$lang->demandpool->menuOrder[10] = 'track';
$lang->demandpool->menuOrder[15] = 'view';

$lang->projectrole = new stdclass();
$lang->navGroup->projectrole = 'admin';

$lang->defaultProductTitle = '默认产品';

$lang->error->noDefaultProduct = '请先在产品管理中添加一个默认产品。';

$lang->transTo      = '转办人';
$lang->isTrans      = '是否转办';
$lang->transToEmpty = '转办人不能为空';
$lang->isTransList  = array();
$lang->isTransList['yes'] = '是';
$lang->isTransList['no']  = '否';
