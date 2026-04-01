<?php
$projectBusiness = $this->dao->select('id,business,PRDdate,acceptanceDate,goLiveDate')->from('zt_flow_projectbusiness')->where('parent')->eq($dataID)->andWhere('deleted')->eq('0')->fetchAll('id');

$newBusinessID  = array_column($projectBusiness, 'business');

if($projectBusiness)
{
    foreach($projectBusiness as $id => $business)
    {
        $businessData = new stdClass();

        $businessData->PRDdate        = $business->PRDdate;
        $businessData->acceptanceDate = $business->acceptanceDate;
        $businessData->goLiveDate     = $business->goLiveDate;

        $this->dao->update('zt_flow_business')->data($businessData)->where('id')->eq($business->business)->exec();
    }
}


/* send OpenMessage */
if(SX_ENABLE)
{
    $this->loadModel('apiRequest');

    $projectapprovalID = $this->session->projectapprovalID;
    $this->loadModel('projectapproval');
    $projectapproval = $this->projectapproval->getByID($projectapprovalID);
    $review_url = helper::createLink('my','audit','browseType=projectapproval&param=&orderBy=time_desc');
    $msgContent = sprintf($this->lang->projectapproval->openMessageTemplate,$review_url,$projectapproval->projectNumber,$projectapproval->name,$this->session->user->account);

    if(is_array($approvalReviewers) && count($approvalReviewers))
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



