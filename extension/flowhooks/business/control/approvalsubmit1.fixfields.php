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
        ->andWhere('status')->in('approvedProject,design,devTest,closure')
        ->orderBy('id_desc')
        ->fetchPairs('id', 'name');
}
if($data->project)
{
    $fields['project']->options += $this->dao->select('id, name')->from('zt_flow_projectapproval')
        ->where('id')->eq($data->project)
        ->fetchPairs('id', 'name');
}
