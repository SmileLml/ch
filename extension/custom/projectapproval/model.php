<?php
class projectapprovalModel extends model
{
    public function updateReviewFields()
    {
        $dataIdList = array('857','69','2','4','28','71','876','830','74','728','696','52','48','877','698','658','647','655','729','58','677','796','793','82','839','631','676','884','688','787','887','690','818','705','772','861','960','815','8','874');
        $projectapprovals = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->in($dataIdList)->andWhere('status')->eq('finished')->fetchAll();
        foreach($projectapprovals as $projectapproval)
        {
            $currentAction = $this->dao->select('*')->from('zt_action')->where('action')->eq('changefinishprojectapproval')->andWhere('objectID')->eq($projectapproval->id)->andWhere('objectType')->eq('projectapproval')->fetch();
            if($currentAction) continue;
            $this->dao->update('zt_flow_projectapproval')
                ->set('finishReviewDate')->eq($projectapproval->reviewDate)
                ->set('finishReviewLocation')->eq($projectapproval->reviewLocation)
                ->set('finishParticipant')->eq($projectapproval->participant)
                ->set('finishAbsentee')->eq($projectapproval->absentee)
                ->set('finishRecorder')->eq($projectapproval->recorder)
                ->where('id')->eq($projectapproval->id)
                ->exec();

            $actionID = $this->dao->select('id')->from('zt_action')
                ->where('action')->eq('evaluationfeedback')
                ->andWhere('objectType')->eq('projectapproval')
                ->andWhere('objectID')->eq($projectapproval->id)
                ->fetch('id');

            $histories = $this->dao->select('*')->from('zt_history')->where('action')->eq($actionID)->fetchAll();

            $data = array();
            $data['reviewDate']     = '';
            $data['reviewLocation'] = '';
            $data['participant']    = '';
            $data['absentee']       = '';
            $data['recorder']       = '';
            foreach($histories as $history)
            {
                if($history->field == 'reviewDate')     $data['reviewDate'] = $history->new;
                if($history->field == 'reviewLocation') $data['reviewLocation'] = $history->new;
                if($history->field == 'participant')    $data['participant'] = $history->new;
                if($history->field == 'absentee')       $data['absentee'] = $history->new;
                if($history->field == 'recorder')       $data['recorder'] = $history->new;
            }

            $this->dao->update('zt_flow_projectapproval')
                ->set('reviewDate')->eq($data['reviewDate'])
                ->set('reviewLocation')->eq($data['reviewLocation'])
                ->set('participant')->eq($data['participant'])
                ->set('absentee')->eq($data['absentee'])
                ->set('recorder')->eq($data['recorder'])
                ->where('id')->eq($projectapproval->id)
                ->exec();

            $currentProjectapproval = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($projectapproval->id)->fetch();

            $oldData = new stdClass();
            $oldData->finishReviewDate     = $projectapproval->finishReviewDate;
            $oldData->finishReviewLocation = $projectapproval->finishReviewLocation;
            $oldData->finishParticipant    = $projectapproval->finishParticipant;
            $oldData->finishAbsentee       = $projectapproval->finishAbsentee;
            $oldData->finishRecorder       = $projectapproval->finishRecorder;
            $oldData->reviewDate           = $projectapproval->reviewDate;
            $oldData->reviewLocation       = $projectapproval->reviewLocation;
            $oldData->participant          = $projectapproval->participant;
            $oldData->absentee             = $projectapproval->absentee;
            $oldData->recorder             = $projectapproval->recorder;

            $changes  = common::createChanges($oldData, $currentProjectapproval);
            $actionID = $this->loadModel('action')->create('projectapproval', $projectapproval->id, 'changefinishprojectapproval');
            $this->loadModel('action')->logHistory($actionID, $changes);
        }
    }
    /**
     * Get actual cost.
     *
     * @param array $businessIds
     */
    public function getActualCost($businessIds)
    {
        if(empty($businessIds)) return '';
        $actualCost = $this->dao->select('sum(estimate) as actualCost')->from(TABLE_STORY)
            ->where('business')->in($businessIds)
            ->andWhere('type')->eq('requirement')
            ->andWhere('status')->in(array('closed', 'changing', 'devInProgress', 'beOnline', 'cancelled'))
            ->andWhere('deleted')->eq(0)
            ->fetch();
        return $actualCost->actualCost;
    }

