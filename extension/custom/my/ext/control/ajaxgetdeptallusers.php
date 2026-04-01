<?php
class myMy extends my
{
    public function ajaxGetDeptAllUsers($deptID)
    {
        if(!$deptID)
        {
            echo json_encode(array());
            exit;
        }
        $deptIdList = $this->loadModel('dept')->getAllChildId($deptID);
        $users      = $this->loadModel('dept')->getUsers('all', $deptIdList);
        echo json_encode(array_column($users, 'account'));exit;
    }
}