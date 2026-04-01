<?php
$fields['reviewResult']->options['noReview'] = '不评审';

$this->loadModel('demand');
$this->loadModel('project');
if($data->project)
{
    $project = $this->dao->select('businessPM')->from('zt_flow_projectapproval')->where('id')->eq($data->project)->fetch();

    $itPM = $this->dao->select('account')->from('zt_flow_projectmembers')->where('parent')->eq($data->project)->andWhere('projectRole')->eq('itPM')->fetch('account');

    if(isset($fields['businessPM']))
    {
        $fields['businessPM']->default  = $project->businessPM;
        $fields['businessPM']->readonly = 1;
    }

    if(isset($fields['itPM']))
    {
        $fields['itPM']->default  = $itPM;
        $fields['itPM']->readonly = 1;
    }

    $data->businessPM = $project->businessPM;
    $data->itPM       = $itPM;
}
else
{
    $CreatedDept         = $this->loadModel('dept')->getByID($data->createdDept);
    $isCreatedDeptLeader = strpos($CreatedDept->leaders, $this->app->user->account) === false ? false : true;
    js::set('isCreatedDeptLeader', $isCreatedDeptLeader);
}
$businessstakeholders = $this->dao->select('*')->from('zt_flow_businessstakeholder')->leftJoin('zt_dept')->on('zt_dept.id=zt_flow_businessstakeholder.dept')->where('zt_flow_businessstakeholder.parent')->eq($data->id)->fetchAll();

$deptLeaders      = array();
$stakeholdersHtml = '';
foreach($businessstakeholders as $businessstakeholder)
{
    if(!in_array($businessstakeholder->dept, $data->dept)) continue;
    $readonly = '';
    $required = 'required';
    if(strpos($businessstakeholder->leaders, $this->app->user->account) === false)
    {
        $readonly = 'readonly';
        $required = '';
    }
    $stakeholdersHtml .= '<tr>';
    $stakeholdersHtml .= '<th>';
    $stakeholdersHtml .= $this->lang->flow->stakeholder."($businessstakeholder->name)";
    $stakeholdersHtml .= '</th>';
    $stakeholdersHtml .= '<td class="' . $required . '">';
    $stakeholdersHtml .= html::input("stakeholder[{$businessstakeholder->id}]", $businessstakeholder->stakeholder, "class='form-control' $readonly");
    $stakeholdersHtml .= '</td>';
    $stakeholdersHtml .= '</tr>';
}

js::set('stakeholdersHtml', $stakeholdersHtml);
