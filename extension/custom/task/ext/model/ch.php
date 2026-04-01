<?php
/**
 * is be online business
 * @param object $task
 * @return bool
 */
public function beOnlineBusiness($taskID)
{
    $task = $this->getById($taskID);
    if(!$task->story) return false;

    $requirementID = $this->dao->select('AID')->from(TABLE_RELATION)->where('BType')->eq('story')->andWhere('BID')->eq($task->story)->fetch('AID');
    if(!$requirementID) return false;

    $businessID = $this->dao->select('business')->from('zt_story')->where('id')->eq($requirementID)->fetch('business');
    if(!$businessID) return false;

    $businessStatus = $this->dao->select('status')->from('zt_flow_business')->where('id')->eq($businessID)->fetch('status');
    if($businessStatus != 'PRDPassed') return false;

    $requirements = $this->dao->select('id')->from('zt_story')->where('business')->eq($businessID)->fetchPairs('id', 'id');
    $storyIdList  = $this->dao->select('BID')->from(TABLE_RELATION)->where('AType')->eq('requirement')->AndWhere('AID')->in($requirements)->fetchPairs('BID', 'BID');
    $taskList     = $this->dao->select('status')->from('zt_task')->where('story')->in($storyIdList)->fetchAll();

    if(count($taskList) != 0)
    {
        $isBeOnline = true;
        foreach($taskList as $task)
        {
            if($task->status != 'closed') $isBeOnline = false;
        }
        if($isBeOnline)
        {
            $this->dao->update('zt_flow_business')->set('status')->eq('beOnline')->set('realGoLiveDate')->eq(helper::now())->where('id')->eq($businessID)->exec();

            $this->loadModel('flow')->mergeVersionByObjectType($businessID, 'business');

            $projectapprovalID     = $this->dao->select('parent')->from('zt_flow_projectbusiness')->where('business')->eq($businessID)->andWhere('deleted')->eq(0)->fetch('parent');
            $businessIdList     = $this->dao->select('business')->from('zt_flow_projectbusiness')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq(0)->fetchPairs('business', 'business');
            $businessStatusList = $this->dao->select('status')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll('status');

            $isClosure = true;
            foreach($businessStatusList as $businessStatus)
            {
                if($businessStatus->status != 'beOnline') $isClosure = false;
            }

            $projectapprovalStatus = $this->dao->select('status')->from('zt_flow_projectapproval')->where('id')->eq($projectapprovalID)->fetch('status');
            $noChangeStatus        = array('cancelled', 'finished', 'cancelReview', 'finishReview', 'closure', 'changeReview');

            if(!in_array($projectapprovalStatus, $noChangeStatus) && $isClosure && $projectapprovalStatus != 'closure')
            {
                $this->dao->update('zt_flow_projectapproval')->set('status')->eq('closure')->where('id')->eq($projectapprovalID)->exec();

                $actionID = $this->loadModel('action')->create('projectapproval', $projectapprovalID, 'changeclosure');
                $result['changes']   = array();
                $result['changes'][] = ['field' => 'status', 'old' => $projectapprovalStatus, 'new' => 'closure'];
                $this->loadModel('action')->logHistory($actionID, $result['changes']);

                $this->loadModel('flow')->mergeVersionByObjectType($projectapprovalID, 'projectapproval');
            }
        }
    }
}

/**
 * Is beyond estimate.
 * @param array  $data
 * @param string $operate
 */
