<?php
$this->loadModel('project');
$this->loadModel('business');

$this->session->set('businessViewBackUrl', helper::createLink('business', 'browse'));

if(common::hasPriv('business', 'view'))
{
    foreach($dataList as $data) $data->name = html::a($this->createlink('business', 'view', "dataID=$data->id"), $data->name);
    $this->view->dataList = $dataList;
}

$fields['project']->options = $this->dao->select('id, name')->from('zt_flow_projectapproval')
    ->orderBy('id_desc')
    ->fetchPairs('id', 'name');
