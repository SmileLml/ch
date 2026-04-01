<?php
/**
 * The model file of xxx module of chandao.net.
 *
 * @copyright   Copyright 2009-2022 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      wangxiaomeng <wangxiaomeng@chandao.com>
 * @package     xxx
 * @version     $Id$
 * @link        https://www.chandao.net
 */
class monitoringModel extends model
{
    /**
     * Get project approval list.
     *
     * @param  array  $params
     * @param  int    $pager
     * @access public
     * @return mixed
     */
    public function getProjectApprovalList($params, $pager)
    {
        $projectApprovalIdList = $this->getPrivilegedData();

        if(empty($projectApprovalIdList)) return [];

        $searchQuery = '';
        if($params) $searchQuery = $this->getSearchQuery($params);

        $projectApprovals = $this->dao->select('*')->from('zt_flow_projectapproval')
            ->where('deleted')->eq('0')
            ->andWhere('status')->in('approvedProject,finished,design,devTest,closure,changeReview')
            ->beginIF($searchQuery)->andWhere($searchQuery)->fi()
            ->beginIF($projectApprovalIdList != 'all')->andWhere('id')->in($projectApprovalIdList)->fi()
            ->orderBy('id desc')
            ->page($pager)
            ->fetchAll('id');

        $businesses = $this->getBusinessList(array_keys($projectApprovals), 'id,name,status,PRDdate,project,goLiveDate,acceptanceDate,PRDconfirmDate,DATE_FORMAT(closeDate,\'%Y-%m-%d\') as closeDate,PRDWarning,goLiveWarning,acceptanceWarning,goLiveConfirmDate', $params);

        foreach($projectApprovals as $projectApproval)
        {
            $projectMembers = $this->dao->select('projectRole, account')->from('zt_flow_projectmembers')
                ->where('parent')->eq($projectApproval->id)
                ->andWhere('projectRole')->in('itPM,productManager,itDevM')
                ->andWhere('deleted')->eq('0')
                ->fetchAll();

            $accountList = [];

            foreach($projectMembers as $projectMember) $accountList[$projectMember->projectRole][] = $projectMember->account;

            $projectApproval->accountList = $accountList;

            $businessList = isset($businesses[$projectApproval->id]) ? $businesses[$projectApproval->id] : [];

            $projectApproval->businessList    = $businessList;
            $projectApproval->projectRowspan  = count($businessList) > 0 ? count($businessList) : 1;
        }

        return $projectApprovals;
    }

    /**
     * Get business list.
     *
     * @param  array  $projectApprovalIdList
     * @param  string $fields
     * @param  array  $params
     * @access public
     * @return array
     */
    public function getBusinessList($projectApprovalIdList, $fields = 'id', $params = [])
    {
        $searchQuery = '1=1';

        if(!empty($params) && isset($params['deferredType']))
        {
            $deferredType = $params['deferredType'];

            if(in_array('PRD', $deferredType))         $searchQuery .= ' AND PRDWarning > 0';
            if(in_array('goLive', $deferredType))      $searchQuery .= ' AND goLiveWarning > 0';
            if(in_array('acceptance', $deferredType))  $searchQuery .= ' AND acceptanceWarning > 0';
        }

        $businesses = $this->dao->select($fields)->from('zt_flow_business')
            ->where('deleted')->eq('0')
            ->andWhere('project')->in($projectApprovalIdList)
            ->andWhere('status')->notin('conformReviewing,changeReviewing')
            ->andWhere($searchQuery)->fi()
            ->orderBy('id desc')
            ->fetchAll('id');

        $businessList = [];

        if($businesses)
        {
            foreach($businesses as $business) $businessList[$business->project][$business->id] = $business;
        }

        return $businessList;
    }

    /**
     * Get workflow field options.
     *
     * @access public
     * @return mixed
     */
    public function getWorkflowFieldOptions()
    {
        $dataList = [];

        $this->loadModel('workflowfield');
        $this->loadModel('workflowaction');

        $priField            = $this->workflowfield->getByField('projectapproval', 'pri');
        $businessStatusField = $this->workflowfield->getByField('business', 'status');

        $dataList['priList']            = ['' => ''] + $priField->options;
        $dataList['businessStatusList'] = ['' => ''] + $businessStatusField->options;

        return $dataList;
    }