    /**
     * Process business.
     *
     * @param array $dataList
     */
    public function processBusiness($dataList, $field = 'business')
    {
        foreach($dataList as $key => $data)
        {
            $dataList[$key] = $this->processBusinessData($data, $field);
        }

        return $dataList;
    }

    /**
     * Process business data.
     * @param object $dataList
     * @param string $type
     */
    public function processBusinessData($data, $field)
    {
        $requirements = $this->dao->select('id,title,estimate')->from(TABLE_STORY)->where('business')->eq($data->$field)->andWhere('type')->eq('requirement')->andWhere('deleted')->eq(0)->fetchAll();

        $data->estimate    = $data->developmentBudget;

        foreach($requirements as $requirement)
        {
            $data->estimate     = bcsub($data->estimate, $requirement->estimate, '2');
        }

        return $data;
    }

    public function changeStatusClosure($businessIdList)
    {
        $projectapprovalIdList     = $this->dao->select('parent')->from('zt_flow_projectbusiness')->where('business')->in($businessIdList)->andWhere('deleted')->eq(0)->fetchPairs('parent');
        $projectapprovalStatusList = $this->dao->select('id,status')->from('zt_flow_projectapproval')->where('id')->in($projectapprovalIdList)->fetchPairs('id');

        $noChangeStatus        = array('cancelled', 'finished', 'cancelReview', 'finishReview', 'closure', 'changeReview');
        $changeProjectapprovalIdList = array();
        foreach($projectapprovalStatusList as $projectapprovalID => $projectapprovalStatus)
        {
            if(!in_array($projectapprovalStatus, $noChangeStatus))
            {
                $businessIdList     = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq(0)->fetchPairs('business', 'business');
                $businessStatusList = $this->dao->select('status')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll('status');

                $isClosure = true;
                foreach($businessStatusList as $businessStatus)
                {
                    if($businessStatus->status != 'beOnline' and $businessStatus->status != 'closed' and $businessStatus->status != 'cancelled') $isClosure = false;
                }
                if($isClosure) $changeProjectapprovalIdList[] = $projectapprovalID;
            }
        }
        if(empty($changeProjectapprovalIdList)) return false;
        $changeProjectapprovalOldStatusList = $this->dao->select('id,status')->from('zt_flow_projectapproval')->where('id')->in($changeProjectapprovalIdList)->fetchPairs('id');
        $this->dao->update('zt_flow_projectapproval')->set('status')->eq('closure')->where('id')->in($changeProjectapprovalIdList)->exec();
        foreach($changeProjectapprovalOldStatusList as $key => $changeProjectapprovalOldStatus)
        {
            if($changeProjectapprovalOldStatus == 'closure') continue;

            $this->loadModel('flow')->mergeVersionByObjectType($key, 'projectapproval');
            $actionID = $this->loadModel('action')->create('projectapproval', $key, 'changeclosure');
            $result['changes']   = array();
            $result['changes'][] = ['field' => 'status', 'old' => $changeProjectapprovalOldStatus, 'new' => 'closure'];
            $this->loadModel('action')->logHistory($actionID, $result['changes']);
        }
    }

    /**
     * Get business menu for projectapproval
     *
     * @access public
     * @return void
     */
    public function getProjectapprovalBusiness()
    {
        return $this->dao->select('id, name')->from('zt_flow_business')
            ->where('deleted')->eq('0')
            ->andWhere('status')->eq('activate')
            ->andWhere('businessPM')->eq($this->app->user->account)
            ->andWhere('project')->eq('')
            ->orderBy('id_desc')
            ->fetchPairs('id', 'name');
    }

    /**
     * Get by id.
     *
     * @param  int    $projectapprovalID
     * @param  string $orderBy
     * @param  int    $pager
     * @access public
     * @return array
     */
    public function getByID($projectapprovalID)
    {
       return $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($projectapprovalID)->fetch();
    }

    /**
     * Get business ids by projectapprovalID.
     *
     * @param  int    $projectapprovalID
     * @access public
     * @return array
     */
    public function getBusinessIdsByProjectapprovalID($projectapprovalID)
    {
       return $this->dao->select('business')
       ->from('zt_flow_projectbusiness')
       ->where('parent')->eq($projectapprovalID)
       ->andWhere('deleted')->eq(0)
       ->fetchPairs();
    }

