<?php
public function getProjectapprovalList($account, $pager = null, $orderBy = 'id_desc', $type = 'list
', $queryID = 0)
{
    $projectapprovalIdList = $this->dao->select('id,parent')->from('zt_flow_projectmembers')->where('projectRole')->eq('itPM')->andWhere('account')->eq($account)->andWhere('deleted')->eq('0')->fetchPairs('id');

    $queryName  = 'workProjectapprovalQuery';
    $formName   = 'workProjectapprovalForm';
    if($queryID)
    {
        $query = $this->loadModel('search')->getQuery($queryID);
        if($query)
        {
            $this->session->set($queryName, $query->sql);
            $this->session->set($formName, $query->form);
        }
        else
        {
            $this->session->set($queryName, ' 1 = 1');
        }
    }
    else
    {
        if($this->session->$queryName == false) $this->session->set($queryName, ' 1 = 1');
    }
    $query = $this->session->$queryName;

    $projectapprovalList = $this->dao->select('*')->from('zt_flow_projectapproval')
        ->where('id')->in($projectapprovalIdList)
        ->andWhere('status')->eq('approvedProject')
        ->andWhere('deleted')->eq('0')
        ->beginIF($type == 'bySearch')->andWhere($query)->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');
    return $projectapprovalList;
}

/**
 * Build business search form.
 */
public function getBusinessList($account, $pager = null, $orderBy = 'realGoLiveDate_desc', $type = 'list
', $queryID = 0)
{
    $projectapprovalIdList = $this->dao->select('parent')->from('zt_flow_projectmembers')->where('projectRole')->eq('businessPM')->andWhere('account')->eq($account)->fetchPairs('parent');
    $businessIdList        = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->in($projectapprovalIdList)->andWhere('deleted')->eq(0)->fetchPairs('business');


    $queryName  = 'workBusinessQuery';
    $formName   = 'workBusinessForm';
    if($queryID)
    {
        $query = $this->loadModel('search')->getQuery($queryID);
        if($query)
        {
            $this->session->set($queryName, $query->sql);
            $this->session->set($formName, $query->form);
        }
        else
        {
            $this->session->set($queryName, ' 1 = 1');
        }
    }
    else
    {
        if($this->session->$queryName == false) $this->session->set($queryName, ' 1 = 1');
    }
    $query = $this->session->$queryName;

    $businessList = $this->dao->select('*')->from('zt_flow_business')
        ->where('id')->in($businessIdList)
        ->andWhere('status')->eq('beOnline')
        ->beginIF($type == 'bySearch')->andWhere($query)->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');
    return $businessList;
}

/**
 * Get reviewing stories.
 *
 * @param  string $orderBy
 * @param  bool   $checkExists
 * @access public
 * @return array
 */
public function getReviewingStories($orderBy = 'id_desc', $checkExists = false)
{
    if(!common::hasPriv('story', 'review')) return array();

    $this->app->loadLang('story');
    $stmt = $this->dao->select("t1.*")->from(TABLE_STORY)->alias('t1')
        ->leftJoin(TABLE_STORYREVIEW)->alias('t2')->on('t1.id = t2.story and t1.version = t2.version')
        ->where('t1.deleted')->eq(0)
        ->beginIF(!$this->app->user->admin)->andWhere('t1.product')->in($this->app->user->view->products)->fi()
        ->andWhere('t2.reviewer')->eq($this->app->user->account)
        ->andWhere('t2.result')->eq('')
        ->andWhere('t1.vision')->eq($this->config->vision)
        ->andWhere('t1.status')->in(array('PRDReviewing', 'confirming'))
        ->orderBy($orderBy)
        ->query();

    $stories = array();
    while($data = $stmt->fetch())
    {
        if($checkExists) return true;
        $story = new stdclass();
        $story->id        = $data->id;
        $story->title     = $data->title;
        $story->type      = 'story';
        $story->storyType = $data->type;
        $story->time      = $data->openedDate;
        $story->status    = $data->status;
        $stories[$story->id] = $story;
    }

    $actions = $this->dao->select('objectID,`date`')->from(TABLE_ACTION)->where('objectType')->eq('story')->andWhere('objectID')->in(array_keys($stories))->andWhere('action')->eq('submitreview')->orderBy('`date`')->fetchPairs('objectID', 'date');
    foreach($actions as $storyID => $date) $stories[$storyID]->time = $date;
    return array_values($stories);
}

/**
 * Get reviewing for flows setting.
 *
 * @param  string $objectType
 * @param  string $orderBy
 * @param  bool   $checkExists
 * @access public
 * @return array
 */
public function getReviewingFlows($objectType = 'all', $orderBy = 'id_desc', $checkExists = false)
{
    $this->loadModel('flow');
    if($this->config->edition != 'max' and $this->config->edition != 'ipd') return array();

    $stmt = $this->dao->select('t2.objectType,t2.objectID')->from(TABLE_APPROVALNODE)->alias('t1')
        ->leftJoin(TABLE_APPROVALOBJECT)->alias('t2')
        ->on('t2.approval = t1.approval')
        ->where('t2.objectType')->ne('review')
        ->beginIF($objectType != 'all')->andWhere('t2.objectType')->eq($objectType)->fi()
        ->andWhere('t1.account')->eq($this->app->user->account)
        ->andWhere('t1.status')->eq('doing')
        ->query();
    $objectIdList = array();
    while($object = $stmt->fetch()) $objectIdList[$object->objectType][$object->objectID] = $object->objectID;

    $infoLeqader = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoLeqader);
    $infoAttache = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoAttache);
    $prdBusiness = array();
    if(isset($infoLeqader[$this->app->user->account]) || isset($infoAttache[$this->app->user->account]))
    {
        $tempProjectapprovals = $this->dao->select('*')->from('zt_flow_projectapproval')->where('status')->in(array('approvedProject', 'changeReview', 'cancelReview', 'finishReview', 'finished', 'design', 'devTest', 'closure'))->fetchAll();
        foreach($tempProjectapprovals as $tempProjectapproval)
        {
            $tempProjectDept = $this->loadModel('dept')->getAllChildId($tempProjectapproval->responsibleDept);
            $isProjectLeader = false;
            $projectDepts    = $this->dao->select('*')->from('zt_dept')->where('id')->in($tempProjectDept)->fetchAll();
            foreach($projectDepts as $dept) if(strpos($dept->leaders, $this->app->user->account) !== false) $isProjectLeader = true;
            if(in_array($this->app->user->dept, $tempProjectDept) || $isProjectLeader) 
            {
                $tempBusiness = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($tempProjectapproval->id)->andWhere('deleted')->eq(0)->fetchPairs('business');
                $prdBusiness = array_merge($prdBusiness, $tempBusiness);
            }
        }
    }
    if(isset($objectIdList['business']))
    {
        $objectIdList['business'] =  array_merge($prdBusiness, $objectIdList['business']);
    }
    else
    {
        $objectIdList['business'] = $prdBusiness;
    }

    if($checkExists) return array_keys($objectIdList);

    $this->loadModel('flow');
    $this->loadModel('workflowaction');
    $flows = $this->dao->select('module,`table`,name,titleField')->from(TABLE_WORKFLOW)->where('module')->in(array_keys($objectIdList))->andWhere('buildin')->eq(0)->fetchAll('module');
    $objectGroup = array();
    foreach($objectIdList as $objectType => $idList)
    {
        $table = zget($this->config->objectTables, $objectType, '');
        if(empty($table) and isset($flows[$objectType])) $table = $flows[$objectType]->table;
        if(empty($table)) continue;

        $objectGroup[$objectType] = $this->dao->select('*')->from($table)
            ->where('id')->in($idList)
            ->beginIF($objectType == 'business')->andWhere('status')->in('conformReviewing,changeReviewing, PRDReviewing')->fi()
            ->beginIF($objectType == 'projectapproval')->andWhere('status')->in('reviewing,underReview,changeReview,cancelReview,finishReview')->fi()
            ->andWhere('deleted')->eq(0)
            ->fetchAll('id');

        $action = $this->workflowaction->getByModuleAndAction($objectType, 'approvalreview');
        if($action)
        {
            foreach($objectGroup[$objectType] as $objectID => $object)
            {
                if(!$this->flow->checkConditions($action->conditions, $object)) unset($objectIdList[$objectType][$objectID], $objectGroup[$objectType][$objectID]);
            }
        }
    }

    $this->app->loadConfig('action');
    $approvalList = array();
    foreach($objectGroup as $objectType => $objects)
    {
        $title = '';
        $titleFieldName  = zget($this->config->action->objectNameFields, $objectType, '');
        $openedDateField = 'openedDate';
        if(in_array($objectType, array('product', 'productplan', 'release', 'build', 'testtask'))) $openedDateField = 'createdDate';
        if(in_array($objectType, array('testsuite', 'caselib')))$openedDateField = 'addedDate';
        if(empty($titleFieldName) and isset($flows[$objectType]))
        {
            if(!empty($flows[$objectType]->titleField)) $titleFieldName = $flows[$objectType]->titleField;
            if(empty($flows[$objectType]->titleField)) $title = $flows[$objectType]->name;

            $openedDateField = 'createdDate';
        }

        foreach($objects as $object)
        {
            $data = new stdclass();
            $data->id     = $object->id;
            $data->title  = (empty($titleFieldName) or !isset($object->$titleFieldName)) ? $title . " #{$object->id}" : $object->$titleFieldName;
            $data->type   = $objectType;
            $data->time   = $object->$openedDateField;

            if(in_array($objectType, ['business', 'projectapproval']))
            {
                $data->title = isset($object->name) ? $object->name : $data->title;
                $data->time  = $object->editedDate;
            }
            $data->status = 'doing';
            $approvalList[] = $data;
        }
    }

    return $approvalList;
}

