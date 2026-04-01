<?php
public function getAllDepartment()
{
    return $this->dao->select('*')->from(TABLE_DEPT)->fetchAll('id');
}