    /**
     * Get business list by projectapprovalID.
     *
     * @param  int    $projectapprovalID
     * @param  string $orderBy
     * @param  int    $pager
     * @access public
     * @return array
     */
    public function getBusinessListByProjectapprovalID($projectapprovalID, $orderBy, $pager)
    {
        $businessIdList = $this->dao->select('business')
            ->from('zt_flow_projectbusiness')
            ->where('parent')->eq($projectapprovalID)
            ->andWhere('deleted')->eq(0)
            ->fetchPairs();

        $businessList = $this->dao->select('*')->from('zt_flow_business')
            ->where('deleted')->eq('0')
            ->andWhere('id')->in($businessIdList)
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');

        foreach($businessList as $data) $data = $this->loadModel('flow')->processDBData('business', $data);

        return $businessList;
    }

    /**
     * Get business list.
     *
     * @param  string    $status
     * @access public
     * @return array
     */
    public function getBusinessPairs($status = '')
    {
        $businessList = $this->dao->select('*')->from('zt_flow_business')
            ->where('deleted')->eq('0')
            ->beginIF(!empty($status))->andWhere('status')->eq($status)->fi()
            ->fetchPairs('id', 'name');

        return $businessList;
    }

    /**
     * Update business status to declined.
     *
     * @param  int    $projectapprovalID
     * @access public
     * @return void
     */
    public function updateBusinessStatusToDeclined($projectapprovalID)
    {
        $businessIDList = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq(0)->fetchPairs('business', 'business');

        if($businessIDList)
        {
            $businessList = $this->dao->select('id, status')->from('zt_flow_business')->where('id')->in($businessIDList)->fetchPairs('id', 'status');

            $this->dao->update('zt_flow_business')->set('status')->eq('declined')->set('project')->eq($projectapprovalID)->where('id')->in($businessIDList)->exec();

            $this->loadModel('flow');
            $this->loadModel('action');
            foreach($businessIDList as $businessID)
            {
                $actionID = $this->loadModel('action')->create('business', $businessID, 'changedeclined');

                $changes[] = ['field' => 'status', 'old' => $businessList[$businessID], 'new' => 'declined'];
                $this->action->logHistory($actionID, $changes);

                $this->flow->mergeVersionByObjectType($businessID, 'business');
            }
        }
    }

    /**
     * Link business.
     *
     * @param  int    $projectapprovalID
     * @access public
     * @return array
     */
    public function linkBusiness($projectapprovalID)
    {
        $project = $this->loadModel('project')->getByInstance($projectapprovalID);

        $data = array();
        foreach($_POST['business'] as $key => $id)
        {
            $data[$key] = new stdClass();
            $data[$key]->business          = $id;
            $data[$key]->parent            = $projectapprovalID;
            $data[$key]->developmentBudget = $_POST['developmentBudget'][$key];
            $data[$key]->headBusiness      = $_POST['headBusiness'][$key];
            $data[$key]->outsourcingBudget = $_POST['outsourcingBudget'][$key];

            if($project) $data[$key]->project = $project->id;
        }

        foreach($data as $item)
        {
            $this->dao->insert('zt_flow_projectbusiness')->data($item)->exec();
        }

        $projectBusiness = $this->dao->select('id,business,PRDdate,acceptanceDate,goLiveDate')->from('zt_flow_projectbusiness')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq('0')->fetchAll('id');
        $this->dao->update('zt_flow_business')->set('status')->eq('approvedProject')->set('project')->eq($projectapprovalID)->where('id')->in($_POST['business'])->exec();
        $this->loadModel('flow')->mergeVersionByObjectType($businessID, 'business');

        $projectapproval  = $this->getByID($projectapprovalID);
        $bussinessList    = $this->dao->select("*")->from('zt_flow_business')->where('id')->in($_POST['business'])->fetchAll();
        $relevantDeptList = explode(',', $projectapproval->relevantDept);
        $businessUnitList = explode(',', $projectapproval->businessUnit);
        foreach($bussinessList as $business)
        {
            if(!in_array($business->createdDept, $relevantDeptList))  $relevantDeptList[] = $business->createdDept;
            if(!in_array($business->businessUnit, $businessUnitList)) $businessUnitList[] = (string)$business->businessUnit;
        }

        $this->dao->update('zt_flow_projectapproval')->set('relevantDept')->eq(implode(',', $relevantDeptList))->set('businessUnit')->eq(implode(',', $businessUnitList))->where('id')->eq($projectapprovalID)->exec();

        $this->updateProjectApproval($projectapprovalID, $_POST['business']);

        return true;
    }

