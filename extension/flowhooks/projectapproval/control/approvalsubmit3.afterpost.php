<?php
$this->loadModel('action');
$newBusinessID  = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($result['recordID'])->andWhere('deleted')->eq('0')->fetchPairs('business');
$diffBusinessID = $newBusinessID ? array_diff($newBusinessID, $oldBusiness) : [];

if($diffBusinessID)
{
    $diffBusinessList = $this->dao->select('id,status')->from('zt_flow_business')->where('id')->in($diffBusinessID)->fetchPairs('id');
    $this->dao->update('zt_flow_business')->set('status')->eq('projecting')->where('id')->in($diffBusinessID)->exec();
    foreach($diffBusinessID as $tempBusinessID)
    {
        $actionID = $this->action->create('business', $tempBusinessID, 'projectsubmit3');
        $change = array();
        $change[] = array('field' => 'status', 'old' => $diffBusinessList[$tempBusinessID], 'new' => 'projecting');
        $this->action->logHistory($actionID, $change);
    }
}

/* send OpenMessage */
if(SX_ENABLE)
{
    $this->loadModel('apiRequest');

    $projectapprovalID = $this->session->projectapprovalID;
    $this->loadModel('projectapproval');
    $projectapproval   = $this->projectapproval->getByID($projectapprovalID);
    $review_url = helper::createLink('my','audit','browseType=projectapproval&param=&orderBy=time_desc');
    $msgContent = sprintf($this->lang->projectapproval->openMessageTemplate,$review_url,$projectapproval->projectNumber,$projectapproval->name,$this->session->user->account);

    if (is_array($approvalReviewers) && count($approvalReviewers))
    {
        $reviewers = array_pop($approvalReviewers);
        foreach($reviewers as $value)
        {
            if(!empty($value))
            {
                try {
                    $this->apiRequest->sendOpenMessage($value,$msgContent);
                } catch(Exception $e) {

                }
            }
        }
    }
}