    /**
     * Get termination date.
     *
     * @param  array  $businessList
     * @access public
     * @return string
     */
    public function getTerminationDate($businessList)
    {
        $latestAcceptanceDate = '';

        foreach($businessList as $business)
        {
            $acceptanceDate = !helper::isZeroDate($business->acceptanceDate) ? $business->acceptanceDate : '';

            if(empty($latestAcceptanceDate) || $acceptanceDate > $latestAcceptanceDate) $latestAcceptanceDate = $acceptanceDate;
        }

        if(!empty($latestAcceptanceDate))
        {
            $date = new DateTime($latestAcceptanceDate);
            $date->modify('+1 month');

            $latestAcceptanceDate = $date->format('Y-m-d');
        }

        return $latestAcceptanceDate;
    }

    /**
     * Get overdue warning.
     *
     * @param  date   $confirmationDate
     * @param  date   $plannedDate
     * @access public
     * @return string
     */
    public function getOverdueWarning($dateValue)
    {
        if($dateValue)
        {
            if($dateValue == 'completed') return $this->lang->monitoring->completed;

            return '<span style="color: red;">' . $dateValue . '</span>';
        }

        return '';
    }

    /**
     * Get project approval list.
     *
     * @param  string $status
     * @access public
     * @return array
     */
    public function getProjectApprovalPairs($statusList = 'approvedProject,finished,design,devTest,closure,changeReview', $searchQuery = '')
    {
        return $this->dao->select('*')->from('zt_flow_projectapproval')
            ->where('deleted')->eq('0')
            ->andWhere('status')->in($statusList)
            ->beginIF($searchQuery)->andWhere($searchQuery)->fi()
            ->orderBy('id desc')
            ->fetchPairs('id', 'name');
    }

    /**
     * Get search query.
     *
     * @param  array  $params
     * @access public
     * @return string
     */
    public function getSearchQuery($params)
    {
        $searchQuery = '1=1';

        if(!empty($params['projectapproval'])) $searchQuery .= " AND id = '{$params['projectapproval']}'";
        if(!empty($params['projectpri']))      $searchQuery .= " AND pri = '{$params['projectpri']}'";
        if(!empty($params['responsibleDept'])) $searchQuery .= " AND responsibleDept = '{$params['responsibleDept']}'";
        if(!empty($params['businessPM']))      $searchQuery .= " AND businessPM = '{$params['businessPM']}'";

        if(!empty($params['itPM']))
        {
            $idListByItPM = $this->getProjectApprovalIdByRole('itPM', $params['itPM']);
            $idListByItPM ? ($searchQuery .= " AND id IN (" . join(',', $idListByItPM) . ')') : ($searchQuery .= " AND id = 0");
        }

        if(!empty($params['businessDept']))
        {
            $users = $this->dao->select('account')->from(TABLE_USER)
                ->where('deleted')->eq(0)
                ->andWhere('dept')->in($params['businessDept'])
                ->fetchPairs();

            if(empty($users)) $searchQuery .= " AND id = 0";
            if($users)
            {
                $idListByItPM = $this->getProjectApprovalIdByRole('itPM', implode(',', $users));
                $idListByItPM ? ($searchQuery .= " AND id IN (" . join(',', $idListByItPM) . ')') : ($searchQuery .= " AND id = 0");
            }
        }

        if(!empty($params['productManager']))
        {
            $idListByProductManager = $this->getProjectApprovalIdByRole('productManager', $params['productManager']);
            $idListByProductManager ? ($searchQuery .= " AND id IN (" . join(',', $idListByProductManager) . ')') : ($searchQuery .= " AND id = 0");
        }

        if(!empty($params['itDevM']))
        {
            $idListByItDevM = $this->getProjectApprovalIdByRole('itDevM', $params['itDevM']);
            $idListByItDevM ? ($searchQuery .= " AND id IN (" . join(',', $idListByItDevM) . ')') : ($searchQuery .= " AND id = 0");
        }

        if(isset($params['deferredType']))
        {
            $deferredType = $params['deferredType'];

            if(in_array('PRD', $deferredType))         $searchQuery .= $this->getSearchQueryByDeferredType('PRDWarning');
            if(in_array('goLive', $deferredType))      $searchQuery .= $this->getSearchQueryByDeferredType('goLiveWarning');
            if(in_array('acceptance', $deferredType))  $searchQuery .= $this->getSearchQueryByDeferredType('acceptanceWarning');
            if(in_array('termination', $deferredType)) $searchQuery .= " AND terminationWarning > 0";
        }

        return $searchQuery;
    }

