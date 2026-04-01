<?php
if(!empty($_POST['project']))
{
    $projectCostBudget = $_COOKIE['projectCostBudget'];

    if($_POST['businessTotal'] + $_POST['developmentBudget'] > $projectCostBudget) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->overSetBudget));
}

$haveEstimate = $this->dao->select('sum(estimate) as haveEstimate')->from(TABLE_STORY)->where('business')->eq($dataID)->andWhere('type')->eq('requirement')->andWhere('deleted')->eq(0)->fetch('haveEstimate');

if($haveEstimate > $_POST['developmentBudget']) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->budgetNotEnough));