public function isBeyondEstimate($data, $operate = 'create')
{
    if($operate == 'edit')
    {
        $taskIDList  = $data['taskIDList'];
        $storyIDList = $this->dao->select('id, story')->from('zt_task')->where('id')->in($taskIDList)->fetchPairs('id');
    }
    $storyIDList = $operate == 'create' ? $data['story'] : $storyIDList;
    $newStoryIDList = array();
    foreach($storyIDList as $key => $value)
    {
        if($value == 'ditto') $value = $preValue;
        $preValue = $value;
        $newStoryIDList[$value][] = $key;
    }

    foreach($newStoryIDList as $key => $value)
    {
        if($key)
        {
            $storyInfo           = $this->loadModel('story')->getById($key);
            $linkedStoryEstimate = $this->dao->select('sum(estimate) as estimateSum')
                ->from(TABLE_TASK)
                ->where('story')->eq($key)
                ->beginIF($operate == 'edit')->andWhere('id')->notin($value)->fi()
                ->beginIF(isset($_POST['parent']))->andWhere('id')->notin(array_filter(array_unique(array_values($_POST['parent']))))->fi()
                ->andWhere('deleted')->eq(0)
                ->andWhere('parent')->ne('-1')
                ->fetch('estimateSum');
            $estimate     = 0;
            $estimateList = $operate == 'create' ? $_POST['estimate'] : $_POST['estimates'];
            foreach($value as $taskID)
            {
                $estimate += (int)$estimateList[$taskID];
            }

            if((int)$linkedStoryEstimate + $estimate > ($storyInfo->estimate * 8)) return false;
        }
    }
    return true;
}

/**
 * Batch change the module of task.
 *
 * @param  array  $taskIDList
 * @param  int    $stroyID
 * @access public
 * @return array
 */
public function batchChangeStory($taskIDList, $storyID)
{
    $now        = helper::now();
    $allChanges = array();
    $oldTasks   = $this->getByList($taskIDList);

    foreach($taskIDList as $taskID)
    {
        $oldTask = $oldTasks[$taskID];
        if($storyID == $oldTask->story) continue;

        $task = new stdclass();
        $task->lastEditedBy   = $this->app->user->account;
        $task->lastEditedDate = $now;
        $task->story          = $storyID;

        $this->dao->update(TABLE_TASK)->data($task)->autoCheck()->where('id')->eq((int)$taskID)->exec();
        if(!dao::isError()) $allChanges[$taskID] = common::createChanges($oldTask, $task);
    }
    return $allChanges;
}

/**
 * Get tasks of a execution.
 *
 * @param int|array $executionID
 * @param int       $productID
 * @param string    $type
 * @param string    $modules
 * @param string    $orderBy
 * @param null      $pager
 * @param int       $projectID
 * @access public
 * @return array|void
 */
