<?php
$this->loadModel('action');
$project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();

if($project->status == 'approvedProject')
{
    $projectBusinesses = $this->dao->select('business,PRDdate,acceptanceDate,goLiveDate')->from('zt_flow_projectbusiness')->where('parent')->eq($result['recordID'])->andWhere('deleted')->eq('0')->fetchAll('business');

    $newBusinessID  = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($result['recordID'])->andWhere('deleted')->eq('0')->fetchPairs('business');

    if($newBusinessID) $this->dao->update('zt_flow_business')->set('status')->eq('approvedProject')->where('id')->in($newBusinessID)->exec();
    foreach($newBusinessID as $tempBusinessID)
    {
        $actionID = $this->action->create('business', $tempBusinessID, 'evaluationfeedback');
        $change = array();
        $change[] = array('field' => 'status', 'old' => 'projecting', 'new' => 'approvedProject');
        $this->action->logHistory($actionID, $change);
    }

    $this->loadModel('projectapproval')->updateProjectApproval($dataID, $projectBusinesses, 'evaluationfeedback');

    $this->flow->createProjectByProjectApproval($dataID);
}
