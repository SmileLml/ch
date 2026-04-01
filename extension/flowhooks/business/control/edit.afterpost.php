<?php
$demands      = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($dataID)->fetch('demand');
$oldDemands   = explode(',', $oldDemands);
$demands      = explode(',', $demands);
$noIntegrated = array_diff($oldDemands, $demands);

$this->dao->update(TABLE_DEMAND)->set('stage')->eq(1)->where('id')->in($demands)->exec();
if($noIntegrated) $this->dao->update(TABLE_DEMAND)->set('stage')->eq(0)->where('id')->in($noIntegrated)->exec();