public function getExecutionTasks($executionID, $productID = 0, $type = 'all', $modules = 0, $orderBy = 'status_asc, id_desc', $pager = null, $projectID = 0)
{
    if(is_string($type)) $type = strtolower($type);
    $orderBy = str_replace('pri_', 'priOrder_', $orderBy);
    $fields  = "DISTINCT t1.*, t2.id AS storyID, t2.title AS storyTitle, t2.product, t2.branch, t2.version AS latestStoryVersion, t2.status AS storyStatus, IF(t1.`pri` = 0, {$this->config->maxPriValue}, t1.`pri`) as priOrder";
    ($this->config->edition == 'max' or $this->config->edition == 'ipd') && $fields .= ', t5.name as designName, t5.version as latestDesignVersion';

    $actionIDList = array();
    if($type == 'assignedbyme') $actionIDList = $this->dao->select('objectID')->from(TABLE_ACTION)->where('objectType')->eq('task')->andWhere('action')->eq('assigned')->andWhere('actor')->eq($this->app->user->account)->fetchPairs('objectID', 'objectID');

    $tasks = $this->dao->select($fields)
        ->from(TABLE_TASK)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_TASKTEAM)->alias('t3')->on('t3.task = t1.id')
        ->beginIF($productID)->leftJoin(TABLE_MODULE)->alias('t4')->on('t1.module = t4.id')->fi()
        ->beginIF($this->config->edition == 'max' or $this->config->edition == 'ipd')->leftJoin(TABLE_DESIGN)->alias('t5')->on('t1.design= t5.id')->fi()
        ->where('t1.execution')->in($executionID)
        ->beginIF($projectID)->andWhere('t1.project')->eq($projectID)->fi()
        ->beginIF($type == 'myinvolved')
        ->andWhere("((t3.`account` = '{$this->app->user->account}') OR t1.`assignedTo` = '{$this->app->user->account}' OR t1.`finishedby` = '{$this->app->user->account}')")
        ->fi()
        ->beginIF($productID)->andWhere("((t4.root=" . (int)$productID . " and t4.type='story') OR t2.product=" . (int)$productID . ")")->fi()
        ->beginIF($type == 'undone')->andWhere('t1.status')->notIN('done,closed')->fi()
        ->beginIF($type == 'needconfirm')->andWhere('t2.version > t1.storyVersion')->andWhere("t2.status = 'active'")->fi()
        ->beginIF($type == 'assignedtome')->andWhere("(t1.assignedTo = '{$this->app->user->account}' or (t1.mode = 'multi' and t3.`account` = '{$this->app->user->account}' and t1.status != 'closed' and t3.status != 'done') )")->fi()
        ->beginIF($type == 'finishedbyme')
        ->andWhere('t1.finishedby', 1)->eq($this->app->user->account)
        ->orWhere('t3.status')->eq("done")
        ->markRight(1)
        ->fi()
        ->beginIF($type == 'delayed')->andWhere('t1.deadline')->gt('1970-1-1')->andWhere('t1.deadline')->lt(date(DT_DATE1))->andWhere('t1.status')->in('wait,doing')->fi()
        ->beginIF(is_array($type) or strpos(',all,undone,needconfirm,assignedtome,delayed,finishedbyme,myinvolved,assignedbyme,review,', ",$type,") === false)->andWhere('t1.status')->in($type)->fi()
        ->beginIF($modules)->andWhere('t1.module')->in($modules)->fi()
        ->beginIF($type == 'assignedbyme')->andWhere('t1.id')->in($actionIDList)->andWhere('t1.status')->ne('closed')->fi()
        ->beginIF($type == 'review')
        ->andWhere("FIND_IN_SET('{$this->app->user->account}', t1.reviewers)")
        ->andWhere('t1.reviewStatus')->eq('doing')
        ->fi()
        ->andWhere('t1.deleted')->eq(0)
        ->orderBy($orderBy)
        ->page($pager, 't1.id')
        ->fetchAll('id');

    $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'task', ($productID or in_array($type, array('myinvolved', 'needconfirm', 'assignedtome', 'finishedbyme'))) ? false : true);

    if(empty($tasks)) return array();

    $taskList = array_keys($tasks);
    $taskTeam = $this->dao->select('*')->from(TABLE_TASKTEAM)->where('task')->in($taskList)->fetchGroup('task');
    if(!empty($taskTeam))
    {
        foreach($taskTeam as $taskID => $team) $tasks[$taskID]->team = $team;
    }

    $parents = array();
    foreach($tasks as $task)
    {
        if($task->parent > 0) $parents[$task->parent] = $task->parent;
    }
    $parents  = $this->dao->select('*')->from(TABLE_TASK)->where('id')->in($parents)->fetchAll('id');
    $userList = $this->dao->select('account,realname')->from(TABLE_USER)->fetchPairs('account');

    if($this->config->vision == 'lite') $tasks = $this->appendLane($tasks);
    foreach($tasks as $task)
    {
        $task->assignedToRealName = zget($userList, $task->assignedTo);
        if($task->parent > 0)
        {
            if(isset($tasks[$task->parent]))
            {
                $tasks[$task->parent]->children[$task->id] = $task;
                unset($tasks[$task->id]);
            }
            else
            {
                $parent = $parents[$task->parent];
                $task->parentName = $parent->name;
            }
        }
    }

    return $this->processTasks($tasks);
}

/**
 * Get execution by ch project.
 *
 * @param  int    $chProjectID
 * @param  int    $storyID
 * @access public
 * @return array
 */
public function getExecutionByChProject($chProjectID, $storyID = 0)
{
    $executionIdList = $this->loadModel('chproject')->getIntances($chProjectID);
    $storyExecutions = $storyID ? $this->loadModel('story')->getProjectPairsByID($storyID, 'id', 'sprint') : [];

    $executions = $this->dao->select("t1.id, concat(t1.name, '/', t2.name) as executionName")->from(TABLE_EXECUTION)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->where('t1.id')->in($executionIdList)
        ->beginIF($storyExecutions)->andWhere('t1.id')->in($storyExecutions)->fi()
        ->fetchPairs();

    return $executions;
}

