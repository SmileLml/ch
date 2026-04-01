<?php
$filter->flow = new stdclass();
$filter->flow->ajaxgetmore = new stdclass();
$filter->flow->ajaxgetmore->get['search'] = 'reg::any';
$filter->flow->ajaxgetmore->get['limit']  = 'int';

$filter->flow = new stdclass();
$filter->flow->view = new stdclass();
$filter->flow->view->cookie['projectapprovalVersion'] = 'reg::any';
$filter->flow->view->cookie['businessVersion'] = 'reg::any';

$filter->flow->operate = new stdclass();
$filter->flow->operate->cookie['projectCostBudget'] = 'reg::any';

$config->searchLimit = 0;
$config->flowLimit   = 0;
