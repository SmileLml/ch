<?php
helper::importControl('bug');
class mybug extends bug
{
    /**
     * Delete a bug.
     *
     * @param  int    $bugID
     * @param  string $confirm  yes|no
     * @param  string $from taskkanban
     * @access public
     * @return void
     */
    public function delete($bugID, $confirm = 'no', $from = '')
    {
        $bug = $this->bug->getById($bugID);
        if($confirm == 'no')
        {
            return print(js::confirm($this->lang->bug->confirmDelete, inlink('delete', "bugID=$bugID&confirm=yes&from=$from")));
        }
        else
        {
            $this->bug->delete(TABLE_BUG, $bugID);
            if($bug->toTask != 0)
            {
                $task = $this->task->getById($bug->toTask);
                if(!$task->deleted)
                {
                    $confirmURL = $this->createLink('task', 'view', "taskID=$bug->toTask");
                    unset($_GET['onlybody']);
                    $cancelURL  = $this->createLink('bug', 'view', "bugID=$bugID");
                    return print(js::confirm(sprintf($this->lang->bug->remindTask, $bug->toTask), $confirmURL, $cancelURL, 'parent', 'parent.parent'));
                }
            }

            $this->executeHooks($bugID);

            if($this->viewType == 'json') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess));

            if(isonlybody()) return print(js::reload('parent.parent'));

            if($from == 'taskkanban')
            {
                $execLaneType    = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy     = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($bug->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                $kanbanType      = $execLaneType == 'all' ? 'bug' : key($kanbanData);
                $kanbanData      = $kanbanData[$kanbanType];
                $kanbanData      = json_encode($kanbanData);
                return print(js::closeModal('parent', '', "parent.updateKanban(\"bug\", $kanbanData)"));
            }

            $locateLink = $this->session->bugList ? $this->session->bugList : inlink('browse', "productID={$bug->product}");

            if($from == 'chproject') $locateLink = $this->session->teamBugList ? $this->session->teamBugList : inlink('browse', "productID={$bug->product}");

            return print(js::locate($locateLink, 'parent'));
        }
    }
}
