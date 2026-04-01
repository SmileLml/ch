<?php
class myChproject extends chproject
{
    /**
     * Browse test tasks of execution.
     *
     * @param  int    $executionID
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function testtask($projectID = 0, $intanceProjectID = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('testtask');
        $this->loadModel('execution');
        $this->loadModel('product');
        $this->app->loadLang('testreport');

        $intanceExecutionIDList = $this->chproject->getIntances($projectID);
        if($intanceProjectID)
        {
            $executionPairs         = $this->execution->getPairs($intanceProjectID);
            $executionIdList        = array_keys($executionPairs);
            $intanceExecutionIDList = array_intersect($intanceExecutionIDList, $executionIdList);
        }

        /* Save session. */
        $this->session->set('teamTesttaskList', $this->app->getURI(true), 'chteam');
        $this->session->set('teamBuildList', $this->app->getURI(true), 'chteam');

        $executionID = $intanceExecutionIDList[0] ?? 0;
        $execution   = $this->execution->getById($executionID);

        /* Set menu. */
        $this->chproject->setMenu($projectID);
        $products = $this->chproject->getIntanceProductPairs($projectID);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        $productTasks = array();

        $tasks = $this->testtask->getExecutionTasks($intanceExecutionIDList, 'execution', $orderBy, $pager);

        foreach($tasks as $key => $task) $productTasks[$task->product][] = $task;

        $chproject = $this->chproject->getByID($projectID);

        $this->view->title               = $chproject->name . $this->lang->colon . $this->lang->testtask->common;
        $this->view->position[]          = html::a($this->createLink('chproject', 'testtask', "executionID=$executionID"), $this->executions[$executionID]);
        $this->view->position[]          = $this->lang->testtask->common;
        $this->view->execution           = $execution;
        $this->view->project             = $this->loadModel('project')->getByID($execution->project);
        $this->view->executionID         = $executionID;
        $this->view->pager               = $pager;
        $this->view->orderBy             = $orderBy;
        $this->view->tasks               = $productTasks;
        $this->view->users               = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->products            = $this->loadModel('product')->getPairsByIDList($products);
        $this->view->canBeChanged        = common::canModify('execution', $execution); // Determines whether an object is editable.
        $this->view->intanceProjectPairs = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $this->view->projectID           = $projectID;
        $this->view->intanceProjectID    = $intanceProjectID;

        $this->display();
    }
}
