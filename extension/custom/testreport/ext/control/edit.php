<?php
class mytestreport extends testreport
{
    /**
     * Edit report
     *
     * @param  int       $reportID
     * @param  string    $begin
     * @param  string    $end
     * @access public
     * @return void
     */
    public function edit($reportID, $begin = '', $end ='', $chprojectID = 0)
    {
        if($_POST)
        {
            $changes = $this->testreport->update($reportID);
            if(dao::isError()) return print(js::error(dao::getError()));

            $files      = $this->loadModel('file')->saveUpload('testreport', $reportID);
            $fileAction = !empty($files) ? $this->lang->addFiles . join(',', $files) . "\n" : '';
            $actionID   = $this->loadModel('action')->create('testreport', $reportID, 'Edited', $fileAction);
            if(!empty($changes)) $this->action->logHistory($actionID, $changes);

            if($this->app->tab == 'chteam') return print(js::locate(inlink('view', "reportID=$reportID&tab=basic&recTotal=0&recPerPage=100&pageID=1&chprojectID=$chprojectID"), 'parent'));

            return print(js::locate(inlink('view', "reportID=$reportID"), 'parent'));
        }

        $report    = $this->testreport->getById($reportID);
        $execution = $this->execution->getById($report->execution);
        $begin     = !empty($begin) ? date("Y-m-d", strtotime($begin)) : $report->begin;
        $end       = !empty($end) ? date("Y-m-d", strtotime($end)) : $report->end;

        if($this->app->tab == 'qa' and !empty($report->product))
        {
            $product   = $this->product->getById($report->product);
            $productID = $this->commonAction($report->product, 'product');
            if($productID != $report->product) return print(js::error($this->lang->error->accessDenied) . js::locate('back'));

            $browseLink = inlink('browse', "objectID=$productID&objectType=product");
            $this->view->position[] = html::a($browseLink, $product->name);
            $this->view->position[] = $this->lang->testreport->edit;
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

            $browseLink = inlink('browse', "objectID=$objectID&objectType=execution");
            $this->view->position[] = html::a($browseLink, $execution->name);
            $this->view->position[] = $this->lang->testreport->edit;
        }
        elseif($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($chprojectID);
        }

        if($report->objectType == 'testtask')
        {
            $productIdList[$report->product] = $report->product;

            $task      = $this->testtask->getById($report->objectID);
            $execution = $this->execution->getById($task->execution);
            $builds    = array();
            if($task->build == 'trunk')
            {
                echo js::alert($this->lang->testreport->errorTrunk);
                return print(js::locate('back'));
            }
            else
            {
                $build   = $this->build->getById($task->build);
                $stories = empty($build->stories) ? array() : $this->story->getByList($build->stories);

                if(!empty($build->id)) $builds[$build->id] = $build;
                $bugs = $this->testreport->getBugs4Test($builds, $report->product, $begin, $end);
            }
            $tasks = array($task->id => $task);

            $this->setChartDatas($report->objectID);
        }
        elseif($report->objectType == 'execution' or $report->objectType == 'project')
        {
            $tasks = $this->testtask->getByList($report->tasks);
            $productIdList[$report->product] = $report->product;

            foreach($tasks as $task) $this->setChartDatas($task->id);

            $builds  = $this->build->getByList($report->builds);
            $stories = !empty($builds) ? $this->testreport->getStories4Test($builds) : $this->story->getExecutionStories($execution->id);;
            $bugs    = $this->testreport->getBugs4Test($builds, $productIdList, $begin, $end, 'execution');
        }

        $cases = $this->testreport->getTaskCases($tasks, $begin, $end);

        list($bugInfo, $bugSummary) = $this->testreport->getBug4Report($tasks, $productIdList, $begin, $end, $builds);

        $this->view->title = $report->title . $this->lang->testreport->edit;

        $this->view->report        = $report;
        $this->view->begin         = $begin;
        $this->view->end           = $end;
        $this->view->stories       = $stories;
        $this->view->bugs          = $bugs;
        $this->view->execution     = $execution;
        $this->view->productIdList = join(',', array_keys($productIdList));
        $this->view->tasks         = join(',', array_keys($tasks));
        $this->view->storySummary  = $this->product->summary($stories);

        $this->view->builds = $builds;
        $this->view->users  = $this->user->getPairs('noletter|noclosed|nodeleted');

        $this->view->cases       = $cases;
        $this->view->caseSummary = $this->testreport->getResultSummary($tasks, $cases, $begin, $end);

        $perCaseResult = $this->testreport->getPerCaseResult4Report($tasks, $report->cases, $begin, $end);
        $perCaseRunner = $this->testreport->getPerCaseRunner4Report($tasks, $report->cases, $begin, $end);
        $this->view->datas['testTaskPerRunResult'] = $this->loadModel('report')->computePercent($perCaseResult);
        $this->view->datas['testTaskPerRunner']    = $this->report->computePercent($perCaseRunner);

        $this->view->legacyBugs = $bugSummary['legacyBugs'];
        $this->view->bugInfo    = $bugInfo;
        $this->view->bugSummary = $bugSummary;

        $this->display();
    }
}