/**
 * Get reviewed list.
 *
 * @param  string $browseType
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getReviewedList($browseType, $orderBy = 'time_desc', $pager = null)
{
    $field = $orderBy;
    $direction = 'asc';
    if(strpos($orderBy, '_') !== false) list($field, $direction) = explode('_', $orderBy);

    $actionField = '';
    if($field == 'time')    $actionField = 'date';
    if($field == 'type')    $actionField = 'objectType';
    if($field == 'id')      $actionField = 'objectID';
    if(empty($actionField)) $actionField = 'date';
    $orderBy = $actionField . '_' . $direction;

    $condition = "(`action` = 'reviewed' or `action` like 'approvalreview%')";
    if($browseType == 'createdbyme')
    {
        $condition  = "(objectType in('story','case','feedback') and action = 'submitreview') OR ";
        $condition .= "(objectType = 'review' and action = 'opened') OR ";
        $condition .= "(objectType = 'attend' and action = 'commited') OR ";
        $condition .= "(`action` like 'approvalsubmit%') OR ";
        $condition .= "(objectType in('leave','makeup','overtime','lieu') and action = 'created')";
        $condition  = "($condition)";
    }
    $actionIdList = $this->dao->select('MAX(`id`) as `id`')->from(TABLE_ACTION)
        ->where('actor')->eq($this->app->user->account)
        ->andWhere('vision')->eq($this->config->vision)
        ->andWhere($condition)
        ->groupBy('objectType,objectID')
        ->fetchPairs();
    $actions = $this->dao->select('objectType,objectID,actor,action,`date`,extra')->from(TABLE_ACTION)
        ->where('id')->in($actionIdList)
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll();
    $objectTypeList = array();
    foreach($actions as $action) $objectTypeList[$action->objectType][] = $action->objectID;

    $flows = ($this->config->edition == 'open') ? array() : $this->dao->select('module,`table`,name,titleField')->from(TABLE_WORKFLOW)->where('module')->in(array_keys($objectTypeList))->andWhere('buildin')->eq(0)->fetchAll('module');
    $objectGroup = array();
    foreach($objectTypeList as $objectType => $idList)
    {
        $table = zget($this->config->objectTables, $objectType, '');
        if(empty($table) and isset($flows[$objectType])) $table = $flows[$objectType]->table;
        if(empty($table)) continue;

        $objectGroup[$objectType] = $this->dao->select('*')->from($table)->where('id')->in($idList)->andWhere('deleted')->eq(0)->fetchAll('id');
    }

    foreach($objectGroup as $objectType => $idList)
    {
        $moduleName = $objectType;
        if($objectType == 'case') $moduleName = 'testcase';
        $this->app->loadLang($moduleName);
    }
    $users = $this->loadModel('user')->getPairs('noletter');

    $this->app->loadConfig('action');
    $reviewList = array();
    foreach($actions as $action)
    {
        $objectType = $action->objectType;
        if(!isset($objectGroup[$objectType])) continue;

        $object = $objectGroup[$objectType][$action->objectID];
        if(!isset($objectGroup[$objectType][$action->objectID]) || empty($object)) continue;

        $review = new stdclass();
        $review->id     = $object->id;
        $review->type   = $objectType;
        $review->time   = substr($action->date, 0, 19);
        $review->result = strtolower($action->extra);
        $review->status = $objectType == 'attend' ? $object->reviewStatus : ((isset($object->status) and !isset($flows[$objectType])) ? $object->status : 'done');
        if(strpos($review->result, ',') !== false) list($review->result) = explode(',', $review->result);

        if($objectType == 'story')    $review->storyType = $object->type;
        if($review->type == 'review') $review->type = 'projectreview';
        if($review->type == 'case')   $review->type = 'testcase';
        $review->title = '';
        if(isset($object->title))
        {
            $review->title = $object->title;
        }
        elseif($objectType == 'attend')
        {
            $review->title = sprintf($this->lang->my->auditField->oaTitle[$objectType], zget($users, $object->account), $object->date);
        }
        elseif(isset($this->lang->my->auditField->oaTitle[$objectType]))
        {
            $review->title = sprintf($this->lang->my->auditField->oaTitle[$objectType], zget($users, $object->createdBy), $object->begin . ' ' . substr($object->start, 0, 5) . ' ~ ' . $object->end . ' ' . substr($object->finish, 0, 5));
        }
        else
        {
            $title = '';
            $titleFieldName = zget($this->config->action->objectNameFields, $objectType, '');
            if(empty($titleFieldName) and isset($flows[$objectType]))
            {
                if(!empty($flows[$objectType]->titleField)) $titleFieldName = $flows[$objectType]->titleField;
                if(empty($flows[$objectType]->titleField)) $title = $flows[$objectType]->name;
            }
            $review->title = (empty($titleFieldName) or !isset($object->$titleFieldName)) ? $title . " #{$object->id}" : $object->$titleFieldName;
        }

        if(in_array($objectType, ['business', 'projectapproval']))
        {
            $review->title = isset($object->name) ? $object->name : $review->title;
        }

        $reviewList[] = $review;
    }
    return $reviewList;
}
