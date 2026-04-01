<?php
/**
 * Set search params.
 *
 * @param  object $flow
 * @param  object $action       This param is used in other extensions. Don't remove it.
 * @param  string $actionURL
 * @access public
 * @return void
 */
public function setWorkFlowSearchParams($flow, $action = null, $actionURL = '')
{
    $fieldList = $this->loadModel('workflowfield', 'flow')->getList($flow->module, 'searchOrder, `order`, id');

    $canSearch = false;
    foreach($fieldList as $field)
    {
        if($field->canSearch)
        {
            $canSearch = true;
            break;
        }
    }
    if(!$canSearch) return false;

    $searchConfig = array();
    $searchConfig['module'] = 'work' . $action;

    $fieldValues = array();
    $formName    = "work{$action}Form";
    if($this->session->$formName)
    {
        foreach($this->session->$formName as $formKey => $formField)
        {
            if(strpos($formKey, 'field') !== false)
            {
                $fieldNO      = substr($formKey, 5);
                if(!is_numeric($fieldNO)) continue;

                $fieldNO      = "value" . $fieldNO;
                $formNameList = $this->session->$formName;
                $fieldValue   = $formNameList[$fieldNO];

                if($fieldValue) $fieldValues[$formField][$fieldValue] = $fieldValue;
            }
        }
    }

    foreach($fieldList as $field)
    {
        if(empty($field->canSearch)) continue;

        if($field->field == 'project' && $flow->module = 'business')
        {
            $field->options = $this->dao->select('id, name')->from('zt_flow_projectapproval')->where('deleted')->eq(0)->fetchPairs();
        }
        elseif(in_array($field->control, $this->config->workflowfield->optionControls))
        {
            $field->options = $this->workflowfield->getFieldOptions($field, true, zget($fieldValues, $field->field, ''), '', $this->config->flowLimit);
        }

        $searchConfig['fields'][$field->field] = $field->name;
        $searchConfig['params'][$field->field] = $this->processSearchParams($field->control, $field->options);
    }

    /* Build search form. */
    if(!$actionURL) $actionURL = helper::createLink($flow->module, 'browse', "mode=bysearch");
    $searchConfig['actionURL'] = $actionURL;

    if(!isset($this->config->{$flow->module})) $this->config->{$flow->module} = new stdclass();
    $this->config->{$flow->module}->search = $searchConfig;

    $this->loadModel('search')->setSearchParams($this->config->{$flow->module}->search);
}

/**
 * Add business diff for approval.
 *
 */
public function addBusinessDiffForApproval($businessID, $projectapprovalID, $operate)
{
    $businessIdList = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq(0)->fetchPairs('business');
    unset($businessIdList[$businessID]);
    $actionID  = $this->loadModel('action')->create('projectapproval', $projectapprovalID, $operate);
    $changes   = array();
    $changes[] = array('field' => $this->lang->flow->businessDiff, 'old' => implode(',', $businessIdList), 'new' => implode(',', $businessIdList) . ',' . $businessID);
    $this->action->logHistory($actionID, $changes);
}

/**
 * Check business list date.
 *
 */
public function checkBusinessListDate($param)
{
    $business = $param['children']['sub_projectbusiness'];
    $end      = $param['end'];
    $message  = array();

    foreach($business['business'] as $key => $value)
    {
        if(empty($value)) continue;
        $data['PRDdate']        = $business['PRDdate'][$key];
        $data['acceptanceDate'] = $business['acceptanceDate'][$key];
        $data['goLiveDate']     = $business['goLiveDate'][$key];

        $projectApproval   = $this->loadModel('projectapproval')->getByID($param['project']);
        if($data['PRDdate'] > $data['goLiveDate']) $message['childrensub_projectbusinessPRDdate' . $key] = $this->lang->flow->gtGoLiveDate;

        if($data['goLiveDate'] > $data['acceptanceDate']) $message['childrensub_projectbusinessgoLiveDate' . $key] = $this->lang->flow->gtAcceptanceDate;

        if($data['acceptanceDate'] > $end) $message['childrensub_projectbusinessacceptanceDate' . $key] = sprintf($this->lang->flow->gtEnd, $end);
    }

    return $message;
}

/**
 * Check business date.
 *
 */
public function checkBusinessDate($param)
{
    $checkFields = array('goLiveDate', 'acceptanceDate', 'PRDdate');
    foreach($checkFields as $checkField)
    {
        $langField = 'empty' . ucfirst($checkField);
        if(empty($param[$checkField])) return array('result' => 'fail', 'message' => array($checkField => $this->lang->flow->$langField));
    }

    $projectApproval   = $this->loadModel('projectapproval')->getByID($param['project']);
    if($param['PRDdate'] > $param['goLiveDate']) return array('result' => 'fail', 'message' => $this->lang->flow->gtGoLiveDate);

    if($param['goLiveDate'] > $param['acceptanceDate']) return array('result' => 'fail', 'message' => $this->lang->flow->gtAcceptanceDate);

    if($param['acceptanceDate'] > $projectApproval->end) return array('result' => 'fail', 'message' => sprintf($this->lang->flow->gtEnd, $projectApproval->end));

    return array('result' => 'success', 'message' => '');
}

/**
 * Get projectapproval browse sql.
 */
public function getProjectapprovalBrowseSql()
{
    $this->loadModel('group');
    $userAccount         = $this->app->user->account;
    $architect           = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->architect);
    $PMO                 = $this->loadModel('user')->getUsersByUserGroupName('PMO');
    $projectapprovalRole = $this->loadModel('user')->getUsersByUserGroupName($this->lang->group->projectapprovalRole);
    $seniorExecutive     = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->seniorExecutive);
    $infoAttache         = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoAttache);
    $infoLeqader         = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoLeqader);

    if(isset($architect[$userAccount]) || isset($PMO[$userAccount]) || isset($projectapprovalRole[$userAccount]) || isset($seniorExecutive[$userAccount])  || $this->app->user->admin)
    {
        return '1 AND 1=1';
    }

    if(isset($infoLeqader[$userAccount]) || isset($infoAttache[$userAccount]))
    {
        $allDept               = $this->loadModel('dept')->getAllChildId($this->app->user->dept);
        $leaderDepts           = $this->dao->select('id')->from('zt_dept')->where('leaders')->like("%{$userAccount}%")->fetchPairs('id');

        foreach($leaderDepts as $leaderDept)
        {
           $allDept = array_merge($allDept, $this->loadModel('dept')->getAllChildId($leaderDept));
        }

        $projectapprovalIdList = $this->dao->select('parent')->from('zt_flow_projectmembers')->where('account')->eq($userAccount)->fetchAll('parent');

        $projectapprovalIdList = array_map(function($value){
            return $value->parent;
        }, $projectapprovalIdList);

        $sql = empty($projectapprovalIdList) ? "(1 AND businessPM = '{$userAccount}')" : '(1 AND businessPM = "' . $userAccount . '" OR id in (' . join(',', $projectapprovalIdList) . '))';

        return empty($allDept) ?  '1 AND (1 != 1 OR ' . $sql . ')' : "1 AND (`responsibleDept` in (" . join(',', $allDept) . ") OR $sql)";
    }

    $projectapprovalIdList = $this->dao->select('parent')->from('zt_flow_projectmembers')->where('account')->eq($userAccount)->fetchAll('parent');

    $projectapprovalIdList = array_map(function($value){
        return $value->parent;
    }, $projectapprovalIdList);

    $sql  = "1 AND businessPM = '{$userAccount}'";
    $sql .= empty($projectapprovalIdList) ? '' : ' OR id in (' . join(',', $projectapprovalIdList) . ')';
    return $sql;
}

