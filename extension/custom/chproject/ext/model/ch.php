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
