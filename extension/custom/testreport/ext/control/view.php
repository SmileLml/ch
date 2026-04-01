<?php
class mytestreport extends testreport
{
        /**
     * View report.
     *
     * @param  int    $reportID
     * @param  string $tab
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function view($reportID, $tab = 'basic', $recTotal = 0, $recPerPage = 100, $pageID = 1, $chprojectID = 0)
    {
        $reportID = (int)$reportID;
        $report   = $this->testreport->getById($reportID);
        if(!$report) return print(js::error($this->lang->notFound) . js::locate($this->createLink('qa', 'index')));
        $this->session->project = $report->project;

        $browseLink = '';
        $execution  = $this->execution->getById($report->execution);
        if($this->app->tab == 'qa' and !empty($report->product))
        {
            $product   = $this->product->getById($report->product);
            $productID = $this->commonAction($report->product, 'product');
            if($productID != $report->product) return print(js::error($this->lang->error->accessDenied) . js::locate('back'));
        }
        elseif($this->app->tab == 'execution' or $this->app->tab == 'project')
        {
            if($this->app->tab == 'execution')
            {
                $objectID = $this->commonAction($report->execution, 'execution');
                if($objectID != $report->execution) return print(js::error($this->lang->error->accessDenied) . js::locate('back'));
            }
            else
            {
                $objectID = $this->commonAction($report->project, 'project');
                if($objectID != $report->project) return print(js::error($this->lang->error->accessDenied) . js::locate('back'));
            }
        }
        elseif($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($chprojectID);
            $this->session->set('reportList', $this->createLink('chproject', 'testreport', "project=$chprojectID"), 'chprojectID');
            $this->view->chprojectID = $chprojectID;
        }

        $stories = $report->stories ? $this->story->getByList($report->stories) : array();
        $results = $this->dao->select('*')->from(TABLE_TESTRESULT)->where('run')->in($report->tasks)->andWhere('`case`')->in($report->cases)->fetchAll();
        $failResults = array();
        $runCasesNum = array();
        foreach($results as $result)
        {
            $runCasesNum[$result->case] = $result->case;
            if($result->caseResult == 'fail') $failResults[$result->case] = $result->case;
        }

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'mhtml') $recPerPage = 10;
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        $tasks   = $report->tasks ? $this->testtask->getByList($report->tasks) : array();;
        $builds  = $report->builds ? $this->build->getByList($report->builds) : array();
        $cases   = $this->testreport->getTaskCases($tasks, $report->begin, $report->end, $report->cases);

        list($bugInfo, $bugSummary) = $this->testreport->getBug4Report($tasks, $report->product, $report->begin, $report->end, $builds);

        /* save session .*/
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', false);

        if($report->objectType == 'testtask')
        {
            $this->setChartDatas($report->objectID);
        }
        elseif($tasks)
        {
            foreach($tasks as $task) $this->setChartDatas($task->id);
        }

        $this->view->title      = $report->title;
        $this->view->browseLink = $browseLink;
        $this->view->position[] = $report->title;

        $this->view->tab       = $tab;
        $this->view->pager     = $pager;
        $this->view->report    = $report;
        $this->view->execution = $execution;
        $this->view->stories   = $stories;
        $this->view->bugs      = $report->bugs ? $this->bug->getByList($report->bugs) : array();
        $this->view->builds    = $builds;
        $this->view->cases     = $this->testreport->getTaskCases($tasks, $report->begin, $report->end, $report->cases, $pager);
        $this->view->users     = $this->user->getPairs('noletter|noclosed|nodeleted');
        $this->view->actions   = $this->loadModel('action')->getList('testreport', $reportID);

        $this->view->storySummary = $this->product->summary($stories);
        $this->view->caseSummary  = $this->testreport->getResultSummary($tasks, $cases, $report->begin, $report->end);

        $perCaseResult = $this->testreport->getPerCaseResult4Report($tasks, $report->cases, $report->begin, $report->end);
        $perCaseRunner = $this->testreport->getPerCaseRunner4Report($tasks, $report->cases, $report->begin, $report->end);
        $this->view->datas['testTaskPerRunResult'] = $this->loadModel('report')->computePercent($perCaseResult);
        $this->view->datas['testTaskPerRunner']    = $this->report->computePercent($perCaseRunner);

        $this->view->bugInfo    = $bugInfo;
        $this->view->legacyBugs = $bugSummary['legacyBugs'];
        $this->view->bugSummary = $bugSummary;

        $this->display();
    }
}
