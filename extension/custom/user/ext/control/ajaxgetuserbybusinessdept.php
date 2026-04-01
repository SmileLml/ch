<?php
helper::importControl('user');
class myuser extends user
{
    /**
     * Ajax get user by dept.
     *
     * @param  int    $reateDeptID
     * @param  string $deptID
     * @param  string $field
     * @access public
     * @return mixed
     */
    public function ajaxGetUserByBusinessDept($createDeptID, $deptID, $field)
    {
        $createDeptLeaders = ($createDeptID) ? $this->dao->select('leaders')->from(TABLE_DEPT)->where('id')->eq($createDeptID)->fetch('leaders') : '';
        $deptLeaders       = ($deptID && $deptID != 'null') ? $this->dao->select('id,leaders')->from(TABLE_DEPT)->where('id')->in($deptID)->fetchPairs() : [];

        $leaders = [$createDeptLeaders, ...array_values($deptLeaders)];

        $mergedValues = [];

        foreach($leaders as $value) $mergedValues = array_merge($mergedValues, explode(',', $value));

        $accounts = implode(',', array_unique($mergedValues));

        $users = ['' => ''];
        if($accounts)
        {
            $users += $this->dao->select('account, realname')->from(TABLE_USER)
                ->where('deleted')->eq('0')
                ->andWhere('account')->in($accounts)
                ->andWhere('deleted')->eq('0')
                ->andWhere('type')->eq('inside')
                ->fetchPairs();
        }

        $prefix = 'approval_reviewer';
        $pos    = strpos($field, $prefix);

        $fieldName = '';
        if($pos !== false) $fieldName = substr($field, $pos + strlen($prefix));

        return print(html::select($prefix . '[' . $fieldName . '][]', $users, '', "class='form-control picker-select' multiple id=$field"));
    }
}
