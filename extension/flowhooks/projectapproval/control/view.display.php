<?php
$this->loadModel('projectapproval');
$this->loadModel('action');

$this->session->set('businessViewBackUrl', helper::createLink('projectapproval', 'view', 'dataID=' . $dataID));

$this->lang->projectapproval->action->changedesign                = '$date，状态发生变更，变更为“方案设计阶段”';
$this->lang->projectapproval->action->changedevtest               = '$date，状态发生变更，变更为“研发测试阶段”';
$this->lang->projectapproval->action->changeclosure               = '$date，状态发生变更，变更为“项目收尾阶段”';
$this->lang->projectapproval->action->changeprojectapprovalstatus = '$date，状态通过脚本发生变更';
$this->lang->projectapproval->action->changefinishprojectapproval = '$date，通过脚本修改结项信息';
$this->lang->projectapproval->action->businessreview1             = '$date，由<strong>$actor</strong> 评审业务需求。';
$this->lang->projectapproval->action->businessreview2             = '$date，由<strong>$actor</strong> 变更业务需求。';

$childFields = $this->view->childFields;
$childDatas  = $this->view->childDatas;

$childFields['sub_projectbusiness']['estimate'] = clone $childFields['sub_projectbusiness']['business'];
$childFields['sub_projectbusiness']['estimate']->field = 'estimate';
$childFields['sub_projectbusiness']['estimate']->name  = $this->lang->project->estimate;

$childDatas['sub_projectbusiness'] = $this->loadModel('projectapproval')->processBusiness($childDatas['sub_projectbusiness']);

$oldBusinessIds  = array_column($childDatas['sub_projectbusiness'], 'business');
$oldBusinessList = $this->dao->select('id,name')->from('zt_flow_business')->where('id')->in($oldBusinessIds)->fetchPairs();
$actualCost      = $this->loadModel('projectapproval')->getActualCost($oldBusinessIds);

$childFields['sub_projectbusiness']['business']->options += $oldBusinessList;
$businessIds  = array_column($childDatas['sub_projectbusiness'], 'business');
$businessList = $this->dao->select('*')->from('zt_flow_business')->where('id')->in($businessIds)->fetchAll();
$changeBusiness = array();
$cancelBusiness = array();
$normalBusiness = array();
foreach($businessList as $business)
{
    foreach($childDatas['sub_projectbusiness'] as $sub_projectbusinessKey => $sub_projectbusiness)
    {
        if($business->status == 'projectchange' && $business->id == $sub_projectbusiness->business)
        {
            $changeBusiness[$sub_projectbusinessKey] = $sub_projectbusiness;
            continue;
        }
        elseif(($business->status == 'cancelled' || $business->status == 'closed') && $business->id == $sub_projectbusiness->business)
        {
            $cancelBusiness[$sub_projectbusinessKey] = $sub_projectbusiness;
            continue;
        }
        elseif($business->id == $sub_projectbusiness->business)
        {
            $normalBusiness[$sub_projectbusinessKey] = $sub_projectbusiness;
        }
    }
}

$childDatas['sub_projectbusiness'] = $changeBusiness + $normalBusiness + $cancelBusiness;

$this->view->childFields = $childFields;
$this->view->childDatas  = $childDatas;
$this->view->actualCost  = $actualCost;