    /**
     * Get search query by deferred type.
     *
     * @param  string $deferredType
     * @access public
     * @return mixed
     */
    public function getSearchQueryByDeferredType($deferredType)
    {
        $projectIDList = $this->dao->select('project')->from('zt_flow_business')
            ->where($deferredType)->gt(0)
            ->andWhere('status')->notin('conformReviewing,changeReviewing')
            ->andWhere('deleted')->eq(0)
            ->fetchPairs();

        if($projectIDList)  $searchQuery = " AND id IN (" . join(',', $projectIDList) . ')';
        if(!$projectIDList) $searchQuery = " AND id = 0";

        return $searchQuery;
    }

    /**
     * Get project approval id by project role.
     *
     * @param  string $projectRole
     * @param  string $member
     * @access public
     * @return array
     */
    public function getProjectApprovalIdByRole($projectRole, $member)
    {
        return $this->dao->select('parent')->from('zt_flow_projectmembers')
            ->where('projectRole')->eq($projectRole)
            ->andWhere('account')->in($member)
            ->andWhere('deleted')->eq(0)
            ->fetchPairs();
    }

    /**
     * Get project approval export list.
     *
     * @param  string $exportType
     * @param  array  $params
     * @access public
     * @return mixed
     */
    public function getProjectApprovalExportList($exportType, $params)
    {
        $searchQuery  = '';
        $searchParams = '';

        if($params && $exportType == 'selected')
        {
            $searchParams = $this->parseParams($params);
            $searchQuery  = $this->getSearchQuery($searchParams);
        }

        $projectApprovals = $this->dao->select('id, name as projectName, projectNumber, pri as projectpri, responsibleDept, businessPM, terminationDate, begin, projectReviewDate, status, reviewDate, terminationWarning')->from('zt_flow_projectapproval')
            ->where('deleted')->eq('0')
            ->andWhere('status')->in('approvedProject,finished,design,devTest,closure,changeReview')
            ->beginIF($exportType == 'selected')->andWhere($searchQuery)->fi()
            ->orderBy('id desc')
            ->fetchAll('id');

        $projectApprovalIdList = array_keys($projectApprovals);

        $businesses = $this->getBusinessList($projectApprovalIdList, 'id,name,status,PRDdate,project,goLiveDate,acceptanceDate,PRDconfirmDate,DATE_FORMAT(closeDate,\'%Y-%m-%d\') as closeDate,PRDWarning,goLiveWarning,acceptanceWarning,goLiveConfirmDate', $searchParams);

        return $this->getParseExportList($businesses, $projectApprovals);
    }

