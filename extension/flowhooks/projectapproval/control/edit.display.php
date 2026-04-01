<?php
$childFields = $this->view->childFields;
$childDatas  = $this->view->childDatas;

if(empty($data->netInfoSafe)) $fields['netInfoSafe']->defaultValue = $this->lang->projectapproval->netInfoSafeDefault;
if(empty($data->risk)) $fields['risk']->defaultValue = $this->lang->projectapproval->riskDefault;

$oldBusinessIds  = array_column($childDatas['sub_projectbusiness'], 'business');
$oldBusinessList = $this->dao->select('id,name')->from('zt_flow_business')->where('id')->in($oldBusinessIds)->fetchPairs();

$childFields['sub_projectbusiness']['business']->options += $oldBusinessList;

$this->view->childFields = $childFields;
$this->view->childDatas  = $childDatas;
