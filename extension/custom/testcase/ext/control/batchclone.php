<?php
helper::importControl('testcase');
class mytestcase extends testcase
{
    /**
     * Batch clone case.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  string $type
     * @param  string $tab
     * @access public
     * @return void
     */
    public function batchClone($productID = 0, $branch = 0, $type = 'case', $tab = '')
    {
        if(!$this->post->caseIDList) return print(js::locate($this->session->caseList));
        $this->loadModel('story');
        if($this->post->title)
        {
            $caseIDList = $this->testcase->batchClone();
            if(dao::isError()) return print(js::error(dao::getError()));

            return print(js::locate($this->session->caseList, 'parent'));
        }

        $fromCases = $this->dao->select('*')->from('zt_case')->where('id')->in($this->post->caseIDList)->fetchAll();

        $executionIdList = array();
        foreach($fromCases as $fromcase) $executionIdList[] = $fromcase->execution;

        $formProjects = $this->dao->select('id,project')->from('zt_project')->where('id')->in($executionIdList)->fetchPairs('id');
        foreach($fromCases as $key => $fromcase)
        {
            if($fromcase->project) continue;
            $fromCases[$key]->project = isset($formProjects[$fromcase->execution]) ? $formProjects[$fromcase->execution] : 0;
        }

        if($this->app->tab == 'project')               $this->loadModel('project')->setMenu($this->session->project);
        if($this->app->tab == 'qa' and $type != 'lib') $this->testcase->setMenu($this->products, $productID, $branch);
        if($this->app->tab == 'execution')             $this->loadModel('execution')->setMenu($this->session->execution);

        $this->view->title     = $this->lang->testcase->batchClone;
        $this->view->fromCases = $fromCases;

        $this->display();
    }
}