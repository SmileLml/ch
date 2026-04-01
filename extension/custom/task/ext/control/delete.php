<?php
helper::importControl('task');
class mytask extends task
{
    /**
     * Delete a task.
     *
     * @param  int    $executionID
     * @param  int    $taskID
     * @param  string $confirm yes|no
     * @param  string $from taskkanban
     * @access public
     * @return void
     */
    public function delete($executionID, $taskID, $confirm = 'no', $from = '')
    {
        $task = $this->task->getById($taskID);
        if($task->parent < 0) return print(js::alert($this->lang->task->cannotDeleteParent));

        if($confirm == 'no')
        {
            return print(js::confirm($this->lang->task->confirmDelete, inlink('delete', "executionID=$executionID&taskID=$taskID&confirm=yes&from=$from")));
        }
        else
        {
            $this->task->delete(TABLE_TASK, $taskID);
            if($task->parent > 0)
            {
                $this->task->updateParentStatus($task->id);
                $this->loadModel('action')->create('task', $task->parent, 'deleteChildrenTask', '', $taskID);
            }
            if($task->fromBug != 0) $this->dao->update(TABLE_BUG)->set('toTask')->eq(0)->where('id')->eq($task->fromBug)->exec();
            if($task->story) $this->loadModel('story')->setStage($task->story);

            $this->executeHooks($taskID);

            if(isonlybody()) return print(js::reload('parent.parent'));
            if($from == 'taskkanban')
            {
                $execLaneType    = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy     = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($this->session->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                $kanbanType      = $execLaneType == 'all' ? 'task' : key($kanbanData);
                $kanbanData      = $kanbanData[$kanbanType];
                $kanbanData      = json_encode($kanbanData);
                return print(js::closeModal('parent', '', "parent.updateKanban(\"task\", $kanbanData)"));
            }

            if($from == 'market') return print(js::locate($this->createLink('marketresearch', 'stage', "researchID=$task->project"), 'parent'));

            $locateLink = $this->session->taskList ? $this->session->taskList : $this->createLink('execution', 'task', "executionID={$task->execution}");

            if($this->app->tab == 'chteam') $locateLink = $this->session->teamTaskList;

            return print(js::locate($locateLink, 'parent'));
        }
    }
}
