<?php
$childDatas  = $this->view->childDatas;
$childFields = $this->view->childFields;

$newProjectcost = array();
foreach($childFields['sub_projectcost'] as $projectcostKey => $projectcost)
{
    $newProjectcost[$projectcostKey] = $projectcost;
    if($projectcostKey == 'actualExpend')
    {
        $actualExpend = new stdClass();
        $actualExpend->field   = 'percentageDifference';
        $actualExpend->width   = '80';
        $actualExpend->name    = $this->lang->projectapproval->percentageDifference;
        $actualExpend->show    = 1;
        $actualExpend->options = array();
        $newProjectcost['percentageDifference'] = $actualExpend;
    }
}
$childFields['sub_projectcost'] = $newProjectcost;

foreach($childDatas['sub_projectcost'] as $projectcostKey => $projectcost)
{
    $childDatas['sub_projectcost'][$projectcostKey]->percentageDifference = $projectcost->costBudget > 0 ? number_format(($projectcost->actualExpend - $projectcost->costBudget)/$projectcost->costBudget*100, 2). '%' : '0%';
}
$childDatas['sub_projectreviewdetails'] = array();
$this->view->childDatas  = $childDatas;
$this->view->childFields = $childFields;

if(in_array($flow->module, array('business', 'projectapproval')))
{
    $transToUsers = $this->loadModel('user')->getPairs('noletter|noempty|nodeleted|noclosed');
    if(isset($transToUsers[$this->app->user->account])) unset($transToUsers[$this->app->user->account]);

    $this->view->canTransTo   = true;
    $this->view->transToUsers = $transToUsers;
}