    /**
     * Get parse export list.
     *
     * @param  array  $businesses
     * @param  array  $projectApprovals
     * @access public
     * @return array
     */
    public function getParseExportList($businesses = [], $projectApprovals = [])
    {
        $depts                = ['' => ''] + $this->loadModel('dept')->getOptionMenu();
        $users                = $this->loadModel('user')->getPairs('noclosed|noletter');
        $workflowFieldOptions = $this->getWorkflowFieldOptions();

        $projectapprovalList = [];
        $index = 0;
        foreach($projectApprovals as $projectApprovalID => $projectApproval)
        {
            list($itPMList, $businessDeptList, $productManagerList, $itDevMList) = $this->getProjectRoleList($projectApprovalID);

            if(!isset($projectapprovalList[$index])) $projectapprovalList[$index] = new stdclass();

            $projectapprovalList[$index]->projectNumber          = $projectApproval->projectNumber;
            $projectapprovalList[$index]->projectName            = $projectApproval->projectName;
            $projectapprovalList[$index]->projectpri             = zget($workflowFieldOptions['priList'], $projectApproval->projectpri, '');
            $projectapprovalList[$index]->responsibleDept        = zget($depts, $projectApproval->responsibleDept, '');
            $projectapprovalList[$index]->businessPM             = zget($users, $projectApproval->businessPM, '');
            $projectapprovalList[$index]->businessDept           = $businessDeptList;
            $projectapprovalList[$index]->itPM                   = $itPMList;
            $projectapprovalList[$index]->productManager         = $productManagerList;
            $projectapprovalList[$index]->itDevM                 = $itDevMList;
            $projectapprovalList[$index]->beginDate              = helper::isZeroDate($projectApproval->begin) ? '' : $projectApproval->begin;
            $projectapprovalList[$index]->projectReviewDate      = helper::isZeroDate($projectApproval->projectReviewDate) ? '' : $projectApproval->projectReviewDate;
            $projectapprovalList[$index]->terminationDate        = helper::isZeroDate($projectApproval->terminationDate) ? '' : $projectApproval->terminationDate;
            $projectapprovalList[$index]->terminationConfirmDate = ($projectApproval->status == 'finished' && !helper::isZeroDate($projectApproval->reviewDate)) ? $projectApproval->reviewDate : '';
            $projectapprovalList[$index]->terminationWarning     = $projectApproval->terminationWarning == 'completed' ? $this->lang->monitoring->completed : $projectApproval->terminationWarning;

            $businessList = isset($businesses[$projectApproval->id]) ? $businesses[$projectApproval->id] : [];

            $countBusiness = count($businessList);
            if($countBusiness > 1)
            {
                $rowspans[$index]['rows']['projectNumber']          = $countBusiness;
                $rowspans[$index]['rows']['projectName']            = $countBusiness;
                $rowspans[$index]['rows']['projectpri']             = $countBusiness;
                $rowspans[$index]['rows']['responsibleDept']        = $countBusiness;
                $rowspans[$index]['rows']['businessPM']             = $countBusiness;
                $rowspans[$index]['rows']['businessDept']           = $countBusiness;
                $rowspans[$index]['rows']['itPM']                   = $countBusiness;
                $rowspans[$index]['rows']['productManager']         = $countBusiness;
                $rowspans[$index]['rows']['itDevM']                 = $countBusiness;
                $rowspans[$index]['rows']['beginDate']              = $countBusiness;
                $rowspans[$index]['rows']['projectReviewDate']      = $countBusiness;
                $rowspans[$index]['rows']['terminationDate']        = $countBusiness;
                $rowspans[$index]['rows']['terminationConfirmDate'] = $countBusiness;
                $rowspans[$index]['rows']['terminationWarning']     = $countBusiness;
            }

            if($businessList)
            {
                foreach($businessList as $key => $business)
                {
                    if(!isset($projectapprovalList[$index])) $projectapprovalList[$index] = new stdclass();

                    $projectapprovalList[$index]->businessID            = $business->id;
                    $projectapprovalList[$index]->businessTitle         = $business->name;
                    $projectapprovalList[$index]->businessStatus        = zget($workflowFieldOptions['businessStatusList'], $business->status, '');
                    $projectapprovalList[$index]->PRDdate               = helper::isZeroDate($business->PRDdate) ? '' : $business->PRDdate;
                    $projectapprovalList[$index]->PRDconfirmDate        = helper::isZeroDate($business->PRDconfirmDate) ? '' : $business->PRDconfirmDate;
                    $projectapprovalList[$index]->PRDWarning            = $business->PRDWarning == 'completed' ? $this->lang->monitoring->completed : $business->PRDWarning;
                    $projectapprovalList[$index]->goLiveDate            = helper::isZeroDate($business->goLiveDate) ? '' : $business->goLiveDate;
                    $projectapprovalList[$index]->goLiveConfirmDate     = helper::isZeroDate($business->goLiveConfirmDate) ? '' : $business->goLiveConfirmDate;
                    $projectapprovalList[$index]->goLiveWarning         = $business->goLiveWarning == 'completed' ? $this->lang->monitoring->completed : $business->goLiveWarning;
                    $projectapprovalList[$index]->acceptanceDate        = helper::isZeroDate($business->acceptanceDate) ? '' : $business->acceptanceDate;
                    $projectapprovalList[$index]->acceptanceConfirmDate = helper::isZeroDate($business->closeDate) ? '' : $business->closeDate;
                    $projectapprovalList[$index]->acceptanceWarning     = $business->acceptanceWarning == 'completed' ? $this->lang->monitoring->completed : $business->acceptanceWarning;

                    $index ++;
                }
            }
            else
            {
                $projectapprovalList[$index]->businessID            = '';
                $projectapprovalList[$index]->businessTitle         = '';
                $projectapprovalList[$index]->businessStatus        = '';
                $projectapprovalList[$index]->PRDdate               = '';
                $projectapprovalList[$index]->PRDconfirmDate        = '';
                $projectapprovalList[$index]->PRDWarning            = '';
                $projectapprovalList[$index]->goLiveDate            = '';
                $projectapprovalList[$index]->goLiveConfirmDate     = '';
                $projectapprovalList[$index]->goLiveWarning         = '';
                $projectapprovalList[$index]->acceptanceDate        = '';
                $projectapprovalList[$index]->acceptanceConfirmDate = '';

                $index ++;
            }
        }

        return [$projectapprovalList, $rowspans];
    }

