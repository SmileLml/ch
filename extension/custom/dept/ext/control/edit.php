<?php
helper::importControl('dept');
class mydept extends dept
{
    /**
     * Edit dept.
     *
     * @param  int    $deptID
     * @access public
     * @return void
     */
    public function edit($deptID)
    {
        if(!empty($_POST))
        {
            $this->dept->update($deptID);
            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));
            return print(js::alert($this->lang->dept->successSave) . js::reload('parent'));
        }

        $dept         = $this->dept->getById($deptID);
        $users        = $this->loadModel('user')->getPairs('noletter|noclosed|nodeleted|all', $dept->manager, $this->config->maxCount);
        $leadersUsers = $this->loadModel('user')->getPairs('noletter|noclosed|nodeleted|all', $dept->leaders, 2);

        if(!empty($this->config->user->moreLink))
        {
            $this->config->moreLinks["manager"] = $this->config->user->moreLink;
            $this->config->moreLinks["leaders[]"] = $this->config->user->moreLink;
        }

        $this->view->optionMenu   = $this->dept->getOptionMenu();
        $this->view->dept         = $dept;
        $this->view->users        = $users;
        $this->view->leadersUsers = $leadersUsers;

        /* Remove self and childs from the $optionMenu. Because it's parent can't be self or childs. */
        $childs = $this->dept->getAllChildId($deptID);
        foreach($childs as $childModuleID) unset($this->view->optionMenu[$childModuleID]);

        $this->display();
    }
}
