<?php
/**
 * Refresh stats fields(estimate,consumed,left,progress) of program, project, execution.
 *
 * @param  bool   $refreshAll
 * @param  array  $projectIdList
 * @access public
 * @return void
 */
public function refreshStats($refreshAll = false, $projectIdList = array())
{
    $updateTime = zget($this->app->config->global, 'projectStatsTime', '');
    $now        = helper::now();
    if($updateTime && time() - strtotime($updateTime) < $this->config->program->refreshInterval && !$refreshAll) return;

    /*
     * If projectStatsTime is before two weeks ago, refresh all executions directly.
     * Else only refresh the latest executions in action table.
     */
    $projects = array();
    if($projectIdList)
    {
        $projects = $projectIdList;
    }
    elseif($updateTime < date('Y-m-d', strtotime('-14 days')) or $refreshAll)
    {
        $projects = $this->dao->select('id')->from(TABLE_PROJECT)->where('type')->eq('project')->fetchPairs('id');
    }
    else
    {
        $projects = $this->dao->select('project')->from(TABLE_ACTION)->where('`date`')->ge($updateTime)->andWhere('project')->ne(0)->fetchPairs('project');
    }
    if(empty($projects)) return;

    $executionGroup = $this->dao->select('id,project')->from(TABLE_PROJECT)->where('project')->in($projects)->andWhere('deleted')->eq(0)->fetchGroup('project', 'id');

    $summary = array();
    /* 1. Execution has no tasks.*/
    foreach($projects as $projectID => $project)
    {
        $executions = zget($executionGroup, $projectID, array());
        foreach($executions as $executionID => $execution)
        {
            $summary[$executionID] = new stdclass();
            $summary[$executionID]->totalEstimate = 0;
            $summary[$executionID]->totalConsumed = 0;
            $summary[$executionID]->totalLeft     = 0;
            $summary[$executionID]->execution     = $executionID;
        }
    }

    /* 2. Get summary and members of executions to be refreshed. */
    $tasks = $this->dao->select('t1.id, execution, t1.estimate, t1.consumed, t1.`left`, t1.status')->from(TABLE_TASK)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project=t2.id')
        ->where('t1.deleted')->eq(0)
        ->andWhere('t1.parent')->ge(0) // Ignore parent task.
        ->beginIF(!empty($projects))->andWhere('t1.project')->in($projects)->fi()
        ->fetchAll('id');

    foreach($tasks as $task)
    {
        if(empty($task->execution)) continue;
        if(!isset($summary[$task->execution]))
        {
            $summary[$task->execution] = new stdclass();
            $summary[$task->execution]->totalEstimate = 0;
            $summary[$task->execution]->totalConsumed = 0;
            $summary[$task->execution]->totalLeft     = 0;
        }

        $summary[$task->execution]->execution      = $task->execution;
        $summary[$task->execution]->totalEstimate += $task->estimate;
        $summary[$task->execution]->totalConsumed += $task->consumed;
        $summary[$task->execution]->totalLeft     += ($task->status == 'closed' or $task->status == 'cancel') ? 0 : $task->left;
    }

    $teamMembers = $this->dao->select('t1.root, COUNT(1) AS members')->from(TABLE_TEAM)->alias('t1')
        ->leftJoin(TABLE_USER)->alias('t2')->on('t1.account=t2.account')
        ->where('t1.type')->eq('project')
        ->beginIF(!empty($projects))->andWhere('t1.root')->in($projects)->fi()
        ->andWhere('t2.deleted')->eq(0)
        ->groupBy('t1.root')
        ->fetchPairs('root');

    $projectsPairs = $this->dao->select('id,deleted')->from(TABLE_PROJECT)->fetchPairs();

    /* 3. Get all parents to be refreshed. */
    $executions = array();
    foreach($summary as $execution) $executions[$execution->execution] = $execution->execution;
    $paths = $this->dao->select('id,path')->from(TABLE_PROJECT)->where('id')->in($executions)->fetchAll();
    $executionPaths = array();
    foreach($paths as $path) $executionPaths[$path->id] = explode(',', trim($path->path, ','));

    /* 4. Compute stats of execution and parents. */
    $stats = array();
    foreach($summary as $execution)
    {
        $executionID = $execution->execution;
        foreach($executionPaths[$executionID] as $nodeID)
        {
            if(!isset($stats[$nodeID])) $stats[$nodeID] = array('totalEstimate' => 0, 'totalConsumed' => 0, 'totalLeft' => 0, 'teamCount' => 0, 'totalLeftNotDel' => 0, 'totalConsumedNotDel' => 0);
            $stats[$nodeID]['totalEstimate'] += $execution->totalEstimate;
            $stats[$nodeID]['totalConsumed'] += $execution->totalConsumed;
            $stats[$nodeID]['totalLeft']     += $execution->totalLeft;

            // Check $execution->execution and $nodeID(path) is not deleted.
            if(empty($projectsPairs[$execution->execution]) && empty($projectsPairs[$nodeID]))
            {
                $stats[$nodeID]['totalConsumedNotDel'] += $execution->totalConsumed;
                $stats[$nodeID]['totalLeftNotDel']     += $execution->totalLeft;
            }
        }
    }

    foreach($teamMembers as $projectID => $teamCount)
    {
        if(!isset($stats[$projectID])) $stats[$projectID] = array('totalEstimate' => 0, 'totalConsumed' => 0, 'totalLeft' => 0, 'teamCount' => 0, 'totalConsumedNotDel' => 0, 'totalLeftNotDel' => 0);
        $stats[$projectID]['teamCount'] = $teamCount;
    }

    /* 5. Refresh stats to db. */
    foreach($stats as $projectID => $project)
    {
        $totalRealNotDel = $project['totalConsumedNotDel'] + $project['totalLeftNotDel'];
        $progress        = $totalRealNotDel ? floor($project['totalConsumedNotDel'] / $totalRealNotDel * 1000) / 1000 * 100 : 0;
        $this->dao->update(TABLE_PROJECT)
            ->set('progress')->eq($progress)
            ->set('teamCount')->eq($project['teamCount'])
            ->set('estimate')->eq($project['totalEstimate'])
            ->set('consumed')->eq($project['totalConsumedNotDel'])
            ->set('left')->eq($project['totalLeftNotDel'])
            ->where('id')->eq($projectID)
            ->exec();
    }

    /* 6. Update programStatsTime. */
    $projectList = $this->dao->select('id,progress,path,consumed,`left`')->from(TABLE_PROJECT)
        ->where('type')->eq('project')
        ->andWhere('parent')->ne(0)
        ->andWhere('deleted')->eq(0)
        ->fetchAll('id');
    $programProgress = array();
    foreach($projectList as $projectID => $project)
    {
        $path = explode(',', trim($project->path, ','));

        foreach($path as $programID)
        {
            if($programID == $projectID) continue;

            if(!isset($programProgress[$programID])) $programProgress[$programID] = array('consumed' => 0, 'left' => 0);
            $programProgress[$programID]['consumed'] += $project->consumed;
            $programProgress[$programID]['left']     += $project->left;
        }
    }

    foreach($programProgress as $programID => $hours)
    {
        $progress = ($hours['consumed'] + $hours['left']) ? floor($hours['consumed'] / ($hours['consumed'] + $hours['left']) * 1000) / 1000 * 100 : 0;

        $this->dao->update(TABLE_PROJECT)->set('progress')->eq($progress)->where('id')->eq($programID)->exec();
    }

    /* 7. Update projectStatsTime in config. */
    $this->loadModel('setting')->setItem('system.common.global.projectStatsTime', $now);
    $this->app->config->global->projectStatsTime = $now;

    /* 8. Clear actions older than 30 days. */
    $this->loadModel('action')->cleanActions();
}
