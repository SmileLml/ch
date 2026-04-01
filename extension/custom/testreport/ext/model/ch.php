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
