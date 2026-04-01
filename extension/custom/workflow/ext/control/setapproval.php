<?php
class myWorkflow extends workflow
{
    public function setApproval($module)
    {
        $this->app->loadLang('workflowcondition');

        if(empty($this->config->openedApproval)) $this->locate(inlink('browseflow'));

        if($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $result = $this->workflow->setApproval($module);
            if($result['result'] != 'success') return $this->send($result);

            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'reload'));
        }
        $this->app->loadLang('workflowrelation');

        $this->view->title         = $this->lang->workflow->setApproval;
        $this->view->flow          = $this->loadModel('workflow', 'flow')->getByModule($module);
        $this->view->fields        = $this->loadModel('workflowfield', 'flow')->getFieldPairs($module);
        $this->view->approvalFlows = $this->loadModel('approvalflow')->getPairs('workflow');
        $this->view->approvalList  = $this->dao->select('*')->from(TABLE_APPROVALFLOWOBJECT)->where('objectType')->eq($module)->orderBy('id')->fetchAll('id');
        $this->view->editorMode    = 'advanced';
        $this->display();
    }
}
