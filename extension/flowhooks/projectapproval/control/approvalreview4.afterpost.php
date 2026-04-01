<?php
$project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();

if($project->status == 'cancelled')
{
    $projectBusinesses = $this->dao->select('business,PRDdate,acceptanceDate,goLiveDate')->from('zt_flow_projectbusiness')->where('parent')->eq($dataID)->andWhere('deleted')->eq('0')->fetchAll('business');
    $this->loadModel('projectapproval')->updateProjectApproval($dataID, $projectBusinesses, 'projectreview4') ;

    $businessIdList = array_keys($projectBusinesses);

    $removeBusinessList = $this->dao->select('id')->from('zt_flow_business')->where('project')->eq($result['recordID'])->andWhere('deleted')->eq('0')->andWhere('id')->notIN($businessIdList)->fetchPairs('id');

    if($removeBusinessList)
    {
        $this->dao->update('zt_flow_business')->set('status')->eq('activate')->set('project')->eq('0')->where('id')->in($removeBusinessList)->exec();

        if($removeBusinessList)
        {
            $this->loadModel('flow');
            foreach($removeBusinessList as $businessID) $this->flow->mergeVersionByObjectType($businessID, 'business');
        }
    }

    $oldBusinessStatusList = $this->dao->select('id,status')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchPairs('id');
    $this->dao->update('zt_flow_business')->set('status')->eq('cancelled')->set('project')->eq($result['recordID'])->where('id')->in($businessIdList)->andWhere('status')->notIN(array('beOnline', 'closed'))->exec();

    foreach($oldBusinessStatusList as $oldBusinessID => $oldBusinessStatus)
    {
        if(in_array($oldBusinessStatus, array('beOnline', 'closed'))) continue;
        $actionID  = $this->loadModel('action')->create('business', $oldBusinessID, 'projectreview4');
        $changes   = array();
        $changes[] = ['field' => 'status', 'old' => $oldBusinessStatus, 'new' => 'cancelled'];
        if($changes) $this->action->logHistory($actionID, $changes);
    }

    if($businessIdList)
    {
        $this->loadModel('flow');
        foreach($businessIdList as $businessID) $this->flow->mergeVersionByObjectType($businessID, 'business');
    }
    // $businessDemandList = $this->dao->select('id, demand')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchPairs();
    // $demandIdList       = implode(',', $businessDemandList);

    // $this->dao->update('zt_demand')->set('status')->eq('closed')->set('project')->eq($dataID)->where('id')->in($demandIdList)->exec();
    // foreach($demandIdList as $demandID) $this->loadModel('action')->create('demand', $demandID, 'closedByBusiness', '', $dataID);

    $projectID = $this->dao->select('id')->from('zt_project')->where('instance')->eq($dataID)->fetch('id');

    $_POST            = array();
    $_POST['status']  = 'closed';
    $_POST['realEnd'] = date('Y-m-d', time());
    $_POST['comment'] = '';

    $changes = $this->loadModel('project')->close($projectID);
    if(dao::isError()) return print(js::error(dao::getError()));

    if($this->post->comment != '' or !empty($changes))
    {
        $actionID = $this->loadModel('action')->create('project', $projectID, 'Closed', $this->post->comment);
        $this->loadModel('action')->logHistory($actionID, $changes);
    }
    $this->executeHooks($projectID);

    $this->flow->mergeVersionByObjectType($dataID, 'projectapproval');

    $this->flow->updateProjectByProjectApproval($dataID);
}
if($result['result'] == 'success' && $_POST['reviewResult'] == 'reject') $this->flow->updateBusinessVersion($dataID);
