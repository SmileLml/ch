<?php
helper::importControl('bug');
class mybug extends bug
{
    /**
     * Batch update assign of bug.
     *
     * @param  int     $objectID  projectID|executionID|chProjectID
     * @param  string  $type      execution|project|product|my|chProject
     * @access public
     * @return void
     */
    public function batchAssignTo($objectID, $type = 'execution')
    {
        if(!empty($_POST) && isset($_POST['bugIDList']))
        {
            $bugIDList = $this->post->bugIDList;
            $bugIDList = array_unique($bugIDList);
            unset($_POST['bugIDList']);
            foreach($bugIDList as $bugID)
            {
                $this->loadModel('action');
                $changes = $this->bug->assign($bugID);
                if(dao::isError()) return print(js::error(dao::getError()));
                $actionID = $this->action->create('bug', $bugID, 'Assigned', $this->post->comment, $this->post->assignedTo);
                $this->action->logHistory($actionID, $changes);
            }
            $this->loadModel('score')->create('ajax', 'batchOther');
        }

        if($type == 'product' || $type == 'my') return print(js::reload('parent'));
        if($type == 'execution') return print(js::locate($this->createLink('execution', 'bug', "executionID=$objectID")));
        if($type == 'project')   return print(js::locate($this->createLink('project', 'bug', "projectID=$objectID")));
        if($type == 'chproject') return print(js::locate($this->createLink('chproject', 'bug', "projectID=$objectID")));
    }
}