/**
 * Get business browse sql.
 *
 * @access public
 * @return string
 */
public function getBusinessBrowseSql()
{
    $account = $this->app->user->account;
    $dept    = $this->app->user->dept;

    $projectapprovalIdList = $this->dao->select('parent')->from('zt_flow_projectmembers')->where('account')->eq($account)->fetchPairs('parent', 'parent');

    $sql  = " (businessPM = '{$account}' OR createdBy = '{$account}'";
    $sql .= empty($projectapprovalIdList) ? '' : ' OR project in (' . join(',', $projectapprovalIdList) . ')';

    if($dept)
    {
        $deptPath     = $this->dao->select('path')->from('zt_dept')->where('id')->eq($dept)->fetch('path');
        $allChildDept = $this->loadModel('dept')->getAllChildId($this->app->user->dept);
        $allDept      = trim($deptPath . implode(',', $allChildDept), ',');
        if($allDept)
        {
            $sql .= empty($allDept) ?  '' : " OR `createdDept` in ($allDept)";

            foreach(explode(',', $allDept) as $deptId) $sql .= " OR find_in_set($deptId, `dept`)";
        }
    }

    $reviewBusinessIdList = $this->dao->select('t2.objectID')->from(TABLE_APPROVALNODE)->alias('t1')
        ->leftJoin(TABLE_APPROVALOBJECT)->alias('t2')->on('t1.approval = t2.approval')
        ->where('t1.account')->eq($account)
        ->andWhere('t1.result')->ne('ignore')
        ->andWhere('t2.objectType')->eq('business')
        ->fetchPairs();
    if($reviewBusinessIdList) $sql .= ' OR id in (' . join(',', $reviewBusinessIdList) . ')';

    /* 创建SQL查询数据。*/
    $businessIdList = $this->dao->select('id')->from('zt_flow_business')
        ->where('deleted')->eq('0')
        ->andWhere('acl', true)->eq('open')
        ->orWhere('(acl')->eq('private')
        ->andWhere($sql)
        ->markRight(3)
        ->fetchPairs();

     $businessQuery = '';
     if($businessIdList) $businessQuery = '`id` IN (' . join(',', $businessIdList) . ')';

    return $businessQuery;
}

/**
 * Ajax get project cost.
 *
 * @access public
 * @return mixed
 */
public function ajaxGetProjectCost()
{
    $projectCosts = $this->config->costType;

    $this->loadModel('projectrole');
    $result = [];
    foreach($projectCosts as $key => $projectCost)
    {
        $config           = json_decode($projectCost);
        $config->costUnit = zget($this->lang->projectrole->costUnitList, $config->costUnit, '');
        $result[$key]     = $config;
    }

    return $result;
}

/**
 * Create version by object type.
 *
 * @param  int    $objectID
 * @param  string $objectType
 * @access public
 * @return mixed
 */
public function createVersionByObjectType($objectID, $objectType)
{
    $now           = helper::now();
    $objectVersion = $this->dao->select('version')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($objectID)->andWhere('objectType')->eq($objectType)->orderBy('version_desc')->fetch('version');
    $version       = $objectVersion ? $objectVersion + 1 : 1;

    $flow    = $this->loadModel('workflow', 'flow')->getByModule($objectType);
    $element = $this->getDataByID($flow, $objectID);

    if($objectType == 'projectapproval')
    {
        $this->updateBusinessInfo($objectID);
        $this->updateProjectCost($objectID);
    }

    $childDatas = [];
    $fields       = $this->loadModel('workflowaction', 'flow')->getFields($objectType, 'view', true);
    $childModules = $this->loadModel('workflow', 'flow')->getList('browse', 'table', '', $objectType);

    foreach($childModules as $childModule)
    {
        $key = 'sub_' . $childModule->module;

        if(isset($fields[$key]) && $fields[$key]->show)
        {
            $childData = [];
            $childData = $this->getDataList($childModule, '', 0, '', $objectID, 'id_asc');

            $childDatas[$key] = $childData;
        }
    }

    if($objectType == 'business')
    {
        $businessstakeholder = $this->dao->select('*')->from('zt_flow_businessstakeholder')->where('parent')->eq($objectID)->fetchAll();
        $childDatas['sub_businessstakeholder'] = $businessstakeholder;
    }

    $element->children = $childDatas;

    $object = new stdClass();
    $object->objectID    = $objectID;
    $object->objectType  = $objectType;
    $object->version     = $version;
    $object->createdDate = $now;
    $object->createdBy   = $this->app->user->account;
    $object->element     = json_encode($element);
    $object->action      = $this->app->rawMethod;
    if($this->app->rawMethod == 'approvalsubmit3')
    {
        $actionCount = $this->dao->select('actionCount')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($objectID)->andWhere('objectType')->eq($objectType)->andWhere('action')->eq('approvalsubmit3')->orderBy('version_desc')->fetch('actionCount');
        $object->actionCount = $actionCount ? $actionCount + 1 : 1;
    }

    $this->dao->insert(TABLE_OBJECTVERSION)->data($object)->exec();
}

/**
 * Create version by object type.
 *
 * @param  int    $objectID
 * @param  string $objectType
 * @access public
 * @return mixed
 */
public function createChildHistory($objectID, $actionID, $oldVersion, $version)
{
    $object    = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($objectID)->andWhere('objectType')->eq('projectapproval')->andWhere('version')->eq($version)->fetch('element');
    $oldObject = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($objectID)->andWhere('objectType')->eq('projectapproval')->andWhere('version')->eq($oldVersion)->fetch('element');

    $childHistory = new stdClass();
    $childHistory->action = $actionID;
    $childHistory->old    = $oldObject;
    $childHistory->new    = $object;

    $this->dao->insert(TABLE_CHILDHISTORY)->data($childHistory)->exec();
}

/**
 * Merge version by object type.
 *
 * @param  int    $objectID
 * @param  string $objectType
 * @access public
 * @return mixed
 */