    /**
     * Get project role list.
     *
     * @param  int    $projectApprovalID
     * @access public
     * @return array
     */
    public function getProjectRoleList($projectApprovalID)
    {
        $depts     = ['' => ''] + $this->loadModel('dept')->getOptionMenu();
        $users     = $this->loadModel('user')->getPairs('noclosed|noletter');
        $userDepts = $this->dao->select('account, dept')->from(TABLE_USER)->fetchPairs('account', 'dept');

        $projectMembers = $this->dao->select('projectRole, account')->from('zt_flow_projectmembers')
            ->where('parent')->eq($projectApprovalID)
            ->andWhere('projectRole')->in('itPM,productManager,itDevM')
            ->andWhere('deleted')->eq('0')
            ->fetchAll();

        $itPMList           = '';
        $itDevMList         = '';
        $businessDeptList   = '';
        $productManagerList = '';

        foreach($projectMembers as $projectMember)
        {
            $account = $projectMember->account;
            if($projectMember->projectRole == 'itPM')
            {
                $itPMList .= ',' . zget($users, $account);

                if(isset($userDepts[$account]) && !empty($userDepts[$account])) $businessDeptList .= '、' . zget($depts, $userDepts[$account]);
            }

            if($projectMember->projectRole == 'productManager') $productManagerList .= ',' . zget($users, $account);
            if($projectMember->projectRole == 'itDevM')         $itDevMList         .= ',' . zget($users, $account);
        }

        $itPMList           = trim($itPMList, ',');
        $businessDeptList   = trim($businessDeptList, '、');
        $productManagerList = trim($productManagerList, ',');
        $itDevMList         = trim($itDevMList, ',');

        return [$itPMList, $businessDeptList, $productManagerList, $itDevMList];
    }

