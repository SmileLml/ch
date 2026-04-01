<?php
helper::importControl('testcase');
class mytestcase extends testcase
{
    /**
     * Create bug.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  string $extras
     * @param  int    $extras
     * @access public
     * @return void
     */
    public function createBug($productID, $branch = 0, $extras = '', $chprojectID = 0)
    {
        $extras = str_replace(array(',', ' '), array('&', ''), $extras);
        parse_str($extras, $params);
        extract($params);

        $this->loadModel('testtask');
        $case = '';
        if($runID)
        {
            $case    = $this->testtask->getRunById($runID)->case;
            $results = $this->testtask->getResults($runID);
        }
        elseif($caseID)
        {
            $case    = $this->testcase->getById($caseID);
            $results = $this->testtask->getResults(0, $caseID);

            if($this->app->tab == 'chteam') $this->view->projectID = $case->project;
        }

        if(!$case) return print(js::error($this->lang->notFound) . js::locate('back', 'parent'));

        if(!isset($this->products[$productID]))
        {
            $product = $this->product->getByID($productID);
            $this->products[$productID] = $product->name;
        }

        $this->view->title       = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->createBug;
        $this->view->runID       = $runID;
        $this->view->case        = $case;
        $this->view->caseID      = $caseID;
        $this->view->version     = $version;
        $this->view->chprojectID = $chprojectID;
        $this->display();
    }
}