public function mergeVersionByObjectType($objectID, $objectType)
{
    $flow    = $this->loadModel('workflow', 'flow')->getByModule($objectType);
    $element = $this->getDataByID($flow, $objectID);

    $childDatas = [];
    $fields       = $this->loadModel('workflowaction', 'flow')->getFields($objectType, 'view', true);
    $childModules = $this->loadModel('workflow', 'flow')->getList('browse', 'table', '', $objectType);

    if($childModules)
    {
        foreach($childModules as $childModule)
        {
            $key = 'sub_' . $childModule->module;

            if(isset($fields[$key]) && $fields[$key]->show)
            {
                $childData = [];
                $childData = $this->getDataList($childModule, '', 0, '', $objectID, 'id_asc');

                $childDatas[$key] = $childData;
            }
        }
    }

    if($objectType == 'business')
    {
        $businessstakeholder = $this->dao->select('*')->from('zt_flow_businessstakeholder')->where('parent')->eq($objectID)->andWhere('stakeholder')->ne('')->fetchAll();
        $childDatas['sub_businessstakeholder'] = $businessstakeholder;
    }

    $element->children = $childDatas;
    if($objectType == 'business' && $element->status == 'projectchange')
    {
        $versionInfo = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($objectID)->andWhere('objectType')->eq($objectType)->andWhere('version')->eq($element->version)->fetch('element');
        $versionInfo = json_decode($versionInfo);
        if($versionInfo->status == 'approvedProject') return;
    }

    if($objectType == 'projectapproval')
    {
        $changeStatus = array('approvedProject', 'design', 'devTest', 'closure');
        if(in_array($element->status, $changeStatus))
        {
            $currentStatus = $element->oldStatus;
            $projectID   = $this->dao->select('id')->from('zt_project')->where('instance')->eq($objectID)->fetch('id');
            $storyIdList = $this->dao->select('story')->from('zt_projectstory')->where('project')->eq($projectID)->fetchPairs();
            if($storyIdList)
            {
                $storyList = $this->dao->select('id')->from('zt_story')->where('id')->in($storyIdList)->andWhere('deleted')->eq(0)->fetchAll();
                if($storyList) $currentStatus = 'design';
            }
            

            $businessIdList = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($objectID)->andWhere('deleted')->eq(0)->fetchPairs('business', 'business');

            if($businessIdList && $storyIdList)
            {
                $businessList   = $this->dao->select('*')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll();
                $isAllBeOnline = true;
                foreach($businessList as $business)
                {
                    if(!in_array($business->status, array('beOnline', 'closed', 'cancelled'))) $isAllBeOnline = false;
                    if(in_array($business->status, array('beOnline', 'closed', 'PRDPassed'))) $currentStatus = 'devTest';
                }
                if($isAllBeOnline) $currentStatus = 'closure';
            }

            if($currentStatus)
            {
                $element->oldStatus = $element->status;
                $element->status    = $currentStatus;

                $this->dao->update('zt_flow_projectapproval')->set('status')->eq($currentStatus)->set('oldStatus')->eq($element->oldStatus)->where('id')->eq($objectID)->exec();
                if($element->oldStatus != $currentStatus)
                {
                    $this->dao->update(TABLE_OBJECTVERSION)->set('element')->eq(json_encode($element))->where('objectID')->eq($objectID)->andWhere('objectType')->eq($objectType)->orderBy('createdDate_desc')->limit(1)->exec();

                    if(in_array($currentStatus, array('design', 'devTest', 'closure')))
                    {
                        $actionID  = $this->loadModel('action')->create('projectapproval', $objectID, 'change'.$currentStatus);
                        $changes[] = ['field' => 'status', 'old' => $element->oldStatus, 'new' => $currentStatus];
                        $this->loadModel('action')->logHistory($actionID, $changes);

                        $this->mergeVersionByObjectType($objectID, 'projectapproval');
                    }
                }
            }
        }
    }

    $this->dao->update(TABLE_OBJECTVERSION)->set('element')->eq(json_encode($element))->where('objectID')->eq($objectID)->andWhere('objectType')->eq($objectType)->orderBy('createdDate_desc')->limit(1)->exec();
}

/**
 * Rollback version by object type.
 *
 * @param  int    $objectID
 * @param  string $objectType
 * @param  string $action
 *
 * @access public
 * @return mixed
 */
public function rollbackVersionByObjectType($objectID, $objectType, $action)
{
    $version = $this->dao->select('version')->from('zt_flow_' . $objectType)->where('id')->eq($objectID)->fetch('version');

    $this->dao->delete()->from(TABLE_OBJECTVERSION)->where('objectID')->eq($objectID)->andWhere('objectType')->eq($objectType)->andWhere('version')->eq($version + 1)->exec();

    $object = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($objectID)->andWhere('objectType')->eq($objectType)->orderBy('createdDate_desc')->limit(1)->fetch('element');

    if($object)
    {
        $object   = json_decode($object);
        $children = $object->children;
        $objectID = $object->id;

        unset($object->children);
        unset($object->id);
        unset($object->filesfiles);
        unset($object->finishFilesfiles);

        if($objectType == 'business')
        {
            $portionPRD  = array('PRDPassed', 'PRDReviewing');
            $oldStatus   = $this->dao->select('oldStatus')->from('zt_flow_' . $objectType)->where('id')->eq($objectID)->fetch('oldStatus');

            $newBusiness = $this->dao->select('*')->from('zt_flow_' . $objectType)->where('id')->eq($objectID)->fetch();

            if(in_array($oldStatus, $portionPRD)) $object->status = 'portionPRD';
        }

        if($objectType == 'projectapproval')
        {
            $changeStatus = array('approvedProject', 'design', 'devTest', 'closure');
            if(in_array($object->status, $changeStatus))
            {
                $oldStatus    = $this->dao->select('oldStatus')->from('zt_flow_' . $objectType)->where('id')->eq($objectID)->fetch('oldStatus');
                $currentStatus = $oldStatus;
                $projectID   = $this->dao->select('id')->from('zt_project')->where('instance')->eq($objectID)->fetch('id');
                $storyIdList = $this->dao->select('story')->from('zt_projectstory')->where('project')->eq($projectID)->fetchPairs();
                if($storyIdList)
                {
                    $storyList = $this->dao->select('id')->from('zt_story')->where('id')->in($storyIdList)->andWhere('deleted')->eq(0)->fetchAll();
                    if($storyList) $currentStatus = 'design';
                }

                $businessIdList = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($objectID)->andWhere('deleted')->eq(0)->fetchPairs('business', 'business');
                $businessList   = $this->dao->select('*')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll();
                $isAllBeOnline = true;
                foreach($businessList as $business)
                {
                    if(!in_array($business->status, array('beOnline', 'closed', 'cancelled'))) $isAllBeOnline = false;
                    if(in_array($business->status, array('beOnline', 'closed', 'PRDPassed'))) $currentStatus = 'devTest';
                }
                if($isAllBeOnline) $currentStatus = 'closure';

                if($currentStatus) $object->status = $currentStatus;
            }
        }

        foreach($object as $key => $value) if(is_array($value)) $object->$key = implode(',', $value);

        $object->version = $version;
        $this->dao->update('zt_flow_'.$objectType)->data($object)->where('id')->eq($objectID)->exec();

        if($objectType == 'business')
        {
            $actionID = $this->loadModel('action')->create('business', $objectID, $action);
            $this->loadModel('action')->logHistory($actionID, common::createChanges($newBusiness, $object));
        }

        if($oldStatus != $object->status)
        {
            if($object->status == 'portionPRD')
            {
                $actionID = $this->loadModel('action')->create('business', $objectID, 'changeportionprd');
                $result['changes']   = array();
                $result['changes'][] = ['field' => 'status', 'old' => $oldStatus, 'new' => 'portionPRD'];
                $this->loadModel('action')->logHistory($actionID, $result['changes']);
            }
            if(in_array($object->status, array('design', 'devTest', 'closure')))
            {
                $actionID = $this->loadModel('action')->create('projectapproval', $objectID, 'change'.$object->status);
                $result['changes']   = array();
                $result['changes'][] = ['field' => 'status', 'old' => $oldStatus, 'new' => $object->status];
                $this->loadModel('action')->logHistory($actionID, $result['changes']);
            }
        }

        if($object->status == 'portionPRD')
        {
            $projectApprovalID     = $this->dao->select('parent')->from('zt_flow_projectbusiness')->where('business')->eq($objectID)->andWhere('deleted')->eq(0)->fetch('parent');
            $projectapprovalStatus = $this->dao->select('status')->from('zt_flow_projectapproval')->where('id')->eq($projectApprovalID)->fetch('status');
            if($projectapprovalStatus == 'devTest')
            {
                $businessIdList     = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectApprovalID)->andWhere('deleted')->eq(0)->fetchPairs('business');
                $businessStatusList = $this->dao->select('status')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll('status');
                $isDesign = false;
                foreach($businessStatusList as $businessStatus)
                {
                    if(in_array($businessStatus, array('PRDPassed', 'closed', 'beOnline'))) $isDesign = true;
                }
                if($isDesign)
                {
                    $this->dao->update('zt_flow_projectapproval')->set('status')->eq('design')->where('id')->eq($projectApprovalID)->exec();
                    $actionID = $this->loadModel('action')->create('projectapproval', $projectApprovalID, 'changedesign');
                    $result['changes']   = array();
                    $result['changes'][] = ['field' => 'status', 'old' => 'devTest', 'new' => 'design'];
                    $this->loadModel('action')->logHistory($actionID, $result['changes']);

                    $this->mergeVersionByObjectType($projectApprovalID, 'projectapproval');
                }
            }
        }

        if($objectType == 'business')
        {
            $businessstakeholders = $this->dao->select('*')->from('zt_flow_businessstakeholder')->where('parent')->eq($objectID)->andWhere('stakeholder')->ne('')->fetchAll();

            $this->dao->delete()->from("zt_flow_businessstakeholder")->where('parent')->eq($objectID)->exec();

            if($businessstakeholders)
            {
                foreach($businessstakeholders as $businessstakeholder) $this->dao->insert('zt_flow_businessstakeholder')->data($businessstakeholder)->exec();
            }
        }

        $childModules = $this->loadModel('workflow', 'flow')->getList('browse', 'table', '', $objectType);

        if($childModules)
        {
            foreach($childModules as $childModule)
            {
                $table = 'zt_flow_' . $childModule->module;
                $key   = 'sub_' . $childModule->module;

                if($childModule->module == 'projectbusiness' && (strpos($action, 'approvalcancel') !== false || strpos($action, 'approvalreview') !== false))
                {
                    $oldBusinessIdList = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($objectID)->andWhere('deleted')->eq('0')->fetchPairs('business');
                }

                $this->dao->delete()->from("`$table`")->where('parent')->eq($objectID)->exec();
                $childDatas = $children->$key;
                foreach($childDatas as $childData)
                {
                    unset($childData->id);
                    unset($childData->mailto);
                    if($table == 'zt_flow_projectprocess')
                    {
                        $childData->changeType = implode(',', $childData->changeType);
                        $childData->name       = implode(',', $childData->name);
                    }
                    $this->dao->insert("`$table`")->data($childData)->exec();
                }

                if($childModule->module == 'projectbusiness' && (strpos($action, 'approvalcancel') !== false || strpos($action, 'approvalreview') !== false)) $this->rollbackBusinessByProjectapproval($objectID, $oldBusinessIdList, $childDatas, $action);
            }
        }
    }
}

