<?php
class projectroleModel extends model
{
    public function getRoleList()
    {
        $options = $this->dao->select('options')->from(TABLE_WORKFLOWFIELD)->where('module')->eq('projectmembers')->andWhere('field')->eq('projectRole')->fetch('options');
        return json_decode($options, true);
    }

    public function getCostTypeList()
    {
        $options = $this->dao->select('options')->from(TABLE_WORKFLOWFIELD)->where('module')->eq('projectcost')->andWhere('field')->eq('costType')->fetch('options');
        return json_decode($options, true);
    }
}
