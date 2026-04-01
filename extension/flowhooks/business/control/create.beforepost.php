<?php
$this->loadModel('demand');
$integratedDemands = $this->dao->select('id')->from('zt_demand')->where('id')->in($_POST['demand'])->andWhere('stage')->eq('1')->fetchAll();
if($integratedDemands) return $this->send(array('result' => 'fail', 'message' => $this->lang->demand->integratedDemand));

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