/**
 * Build task view menu.
 *
 * @param  object $task
 * @access public
 * @return string
 */
public function buildOperateViewMenu($task)
{
    if($task->deleted) return '';

    $plmCanStart = true;
    $isPLMMode   = $this->config->systemMode == 'PLM';

    if($isPLMMode)
    {
        $execution           = $this->loadModel('execution')->getByID($task->execution);
        $execution->ipdStage = $this->loadModel('execution')->canStageStart($execution);
        $plmCanStart = $execution->status == 'wait' ? $execution->ipdStage['canStart'] : 1;
        if($execution->status == 'close') $plmCanStart = false;
        if($execution->parallel) $plmCanStart = true;
    }

    $menu   = '';
    $params = "taskID=$task->id";
    if((empty($task->team) || empty($task->children)) && $task->executionList->type != 'kanban')
    {
        $menu .= $this->buildMenu('task', 'batchCreate', "execution=$task->execution&storyID=$task->story&moduleID=$task->module&taskID=$task->id", $task, 'view', 'split', '', '', '', "title='{$this->lang->task->children}'", $this->lang->task->children);
    }

    $menu .= $this->buildMenu('task', 'assignTo', "executionID=$task->execution&taskID=$task->id", $task, 'button', '', '', 'iframe', true, '', $this->lang->task->assignTo);

    if($plmCanStart) $menu .= $this->buildMenu('task', 'start',          $params, $task, 'view', '', '', 'iframe showinonlybody', true);
    if($plmCanStart) $menu .= $this->buildMenu('task', 'restart',        $params, $task, 'view', '', '', 'iframe showinonlybody', true);
    //if(empty($task->linkedBranch))
    //{
    //    $hasRepo = $this->loadModel('repo')->getRepoPairs('execution', $task->execution, false);
    //    if($hasRepo) $menu .= $this->buildMenu('repo', 'createBranch', $params . "&execution={$task->execution}", $task, '', 'treemap', '', 'iframe showinonlybody', true, '', $this->lang->repo->createBranchAction);
    //}
    if($plmCanStart) $menu .= $this->buildMenu('task', 'recordEstimate', $params, $task, 'view', '', '', 'iframe showinonlybody', true);

    $menu .= $this->buildMenu('task', 'pause',          $params, $task, 'view', '', '', 'iframe showinonlybody', true);
    if($plmCanStart) $menu .= $this->buildMenu('task', 'finish',         $params, $task, 'view', '', '', 'iframe showinonlybody text-success', true);
    $menu .= $this->buildMenu('task', 'activate',       $params, $task, 'view', '', '', 'iframe showinonlybody text-success', true);
    $menu .= $this->buildMenu('task', 'close',          $params, $task, 'view', '', '', 'iframe showinonlybody', true);
    $menu .= $this->buildMenu('task', 'cancel',         $params, $task, 'view', '', '', 'iframe showinonlybody', true);

    $menu .= "<div class='divider'></div>";
    $menu .= $this->buildFlowMenu('task', $task, 'view', 'direct');
    $menu .= "<div class='divider'></div>";

    $menu .= $this->buildMenu('task', 'edit', $params, $task, 'view', '', '', 'showinonlybody');
    $menu .= $this->buildMenu('task', 'create', "projctID={$task->execution}&storyID=0&moduleID=0&taskID=$task->id&todoID=0&extra=&bugID=0&chprojectID={$this->session->chproject}", $task, 'view', 'copy');
    $menu .= $this->buildMenu('task', 'delete', "executionID=$task->execution&taskID=$task->id", $task, 'view', 'trash', 'hiddenwin', 'showinonlybody');
    if($task->parent > 0) $menu .= $this->buildMenu('task', 'view', "taskID=$task->parent", $task, 'view', 'chevron-double-up', '', '', '', '', $this->lang->task->parent);

    return $menu;
}

