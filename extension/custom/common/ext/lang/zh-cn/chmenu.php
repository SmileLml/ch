<?php
$lang->navIcons['chteam'] = "<i class='icon icon-team'></i>";

$lang->navIconNames['chteam'] = 'team';

$lang->mainNav->chteam = "{$lang->navIcons['chteam']} {$lang->chteam->common}|chproject|browse|";
$lang->mainNav->menuOrder[23] = 'chteam';

$lang->chteam->homeMenu = new stdclass();
$lang->chteam->homeMenu->browse = array('link' => "{$lang->chproject->browse}|chproject|browse|", 'alias' => 'create,edit');
$lang->chteam->homeMenu->team   = array('link' => "{$lang->chteam->browse}|chteam|browse|", 'alias' => 'create,edit');

$lang->chteam->menu = new stdclass();
$lang->chteam->menu->task   = array('link' => "{$lang->task->common}|chproject|task|project=%s", 'subModule' => 'task');
$lang->chteam->menu->story  = array('link' => "{$lang->SRCommon}|chproject|story|project=%s", 'subModule' => 'story', 'alias' => 'storykanban,linkstory');
$lang->chteam->menu->burn   = array('link' => "{$lang->chproject->burn}|chproject|burn|project=%s", 'subModule' => 'burn');
$lang->chteam->menu->qa     = array('link' => "{$lang->qa->common}|chproject|bug|project=%s&intanceProjectID=0&productID=0&branch=all&orderBy=&build=&type=bysearch&param=myQueryID", 'subModule' => 'bug,testtask,testcase,testreport', 'alias' => 'bug,testtask,testcase,testreport');
$lang->chteam->menu->kanban = array('link' => "{$lang->kanban->common}|chproject|kanban|project=%s", 'alias' => 'cfd');
$lang->chteam->menu->view   = array('link' => "{$lang->executionview->common}|chproject|gantt|project=%s", 'subModule' => 'execution', 'alias' => 'grouptask,gantt,taskeffort,tree');

$lang->chteam->menu->qa['subMenu'] = new stdclass();
$lang->chteam->menu->qa['subMenu']->bug        = array('link' => "{$lang->bug->common}|chproject|bug|project=%s&intanceProjectID=0&productID=0&branch=all&orderBy=&build=&type=bysearch&param=myQueryID", 'subModule' => 'bug');
$lang->chteam->menu->qa['subMenu']->testcase   = array('link' => "{$lang->testcase->shortCommon}|chproject|testcase|project=%s", 'subModule' => 'testcase');
$lang->chteam->menu->qa['subMenu']->testtask   = array('link' => "{$lang->testtask->common}|chproject|testtask|project=%s", 'subModule' => 'testtask');
$lang->chteam->menu->qa['subMenu']->testreport = array('link' => "{$lang->testreport->common}|chproject|testreport|project=%s", 'subModule' => 'testreport');

$lang->chteam->menu->kanban['subMenu'] = new stdclass();
$lang->chteam->menu->kanban['subMenu']->kanban = array('link' => "{$lang->kanban->common}|chproject|kanban|project=%s");
$lang->chteam->menu->kanban['subMenu']->cfd    = array('link' => "{$lang->execution->CFD}|chproject|cfd|project=%s");

$lang->chteam->menu->view['subMenu'] = new stdclass();
$lang->chteam->menu->view['subMenu']->execution  = array('link' => "{$lang->chproject->gantt}|chproject|gantt|project=%s", 'subModule' => 'execution', 'alias' => 'maintainrelation,relation');
$lang->chteam->menu->view['subMenu']->grouptask  = array('link' => "{$lang->groupView}|chproject|grouptask|project=%s");
$lang->chteam->menu->view['subMenu']->tree       = array('link' => "{$lang->treeView}|chproject|tree|project=%s");
$lang->chteam->menu->view['subMenu']->taskeffort = array('link' => "{$lang->taskEffort}|chproject|taskeffort|project=%s");
