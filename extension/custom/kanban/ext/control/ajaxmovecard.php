<?php
helper::importControl('kanban');
class myKanban extends kanban
{
    /**
     * Ajax move card.
     *
     * @param  int    $cardID
     * @param  int    $fromColID
     * @param  int    $toColID
     * @param  int    $fromLaneID
     * @param  int    $toLaneID
     * @param  int    $executionID
     * @param  string $browseType
     * @param  string $groupBy
     * @param  int    $regionID
     * @param  string $orderBy
     * @access public
     * @return void
     */
    public function ajaxMoveCard($cardID = 0, $fromColID = 0, $toColID = 0, $fromLaneID = 0, $toLaneID = 0, $executionID = 0, $browseType = 'all', $groupBy = '', $regionID = 0, $orderBy = '')
    {
        if($this->app->tab == 'chteam')
        {
            $fromColID       = $this->loadModel('chproject')->getKanbanColumnID($fromLaneID, $cardID, $fromColID);
            $toColID         = $this->loadModel('chproject')->getKanbanColumnID($fromLaneID, $cardID, $toColID);
            $executionIdList = $this->loadModel('chproject')->getIntances($executionID);
            $executionID     = $this->dao->select('execution')->from(TABLE_KANBANLANE)->where('id')->eq($fromLaneID)->fetch('execution');
        }

        $fromCell = $this->dao->select('id, cards, lane')->from(TABLE_KANBANCELL)
            ->where('kanban')->eq($executionID)
            ->andWhere('`column`')->eq($fromColID)
            ->beginIF(!$groupBy or $groupBy == 'default')->andWhere('lane')->eq($fromLaneID)->fi()
            ->beginIF($groupBy and $groupBy != 'default')
            ->andWhere('type')->eq($browseType)
            ->andWhere('cards')->like("%,$cardID,%")
            ->fi()
            ->fetch();

        if($groupBy and $groupBy != 'default') $fromLaneID = $toLaneID = $fromCell->lane;

        $toCell = $this->dao->select('id, cards')->from(TABLE_KANBANCELL)
            ->where('kanban')->eq($executionID)
            ->andWhere('lane')->eq($toLaneID)
            ->andWhere('`column`')->eq($toColID)
            ->fetch();

        $fromCards = str_replace(",$cardID,", ',', $fromCell->cards);
        $fromCards = $fromCards == ',' ? '' : $fromCards;
        $toCards   = ",$cardID," . ltrim($toCell->cards, ',');

        $this->dao->update(TABLE_KANBANCELL)->set('cards')->eq($fromCards)
            ->where('kanban')->eq($executionID)
            ->andWhere('lane')->eq($fromLaneID)
            ->andWhere('`column`')->eq($fromColID)
            ->exec();

        $this->dao->update(TABLE_KANBANCELL)->set('cards')->eq($toCards)
            ->where('kanban')->eq($executionID)
            ->andWhere('lane')->eq($toLaneID)
            ->andWhere('`column`')->eq($toColID)
            ->exec();

        $toColumn = $this->kanban->getColumnByID($toColID);
        if($toColumn->laneType == 'story' and in_array($toColumn->type, array('tested', 'verified', 'released', 'closed')))
        {
            $data = new stdclass();
            $data->stage = $toColumn->type;
            if($toColumn->type == 'released')
            {
                $fromColumn = $this->kanban->getColumnByID($fromColID);
                if($fromColumn->type == 'closed') $data->status = 'active';
            }
            $this->dao->update(TABLE_STORY)->data($data)->where('id')->eq($cardID)->exec();
            $this->dao->update(TABLE_STORYSTAGE)->set('stage')->eq($toColumn->type)->where('story')->eq($cardID)->exec();
        }

        $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
        $rdSearchValue   = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
        $kanbanGroup     = $regionID == 0 ? $this->kanban->getExecutionKanban($executionID, $browseType, $groupBy, $taskSearchValue) : $this->kanban->getRDKanban($executionID, $browseType, $orderBy, $regionID, $groupBy, $rdSearchValue);

        if($this->app->tab == 'chteam') $kanbanGroup = $this->kanban->getExecutionKanban($executionIdList, $browseType, $groupBy, $taskSearchValue);

        echo json_encode($kanbanGroup);
    }
}
