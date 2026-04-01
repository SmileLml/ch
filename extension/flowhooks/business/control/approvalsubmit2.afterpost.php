<?php
$demands      = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($dataID)->fetch('demand');
$oldDemands   = explode(',', $oldDemands);
$demands      = explode(',', $demands);
$noIntegrated = array_diff($oldDemands, $demands);

$this->dao->update(TABLE_DEMAND)->set('stage')->eq(1)->where('id')->in($demands)->exec();
if($noIntegrated) $this->dao->update(TABLE_DEMAND)->set('stage')->eq(0)->where('id')->in($noIntegrated)->exec();

if($result['result'] == 'success')
{
    $businessstakeholders = array();

    array_pop($depts);
    $oldDepts = explode(',', $oldDepts);
    foreach($depts as $dept)
    {
        if(in_array($dept->id, $oldDepts)) continue;

        $businessstakeholder = new stdClass();
        $businessstakeholder->parent      = $dataID;
        $businessstakeholder->createdBy   = $this->app->user->account;
        $businessstakeholder->createdDate = date('Y-m-d h:i:s');
        $businessstakeholder->dept        = $dept->id;
        $businessstakeholder->stakeholder = '';

        $businessstakeholders[] = $businessstakeholder;
    }

    foreach($businessstakeholders as $businessstakeholder) $this->dao->insert('zt_flow_businessstakeholder')->data($businessstakeholder)->exec();

    if($this->session->businessList)
    {
        $businessLink = $this->session->businessList;
        $this->session->set('businessList', '');
        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "sayHello"));
    }
}
