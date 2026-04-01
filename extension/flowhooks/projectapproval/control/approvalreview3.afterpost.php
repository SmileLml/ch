<?php
$this->loadModel('action');
$this->dao->update('zt_flow_projectapproval')->set('businessCancel')->eq('N')->where('id')->eq($dataID)->exec();

if($result['result'] == 'success')
{
    if($_POST['reviewResult'] == 'pass')
    {
        $project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();

        if(in_array($project->status, array('approvedProject', 'design', 'closure', 'devTest')))
        {
            $projectBusinesses = $this->dao->select('business,PRDdate,acceptanceDate,goLiveDate')->from('zt_flow_projectbusiness')->where('parent')->eq($result['recordID'])->andWhere('deleted')->eq('0')->fetchAll('business');

            $businessIdList = array_keys($projectBusinesses);

            $notNeedStatus        = array('draft', 'conformReviewing', 'changeReviewing');
            $removeBusinessList   = $this->dao->select('id')->from('zt_flow_business')->where('project')->eq($result['recordID'])->andWhere('deleted')->eq('0')->andWhere('id')->notIN($businessIdList)->andWhere('status')->notin($notNeedStatus)->fetchPairs('id');
            $activateBusinessList = $this->dao->select('id')->from('zt_flow_business')->where('deleted')->eq('0')->andWhere('id')->in($businessIdList)->andWhere('status')->eq('projecting')->fetchPairs('id');

            if($removeBusinessList)   $this->dao->update('zt_flow_business')->set('status')->eq('activate')->set('project')->eq('')->where('id')->in($removeBusinessList)->exec();
            if($activateBusinessList)
            {
                $this->dao->update('zt_flow_business')->set('status')->eq('approvedProject')->where('id')->in($activateBusinessList)->exec();
                foreach($activateBusinessList as $tempBusinessID)
                {
                    $actionID = $this->action->create('business', $tempBusinessID, 'projectreview3');
                    $change = array();
                    $change[] = array('field' => 'status', 'old' => 'projecting', 'new' => 'approvedProject');
                    $this->action->logHistory($actionID, $change);
                }
            }

            $this->loadModel('projectapproval')->updateProjectApproval($dataID, $projectBusinesses, 'projectreview3');

            $this->flow->mergeVersionByObjectType($dataID, 'projectapproval');

            $this->flow->updateProjectByProjectApproval($dataID);

            $this->flow->updateBusinessVersion($dataID, 'merge');
        }
    }
    elseif($_POST['reviewResult'] == 'reject')
    {
        $this->flow->updateBusinessVersion($dataID);
    }
}