    /**
     * Get privileged data.
     *
     * @access public
     * @return mixed
     */
    public function getPrivilegedData()
    {
        if($this->app->user->admin) return 'all';

        $this->loadModel('user');
        $this->app->loadLang('flow');

        $userAccount = $this->app->user->account;

        $architect       = $this->user->getUsersByUserGroupName($this->lang->flow->architect);
        $PMO             = $this->user->getUsersByUserGroupName('PMO');
        $seniorExecutive = $this->user->getUsersByUserGroupName($this->lang->flow->seniorExecutive);
        $QA              = $this->user->getUsersByUserGroupName('QA');

        if(isset($architect[$userAccount]) || isset($PMO[$userAccount]) || isset($seniorExecutive[$userAccount]) || isset($QA[$userAccount])) return 'all';

        $infoLeqader = $this->user->getUsersByUserGroupName($this->lang->flow->infoLeqader);
        $infoAttache = $this->user->getUsersByUserGroupName($this->lang->flow->architect);

        $responsibleDeptQuery  = '';
        $businessPMSearchQuery = "( businessPM = '" . $this->app->user->account . "'";
        if(isset($infoLeqader[$userAccount]) || isset($infoAttache[$userAccount]))
        {
            $responsibleDeptQuery = " OR `responsibleDept` = '" . $this->app->user->dept . "'";
        }

        $parentIdList = $this->dao->select('parent')->from('zt_flow_projectmembers')
            ->where('deleted')->eq('0')
            ->andWhere('account')->eq($this->app->user->account)
            ->fetchPairs();

        $projectMemberQuery = '';
        if($parentIdList) $projectMemberQuery = ' OR id IN (' . join(',', $parentIdList) . ')';

        $searchQuery = $businessPMSearchQuery . $responsibleDeptQuery . $projectMemberQuery . ')';

        $projectIdList = array_keys($this->getProjectApprovalPairs('approvedProject,finished,design,devTest,closure,changeReview', $searchQuery));

        return $projectIdList;
    }

    /**
     * Batch update overdue warning.
     *
     * @param  string $type
     * @access public
     * @return array
     */
    public function batchUpdateOverdueWarning()
    {
        $projectApprovals = $this->dao->select('id, status, reviewDate')->from('zt_flow_projectapproval')
            ->where('deleted')->eq('0')
            ->andWhere('status')->in('approvedProject,finished,design,devTest,closure')
            ->fetchAll('id');

        $businesses = $this->updateBusinessOverdueWarning(array_keys($projectApprovals), 'id,name,status,PRDdate,project,goLiveDate,acceptanceDate,PRDconfirmDate,DATE_FORMAT(closeDate,\'%Y-%m-%d\') as closeDate,PRDWarning,goLiveWarning,acceptanceWarning,goLiveConfirmDate');

        foreach($projectApprovals as $projectApproval)
        {
            $businessList = isset($businesses[$projectApproval->id]) ? $businesses[$projectApproval->id] : [];

            $terminationDate = $this->getTerminationDate($businessList);

            if($terminationDate != $projectApproval->terminationDate) $this->dao->update("zt_flow_projectapproval")->set('terminationDate')->eq($terminationDate)->where('id')->eq($projectApproval->id)->exec();

            if($projectApproval->terminationWarning != 'completed')
            {
                $reviewDate = '';

                if($projectApproval->status == 'finished') $reviewDate = $projectApproval->reviewDate;

                $this->updateOverdueWarning($projectApproval->id, 'projectapproval', 'terminationWarning', $reviewDate, $terminationDate);
            }
        }

        return $projectApprovals;
    }

    /**
     * Update business overdue warning.
     *
     * @param  array  $projectApprovalIdList
     * @param  string $fields
     * @access public
     * @return array
     */
    public function updateBusinessOverdueWarning($projectApprovalIdList, $fields = 'id')
    {
        $businesses = $this->dao->select($fields)->from('zt_flow_business')
            ->where('deleted')->eq('0')
            ->andWhere('status')->notin('conformReviewing,changeReviewing')
            ->andWhere('project')->in($projectApprovalIdList)
            ->orderBy('id desc')
            ->fetchAll('id');

        $requirements = $this->dao->select('business, MAX(actualonlinedate) AS actualonlinedate')->from(TABLE_STORY)
            ->where('deleted')->eq('0')
            ->andWhere('business')->in(array_keys($businesses))
            ->andWhere('type')->eq('requirement')
            ->groupBy('business')
            ->fetchPairs('business');

        $businessList = [];

        foreach($businesses as $business)
        {
            $goLiveConfirmDate = (!helper::isZeroDate($business->PRDconfirmDate) && isset($requirements[$business->id])) ? $requirements[$business->id] : '';

            if($business->goLiveConfirmDate != $goLiveConfirmDate) $this->dao->update('zt_flow_business')->set('goLiveConfirmDate')->eq($goLiveConfirmDate)->where('id')->eq($business->id)->exec();

            if($business->PRDwarning        != 'completed') $this->updateOverdueWarning($business->id, 'business', 'PRDwarning',        $business->PRDconfirmDate, $business->PRDdate);
            if($business->goLiveWarning     != 'completed') $this->updateOverdueWarning($business->id, 'business', 'goLiveWarning',     $goLiveConfirmDate,        $business->goLiveDate);
            if($business->acceptanceWarning != 'completed') $this->updateOverdueWarning($business->id, 'business', 'acceptanceWarning', $business->closeDate,      $business->acceptanceDate);
            $businessList[$business->project][$business->id] = $business;
        }

        return $businessList;
    }