    /**
     * Update project approval.
     *
     * @param  int    $projectapprovalID
     * @param  array  $businessIdList
     * @access public
     * @return mixed
     */
    public function updateProjectApproval($projectapprovalID, $projectBusinesses, $operate = 'projectapproval')
    {
        $this->loadModel('flow');

        foreach($projectBusinesses as $businessID => $projectBusiness)
        {
            $oldBusiness = $this->dao->select('*')->from('zt_flow_business')->where('id')->eq($businessID)->fetch();

            $businessData = array();
            $businessData['project']        = $projectapprovalID;
            $businessData['PRDdate']        = $projectBusiness->PRDdate;
            $businessData['acceptanceDate'] = $projectBusiness->acceptanceDate;
            $businessData['goLiveDate']     = $projectBusiness->goLiveDate;

            $this->dao->update('zt_flow_business')->data($businessData)->where('id')->eq($businessID)->exec();

            $actionID = $this->loadModel('action')->create('business', $businessID, $operate);
            $changes  = common::createChanges($oldBusiness, $businessData);
            if($changes) $this->action->logHistory($actionID, $changes);

            $this->flow->mergeVersionByObjectType($businessID, 'business');
        }

        $businessDemandList = $this->dao->select('id, demand')->from('zt_flow_business')->where('id')->in(array_keys($projectBusinesses))->fetchPairs();
        $demandIdList       = implode(',', $businessDemandList);

        $this->dao->update('zt_demand')->set('project')->eq($projectapprovalID)->where('id')->in($demandIdList)->exec();
    }

    /**
     * Get value by work flow.
     *
     * @param  int    $field
     * @param  object $data
     * @param  array  $relation
     * @access public
     * @return mixed
     */
    public function getValueByWorkFlow($field, $data, $relation)
    {
        $this->loadModel('flow');

        $fieldValue = '';
        if(!empty($data->{$field->field}))
        {
            if(is_array($data->{$field->field}))
            {
                foreach($data->{$field->field} as $value) $fieldValue .= $this->flow->processFieldValue($field, $relation, $value) . ' ';
            }
            else
            {
                $fieldValue = $this->flow->processFieldValue($field, $relation, $data->{$field->field});
            }
        }

        return $fieldValue;
    }

    /**
     * Get project approval version data.
     *
     * @param  object $data
     * @param  string $version
     * @access public
     * @return mixed
     */
    public function getProjectApprovalVersionData($data, $version = '')
    {
        $children = '';
        $object   = $this->dao->select('element')->from(TABLE_OBJECTVERSION)->where('objectID')->eq($data->id)->andWhere('objectType')->eq('projectapproval')->andWhere('version')->eq($version)->fetch('element');
        if($object)
        {
            $object   = json_decode($object);
            $children = $object->children;
            unset($object->children);
            unset($object->uid);
            unset($object->version);
            unset($object->status);
            unset($object->reviewStatus);
            unset($object->reviewers);

            foreach($object as $key => $value) if(property_exists($data, $key)) $data->$key = $value;
        }

        $childDatas = [];
        if($children)
        {
            foreach($children as $sub => $child) $subDatas[$sub] = (array)$child;

            $childDatas = $subDatas;
        }

        return [$data, $childDatas];
    }

    /**
     * Get project approval cost data.
     *
     * @param  object $data
     * @param  array  $childDatas
     * @access public
     * @return object
     */
    public function getProjectApprovalCostData($data, $childDatas)
    {
        $data->businessInto     = 0;
        $data->itPlanInto       = 0;
        $data->purchasingBudget = 0;
        $data->itCost           = 0;

        if(isset($childDatas['sub_projectcost']) && !empty($childDatas['sub_projectcost']))
        {
            foreach($childDatas['sub_projectcost'] as $projectCost)
            {
                if($projectCost->costType == 'businessInto')     $data->businessInto     += $projectCost->costBudget;
                if($projectCost->costType == 'itPlanInto')       $data->itPlanInto       += $projectCost->costBudget;
                if($projectCost->costType == 'purchasingBudget') $data->purchasingBudget += $projectCost->costBudget;
                if($projectCost->costType == 'itCost')           $data->itCost           += $projectCost->costBudget;
            }
        }

        $costUnit = $this->loadModel('flow')->ajaxGetProjectCost();

        $data->businessInto     = $data->businessInto     . ' ' . (isset($costUnit['businessInto'])     ? $costUnit['businessInto']->costUnit : '');
        $data->itPlanInto       = $data->itPlanInto       . ' ' . (isset($costUnit['itPlanInto'])       ? $costUnit['itPlanInto']->costUnit : '');
        $data->purchasingBudget = $data->purchasingBudget . ' ' . (isset($costUnit['purchasingBudget']) ? $costUnit['purchasingBudget']->costUnit : '');
        $data->itCost           = $data->itCost           . ' ' . (isset($costUnit['itCost'])           ? $costUnit['itCost']->costUnit : '');

        return $data;
    }

