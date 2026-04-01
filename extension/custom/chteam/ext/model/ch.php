<?php
/**
 * Get ch team pairs.
 *
 * @access public
 * @return array
 */
public function getPairs()
{
    return $this->dao->select('id,name')->from(TABLE_CHTEAM)->where('deleted')->eq(0)->fetchPairs();
}