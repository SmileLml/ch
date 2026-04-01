<?php
helper::importControl('testtask');
class mytesttask extends testtask
{
    /**
     * View test results of a test run.
     *
     * @param  int    $runID
     * @param  int    $caseID
     * @param  int    $version
     * @param  string $status  all|done
     * @param  int    $$chprojectID
     * @access public
     * @return void
     */
    public function results($runID, $caseID = 0, $version = 0, $status = 'done', $chprojectID = 0)
    {
        if($this->app->tab == 'project') $this->loadModel('project')->setMenu($this->session->project);

        if($runID)
        {
            $case    = $this->testtask->getRunById($runID)->case;
            $results = $this->testtask->getResults($runID, 0, $status);

            $testtaskID = $this->dao->select('task')->from(TABLE_TESTRUN)->where('id')->eq($runID)->fetch('task');
            $testtask   = $this->dao->select('id, build, execution, product')->from(TABLE_TESTTASK)->where('id')->eq($testtaskID)->fetch();

            $this->view->testtask = $testtask;
        }
        else
        {
            $case    = $this->loadModel('testcase')->getByID($caseID, $version);
            $results = $this->testtask->getResults(0, $caseID, $status);

            if($this->app->tab == 'chteam') $this->view->projectID = $case->project;
        }

        $this->view->case        = $case;
        $this->view->runID       = $runID;
        $this->view->results     = $results;
        $this->view->builds      = $this->loadModel('build')->getBuildPairs($case->product, $case->branch);
        $this->view->users       = $this->loadModel('user')->getPairs('noclosed, noletter');
        $this->view->chprojectID = $chprojectID;

        $this->display();
    }
}