<?php
helper::importControl('kanban');
class myKanban extends kanban
{
    /**
     * Set WIP.
     *
     * @param  int    $columnID
     * @param  int    $executionID
     * @param  string $from kanban|execution
     * @access public
     * @return void
     */
    public function setWIP($columnID, $executionID = 0, $from = 'kanban')
    {
        $column = $this->kanban->getColumnById($columnID);
        if($_POST)
        {
            if($from == 'chproject')
            {
                list($needUpdateLaneColumns, $kanbanType) = $this->kanban->getNeedUpdateLaneColumns($executionID, $columnID, $column->type);

                foreach($needUpdateLaneColumns as $needUpdateLaneColumnID)
                {
                    $execxutionID = $this->dao->select('kanban')->from(TABLE_KANBANCELL)->where('id')->eq($needUpdateLaneColumnID)->andWhere('type')->eq($kanbanType)->fetch('kanban');

                    $this->kanban->setWIP($needUpdateLaneColumnID);
                    if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

                    $this->loadModel('action')->create('kanbancolumn', $needUpdateLaneColumnID, 'Edited', '', $executionID);
                }

                return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'parent'));
            }

            $this->kanban->setWIP($columnID);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->loadModel('action')->create('kanbancolumn', $columnID, 'Edited', '', $executionID);

            if($from == 'RDKanban')
            {
                if(dao::isError()) return $this->sendError(dao::getError());

                $regionID     = $column->region;
                $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                $kanbanData   = $this->loadModel('kanban')->getRDKanban($executionID, $execLaneType, 'id_desc', $regionID, $execGroupBy);
                $kanbanData   = json_encode($kanbanData);
                return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.updateKanban($kanbanData, $regionID)"));
            }
            elseif($from == 'kanban')
            {
                $region      = $this->kanban->getRegionByID($column->region);
                $kanbanGroup = $this->kanban->getKanbanData($region->kanban, $region->id);
                return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => array('target' => 'parent', 'name' => 'updateRegion', 'params' => array($column->region, $kanbanGroup))));
            }
            else
            {
                return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'parent'));
            }
        }

        $this->app->loadLang('story');

        if(!$column) return print(js::error($this->lang->notFound) . js::locate($this->createLink('execution', 'kanban', "executionID=$executionID")));

        $title  = isset($column->parentName) ? $column->parentName . '/' . $column->name : $column->name;

        $this->view->title  = $title . $this->lang->colon . $this->lang->kanban->setWIP . '(' . $this->lang->kanban->WIP . ')';
        $this->view->column = $column;
        $this->view->from   = $from;

        if($from != 'kanban') $this->view->status = zget($this->config->kanban->{$column->laneType . 'ColumnStatusList'}, $column->type);
        $this->display();
    }
}