/**
 * Judge an action is clickable or not.
 *
 * @param  object    $task
 * @param  string    $action
 * @access public
 * @return bool
 */
public static function isClickable($task, $action)
{
    global $app, $config;

    $isNotCloseProject = true;
    if(!empty($task->project))
    {
        $projectapprovalID = $app->dbQuery('SELECT instance FROM zt_project WHERE id = ' . $task->project)->fetch();
        $projectapproval = $app->dbQuery('SELECT `status` FROM zt_flow_projectapproval WHERE id = ' . $projectapprovalID->instance)->fetch();
        if($projectapproval->status == 'cancelled' || $projectapproval->status == 'finished') $isNotCloseProject = false;
    }

    $action = strtolower($action);

    if($action == 'start'          and $task->parent < 0) return false;
    if($action == 'finish'         and $task->parent < 0) return false;
    if($action == 'pause'          and $task->parent < 0) return false;
    if($action == 'assignto'       and $task->parent < 0) return false;
    if($action == 'close'          and $task->parent < 0) return false;
    if($action == 'batchcreate'    and !empty($task->team))     return false;
    if($action == 'batchcreate'    and $task->parent > 0)       return false;
    if($action == 'recordestimate' and $task->parent == -1)     return false;
    if($action == 'delete'         and $task->parent < 0)       return false;

    if(!empty($task->team))
    {
        global $app;
        $myself = new self();
        if($task->mode == 'linear')
        {
            if($action == 'assignto' and strpos('done,cencel,closed', $task->status) === false) return false;
            if($action == 'start' and strpos('wait,doing', $task->status) !== false)
            {
                if($task->assignedTo != $app->user->account) return false;

                $currentTeam = $myself->getTeamByAccount($task->team, $app->user->account);
                if($currentTeam and $currentTeam->status == 'wait') return true;
            }
            if($action == 'finish' and $task->assignedTo != $app->user->account) return false;
        }
        elseif($task->mode == 'multi')
        {
            $currentTeam = $myself->getTeamByAccount($task->team, $app->user->account);
            if($action == 'start' and strpos('wait,doing', $task->status) !== false and $currentTeam and $currentTeam->status == 'wait') return true;
            if($action == 'finish' and (empty($currentTeam) or $currentTeam->status == 'done')) return false;
        }
    }

    if($action == 'start')     return $task->status == 'wait';
    if($action == 'restart')   return $task->status == 'pause';
    if($action == 'pause')     return $task->status == 'doing';
    if($action == 'assignto')  return $task->status != 'closed' and $task->status != 'cancel';
    if($action == 'close')     return $task->status == 'done'   or  $task->status == 'cancel';
    if($action == 'activate')  return $task->status == 'done'   or  $task->status == 'closed'  or $task->status  == 'cancel';
    if($action == 'finish')    return $task->status != 'done'   and $task->status != 'closed'  and $task->status != 'cancel';
    if($action == 'cancel')    return $task->status != 'done'   and $task->status != 'closed'  and $task->status != 'cancel';
    if($action == 'edit')      return $isNotCloseProject;
    if($action == 'batchedit') return $isNotCloseProject;
    return true;
}

/**
 * Check estimate by story.
 *
 * @param  array  $taskIDList
 * @param  int    $storyID
 * @param  int    $storyEstimate
 * @access public
 * @return mixed
 */
public function checkEstimateByStory($taskIDList, $storyID, $storyEstimate)
{
    $sumTaskEstimate = $this->dao->select('sum(estimate) as sumEstimate')->from('zt_task')
        ->where('id')->in($taskIDList)
        ->andWhere('deleted')->eq(0)
        ->andWhere('parent')->ne('-1')
        ->fetch('sumEstimate');

    $taskEstimateSum = $this->dao->select('sum(estimate) as estimateSum')->from('zt_task')
        ->where('story')->eq($storyID)
        ->andWhere('id')->notin($taskIDList)
        ->andWhere('deleted')->eq(0)
        ->andWhere('parent')->ne('-1')
        ->fetch('estimateSum');
    if(((float)$sumTaskEstimate + (float)$taskEstimateSum) > ($storyEstimate * 8)) return true;

    return false;
}

