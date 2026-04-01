<?php
helper::importControl('kanban');
class myKanban extends kanban
{
    /**
     * Set lane column info.
     *
     * @param  int $columnID
     * @param  int $executionID
     * @param  string $from kanban|execution
     * @access public
     * @return void
     */
    public function setColumn($columnID, $executionID = 0, $from = 'kanban')
    {
        $column = $this->kanban->getColumnByID($columnID);

        if($_POST)
        {
            if($from == 'chproject')
            {
                list($needUpdateLaneColumns, $kanbanType) = $this->kanban->getNeedUpdateLaneColumns($executionID, $columnID, $column->type);

                foreach($needUpdateLaneColumns as $needUpdateLaneColumnID)
                {
                    $column       = $this->kanban->getColumnByID($needUpdateLaneColumnID);
                    $execxutionID = $this->dao->select('kanban')->from(TABLE_KANBANCELL)->where('id')->eq($needUpdateLaneColumnID)->andWhere('type')->eq($kanbanType)->fetch('kanban');
                    $changes      = $this->kanban->updateLaneColumn($needUpdateLaneColumnID, $column);
                    if(dao::isError()) return $this->sendError(dao::getError());

                    if($changes)
                    {
                        $actionID = $this->loadModel('action')->create('kanbancolumn', $needUpdateLaneColumnID, 'Edited', '', $executionID);
                        $this->action->logHistory($actionID, $changes);
                    }
                }
            }
            else
            {
                $changes = $this->kanban->updateLaneColumn($columnID, $column);
                if(dao::isError()) return $this->sendError(dao::getError());
                if($changes)
                {
                    $actionID = $this->loadModel('action')->create('kanbancolumn', $columnID, 'Edited', '', $executionID);
                    $this->action->logHistory($actionID, $changes);
                }
            }

            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => array('target' => 'parent', 'name' => 'updateColumnName', 'params' => array($columnID, $this->post->name, $this->post->color))));
        }

        $this->view->canEdit = $from == 'RDKanban' ? 0 : 1;
        $this->view->column  = $column;
        $this->view->title   = $column->name . $this->lang->colon . $this->lang->kanban->editColumn;
        $this->display();
    }
}
