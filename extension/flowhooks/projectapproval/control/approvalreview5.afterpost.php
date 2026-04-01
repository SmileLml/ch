<?php
$project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();

if($project->status == 'finished')
{
    $this->flow->mergeVersionByObjectType($dataID, 'projectapproval');
}