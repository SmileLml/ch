<?php
helper::importControl('approvalflow');
class myApprovalflow extends approvalflow
{
    /**
     * Design flow.
     *
     * @param int $flowID
     * @access public
     * @return void
     */
    public function design($flowID = 0)
    {
        $flow = $this->approvalflow->getByID($flowID);

        if(!empty($_POST))
        {
            $this->approvalflow->updateNodes($flow);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => inlink('browse', "type=$flow->type")));
        }

        $this->view->flow                = $flow;
        $this->view->users               = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->depts               = $this->loadModel('dept')->getPairs();
        $this->view->roles               = $this->approvalflow->getRolePairs();
        $this->view->title               = $flow->name . '-' . $this->lang->approvalflow->common;
        $this->view->permissionGroupings = $this->loadModel('group')->getPairs();

        $this->display();
    }
}