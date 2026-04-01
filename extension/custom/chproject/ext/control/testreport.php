<?php
class myChproject extends chproject
{
    /**
     * Browse report.
     *
     * @param  int    $objectID
     * @param  string $objectType
     * @param  string $extra
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function testreport($projectID = 0, $intanceProjectID = 0, $extra = '', $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('testtask');
        $this->loadModel('testreport');
        $this->loadModel('user');

        $this->chproject->setMenu($projectID);

        $project = $this->chproject->getById($projectID);

        if($extra) $task = $this->testtask->getById($extra);

        $title = $extra ? $task->name : $project->name;

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'mhtml') $recPerPage = 10;
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        $intanceExecutionIdList = $this->chproject->getIntancesProjectOptions($projectID, 'executionID', 'executionID', $intanceProjectID);
        $reports                = $this->testreport->getExecutionReports($intanceExecutionIdList, $extra, $orderBy, $pager);

        if($extra or isset($_POST['taskIdList']))
        {
            $taskIdList = isset($_POST['taskIdList']) ? $_POST['taskIdList'] : array($extra);
            foreach($reports as $reportID => $report)
            {
                $tasks = explode(',', $report->tasks);
                if(count($tasks) != count($taskIdList) or array_diff($tasks, $taskIdList)) unset($reports[$reportID]);
            }
            $pager->setRecTotal(count($reports));
        }

        if(empty($reports) and common::hasPriv('testreport', 'create'))
        {
            $param = '';
            if($extra or !empty($_POST['taskIdList']))
            {
                $param  = "objectID=$task->execution&objectType=execution";
                $param .= isset($_POST['taskIdList']) ? '&extra=' . join(',', $_POST['taskIdList']) : '&extra=' . $extra;
                $param .= "&begin=&end=&projectID=$projectID";
            }

            if($param) $this->locate($this->createLink('testreport', 'create', $param));
        }

        $this->session->set('teamReportList', $this->app->getURI(true) . "#app=chteam", 'chproject');

        $executions = array();
        $tasks      = array();
        foreach($reports as $report)
        {
            $executions[$report->execution] = $report->execution;
            foreach(explode(',', $report->tasks) as $taskID) $tasks[$taskID] = $taskID;
        }

        if($executions) $executions = $this->dao->select('id,name,multiple')->from(TABLE_PROJECT)->where('id')->in($executions)->fetchAll('id');
        if($tasks)      $tasks      = $this->dao->select('*')->from(TABLE_TESTTASK)->where('id')->in($tasks)->fetchAll('id');

        $productTasks = array();
        foreach($tasks as $key => $task) $productTasks[$task->product][] = $task;

        $this->view->title               = $title . $this->lang->colon . $this->lang->testreport->common;
        $this->view->position[]          = html::a(inlink('testreport', "projectID=$projectID&intanceProjectID=$intanceProjectID&extra=$extra"), $title);
        $this->view->position[]          = $this->lang->testreport->browse;
        $this->view->reports             = $reports;
        $this->view->orderBy             = $orderBy;
        $this->view->projectID           = $projectID;
        $this->view->extra               = $extra;
        $this->view->pager               = $pager;
        $this->view->users               = $this->user->getPairs('noletter|noclosed|nodeleted');
        $this->view->tasks               = $productTasks;
        $this->view->executions          = $executions;
        $this->view->canBeChanged        = common::canModify('project', $project); // Determines whether an object is editable.
        $this->view->intanceProjectPairs = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $this->view->intanceProjectID    = $intanceProjectID;
        $this->view->intanceProject      = $this->loadModel('project')->getById($intanceprojectID);

        $this->display();
    }
}
