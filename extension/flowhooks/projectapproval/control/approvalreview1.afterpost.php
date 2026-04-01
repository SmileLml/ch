<?php
$project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();

if($project->status == 'toBeEvaluated')
{
    $lastSubmit1    = $this->dao->select('id')->from('zt_action')->where('objectID')->eq($project->id)->andWhere('action')->eq('approvalsubmit1')->orderBy('id_desc')->fetch();
    $reviewMembers  = $this->dao->select('actor,actor')->from('zt_action')->where('objectID')->eq($project->id)->andWhere('action')->eq('approvalreview1')->andWhere('id')->gt($lastSubmit1->id)->fetchPairs();

    $this->flow->createProjectMembers($dataID, '', $reviewMembers);
    $this->flow->mergeVersionByObjectType($dataID, 'projectapproval');
}
