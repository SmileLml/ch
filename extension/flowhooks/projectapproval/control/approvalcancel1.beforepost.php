<?php
$currentProjectapproval = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();
if($currentProjectapproval->status != 'reviewing') return $this->send(array('result' => 'fail', 'message' => $this->lang->flow->errorData));