    /**
     * Update overdue warning.
     *
     * @param  int    $objectID
     * @param  string $objectType
     * @param  string $dateType
     * @param  date   $confirmationDate
     * @param  date   $plannedDate
     * @access public
     * @return array
     */
    public function updateOverdueWarning($objectID, $objectType, $dateType, $confirmationDate, $plannedDate)
    {
        $diffDays = '';
        $today    = helper::today();

        if(!helper::isZeroDate($confirmationDate))
        {
            $diffDays = 'completed';
        }
        elseif($today > $plannedDate && !helper::isZeroDate($plannedDate))
        {
            $diffDays = helper::diffDate($today, $plannedDate);
        }

        $this->dao->update("zt_flow_{$objectType}")->set($dateType)->eq($diffDays)->where('id')->eq($objectID)->exec();
    }

    /**
     * Parse params.
     *
     * @param  string $params
     * @access public
     * @return array
     */
    public function parseParams($params)
    {
        if(empty($params)) return array();
        $params = explode('|', $params);

        list($projectapproval, $deferredType, $projectpri, $responsibleDept, $businessPM, $itPM, $businessDept, $productManager, $itDevM) = $params;

        $parsedParams = array();
        $parsedParams['projectapproval'] = $projectapproval;
        $parsedParams['deferredType']    = explode(',', $deferredType);
        $parsedParams['projectpri']      = $projectpri;
        $parsedParams['responsibleDept'] = $responsibleDept;
        $parsedParams['businessPM']      = $businessPM;
        $parsedParams['itPM']            = $itPM;
        $parsedParams['businessDept']    = $businessDept;
        $parsedParams['productManager']  = $productManager;
        $parsedParams['itDevM']          = $itDevM;

        return $parsedParams;
    }

    /**
     * Send overdue reminder.
     *
     * @param  string $type
     * @access public
     * @return mixed
     */
    public function sendOverdueReminder($type)
    {
        $projectApprovals = $this->dao->select('id, name, businessPM')->from('zt_flow_projectapproval')
            ->where('deleted')->eq('0')
            ->andWhere('status')->in('approvedProject,finished,design,devTest,closure')
            ->orderBy('id desc')
            ->fetchAll('id');

        if(empty($projectApprovals)) return;

        $overdueReminders = $this->dao->select('id, name, project,' . $type)->from('zt_flow_business')
            ->where('deleted')->eq('0')
            ->andWhere('project')->in(array_keys($projectApprovals))
            ->andWhere('status')->notin('conformReviewing,changeReviewing')
            ->andWhere($type)->gt('0')
            ->fetchGroup('project');

        foreach($projectApprovals as $projectApprovalID => $projectApproval)
        {
            $projectMembers = $this->dao->select('projectRole, account')->from('zt_flow_projectmembers')
                ->where('parent')->eq($projectApprovalID)
                ->andWhere('projectRole')->in('productManager,itDevM')
                ->andWhere('deleted')->eq('0')
                ->fetchGroup('projectRole');

            $productManagerList = [];
            $itDevMList         = [];

            if(isset($projectMembers['productManager'])) $productManagerList = $projectMembers['productManager'];
            if(isset($projectMembers['itDevM']))         $itDevMList         = $projectMembers['itDevM'];

            if(isset($overdueReminders[$projectApprovalID]))
            {
                $titleContent = '';
                foreach($overdueReminders[$projectApprovalID] as $overdueReminder)
                {
                    $titleContent .= $overdueReminder->name . '(' . $overdueReminder->$type . '天),';
                }

                $titleContent = trim($titleContent, ',');

                if($type == 'PRDWarning' && $productManagerList)                     $this->sendPRDWarning($titleContent, $productManagerList, $projectApproval);
                if($type == 'goLiveWarning' && ($productManagerList || $itDevMList)) $this->sendGoLiveWarning($titleContent, $productManagerList, $itDevMList, $projectApproval);
                if($type == 'acceptanceWarning' && ($projectApproval->businessPM))   $this->sendAcceptanceWarning($titleContent, $projectApproval);
            }
        }
    }

