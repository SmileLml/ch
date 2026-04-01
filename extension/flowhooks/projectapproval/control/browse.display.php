<?php
$this->loadModel('projectapproval');

$this->session->set('projectapprovalViewBackUrl', $this->app->getURI());
foreach($dataList as $data)
{
    $data->name = html::a($this->createlink('projectapproval', 'view', "dataID=$data->id"), $data->name);

    $data->totalCost = $this->loadModel('project')->getBudgetWithUnit($data->totalCost);
}
$fields['actions']->width = 200;

$this->view->dataList = $dataList;
