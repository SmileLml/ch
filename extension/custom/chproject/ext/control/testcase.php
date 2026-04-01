<?php
helper::importControl('chproject');
class myChproject extends chproject
{
    /**
     * Execution case list.
     *
     * @param  int    $executionID
     * @param  int    $productID
     * @param  int    $branchID
     * @param  string $type
     * @param  int    $moduleID
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function testcase($projectID = 0, $intanceProjectID = 0, $productID = 0, $branchID = 'all', $type = 'all', $moduleID = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('testcase');
        $this->loadModel('testtask');
        $this->loadModel('tree');
        $this->loadModel('product');
        $this->loadModel('execution');
        $this->session->set('caseList', $this->app->getURI(true), $this->app->tab);

        $this->chproject->setMenu($projectID);

        $project  = $this->chproject->getById($projectID);
        $intances = $this->chproject->getIntances($projectID);
        $hasProductIntances = $this->chproject->getIntances($projectID, true);

        $this->session->set('teamTestcaseList', $this->app->getURI(true), $this->app->tab);

        $products = $this->product->getProducts($intances);

        if(count($products) === 1) $productID = current($products)->id;

        $productPairs = array('0' => $this->lang->product->all);
        foreach($products as $productData) $productPairs[$productData->id] = $productData->name;

        $this->lang->modulePageNav = $this->product->select($hasProductIntances ? $productPairs : [], $productID, 'chproject', 'testcase', implode(',', $hasProductIntances), $branchID, '', 'testcase');

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);
        $executions = $intanceProjectID ? $this->execution->getPairsByProjectID($intanceProjectID, 'id') : [];
        $cases = $this->loadModel('testcase')->getExecutionCases($executions ? array_intersect($intances, $executions) : $intances, $productID, $branchID, $moduleID, $orderBy, $pager, $type);
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', false);
        $cases = $this->testcase->appendData($cases, 'case');
        $cases = $this->loadModel('story')->checkNeedConfirm($cases);

        $modules = $this->tree->getAllModulePairs('case');

        $tree = $moduleID ? $this->tree->getByID($moduleID) : '';
        if($this->config->edition == 'ipd') $cases = $this->loadModel('story')->getAffectObject($cases, 'case');

        $linkParams = ['chproject' => $projectID, 'intanceProjectID' => $intanceProjectID, 'productID' => $productID, 'orderBy' => $orderBy, 'type' => $type, 'branch' => $branchID];
        $moduleTree = $this->tree->getTeamTreeMenu('case', $projectID, 0, $linkParams);

        $intanceProjects  = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $linkProject = $this->chproject->getCaseExecutionProject($intances);
        foreach($cases as $case)
        {
            $linkExecutions = $this->chproject->getCaseExecution($case, $intances);

            $linkProjectName = '';
            foreach($linkExecutions as $linkExecution)
            {
                if(isset($intanceProjects[$linkProject[$linkExecution]])) $linkProjectName .= $intanceProjects[$linkProject[$linkExecution]] . ',';
            }

            $case->linkProjectName = trim($linkProjectName, ',');
        }

        $this->view->title            = $project->name . $this->lang->colon . $this->lang->execution->testcase;
        $this->view->productID        = $productID;
        $this->view->cases            = $cases;
        $this->view->orderBy          = $orderBy;
        $this->view->pager            = $pager;
        $this->view->type             = $type;
        $this->view->users            = $this->loadModel('user')->getPairs('noletter');
        $this->view->project          = $project;
        $this->view->moduleTree       = $moduleTree;
        $this->view->modules          = $modules;
        $this->view->moduleID         = $moduleID;
        $this->view->moduleName       = $moduleID ? $tree->name : $this->lang->tree->all;
        $this->view->branchID         = $branchID;
        $this->view->projectPairs     = $this->loadModel('project')->getPairsByProgram();
        $this->view->products         = $this->product->getPairs();
        $this->view->projectID        = $projectID;
        $this->view->intanceProjects  = $intanceProjects;
        $this->view->intanceProjectID = $intanceProjectID;
        $this->view->defaultProduct   = (empty($productID) and !empty($products)) ? current(array_keys($products)) : $productID;

        $this->display();
    }
}