    /**
     * Send PRD warning overdue reminder.
     *
     * @param  string $titleContent
     * @param  array  $productManagerList
     * @param  object $projectApproval
     * @access public
     * @return mixed
     */
    public function sendPRDWarning($titleContent, $productManagerList, $projectApproval)
    {
        $toList = [];
        foreach($productManagerList as $member) $toList[$member->account] = $member->account;

        $content = sprintf($this->lang->monitoring->overdueReminder['PRD'], $projectApproval->name, $titleContent);
        $subject = '【' . $this->lang->monitoring->PRDWarning . '】';

        $this->loadModel('mail')->send(implode(',', $toList), $subject, $content, '', true);

        if(SX_ENABLE) foreach($toList as $account) $this->loadModel('apirequest')->sendOpenMessage($account, $content);
    }

    /**
     * Send go live warning overdue reminder.
     *
     * @param  string $titleContent
     * @param  array  $productManagerList
     * @param  array  $itDevMList
     * @param  object $projectApproval
     * @access public
     * @return mixed
     */
    public function sendGoLiveWarning($titleContent, $productManagerList, $itDevMList, $projectApproval)
    {
        $toList = [];
        foreach($productManagerList as $member) $toList[$member->account] = $member->account;
        foreach($itDevMList as $itDevM)         $toList[$itDevM->account] = $itDevM->account;

        $content = sprintf($this->lang->monitoring->overdueReminder['goLive'], $projectApproval->name, $titleContent);
        $subject = '【' . $this->lang->monitoring->goLiveWarning . '】';

        $this->loadModel('mail')->send(implode(',', $toList), $subject, $content);

        if(SX_ENABLE) foreach($toList as $account) $this->loadModel('apirequest')->sendOpenMessage($account, $content);
    }

    /**
     * Send acceptance warning overdue reminder.
     *
     * @param  string $titleContent
     * @param  object $projectApproval
     * @access public
     * @return mixed
     */
    public function sendAcceptanceWarning($titleContent, $projectApproval)
    {
        $content = sprintf($this->lang->monitoring->overdueReminder['acceptance'], $projectApproval->name, $titleContent);
        $subject = '【' . $this->lang->monitoring->acceptanceWarning . '】';

        $this->loadModel('mail')->send($projectApproval->businessPM, $subject, $content, '', true);

        if(SX_ENABLE) $this->loadModel('apirequest')->sendOpenMessage($projectApproval->businessPM, $content);
    }

    /**
     * Send termination warning overdue reminder.
     *
     * @access public
     * @return mixed
     */
    public function sendTerminationWarning()
    {
        $projectApprovals = $this->dao->select('id, name, businessPM, terminationWarning')->from('zt_flow_projectapproval')
            ->where('deleted')->eq('0')
            ->andWhere('status')->in('approvedProject,finished,design,devTest,closure')
            ->andWhere('terminationWarning')->gt(0)
            ->fetchAll('id');

        foreach($projectApprovals as $projectApproval)
        {
            if($projectApproval->businessPM)
            {
                $content = sprintf($this->lang->monitoring->overdueReminder['termination'], $projectApproval->name, $projectApproval->terminationWarning);
                $subject = '【' . $this->lang->monitoring->terminationWarning . '】';

                $this->loadModel('mail')->send($projectApproval->businessPM, $subject, $content, '', true);

                if(SX_ENABLE) $this->loadModel('apirequest')->sendOpenMessage($projectApproval->businessPM, $content);
            }
        }
    }
}