/**
 * Update business info.
 *
 * @param  int    $projectApprovalID
 * @access public
 * @return mixed
 */
public function updateBusinessInfo($projectApprovalID)
{
    $projectBusiness = $this->dao->select('id,business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectApprovalID)->andWhere('deleted')->eq('0')->fetchPairs();
    $businesses      = $this->dao->select('id,developmentBudget,businessPM,outsourcingBudget')->from('zt_flow_business')->where('id')->in($projectBusiness)->fetchAll('id');

    foreach($projectBusiness as $id => $business)
    {
        $projectBusinessData = new stdClass();

        $businessPM = $this->dao->select('realname')->from(TABLE_USER)->where('account')->eq($businesses[$business]->businessPM)->fetch('realname');

        $projectBusinessData->developmentBudget = $businesses[$business]->developmentBudget;
        $projectBusinessData->headBusiness      = $businessPM;
        $projectBusinessData->outsourcingBudget = $businesses[$business]->outsourcingBudget;

        $this->dao->update('zt_flow_projectbusiness')->data($projectBusinessData)->where('id')->eq($id)->exec();
    }
}

/**
 * Create project cost.
 *
 * @param  int    $projectApprovalID
 * @access public
 * @return mixed
 */
public function updateProjectCost($projectApprovalID)
{
    $this->loadModel('projectrole');

    $projectCosts = $this->dao->select('id,costType')->from('zt_flow_projectcost')->where('parent')->eq($projectApprovalID)->andWhere('deleted')->eq('0')->fetchPairs();

    foreach($projectCosts as $id => $costType)
    {
        $costData = new stdClass();

        $costTypeList = $this->config->costType;
        $costType     = json_decode($costTypeList->$costType);

        $costData->costUnit = zget($this->lang->projectrole->costUnitList, $costType->costUnit, '');
        $costData->costDesc = $costType->costDesc;

        $this->dao->update('zt_flow_projectcost')->data($costData)->where('id')->eq($id)->exec();
    }
}

/**
 * Create project by project approval.
 *
 * @param  int    $dataID
 * @access public
 * @return mixed
 */
public function createProjectByProjectApproval($dataID)
{
    $project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();

    $programName = '春航' . substr($project->projectNumber, 0, 4) . '年项目集';
    $parent      = $this->dao->select('id')->from(TABLE_PROGRAM)->where('name')->eq($programName)->andWhere('deleted')->eq(0)->fetch('id');

    if(empty($project->program) && $parent)
    {
        $this->dao->update('zt_flow_projectapproval')->set('program')->eq($parent)->where('id')->eq($project->id)->exec();
        $project->program = $parent;
    }

    if($project->status == 'approvedProject')
    {
        $project->name        = $project->projectNumber . '-' . $project->name;
        $project->instance    = $dataID;
        $project->status      = 'wait';
        $project->openedBy    = $project->createdBy;
        $project->openedDate  = helper::now();
        $project->parent      = $project->program;
        $project->level       = $project->pri;
        $project->budget      = $project->future     == '1' ? 0 : $project->budget;
        $project->multiple    = $project->multiple   == '1' ? $project->multiple : 0;
        $project->hasProduct  = $project->hasProduct == '1' ? $project->hasProduct : 0;
        $project->acl         = 'open';
        $project->auth        = 'extend';
        $project->projectType = '2';

        $productPlans      = json_decode($project->productPlan, true);
        $projectColumns    = $this->dao->query("SHOW COLUMNS FROM zt_project")->fetchAll(PDO::FETCH_COLUMN);
        $preProjectColumns = $this->dao->query("SHOW COLUMNS FROM zt_flow_projectapproval")->fetchAll(PDO::FETCH_COLUMN);
        $diffColumns       = array_diff($preProjectColumns, $projectColumns);
        $skip              = 'id,' . implode(',', $diffColumns);

        $this->dao->update('zt_flow_projectapproval')->set('projectApprovalDate')->eq($project->openedDate)->where('id')->eq($dataID)->exec();
        $this->dao->insert(TABLE_PROJECT)->data($project, $skip)->exec();

        /* Add the creater to the team. */
        if(!dao::isError())
        {
            $projectID = $this->dao->lastInsertId();

            /* Set team of project. */
            $members = isset($_POST['teamMembers']) ? $_POST['teamMembers'] : array();

            $projectMembers = $this->dao->select('account,account')->from('zt_flow_projectmembers')->where('parent')->eq($dataID)->andWhere('deleted')->eq('0')->fetchPairs();
            $lastSubmit1    = $this->dao->select('id')->from('zt_action')->where('objectID')->eq($project->id)->andWhere('action')->eq('approvalsubmit1')->orderBy('id_desc')->fetch();
            $review1Members = $this->dao->select('actor,actor')->from('zt_action')->where('objectID')->eq($project->id)->andWhere('action')->eq('approvalreview1')->andWhere('id')->gt($lastSubmit1->id)->fetchPairs();
            $lastSubmit2    = $this->dao->select('id')->from('zt_action')->where('objectID')->eq($project->id)->andWhere('action')->eq('approvalsubmit2')->orderBy('id_desc')->fetch();
            $review2Members = $this->dao->select('actor,actor')->from('zt_action')->where('objectID')->eq($project->id)->andWhere('action')->eq('approvalreview2')->andWhere('id')->gt($lastSubmit2->id)->fetchPairs();

            array_push($members, $project->PM, $project->createdBy, $this->app->user->account);
            $members = array_unique($members + $projectMembers + $review1Members + $review2Members);
            $roles   = $this->loadModel('user')->getUserRoles(array_values($members));

            $this->loadModel('execution');

            $teamMembers = array();
            foreach($members as $account)
            {
                if(empty($account)) continue;

                $member = new stdClass();
                $member->root    = $projectID;
                $member->type    = 'project';
                $member->account = $account;
                $member->role    = zget($roles, $account, '');
                $member->join    = helper::now();
                $member->days    = zget($project, 'days', 0);
                $member->hours   = $this->config->execution->defaultWorkhours;
                $this->dao->insert(TABLE_TEAM)->data($member)->exec();
                $teamMembers[$account] = $member;
            }
            $this->execution->addProjectMembers($projectID, $teamMembers);

            $whitelist = explode(',', $project->whitelist);
            $this->loadModel('personnel')->updateWhitelist($whitelist, 'project', $projectID);

            /* Create doc lib. */
            $this->app->loadLang('doc');
            $authorizedUsers = array();

            if($project->parent and $project->acl == 'program')
            {
                $stakeHolders    = $this->loadModel('stakeholder')->getStakeHolderPairs($project->parent);
                $authorizedUsers = array_keys($stakeHolders);

                foreach(explode(',', $project->whitelist) as $user)
                {
                    if(empty($user)) continue;
                    $authorizedUsers[$user] = $user;
                }

                $authorizedUsers[$project->PM]       = $project->PM;
                $authorizedUsers[$project->openedBy] = $project->openedBy;
                $authorizedUsers[$program->PM]       = $program->PM;
                $authorizedUsers[$program->openedBy] = $program->openedBy;
            }

            $lib = new stdclass();
            $lib->project   = $projectID;
            $lib->name      = $this->lang->doclib->main['project'];
            $lib->type      = 'project';
            $lib->main      = '1';
            $lib->acl       = 'default';
            $lib->users     = ',' . implode(',', array_filter($authorizedUsers)) . ',';
            $lib->vision    = zget($project, 'vision', 'rnd');
            $lib->addedBy   = $this->app->user->account;
            $lib->addedDate = helper::now();
            $this->dao->insert(TABLE_DOCLIB)->data($lib)->exec();

            if(!$project->hasProduct)
            {
                /* If parent not empty, link products or create products. */
                $product = new stdclass();
                $product->name           = $project->name;
                $product->shadow         = 1;
                $product->bind           = $project->parent ? 0 : 1;
                $product->program        = $project->parent ? $project->parent : 0;
                $product->acl            = $project->acl == 'open' ? 'open' : 'private';
                $product->PO             = $project->PM;
                $product->createdBy      = $project->openedBy;
                $product->createdDate    = helper::now();
                $product->status         = 'normal';
                $product->line           = 0;
                $product->desc           = '';
                $product->createdVersion = $this->config->version;
                $product->vision         = zget($project, 'vision', 'rnd');

                $this->dao->insert(TABLE_PRODUCT)->data($product)->exec();
                $productID = $this->dao->lastInsertId();
                $this->loadModel('action')->create('product', $productID, 'opened');
                $this->dao->update(TABLE_PRODUCT)->set('`order`')->eq($productID * 5)->where('id')->eq($productID)->exec();
                if($product->acl != 'open') $this->loadModel('user')->updateUserView($productID, 'product');

                $productSettingList = isset($this->config->global->productSettingList) ? json_decode($this->config->global->productSettingList, true) : array();
                if($productSettingList)
                {
                    $productSettingList[] = $productID;
                    $this->loadModel('setting')->setItem('system.common.global.productSettingList', json_encode($productSettingList));
                }

                $projectProduct = new stdclass();
                $projectProduct->project = $projectID;
                $projectProduct->product = $productID;
                $projectProduct->branch  = 0;
                $projectProduct->plan    = 0;

                $this->dao->insert(TABLE_PROJECTPRODUCT)->data($projectProduct)->exec();
            }

            if($project->hasProduct)
            {
                foreach($productPlans as $index => $productPlan)
                {
                    $_POST['products'][$index] = $productPlan['products'];
                    if(isset($productPlan['branch'])) $_POST['branch'][$index]                  = explode(',', $productPlan['branch']);
                    if(isset($productPlan['plans']))  $_POST['plans'][$productPlan['products']] = explode(',', $productPlan['plans']);
                }

                $this->loadModel('project')->updateProducts($projectID);
            }

            /* Save order. */
            $this->dao->update(TABLE_PROJECT)->set('`order`')->eq($projectID * 5)->where('id')->eq($projectID)->exec();
            $this->loadModel('program')->setTreePath($projectID);

            /* Add project admin. */
            $groupPriv = $this->dao->select('t1.*')->from(TABLE_USERGROUP)->alias('t1')
                ->leftJoin(TABLE_GROUP)->alias('t2')->on('t1.`group` = t2.id')
                ->where('t1.account')->eq($this->app->user->account)
                ->andWhere('t2.role')->eq('projectAdmin')
                ->fetch();

            if(!empty($groupPriv))
            {
                $newProject = $groupPriv->project . ",$projectID";
                $this->dao->update(TABLE_USERGROUP)->set('project')->eq($newProject)->where('account')->eq($groupPriv->account)->andWhere('`group`')->eq($groupPriv->group)->exec();
            }
            else
            {
                $projectAdminID = $this->dao->select('id')->from(TABLE_GROUP)->where('role')->eq('projectAdmin')->fetch('id');

                $groupPriv = new stdclass();
                $groupPriv->account = $this->app->user->account;
                $groupPriv->group   = $projectAdminID;
                $groupPriv->project = $projectID;
                $this->dao->replace(TABLE_USERGROUP)->data($groupPriv)->exec();
            }

            if($project->acl != 'open') $this->loadModel('user')->updateUserView($projectID, 'project');

            if(empty($project->multiple) && !in_array($project->model, array('waterfall', 'waterfallplus'))) $this->loadModel('execution')->createDefaultSprint($projectID);

            $this->dao->update('zt_flow_projectbusiness')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
            $this->dao->update('zt_flow_projectmembers')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
            $this->dao->update('zt_flow_projectcost')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
            $this->dao->update('zt_flow_projectvalue')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
            $this->dao->update('zt_flow_projectprocess')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();

            $this->mergeVersionByObjectType($dataID, 'projectapproval');

            $files = $this->dao->select('*')->from(TABLE_FILE)
                ->where('objectType')->eq('projectapproval')
                ->andWhere('objectID')->eq($dataID)
                ->fetchAll();
            foreach($files as $file)
            {
                unset($file->id);
                $file->objectType = 'project';
                $file->objectID   = $projectID;
                $this->dao->insert(TABLE_FILE)->data($file)->exec();
            }

            $this->loadModel('action')->create('project', $projectID, 'opened');
        }
    }
}

/**
 * Update project by project approval.
 *
 * @param  int    $dataID
 * @access public
 * @return mixed
 */
public function updateProjectByProjectApproval($dataID)
{
    $project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($dataID)->fetch();

    $project->name   = $project->projectNumber . '-' . $project->name;
    $project->parent = $project->program;

    $projectID         = $this->dao->select('id')->from('zt_project')->where('instance')->eq($dataID)->fetch('id');
    $oldProject        = $this->dao->select('*')->from('zt_project')->where('id')->eq($projectID)->fetch();
    $projectColumns    = $this->dao->query("SHOW COLUMNS FROM zt_project")->fetchAll(PDO::FETCH_COLUMN);
    $preProjectColumns = $this->dao->query("SHOW COLUMNS FROM zt_flow_projectapproval")->fetchAll(PDO::FETCH_COLUMN);
    $diffColumns       = array_diff($preProjectColumns, $projectColumns);
    $skip              = 'id,status,grade,path,openedBy,openedDate,level,multiple,hasProduct,acl,auth,projectType,assignedTo,instance,approval,reviewers,reviewOpinion,reviewResult,reviewStatus,version,' . implode(',', $diffColumns);


    $this->dao->update(TABLE_PROJECT)->data($project, $skip)->where('id')->eq($projectID)->exec();

    /* Add the creater to the team. */
    if(!dao::isError())
    {
        $projectMembers = $this->dao->select('account,account')->from('zt_flow_projectmembers')->where('parent')->eq($dataID)->andWhere('deleted')->eq('0')->fetchPairs();

        $this->loadModel('user');
        $team    = $this->user->getTeamMemberPairs($projectID, 'project');
        $members = array_unique($projectMembers + [$project->PM, $project->createdBy, $this->app->user->account]);
        $roles   = $this->loadModel('user')->getUserRoles(array_values($members));

        $this->loadModel('execution');

        $teamMembers = array();
        foreach($members as $account)
        {
            if(empty($account) or isset($team[$account])) continue;

            $member = new stdclass();
            $member->root    = (int)$projectID;
            $member->account = $account;
            $member->join    = helper::today();
            $member->role    = zget($roles, $account, '');
            $member->days    = zget($project, 'days', 0);
            $member->type    = 'project';
            $member->hours   = $this->config->execution->defaultWorkhours;
            $this->dao->replace(TABLE_TEAM)->data($member)->exec();

            $teamMembers[$account] = $member;
        }

        if(!empty($projectID) and !empty($teamMembers)) $this->loadModel('execution')->addProjectMembers($projectID, $teamMembers);

        if($oldProject->parent != $project->program) $this->loadModel('program')->processNode($projectID, $project->program, $oldProject->path, $oldProject->grade);

        $this->dao->update('zt_flow_projectbusiness')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
        $this->dao->update('zt_flow_projectmembers')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
        $this->dao->update('zt_flow_projectcost')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
        $this->dao->update('zt_flow_projectvalue')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();
        $this->dao->update('zt_flow_projectprocess')->set('project')->eq($projectID)->where('parent')->eq($dataID)->exec();

        foreach(explode(',', $skip) as $field)
        {
            if(property_exists($project, $field)) unset($project->$field);
        }

        $changes = common::createChanges($oldProject, $project);
        if($changes)
        {
            $this->loadModel('action');
            $actionID = $this->action->create('project', $projectID, 'edited');
            $this->action->logHistory($actionID, $changes);
        }
    }
}

/**
 * Create project members.
 *
 * @param  int    $projectapprovalID
 * @param  int    $projectID
 * @param  array  $reviewMembers
 * @access public
 * @return mixed
 */
public function createProjectMembers($projectapprovalID, $projectID, $reviewMembers)
{
    $PMOGroupID = $this->dao->select('id')->from(TABLE_GROUP)->where('name')->eq('PMO')->fetch('id');
    $PMO        = $this->dao->select('account')->from(TABLE_USERGROUP)->where('`group`')->eq($PMOGroupID)->andWhere('account')->in($reviewMembers)->fetchPairs('account');

    $architectGroupID = $this->dao->select('id')->from(TABLE_GROUP)->where('name')->eq($this->lang->flow->architect)->fetch('id');
    $architect        = $this->dao->select('account')->from(TABLE_USERGROUP)->where('`group`')->eq($architectGroupID)->andWhere('account')->in($reviewMembers)->fetchPairs('account');

    if($PMO)
    {
        foreach($PMO as $account)
        {
            $members = new stdclass();

            $members->account     = $account;
            $members->projectRole = 'PMO';
            $members->project     = $projectID;
            $members->parent      = $projectapprovalID;
            $members->description = $this->config->PMO;

            $this->dao->insert('zt_flow_projectmembers')->data($members)->exec();
        }
    }

    if($architect)
    {
        foreach($architect as $account)
        {
            $members = new stdclass();

            $members->account     = $account;
            $members->projectRole = 'businessArchitect';
            $members->project     = $projectID;
            $members->parent      = $projectapprovalID;
            $members->description = $this->config->businessArchitect;

            $this->dao->insert('zt_flow_projectmembers')->data($members)->exec();
        }
    }
}

public function checkProcess()
{
    if($_POST['process'] == 'Y')
    {
        if(empty($_POST['children']['sub_projectprocess']['changeType']) || !isset($_POST['children']['sub_projectprocess']['changeType'])) return array('result' => 'fail', 'message' => array('sub_projectprocess' => $this->lang->flow->emptyProcess));
        $subProcess = $_POST['children']['sub_projectprocess'];
        foreach($subProcess['detail'] as $key => $value)
        {
            if(!isset($subProcess['changeType'][$key]) || empty($subProcess['changeType'][$key])) return array('result' => 'fail', 'message' => array('sub_projectprocess' => $this->lang->flow->emptyProcess));

            if(empty($value) && !in_array('LCBZH', $subProcess['changeType'][$key])) return array('result' => 'fail', 'message' => array('sub_projectprocess' => $this->lang->flow->emptyProcess));
            $isEmptySubProcess = true;
            foreach($subProcess['changeType'][$key] as $changeType) if(!empty($changeType)) $isEmptySubProcess = false;
            if($isEmptySubProcess) return array('result' => 'fail', 'message' => array('sub_projectprocess' => $this->lang->flow->emptyProcess));

            $isEmptySubProcess = true;
            foreach($subProcess['name'][$key] as $processName) if(!empty($processName)) $isEmptySubProcess = false;
            if(in_array('LCBZH', $subProcess['changeType'][$key])) $isEmptySubProcess = false;
            if($isEmptySubProcess) return array('result' => 'fail', 'message' => array('sub_projectprocess' => $this->lang->flow->emptyProcess));
        }
    }
    else
    {
        $_POST['children']['sub_projectprocess'] = array();
    }

    return array('result' => 'success');
}

/**
 * Remove empty entries.
 *
 * @param  array  $subDatas
 * @access public
 * @return array
 */
public function removeEmptyEntries($subDatas)
{
    $filteredArray = [];

    $indexes = array_keys($subDatas[array_key_first($subDatas)]);

    foreach($indexes as $index)
    {
        $isEmpty = true;

        foreach($subDatas as $fieldArray)
        {
            if(is_array($fieldArray[$index]))
            {
                foreach($fieldArray[$index] as $mult)
                {
                    if(!empty($mult)) $isEmpty = false;
                }
            }
            if(!is_array($fieldArray[$index]) && !empty(trim($fieldArray[$index])))
            {
                $isEmpty = false;
                break;
            }
        }

        if(!$isEmpty)
        {
            foreach($subDatas as $fieldName => $fieldArray) $filteredArray[$fieldName][$index] = $fieldArray[$index];
        }
    }

    return $filteredArray;
}

/**
 * Get review result options.
 *
 * @param  int    $dataID
 * @param  object $flow
 * @param  array  $options
 * @access public
 * @return array
 */
public function getReviewResultOptions($dataID, $flow, $options)
{
    $this->loadModel('approval');

    $approval = $this->approval->getByObject($flow->module, $dataID);

    $node = $this->dao->select('node')->from(TABLE_APPROVALNODE)
        ->where('approval')->eq($approval->id)
        ->andWhere('status')->eq('doing')
        ->andWhere('account')->eq($this->app->user->account)
        ->fetch('node');

    foreach(json_decode($approval->nodes, true) as $approvalNode)
    {
        if(isset($approvalNode['id']) && $approvalNode['id'] == $node && $approvalNode['onlyBy'] == 'yes') $options = ['pass' => $this->lang->flow->pass];
    }

    return $options;
}

/**
 * Rollback business by projectapproval change.
 *
 * @param  int    $objectID
 * @param  array  $oldBusinessIdList
 * @param  array  $childDatas
 * @param  string $action
 *
 * @access public
 * @return mixed
 */
public function rollbackBusinessByProjectapproval($objectID, $oldBusinessIdList, $childDatas, $action)
{
    $operate = str_replace('approval', 'project', $action);
    $operate = str_replace('review', 'reviewreject', $operate);
    $this->loadModel('action');
    $businessIdList = [];
    foreach($childDatas as $childData) $businessIdList[$childData->business] = $childData->business;

    $removeBusinessList = array_diff($oldBusinessIdList, $businessIdList);

    if($removeBusinessList)
    {
        $removeBusinesses = $this->dao->select('id,status')->from('zt_flow_business')->where('id')->in($removeBusinessList)->fetchPairs('id');
        $this->dao->update('zt_flow_business')->set('status')->eq('activate')->set('project')->eq('')->where('id')->in($removeBusinessList)->exec();

        foreach($removeBusinessList as $businessID)
        {
            $actionID = $this->action->create('business', $businessID, $operate);
            $change = array();
            $change[] = array('field' => 'status', 'old' => $removeBusinesses[$businessID], 'new' => 'activate');
            $this->action->logHistory($actionID, $change);
            $this->mergeVersionByObjectType($businessID, 'business');
        }
    }

    if($businessIdList)
    {
        $projectID = $this->dao->select('id')->from('zt_project')->where('instance')->eq($objectID)->fetch('id');
        if($projectID)
        {
            $this->dao->update('zt_flow_projectbusiness')->set('project')->eq($projectID)->where('business')->in($businessIdList)->exec();

            $this->dao->update('zt_flow_business')->set('project')->eq($objectID)->where('id')->in($businessIdList)->exec();

            $businessDemandList = $this->dao->select('id, demand')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchPairs();
            $demandIdList       = implode(',', $businessDemandList);

            $this->dao->update('zt_demand')->set('project')->eq($objectID)->where('id')->in($demandIdList)->exec();
        }

        if(in_array($action, ['approvalcancel1', 'approvalcancel2', 'approvalreview1', 'approvalreview2']))
        {
            $newBusinessList = $this->dao->select('id,status')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchPairs('id');
            $this->dao->update('zt_flow_business')->set('status')->eq('projecting')->where('id')->in($businessIdList)->exec();
            foreach($businessIdList as $tempBusinessID)
            {
                $actionID = $this->action->create('business', $tempBusinessID, $operate);
                $change = array();
                $change[] = array('field' => 'status', 'old' => $newBusinessList[$tempBusinessID], 'new' => 'projecting');
                $this->action->logHistory($actionID, $change);
            }
        }
    }
}

/**
 * Update business info.
 *
 * @param  int    $projectApprovalID
 * @access public
 * @return mixed
 */
public function updateBusinessByProjectChange($projectApprovalID)
{
    $copyBusinessList = $this->dao->select('*')->from('zt_copyflow_business')->where('project')->eq($projectApprovalID)->andWhere('operator')->eq($this->app->user->account)->fetchAll('business');
    $businessIdList   = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectApprovalID)->andWhere('deleted')->eq(0)->fetchPairs('business', 'business');

    if($copyBusinessList)
    {
        $this->loadModel('action');
        $editedDate = helper::now();

        foreach($copyBusinessList as $businessID => $copyBusiness)
        {
            if(!isset($businessIdList[$businessID])) continue;

            unset($copyBusiness->id);
            unset($copyBusiness->business);
            unset($copyBusiness->operator);

            $copyBusiness->editedBy   = $this->app->user->account;
            $copyBusiness->editedDate = $editedDate;

            $business = $this->dao->select('*')->from('zt_flow_business')->where('id')->eq($businessID)->fetch();
            $change   = commonModel::createChanges($business, $copyBusiness);

            $oldDemands   = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($dataID)->fetch('demand');
            $oldDemands   = explode(',', $oldDemands);
            $demands      = explode(',', $copyBusiness->demand);
            $noIntegrated = array_diff($oldDemands, $demands);

            $this->dao->update(TABLE_DEMAND)->set('stage')->eq(1)->set('project')->eq($copyBusiness->project)->where('id')->in($demands)->exec();
            if($noIntegrated) $this->dao->update(TABLE_DEMAND)->set('stage')->eq(0)->set('project')->eq(0)->where('id')->in($noIntegrated)->exec();

            $copyBusiness->oldStatus = $business->status;
            if($change)
            {
                $this->dao->update('zt_flow_business')->data($copyBusiness)->where('id')->eq($businessID)->exec();

                $currentOperate = $this->app->rawMethod == 'approvalsubmit3' ? 'projectchange' : 'projectcancel';
                $actionID = $this->action->create('business', $businessID, $currentOperate);

                $this->action->logHistory($actionID, $change);
                $tmpChange = array();
                foreach($change as $changeKey => $changeValue)
                {
                    if(in_array($changeValue['field'], array('status', 'isCancel', 'version'))) unset($change[$changeKey]);
                }
                if(!empty($change)) $this->createVersionByObjectType($businessID, 'business');
            }
        }
    }
}

