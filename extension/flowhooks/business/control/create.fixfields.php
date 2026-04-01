<?php
$this->loadModel('demand');

$demands      = $this->demand->getListByIds($this->session->demandIDList, 'active', 0);
$data         = $this->demand->getIntegrationData($demands);
$demandIDList = array_keys($demands);
$ignoreIDList = $this->session->demandIDList ? array_diff($this->session->demandIDList, $demandIDList) : [];
$this->session->set('demandIDList', $demandIDList);
if($ignoreIDList)
{
    echo js::confirm(sprintf($this->lang->demand->integrationError, '#' . implode(',#', $ignoreIDList)), $demandIDList ? $this->session->demandIntegration : $this->session->demandList, $this->session->demandList);
    exit;
}

$architect = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->architect);

if(!($this->app->user->admin || in_array($this->app->user->account, array_keys($architect))))
{
    $ignoreIDList = array();
    foreach($demands as $demand)
    {
        if($demand->project)
        {
            $responsibleDept = $this->dao->select('responsibleDept')->from('zt_flow_projectapproval')->where('id')->eq($demand->project)->fetch('responsibleDept');
            $dept            = $this->loadModel('dept')->getById($responsibleDept);
            if(strpos($dept->path, $this->app->user->dept) === false)
            {
                $ignoreIDList[] = $demand->id;
            }

        }
        else
        {
            $ignoreIDList[] = $demand->id;
        }
    }
}

$demandIDList = array_filter($demandIDList, function($value) use($ignoreIDList)
{
    return !in_array($value, $ignoreIDList);
});

$this->session->set('demandIDList', $demandIDList);
if($ignoreIDList)
{
    echo js::confirm(sprintf($this->lang->demand->integrationPrivError, '#' . implode(',#', $ignoreIDList)), $demandIDList ? $this->session->demandIntegration : $this->session->demandList, $this->session->demandList);
    exit;
}

$demandUsers = $this->dao->select('createdBy')->from('zt_demand')->where('id')->in($demandIDList)->fetchPairs('createdBy');
$demandDepts = $this->dao->select('dept')->from(TABLE_USER)->where('account')->in($demandUsers)->fetchPairs('dept');

$fields['demand']->defaultValue = implode(',', $demandIDList);

if(count($demandDepts) == 1) $fields['createdDept']->defaultValue = array_shift($demandDepts);

$demandExtendFields = $this->loadModel('flow')->getExtendFields('demand', 'create');
foreach($demandExtendFields as $field)
{
    $field->module = 'business';
    if($field->field == 'businessUnit') $field->rules = ',1';

    $fields[$field->field] = $field;
}

foreach($fields as $key => $value)
{
    if($fields[$key]->control == 'date') $data[$key] = formatTime($data[$key]);
    if(isset($data[$key]) and !empty($data[$key])) $fields[$key]->defaultValue = $data[$key];
}



if($this->app->user->admin || in_array($this->app->user->account, array_keys($architect)))
{
    $fields['project']->options = $this->dao->select('id, name')->from('zt_flow_projectapproval')
        ->where('deleted')->eq('0')
        ->andWhere('status')->in('approvedProject,design,devTest,closure')
        ->orderBy('id_desc')
        ->fetchPairs('id', 'name');
}
if($data['project'])
{
    $fields['project']->options += $this->dao->select('id, name')->from('zt_flow_projectapproval')
        ->where('id')->eq($data['project'])
        ->fetchPairs('id', 'name');
}
$this->view->fields = $fields;
$this->view->title  = $this->lang->demand->integration;
$this->view->poolID = array_shift($demands)->pool;