/**
 * Batch change the execution and project of task.
 *
 * @access public
 * @return array
 */
public function batchChangeExecution()
{
    $now     = helper::now();
    $account = $this->app->user->account;

    $data = fixer::input('post')
        ->setDefault('lastEditedBy', $account)
        ->setDefault('lastEditedDate', $now)
        ->get();

    $taskIdList = explode(',', $data->taskIdList);
    unset($data->taskIdList);

    $projectID   = $data->project;
    $executionID = $data->execution;

    $oldTask = $this->dao->select('execution, project')->from(TABLE_TASK)->where('id')->in($taskIdList)->limit(1)->fetch();
    if($oldTask->execution == $executionID) return true;

    $this->dao->update(TABLE_TASK)->data($data)->autoCheck()->batchcheck('execution, project', 'notempty')->where('id')->in($taskIdList)->exec();
    if(dao::isError()) return false;

    $this->loadModel('action');
    foreach($taskIdList as $taskID)
    {
        $task = new stdclass();
        $task->lastEditedBy   = $account;
        $task->lastEditedDate = $now;
        $task->execution      = $executionID;
        $task->project        = $projectID;

        $actionID = $this->action->create('task', $taskID, 'changeExecution');
        $this->action->logHistory($actionID, common::createChanges($oldTask, $task));
    }

    $this->changeEffortExecution($taskIdList, $executionID, $projectID);

    $oldProjectID   = $oldTask->project;
    $oldExecutionID = $oldTask->execution;

    $projectIdList = array($projectID => $projectID, $oldProjectID => $oldProjectID);
    $this->loadModel('program')->refreshStats(true, $projectIdList);

    $this->syncExecutionAndProjectStatus($executionID);

    if($oldProjectID != $projectID) $this->changeOldObjectStatus($oldProjectID, 'project');
    $this->changeOldObjectStatus($oldExecutionID, 'execution');

    return true;
}

/**
 * Change the execution of task.
 *
 * @param  array  $taskIDList
 * @param  int    $executionID
 * @param  int    $projectID
 * @access public
 * @return void
 */
public function changeEffortExecution($taskIDList, $executionID, $projectID)
{
    $this->dao->update(TABLE_EFFORT)
        ->data(array('execution' => $executionID, 'project' => $projectID))
        ->where('objectType')->eq('task')
        ->andWhere('objectID')->in($taskIDList)
        ->exec();

    return true;
}

/**
 * Sync the execution and project status.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
public function syncExecutionAndProjectStatus($executionID)
{
    $this->loadModel('common');

    $execution = $this->dao->select('id, project, grade, parent, status, deleted')->from(TABLE_EXECUTION)->where('id')->eq($executionID)->fetch();
    if($execution->status == 'wait')
    {
        $this->dao->update(TABLE_EXECUTION)->set('status')->eq('doing')->set('realBegan')->eq($today)->where('id')->eq($execution->id)->exec();
        $this->loadModel('project')->recordFirstEnd($execution->id); 
        $this->loadModel('action')->create('execution', $execution->id, 'changedoingstatus');
        if($execution->parent)
        {
            $execution = $this->dao->select('*')->from(TABLE_EXECUTION)->where('id')->eq($execution->id)->fetch(); // Get updated execution.
            $this->common->syncExecutionByChild($execution);
        }
    }   

    $project = $this->common->syncProjectStatus($execution);
    $this->common->syncProgramStatus($project);

    return true;
}

/**
 * Sync the execution and project status.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
public function changeOldObjectStatus($oldObjectID, $objectType)
{
    $object = $this->dao->select('consumed, status')->from(TABLE_PROJECT)->where('id')->eq($oldObjectID)->fetch();

    if(empty($object->consumed) && $object->status == 'doing')
    {
        $this->dao->update(TABLE_PROJECT)->set('status')->eq('wait')->where('id')->eq($oldObjectID)->exec();
        $this->loadModel('action')->create($objectType, $oldObjectID, 'changewaitstatus');
    }
}