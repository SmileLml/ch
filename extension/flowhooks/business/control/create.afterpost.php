<?php
if($result['result'] == 'success')
{
    $demand = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($result['recordID'])->fetch('demand');
    $this->dao->update(TABLE_DEMAND)->set('stage')->eq('1')->where('id')->in($demand)->exec();
    foreach(explode(',', $demand) as $demandID) $this->loadModel('action')->create('demand', $demandID, 'integrationintobusiness', '', $result['recordID']);

    if($this->post->isDraft == '1')
    {
        $result['locate'] = $this->session->demandList;
    }
    else
    {
        unset($result['locate']);
        $this->session->set('businessList', $this->createLink('business', 'browse'));
        $result['callback'] = array('name' => 'approvalSubmit', 'params' => array('dataID' => $result['recordID']));
    }
    $this->flow->createVersionByObjectType($result['recordID'], 'business');
}
