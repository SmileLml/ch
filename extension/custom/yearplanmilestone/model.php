<?php
class yearplanmilestoneModel extends model
{
    public function getByParent($parent = 0)
    {
        return $this->dao->select('*')->from('zt_yearplanmilestone')
            ->where('parent')->eq($parent)
            ->fetchAll();
    }
}