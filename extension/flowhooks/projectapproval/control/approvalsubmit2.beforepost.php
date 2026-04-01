<?php
if($_POST['begin'] > $_POST['end'])
{
    dao::$errors['end'][] = sprintf($this->lang->flow->geStartDay, $_POST['end'], $_POST['begin']);
    return $this->send(array('result' => 'fail', 'message' => dao::getError()));
}

$isNotReviewer = true;
$object        = 'projectapproval';
$flow          = $this->loadModel('workflow')->getByModule($object);
$data          = $this->loadModel('flow')->getDataByID($flow, $dataID);
$flowID        = $this->loadModel('approvalflow')->getFlowIDByObject(0, $object, $data, $action->action);
$nodes         = $this->loadModel('approval')->getNodesToConfirm($flowID);

foreach($nodes as $node)
{
    if(isset($node['appointees'])) $isNotReviewer = false;
}
$approvalReviewers = isset($_POST['approval_reviewer']) ? $_POST['approval_reviewer'] : array();
if(empty($approvalReviewers)) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->emptyReviewers));
foreach($approvalReviewers as $nodeIndex => $reviewer)
{
    if($nodeIndex == 'start' || $nodeIndex == 'end') continue;
    if(!empty(array_filter($reviewer))) $isNotReviewer = false;
}
if($isNotReviewer) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->emptyReviewers));

if(isset($_POST['children']['sub_projectcost']['costType']))
{
    $costTypes   = array_filter($_POST['children']['sub_projectcost']['costType']);
    $uniqueTypes = array_filter(array_unique($costTypes));

    if(empty($uniqueTypes)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectcost' => $this->lang->flow->emptyCostType)));
}

if(isset($_POST['children']['sub_projectmembers']['projectRole']))
{
    $projectRoles      = array_filter($_POST['children']['sub_projectmembers']['projectRole']);
    $uniqueProjectRole = array_filter(array_unique($projectRoles));

    if(empty($uniqueProjectRole)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectmembers' => $this->lang->flow->emptyProjectRole)));
}

if(isset($_POST['children']['sub_projectvalue']['valueType']))
{
    $valueTypes      = array_filter($_POST['children']['sub_projectvalue']['valueType']);
    $uniqueValueType = array_unique($valueTypes);

    if(empty($uniqueValueType)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectvalue' => $this->lang->flow->emptyValueType)));
}

$uniqueBusiness = [];
if(isset($_POST['children']['sub_projectbusiness']['business']))
{
    $business       = array_filter($_POST['children']['sub_projectbusiness']['business']);
    $uniqueBusiness = array_filter(array_unique($business));

    if(empty($uniqueBusiness)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectbusiness' => $this->lang->flow->emptyBusiness)));
    if(count($uniqueBusiness) < count($business)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectbusiness' => $this->lang->flow->sameBusiness)));

    $developmentBudget    = $this->dao->select("developmentBudget")->from('zt_flow_business')->where('id')->in($uniqueBusiness)->fetchAll();
    $allDevelopmentBudget = array_sum(array_map(function($budget){ return (int)$budget->developmentBudget; }, $developmentBudget));

    $key = array_search('itPlanInto', $_POST['children']['sub_projectcost']['costType']);
    if(!$key) return $this->send(array('result' => 'fail', 'message' => array('sub_projectcost' => $this->lang->flow->needitPlanInto)));

    $itPlanIntoCostBudget = 0;
    foreach($_POST['children']['sub_projectcost']['costType'] as $projectcostKey => $costTypeValue)
    {
        if($costTypeValue == 'itPlanInto') $itPlanIntoCostBudget += $_POST['children']['sub_projectcost']['costBudget'][$projectcostKey];
    }

    if($allDevelopmentBudget > $itPlanIntoCostBudget) return $this->send(array('result' => 'fail', 'message' => array(('childrensub_projectcostcostBudget' . $key) => $this->lang->flow->overSetBudget)));

    $message = $this->flow->checkBusinessListDate($_POST);
    if(!empty($message)) return $this->send(array('result' => 'fail', 'message' => $message));
}

$oldBusiness = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($dataID)->andWhere('deleted')->eq(0)->fetchPairs();

$mailto = (isset($_POST['mailto']) && !empty($_POST['mailto'])) ? $_POST['mailto'] : [];

$_POST['mailto'] = $this->loadModel('projectapproval')->getMailtoList($mailto);

$businessDept = $this->dao->select('createdDept')->from('zt_flow_business')->where('id')->in($uniqueBusiness)->fetchPairs();
$businessUnit = $this->dao->select('businessUnit')->from('zt_flow_business')->where('id')->in($uniqueBusiness)->fetchPairs();
$relevantDept = isset($_POST['relevantDept']) ? $_POST['relevantDept'] : [];
$unit         = isset($_POST['businessUnit']) ? $_POST['businessUnit'] : [];

$_POST['relevantDept'] = array_filter(array_unique(array_merge($relevantDept, $businessDept)));
$_POST['businessUnit'] = array_filter(array_unique(array_merge($unit, $businessUnit)));

foreach($_POST['children'] as $subModule => $subData)
{
   $_POST['children'][$subModule] = $this->flow->removeEmptyEntries($subData);
}

$checkProcessResult = $this->flow->checkProcess();
if($checkProcessResult['result'] == 'fail') return $this->send($checkProcessResult);

if($_POST['netInfoSafe'] == '<br />') $_POST['netInfoSafe'] = '';
if($_POST['risk'] == '<p><br /></p>') $_POST['risk'] = '';
