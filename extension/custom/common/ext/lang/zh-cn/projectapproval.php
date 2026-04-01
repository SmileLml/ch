<?php
$lang->submit = '提交';

$lang->common->business        = '业务需求';
$lang->common->projectapproval = '项目管理';
$lang->common->finishReport    = '结项报告';

$lang->scrum->menu->storyGroup['dropMenu']->business = array('link' => "{$lang->common->business}|project|business|projectID=%s");

$lang->waterfall->menu->storyGroup['dropMenu'] = $lang->scrum->menu->storyGroup['dropMenu'];

$lang->projectapproval = new stdclass();

$lang->projectapproval->homeMenu = new stdclass();

$lang->projectapproval->homeMenu->browse = array('link' => "{$lang->common->projectapproval}|projectapproval|browse", 'exclude' => 'projectapproval-browse');

$lang->projectapproval->menu = new stdclass();

$lang->projectapproval->menu->business     = array('link' => "{$lang->common->business}|projectapproval|business");
$lang->projectapproval->menu->finishReport = array('link' => "{$lang->common->finishReport}|projectapproval|finishReport");

$lang->common->monitoring = '过程监控';

$lang->my->menu->monitoring = array('link' => "{$lang->common->monitoring}|monitoring|browse");

$lang->my->menuOrder[8] = 'monitoring';

$lang->forumLink = 'https://tucao.9cair.com/pages/product/410202d6a91442e595946b097ac83ae1';

$lang->createObjects['forum'] = '论坛';

unset($lang->createIcons);
$lang->createIcons['todo']        = 'todo';
$lang->createIcons['effort']      = 'time';
$lang->createIcons['forum']       = 'chats';
$lang->createIcons['bug']         = 'bug';
$lang->createIcons['story']       = 'lightbulb';
$lang->createIcons['task']        = 'check-sign';
$lang->createIcons['testcase']    = 'sitemap';
$lang->createIcons['doc']         = 'doc';
$lang->createIcons['execution']   = 'run';
$lang->createIcons['project']     = 'project';
$lang->createIcons['product']     = 'product';
$lang->createIcons['program']     = 'program';
$lang->createIcons['kanbanspace'] = 'cube';
$lang->createIcons['kanban']      = 'kanban';

$lang->my->menu->work['subMenu']->business        = '业务需求|my|work|mode=business';
$lang->my->menu->work['subMenu']->projectapproval = '项目管理|my|work|mode=projectapproval';

$lang->my->menu->work['menuOrder'][6] = 'projectapproval';
$lang->my->menu->work['menuOrder'][7] = 'business';

$lang->common->viewSubChange = '查看子表修改信息';
