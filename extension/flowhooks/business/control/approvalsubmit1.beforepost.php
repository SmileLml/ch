<?php
$approvalReviewers = isset($_POST['approval_reviewer']) ? $_POST['approval_reviewer'] : array();
if(empty($approvalReviewers)) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->emptyReviewers));
$allReviewers = array();
foreach($approvalReviewers as $nodeIndex => $reviewer)
{
    if($nodeIndex == 'start' || $nodeIndex == 'end') continue;
    //if(empty(array_filter($reviewer))) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->emptyReviewers));

    $allReviewers = array_merge($allReviewers, array_filter($reviewer));
}

$depts       = array();
$createdDept = $this->dao->select('*')->from('zt_dept')->where('id')->eq($_POST['createdDept'])->fetch();
if(!empty($_POST['dept']))
{
    $depts = $this->dao->select('*')->from('zt_dept')->where('id')->in($_POST['dept'])->fetchAll();
}

$depts[] = $createdDept;
foreach($depts as $dept)
{
    $hasLeader = false;
    foreach($allReviewers as $reviewer) if(strpos($dept->leaders, $reviewer) !== false) $hasLeader = true;
    if(!$hasLeader) return $this->send(array('result' => 'fail', 'message' => sprintf($this->lang->flow->noHasAllReviewers, $dept->name)));
}

if(!empty($_POST['project']))
{
    $checkResult = $this->flow->checkDevelopmentBudget($_POST['project'], $_POST['developmentBudget']);
    if(!$checkResult) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->overSetBudget));

    $checkDateResult = $this->flow->checkBusinessDate($_POST);
    if($checkDateResult['result'] == 'fail') return $this->send($checkDateResult);
}
else
{
    $_POST['goLiveDate'] = '';
    $_POST['acceptanceDate'] = '';
    $_POST['PRDdate'] = '';
}

$oldDemands = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($dataID)->fetch('demand');
