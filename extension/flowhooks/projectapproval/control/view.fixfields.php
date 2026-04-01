<?php
$this->session->set('projectapprovalID', $data->id);

$this->view->version = $data->version;

$children = '';

if(isset($_COOKIE['projectapprovalVersion']) && !empty($_COOKIE['projectapprovalVersion']))
{
    $version = $_COOKIE['projectapprovalVersion'];

    setcookie('projectapprovalVersion', '');

    $object  = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($data->id)->andWhere('objectType')->eq('projectapproval')->andWhere('version')->eq($version)->fetch('element');
    if($object)
    {
        $object   = json_decode($object);
        $children = $object->children;
        unset($object->children);
        unset($object->uid);
        unset($object->version);
        unset($object->status);
        unset($object->reviewStatus);
        unset($object->reviewers);

        foreach($object as $key => $value) if(property_exists($data, $key)) $data->$key = $value;
    }

    $this->view->version = $version;
}

$isShowChangeDetail      = false;
$isShowCancelDetail      = false;
$extraHistories          = array();
$extraHistoryDetailHtml  = '';

$tempActions = $this->loadModel('action')->getList($flow->module, $data->id);
foreach($tempActions as $tempAction)
{
    foreach($tempAction->history as $historyItem)
    {
        if($historyItem->field == 'version' && $historyItem->new == $this->view->version)
        {
            if($tempAction->action == 'approvalsubmit3')
            {
                $isShowChangeDetail = true;
            }
            elseif($tempAction->action == 'approvalsubmit4')
            {
                $isShowCancelDetail = true;
            }
            if(!isset($object))
            {
                $object = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($data->id)->andWhere('objectType')->eq('projectapproval')->andWhere('version')->eq($this->view->version)->fetch('element');
                if($object) $object = json_decode($object);
            }
            if($object) $extraHistories = $object;

        }
    }

}

$extraHistoryFields = array('changeApplicant', 'changeApplicationDate', 'changeType', 'changeReason', 'changeContent');
foreach($extraHistoryFields as $extraHistoryField)
{
    if(isset($extraHistories->$extraHistoryField))
    {
        $extraHistoryValue = $extraHistories->$extraHistoryField;
        if($extraHistoryField == 'changeApplicant') $extraHistoryValue = zget($this->loadModel('user')->getPairs('noletter'), $extraHistoryValue);
        if($extraHistoryField == 'changeType')
        {
            $changeTypeValue = '';
            foreach($extraHistoryValue as $changeType) $changeTypeValue .= zget($fields['changeType']->options, $changeType). '  ';
            $extraHistoryValue = $changeTypeValue;
        }

        $extraHistoryDetailHtml .= '<p>';
        $extraHistoryDetailHtml .= "<strong>{$this->lang->projectapproval->$extraHistoryField}</strong> : {$extraHistoryValue}";
        $extraHistoryDetailHtml .= '</p>';

    }
}

$this->view->isShowChangeDetail      = $isShowChangeDetail;
$this->view->isShowCancelDetail      = $isShowCancelDetail;
$this->view->extraHistoryDetailHtml  = $extraHistoryDetailHtml;
