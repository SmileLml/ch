<?php
$this->loadModel('business');
$this->lang->business->action->projectcancel       = '$date，由<strong>$actor</strong> 项目取消编辑。';
$this->lang->business->action->cancelprojectcancel = '$date, 由 <strong>$actor</strong> 撤回了项目取消请求';
$this->lang->business->action->passprojectcancel   = '$date, 项目取消请求审批通过。';
$backUrl = $this->session->businessViewBackUrl ? $this->session->businessViewBackUrl : helper::createLink('business', 'browse');
$this->session->set('storyList', helper::createLink('business', 'view', 'dataID=' . $dataID), 'product');
$this->session->set('projectapprovalViewBackUrl', $this->app->getURI());
$this->session->set('demandViewBackUrl', $this->app->getURI());
foreach($processBlocks['basic']->fields as &$value)
{
    if($value->field != 'demand') continue;

    foreach($value->options as $key => &$val)
    {
        $val = $key ? html::a(helper::createLink('demand', 'view', 'id=' . $key), $val) : '';
    }
}

$this->view->processBlocks = $processBlocks;
$this->view->backUrl       = $backUrl;