/**
 * Update business version by recall project change.
 *
 * @param  int    $projectApprovalID
 * @access public
 * @return mixed
 */
public function updateBusinessVersion($projectApprovalID, $operate = 'recall')
{
    $currentOperate = ($this->app->rawMethod == 'approvalreview3' || $this->app->rawMethod == 'approvalcancel3') ? 'projectchange' : 'projectcancel';
    $businessIdList = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectApprovalID)->andWhere('deleted')->eq('0')->fetchPairs('business');

    $this->loadModel('action');

    foreach($businessIdList as $businessID)
    {
        $businessVersion = $this->dao->select('version')->from('zt_flow_business')->where('id')->eq($businessID)->andWhere('deleted')->eq('0')->andWhere('status')->eq('projectchange')->orderBy('id_desc')->limit(1)->fetch('version');
        if(!$businessVersion) continue;

        $objectVersion = $this->dao->select('element')->from('zt_objectversion')->where('objectID')->eq($businessID)->andWhere('objectType')->eq('business')->andWhere('version')->eq($businessVersion)->fetch('element');
        $object = json_decode($objectVersion);

        if($operate == 'merge')
        {
            if($object->status == 'approvedProject')
            {
                $this->dao->update('zt_flow_business')->set('status')->eq('cancelled')->where('id')->eq($businessID)->exec();
            }
            else
            {
                $tempStatus = $object->isCancel == 'Y' ? 'cancelled' : 'approvedProject';
                $oldStatus  = $this->dao->select('oldStatus')->from('zt_flow_business')->where('id')->eq($businessID)->fetch('oldStatus');
                if($tempStatus == 'approvedProject')
                {
                    $portionPRD = array('PRDPassed', 'PRDReviewing');

                    $tempStatus = $oldStatus;
                    if(in_array($oldStatus, $portionPRD)) $tempStatus = 'portionPRD';
                }

                $this->dao->update('zt_flow_business')->set('status')->eq($tempStatus)->where('id')->eq($businessID)->exec();

                if($oldStatus != $tempStatus)
                {
                    $actionID = $this->loadModel('action')->create('business', $businessID, 'changeportionprd');
                    $result['changes']   = array();
                    $result['changes'][] = ['field' => 'status', 'old' => $oldStatus, 'new' => $tempStatus];
                    $this->loadModel('action')->logHistory($actionID, $result['changes']);
                }

                if($tempStatus == 'portionPRD')
                {
                    $projectapprovalStatus = $this->dao->select('status')->from('zt_flow_projectapproval')->where('id')->eq($projectApprovalID)->fetch('status');
                    if($projectapprovalStatus == 'devTest')
                    {
                        $businessStatusList = $this->dao->select('status')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll('status');
                        $isDesign = false;
                        foreach($businessStatusList as $businessStatus)
                        {
                            if(in_array($businessStatus, array('PRDPassed', 'closed', 'beOnline'))) $isDesign = true;
                        }
                        if($isDesign)
                        {
                            $this->dao->update('zt_flow_projectapproval')->set('status')->eq('design')->where('id')->eq($projectApprovalID)->exec();
                            $actionID = $this->loadModel('action')->create('projectapproval', $projectApprovalID, 'changedesign');
                            $result['changes']   = array();
                            $result['changes'][] = ['field' => 'status', 'old' => 'devTest', 'new' => 'design'];
                            $this->loadModel('action')->logHistory($actionID, $result['changes']);

                            $this->mergeVersionByObjectType($projectApprovalID, 'projectapproval');
                        }
                    }
                }
            }

            $this->mergeVersionByObjectType($businessID, 'business');

            $this->action->create('business', $businessID, 'pass' . $currentOperate);
        }

        if($operate == 'recall')
        {
            if($object->status == 'approvedProject')
            {
                $this->dao->update('zt_flow_business')->set('status')->eq('approvedProject')->set('isCancel')->eq('N')->where('id')->eq($businessID)->exec();
            }
            else
            {
                $businessStatus = $this->dao->select('status')->from('zt_flow_business')->where('id')->eq($businessID)->fetch('status');
                if($businessStatus == 'projectchange')
                {
                    $version = $businessVersion - 1;
                    $this->dao->update('zt_flow_business')->set('version')->eq($version)->where('id')->eq($businessID)->exec();
                    $this->rollbackVersionByObjectType($businessID, 'business', $currentOperate);

                    $demands = $this->dao->select('demand')->from('zt_flow_business')->where('id')->eq($businessID)->fetch('demand');

                    $demands      = explode(',', $demands);
                    $oldDemands   = $object->demand;
                    $noIntegrated = array_diff($oldDemands, $demands);

                    $this->dao->update(TABLE_DEMAND)->set('stage')->eq(1)->set('project')->eq($object->project)->where('id')->in($demands)->exec();
                    if($noIntegrated) $this->dao->update(TABLE_DEMAND)->set('stage')->eq(0)->set('project')->eq(0)->where('id')->in($noIntegrated)->exec();
                }
            }

            $this->mergeVersionByObjectType($businessID, 'business');

            $this->action->create('business', $businessID, 'cancel' . $currentOperate);
        }
    }
}

