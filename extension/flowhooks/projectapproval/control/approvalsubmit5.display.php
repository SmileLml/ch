<?php
$projectUnitPrice = $this->loadModel('setting')->getItem("owner=system&module=common&section=&key=projectUnitPrice");
$project          = $this->dao->select('*')->from('zt_project')->where('instance')->eq($dataID)->fetch();
$storyIdList      = $this->dao->select('story')->from('zt_projectstory')->where('project')->eq($project->id)->fetchPairs('story');
$sumEstimate      = $this->dao->select('FORMAT(sum(estimate), 2) as sumEstimate')->from('zt_story')->where('id')->in($storyIdList)->andWhere('type')->eq('requirement')->andWhere('deleted')->eq('0')->fetch('sumEstimate');

$childDatas = $this->view->childDatas;

$itPlanIntoCost = 0;
foreach($childDatas['sub_projectcost'] as $projectcostKey => $projectcostValue)
{
    if($projectcostValue->costType == 'itPlanInto') $itPlanIntoCost += $projectcostValue->costBudget;
}
foreach($childDatas['sub_projectcost'] as $projectcostKey => $projectcostValue)
{
    if($projectcostValue->costType == 'itPlanInto') $childDatas['sub_projectcost'][$projectcostKey]->actualExpend = round((float)$sumEstimate * ($projectcostValue->costBudget/$itPlanIntoCost), 2);
    if($projectcostValue->costType == 'itCost')     $childDatas['sub_projectcost'][$projectcostKey]->actualExpend = $project->actualCostMaintain ? $project->actualCostMaintain : 0;
}

$this->view->childDatas             = $childDatas;
$this->view->itPlanInfoActualExpend = (int)$projectUnitPrice * (float)$sumEstimate;
$this->view->itCostActualExpend     = $project->actualCostMaintain;