    public function changeStatusByBusiness()
    {
        $projectapprovals      = $this->dao->select('id,status')->from('zt_flow_projectapproval')->where('deleted')->eq(0)->andWhere('status')->in('approvedProject,design,devTest,closure')->fetchPairs('id', 'status');
        $projectapprovalIdList = array_keys($projectapprovals);

        $businesses = $this->dao->select('id,status,project')
            ->from('zt_flow_business')
            ->where('deleted')->eq(0)
            ->andWhere('project')->in($projectapprovalIdList)
            ->andWhere('status')->ne('cancelled')
            ->fetchGroup('project');

        $projectIdList = $this->dao->select('instance,id')->from(TABLE_PROJECT)->where('deleted')->eq(0)->andWhere('instance')->in($projectapprovalIdList)->fetchPairs('instance', 'id');

        $storyList = $this->dao->select('t1.id,t2.project')->from('zt_story')->alias('t1')
            ->leftJoin('zt_projectstory t2')->on('t1.id=t2.story')
            ->leftJoin('zt_project t3')->on('t2.project=t3.id')
            ->where('t1.deleted')->eq(0)
            ->andWhere('t2.project')->in($projectIdList)
            ->andWhere('t3.type')->in('project')
            ->fetchPairs('project', 'id');

        foreach($projectapprovals as $projectapprovalID => $projectapprovalStatus)
        {
            $projectID    = $projectIdList[$projectapprovalID];
            $businessList = isset($businesses[$projectapprovalID]) ? $businesses[$projectapprovalID] : [];

            //4.项目管理下全部的业务需求均为“已上线/已验收 ”状态，则变更为项目收尾阶段 。
            if($businessList)
            {
                $designBusinessCount = $this->getBusinessStatusCount($businessList, 'beOnline,closed');
                if($designBusinessCount == count($businessList))
                {
                    if($projectapprovalStatus != 'closure') $this->updateProjectApprovalStatus($projectapprovalID, $projectapprovalStatus, 'closure');
                    continue;
                }
            }

            //项目管理下没有关联的史诗，则变更为已立项 。
            if($projectapprovalStatus != 'approvedProject')
            {
                if(!$storyList[$projectID])
                {
                    $this->updateProjectApprovalStatus($projectapprovalID, $projectapprovalStatus, 'approvedProject');
                    continue;
                }
            }

            if($storyList[$projectID] && $businessList)
            {
                //3.项目管理下的任意一个业务需求变更为“prd已通过”状态，则变更为研发测试阶段 。
                $devTestBusinessCount = $this->getBusinessStatusCount($businessList, 'PRDPassed,beOnline,closed');
                if($devTestBusinessCount > 0)
                {
                    if($projectapprovalStatus != 'devTest') $this->updateProjectApprovalStatus($projectapprovalID, $projectapprovalStatus, 'devTest');
                    continue;
                }

                //2.项目管理下有关联的史诗，且没有“prd已通过”状态的业务需求，则变更为方案设计阶段 。
                $designBusinessCount = $this->getBusinessStatusCount($businessList, 'PRDPassed,beOnline,closed');
                if($designBusinessCount == 0)
                {
                    if($projectapprovalStatus != 'design') $this->updateProjectApprovalStatus($projectapprovalID, $projectapprovalStatus, 'design');
                    continue;
                }
            }
        }
    }

    /**
     * Get story status count.
     *
     * @param  array  $stories
     * @param  array  $status
     * @access public
     * @return int
     */
    public function getBusinessStatusCount($businesses, $status = '')
    {
        $statusList = explode(',', $status);
        $businessIdList = [];
        foreach($businesses as $business)
        {
            if(in_array($business->status, $statusList)) $businessIdList[] = $business->id;
        }

        return count($businessIdList);
    }

