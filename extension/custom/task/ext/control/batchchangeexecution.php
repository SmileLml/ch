<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Update assign of task
     *
     * @param  int    $requestID
     * @param  int    $taskID
     * @param  string $kanbanGroup
     * @param  string $from
     * @access public
     * @return void
     */
    public function batchChangeExecution()
    {
        if(!empty($_POST))
        {
            // 处理批量变更执行的任务
            $this->task->batchChangeExecution();
            if(dao::isError()) return print(js::error(dao::getError()));
            
            return print(js::closeModal('parent.parent'));
        }
        
        $this->view->title      = $this->lang->task->changeProject;
        $this->view->position[] = $this->lang->task->changeProject;
        $this->view->taskIdList = $this->cookie->batchChangeTaskIdList;
        $this->view->projects   = array(0 => '') + $this->project->getPairsByIdList();

        $this->display();
    }
}