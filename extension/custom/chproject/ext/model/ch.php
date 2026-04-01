<?php
/**
 * Get intance products pairs.
 *
 * @param  int    $projectID
 * @param  int    $noShadow
 * @access public
 * @return array
 */
public function getIntanceProductsPairs($projectID, $noShadow = false)
{
    $intanceProductIDList = $this->getIntanceProductPairs($projectID);
    return $this->dao->select('id,name')->from(TABLE_PRODUCT)
        ->where('id')->in($intanceProductIDList)
        ->beginIF($noShadow)->andWhere('shadow')->eq(0)->fi()
        ->fetchPairs();
}

/**
 * Get case execution pairs.
 *
 * @param  object $case
 * @param  array  $intances
 * @access public
 * @return array
 */
public function getCaseExecution($case, $intances)
{
    return $this->dao->select('project')->from(TABLE_PROJECTCASE)->where('`case`')->eq($case->id)->andWhere('project')->in($intances)->fetchPairs();
}

/**
 * Get case execution project.
 *
 * @param  array  $executionIdList
 * @access public
 * @return array
 */
public function getCaseExecutionProject($executionIdList)
{
    return $this->dao->select('id,project')->from(TABLE_PROJECT)->where('id')->in($executionIdList)->fetchPairs('id', 'project');
}
