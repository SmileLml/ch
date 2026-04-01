<?php
helper::importControl('execution');
class myExecution extends execution
{
    /**
     * Set Kanban.
     *
     * @param  int    $executionID
     * @access public
     * @return void
     */
    public function setKanban($executionID)
    {
        $execution = $this->execution->getByID($executionID);

        $this->loadModel('chproject');

        if($_POST)
        {
            if($this->app->tab != 'chteam') $this->execution->setKanban($executionID);
            if($this->app->tab == 'chteam') $this->chproject->setKanban($executionID);

            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'parent'));
        }

        if($this->app->tab == 'chteam')
        {
            $execution   = $this->chproject->getByID($executionID);
            $executionID = $this->chproject->getIntances($executionID);
        }

        $this->view->title         = $this->lang->execution->setKanban;
        $this->view->execution     = $execution;
        $this->view->laneCount     = $this->loadModel('kanban')->getLaneCount($executionID, $execution->type);
        $this->view->heightType    = $execution->displayCards > 2 ? 'custom' : 'auto';
        $this->view->displayCards  = $execution->displayCards ? $execution->displayCards : '';

        $this->display();
    }
}
