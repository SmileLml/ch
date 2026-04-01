<?php
$this->dao->update('zt_flow_projectapproval')->set('businessCancel')->eq('N')->where('id')->eq($dataID)->exec();
$this->flow->updateBusinessVersion($dataID);
