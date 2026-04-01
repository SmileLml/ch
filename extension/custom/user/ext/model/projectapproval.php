<?php
/**
 * Get users by user group name.
 *
 * @param  string $param
 * @param  string $paramName
 * @access public
 * @return mixed
 */
public function getUsersByUserGroupName($param, $paramName = 'name')
{
    $users   = [];
    $groupID = $this->dao->select('id')->from(TABLE_GROUP)->where($paramName)->eq($param)->fetch('id');

    if($groupID)
    {
        $accounts = $this->dao->select('account')->from(TABLE_USERGROUP)->where('`group`')->eq($groupID)->fetchPairs('account');
        $users    = $this->getPairs('noletter|noempty|nodeleted|noclosed', '', '', $accounts);
    }

    return $users;
}
