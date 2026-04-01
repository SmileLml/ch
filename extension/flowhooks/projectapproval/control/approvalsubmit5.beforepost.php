<?php
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

if(isset($_POST['children']['sub_projectvalue']['valueType']))
{
    $valueTypes      = array_filter($_POST['children']['sub_projectvalue']['valueType']);
    $uniqueValueType = array_unique($valueTypes);

    if(empty($uniqueValueType)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectvalue' => $this->lang->flow->emptyValueType)));
}

if(isset($_POST['children']['sub_projectvalue']['valueType']))
{
    $valueTypes      = array_filter($_POST['children']['sub_projectvalue']['valueType']);
    $uniqueValueType = array_unique($valueTypes);

    if(empty($uniqueValueType)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectvalue' => $this->lang->flow->emptyValueType)));
}

foreach($_POST['children'] as $subModule => $subData)
{
   $_POST['children'][$subModule] = $this->flow->removeEmptyEntries($subData);
}

