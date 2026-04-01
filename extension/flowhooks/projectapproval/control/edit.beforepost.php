<?php
if(isset($_POST['children']['sub_projectbusiness']['business']))
{
    $business       = array_filter($_POST['children']['sub_projectbusiness']['business']);
    $uniqueBusiness = array_filter(array_unique($business));

    if(count($uniqueBusiness) < count($business)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectbusiness' => $this->lang->flow->sameBusiness)));
}

$oldBusiness = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($dataID)->andWhere('deleted')->eq('0')->fetchPairs();

foreach($_POST['children'] as $subModule => $subData)
{
   $_POST['children'][$subModule] = $this->flow->removeEmptyEntries($subData);
}

$checkProcessResult = $this->flow->checkProcess();
if($checkProcessResult['result'] == 'fail') return $this->send($checkProcessResult);

if($_POST['netInfoSafe'] == '<br />') $_POST['netInfoSafe'] = '';
if($_POST['risk'] == '<p><br /></p>') $_POST['risk'] = '';