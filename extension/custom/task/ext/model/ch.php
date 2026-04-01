<?php
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
