<?php
helper::importControl('execution');
class myExecution extends execution
{
    /**
     * Ajax update kanban.
     *
     * @param  int    $executionID
     * @param  string $enterTime
     * @param  string $browseType
     * @param  string $groupBy
     * @param  string $from execution|RD
     * @param  string $searchValue
     * @param  string $orderBy
     * @access public
     * @return array
     */
    public function ajaxUpdateKanban($executionID = 0, $enterTime = '', $browseType = '', $groupBy = '', $from = 'execution', $searchValue = '', $orderBy = 'id_asc')
    {
        $this->loadModel('kanban');
        if($groupBy == 'story' and $browseType == 'task' and !isset($this->lang->kanban->orderList[$orderBy])) $orderBy = 'pri_asc';

        $enterTime = date('Y-m-d H:i:s', $enterTime);
        $lastEditedTime = $this->dao->select("max(lastEditedTime) as lastEditedTime")->from(TABLE_KANBANLANE)->where('execution')->eq($executionID)->fetch('lastEditedTime');

        if($from == 'chproject')
        {
            $executionIdList = $this->loadModel('chproject')->getIntances($executionID);
            $lastEditedTime  = $this->dao->select("max(lastEditedTime) as lastEditedTime")->from(TABLE_KANBANLANE)->where('execution')->eq(key($executionIdList))->fetch('lastEditedTime');
        }

        if($from == 'execution') $this->session->set('taskSearchValue', $searchValue);
        if($from == 'RD')        $this->session->set('rdSearchValue', $searchValue);
        if(strtotime($lastEditedTime) < 0 or $lastEditedTime > $enterTime or $groupBy != 'default' or !empty($searchValue))
        {
            $kanbanGroup = in_array($from, ['execution', 'chproject']) ? $this->kanban->getExecutionKanban($executionID, $browseType, $groupBy, $searchValue, $orderBy) : $this->kanban->getRDKanban($executionID, $browseType, $orderBy, 0, $groupBy, $searchValue);
            return print(json_encode($kanbanGroup));
        }
    }
}
