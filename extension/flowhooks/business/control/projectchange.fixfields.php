<?php
$this->loadModel('demand');
$linkedDemand = $this->dao->select("zt_demand.id,CONCAT(zt_demandpool.name, '-', zt_demand.name) as name")
    ->from('zt_demand')
    ->leftJoin('zt_demandpool')->on('zt_demand.pool=zt_demandpool.id')
    ->where('zt_demand.id')->in($data->demand)
    ->fetchPairs('id', 'name');

$fields['demand']->options += $linkedDemand;

$architect = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->architect);

if($this->app->user->admin || in_array($this->app->user->account, array_keys($architect)))
{
    $fields['project']->options = $this->dao->select('id, name')->from('zt_flow_projectapproval')
        ->where('deleted')->eq('0')
        ->andWhere('status')->eq('approvedProject')
        ->orderBy('id_desc')
        ->fetchPairs('id', 'name');
}

$data->project = isset($projectID) ? $projectID : $data->project;

if($data->project)
{
    $fields['project']->options += $this->dao->select('id, name')->from('zt_flow_projectapproval')
        ->where('id')->eq($data->project)
        ->fetchPairs('id', 'name');

    $copyBusiness = $this->dao->select('*')->from('zt_copyflow_business')->where('project')->eq($data->project)->andWhere('business')->eq($data->id)->andWhere('operator')->eq($this->app->user->account)->fetch();

    if($copyBusiness)
    {
        unset($copyBusiness->version);
        foreach($copyBusiness as $field => $value)
        {
            if(property_exists($data, $field) && $data->$field !== $value) $data->$field = $value;
        }
    }

    $this->session->set('changeBusiness', '1');
}

if($this->session->isApprovalsubmit4)
{
    $fields['isCancel']->show = '0';
}
