<?php
$oldDemands = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($dataID)->fetch('demand');

/* Resolve the prefix for project and project approval. */
if(strpos($this->post->project, 'P') !== false)
{
    if(strpos($this->post->project, 'PA') !== false)
    {
        $_POST['projectApproval'] = str_replace('PA', '', $this->post->project);
        $_POST['project']         = '';
        $_POST['projectType']     = 1;
    }
    else
    {
        $_POST['project']         = str_replace('P', '', $this->post->project);
        $_POST['projectApproval'] = '';
        $_POST['projectType']     = 2;
    }
}