    /**
     * Update business status.
     *
     * @param  int    $businessID
     * @param  string $oldStatus
     * @param  string $status
     * @access public
     * @return mixed
     */
    public function updateProjectApprovalStatus($projectapprovalID, $oldStatus, $status)
    {
        //a($projectapprovalID);
        //a($oldStatus);
        //a($status);
        //a('---------------------------------');
        //a('---------------------------------');
        $this->dao->update('zt_flow_projectapproval')->set('status')->eq($status)->where('id')->eq($projectapprovalID)->exec();

        $actionID = $this->loadModel('action')->create('projectapproval', $projectapprovalID, 'changeprojectapprovalstatus');

        $result['changes'][] = ['field' => 'status', 'old' => $oldStatus, 'new' => $status];
        $this->loadModel('action')->logHistory($actionID, $result['changes']);

        $this->loadModel('flow')->mergeVersionByObjectType($projectapprovalID, 'projectapproval');
    }

    public function updateProjectApprovalDate()
    {
        $projects         = $this->dao->select('instance,openedDate')->from(TABLE_PROJECT)->where('deleted')->eq(0)->fetchPairs('instance', 'openedDate');
        $projectapprovals = $this->dao->select('id,projectapprovaldate')->from('zt_flow_projectapproval')->where('deleted')->eq(0)->andWhere('id')->in(array_keys($projects))->fetchPairs();

        foreach($projectapprovals as $projectapprovalID => $projectapprovalDate)
        {
            if(helper::isZeroDate($projectapprovalDate)) $this->dao->update('zt_flow_projectapproval')->set('projectApprovalDate')->eq($projects[$projectapprovalID])->where('id')->eq($projectapprovalID)->exec();
        }
    }

    public function updateErrorVersion()
    {
        //查询版本不一致的业务需求,根据上面的sql，生成查询语句
        $projects = $this->dao->select('fp.id, fp.name, fp.version AS fpversion, latest_version.version AS latversion')
            ->from('zt_flow_projectapproval fp')
            ->leftJoin('(SELECT objectID, MAX(version) AS version, objectType FROM zt_objectversion GROUP BY objectID) AS latest_version')
            ->on('fp.id = latest_version.objectID')
            ->where('fp.version != latest_version.version')
            ->andWhere('latest_version.objectType')->eq('business')
            ->fetchAll('id');
            a($projects);die;

        //将版本号不一致的业务需求，将版本号更新为最新版本号。
        foreach($businesses as $businessID => $business)
        {
            $this->dao->update('zt_flow_business')->set('version')->eq($business->latversion)->where('id')->eq($businessID)->exec();
        }
    }

    public function updateProjectProgram()
    {
        $instanceList = $this->dao->select('id')->from('zt_flow_projectapproval')->where('status')->notin('draft,reviewing,toBeEvaluated,pendingReview,underReview')->fetchPairs('id');
        $projects     = $this->dao->select('id,name,parent,path,grade')->from(TABLE_PROJECT)->where('instance')->in($instanceList)->fetchAll();

        $this->loadModel('program');
        foreach($projects as $project)
        {
            $programName = '春航' . substr($project->name, 0, 4) . '年项目集';
            $parent      = $this->dao->select('id')->from(TABLE_PROGRAM)->where('name')->eq($programName)->andWhere('deleted')->eq(0)->fetch('id');

            if(!empty($parent) && $parent != $project->parent)
            {
                $this->dao->update(TABLE_PROJECT)->set('parent')->eq($parent)->where('id')->eq($project->id)->exec();
                $this->program->processNode($projectID, $parent, $project->path, $project->grade);
            }
        }
    }

    /**
     * Get mailto list.
     *
     * @param  array  $mailto
     * @access public
     * @return array
     */
    public function getMailtoList($mailto = array())
    {
        $this->loadModel('flow');

        $userContact = $this->dao->select('userList')->from(TABLE_USERCONTACT)->where('listName')->eq('PMO')->orWhere('listName')->eq($this->lang->flow->planningDepartment)->fetchPairs();

        $userList = array_merge(explode(',', implode(',', $userContact)));
        $userList = array_merge($userList, $mailto);
        $userList = array_filter(array_unique($userList));

        return $this->dao->select('account')->from(TABLE_USER)->where('account')->in($userList)->andWhere('deleted')->eq(0)->fetchPairs('account');
    }
}
