<?php
$result = $this->flow->checkTransTo();
if($result['result'] != 'success') $this->send($result);

unset($_POST['project']);

$business  = $this->dao->select('*')->from('zt_flow_business')->where('id')->eq($dataID)->fetch();

if($_POST['reviewResult'] == 'pass')
{
    $deptIds   = array();
    $deptIds[] = $business->createdDept;
    if(!empty($business->dept)) $deptIds = array_merge($deptIds, explode(',', $business->dept));

    $depts = $this->dao->select('*')->from('zt_dept')->where('id')->in($deptIds)->fetchAll();
    foreach($depts as $dept)
    {
        if($dept->id == $business->createdDept && strpos($dept->leaders, $this->app->user->account) !== false && empty($_POST['businessPM'])) return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->emptyBusinessPM));

        if(in_array($dept->id, explode(',', $business->dept)) && strpos($dept->leaders, $this->app->user->account) !== false && empty($_POST['stakeholder'][$dept->id])) return $this->send(array('result' => 'fail', 'message' => sprintf($this->lang->flow->emptyStakeholder, $dept->name)));
    }
}
