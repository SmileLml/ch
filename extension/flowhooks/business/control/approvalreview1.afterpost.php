<?php
$business = $this->dao->select('*')->from('zt_flow_business')->where('id')->eq($dataID)->fetch();

if(!empty($business->project) && $business->status == 'activate')
{
    $users   = $this->loadModel('user')->getPairs('noletter|noclosed|nodeleted');
    $project = $this->loadModel('project')->getByInstance($business->project);

    $projectBusiness = new stdClass();
    $projectBusiness->business          = $dataID;
    $projectBusiness->parent            = $business->project;
    $projectBusiness->developmentBudget = $business->developmentBudget;
    $projectBusiness->headBusiness      = zget($users, $business->businessPM, $business->businessPM);
    $projectBusiness->outsourcingBudget = $business->outsourcingBudget;
    $projectBusiness->goLiveDate        = $business->goLiveDate;
    $projectBusiness->acceptanceDate    = $business->acceptanceDate;
    $projectBusiness->PRDdate           = $business->PRDdate;

    if($project) $projectBusiness->project = $project->id;

    $this->dao->insert('zt_flow_projectbusiness')->data($projectBusiness)->exec();
    $this->dao->update('zt_flow_business')->set('status')->eq('approvedProject')->where('id')->eq($dataID)->exec();
    $this->loadModel('flow')->mergeVersionByObjectType($dataID, 'business');

    $projectapproval  = $this->loadModel('projectapproval')->getByID($business->project);
    $relevantDeptList = explode(',', $projectapproval->relevantDept);
    $businessUnitList = explode(',', $projectapproval->businessUnit);

    if(!in_array($business->createdDept, $relevantDeptList))  $relevantDeptList[] = $business->createdDept;
    if(!in_array($business->businessUnit, $businessUnitList)) $businessUnitList[] = (string)$business->businessUnit;

    $this->dao->update('zt_flow_projectapproval')->set('relevantDept')->eq(implode(',', $relevantDeptList))->set('businessUnit')->eq(implode(',', $businessUnitList))->where('id')->eq($business->project)->exec();

    $this->dao->update('zt_project')->set('relevantDept')->eq(implode(',', $relevantDeptList))->set('businessUnit')->eq(implode(',', $businessUnitList))->where('instance')->eq($business->project)->exec();

    $this->loadModel('flow')->addBusinessDiffForApproval($dataID, $business->project, 'businessreview1');

    $this->loadModel('flow')->mergeVersionByObjectType($business->project, 'projectapproval');
}

$this->dao->update('zt_demand')->set('project')->eq($business->project)->where('id')->in($business->demand)->exec();

if($_POST['reviewResult'] == 'pass')
{
    foreach($_POST['stakeholder'] as $deptID => $stakeholder)
    {
        $this->dao->update('zt_flow_businessstakeholder')->set('stakeholder')->eq($stakeholder)->where('parent')->eq($dataID)->andWhere('dept')->eq($deptID)->exec();
    }
    $this->loadModel('flow')->mergeVersionByObjectType($dataID, 'business');
}
