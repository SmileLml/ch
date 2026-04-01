<?php
/**
 * Get report list.
 *
 * @param  int|array    $executionID
 * @param  string $extra
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getExecutionReports($executionID, $extra = '', $orderBy = 'id_desc', $pager = null)
{
    return $this->dao->select('t1.*,t2.name as projectName,t3.name as productName')->from(TABLE_TESTREPORT)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t1.product = t3.id')
        ->where('t1.deleted')->eq(0)
        ->andWhere('t1.execution')->in($executionID)
        ->orderBy('t1.' . $orderBy)
        ->page($pager)
        ->fetchAll('id');
}

/**
 * Get task cases.
 *
 * @param  array  $tasks
 * @param  string $begin
 * @param  string $end
 * @param  string $idList
 * @param  object $pager
 * @access public
 * @return array
 */
public function getTaskCases($tasks, $begin, $end, $idList = '', $pager = null)
{
    $cases = $this->dao->select('t2.*,t1.task,t1.assignedTo,t1.status')->from(TABLE_TESTRUN)->alias('t1')
        ->leftJoin(TABLE_CASE)->alias('t2')->on('t1.case=t2.id')
        ->where('t1.task')->in(array_keys($tasks))
        ->beginIF($idList)->andWhere('t2.id')->in($idList)->fi()
        ->andWhere('t2.deleted')->eq(0)
        ->page($pager)
        ->fetchGroup('task','id');

    foreach($cases as $taskID => $caseList)
    {
        $results = $this->dao->select('t1.*')->from(TABLE_TESTRESULT)->alias('t1')
            ->leftJoin(TABLE_TESTRUN)->alias('t2')->on('t1.run=t2.id')
            ->where('t2.task')->eq($taskID)
            ->andWhere('t1.`case`')->in(array_keys($caseList))
            ->andWhere('t1.date')->ge($begin)
            ->andWhere('t1.date')->le($end . " 23:59:59")
            ->orderBy('date')
            ->fetchAll('case');

        $scenes = array_column($caseList, 'scene');
        $scenes = $this->loadModel('testcase')->getScenesName($scenes, true);

        foreach($caseList as $caseID => $case)
        {
            $case->lastRunner    = '';
            $case->lastRunDate   = '';
            $case->lastRunResult = '';
            $case->status        = 'normal';
            $case->sceneTitle    = zget($scenes, $case->scene, '');

            if(isset($results[$caseID]))
            {
                $result = $results[$caseID];
                $case->lastRunner    = $result->lastRunner;
                $case->lastRunDate   = $result->date;
                $case->lastRunResult = $result->caseResult;
                $case->status        = $result->caseResult == 'blocked' ? 'blocked' : 'normal';
            }
        }
    }
    return $cases;
}