/**
 * Check development budget.
 *
 * @param  int    $projectApprovalID
 * @param  int    $developmentBudget
 * @param  string $notNeedBusiness
 * @param  int    $allCostBudget
 * @param  string $operator
 * @access public
 * @return bool
 */
public function checkDevelopmentBudget($projectApprovalID, $developmentBudget = 0, $notNeedBusiness = '', $allCostBudget = 0, $operator = '')
{
    $notNeedStatus = ['draft', 'activate', 'declined'];

    $copyDevelopmentBudgetSum = 0;

    if($operator)
    {
        $copyBusinessList = $this->dao->select('business,developmentBudget')->from('zt_copyflow_business')
            ->where('project')->eq($projectApprovalID)
            ->andWhere('operator')->eq($operator)
            ->beginIF($notNeedBusiness)->andWhere('business')->notin($notNeedBusiness)->fi()
            ->fetchPairs('business', 'developmentBudget');

        $notNeedBusiness          .= ',' . implode(',', array_keys($copyBusinessList));
        $copyDevelopmentBudgetSum  = array_sum($copyBusinessList);
    }

    $developmentBudgetSum = $this->dao->select('IFNULL(sum(developmentBudget), 0) as developmentBudget')->from('zt_flow_business')
        ->where('project')->eq($projectApprovalID)
        ->andWhere('status')->notin($notNeedStatus)
        ->beginIF($notNeedBusiness)->andWhere('id')->notin($notNeedBusiness)->fi()
        ->fetch('developmentBudget');

    $allDevelopmentBudget = $developmentBudgetSum + $developmentBudget + $copyDevelopmentBudgetSum;

    if(empty($allCostBudget))
    {
        $allCostBudget = $this->dao->select('IFNULL(sum(costBudget), 0) as costBudget')->from('zt_flow_projectcost')
            ->where('parent')->eq($projectApprovalID)
            ->andWhere('costType')->eq('itPlanInto')
            ->andWhere('deleted')->eq('0')
            ->fetch('costBudget');
    }

    if($allDevelopmentBudget > $allCostBudget) return false;

    return true;
}
