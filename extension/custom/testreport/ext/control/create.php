<?php
class mytestreport extends testreport
{
    /**
     * Create report.
     *
     * @param  int    $objectID
     * @param  string $objectType
     * @param  string $extra
     * @param  string $begin
     * @param  string $end
     * @param  int    $chprojectID
     * @access public
     * @return void
     */
    public function create($objectID = 0, $objectType = 'testtask', $extra = '', $begin = '', $end = '', $chprojectID = 0)
    {
        if($_POST)
        {
            $reportID = $this->testreport->create();
            if(dao::isError()) return print(js::error(dao::getError()));
            $this->loadModel('action')->create('testreport', $reportID, 'Opened');
            if($this->viewType == 'json') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'id' => $reportID));

            if($this->app->tab == 'chteam') return print(js::locate(inlink('view', "reportID=$reportID&tab=basic&recTotal=0&recPerPage=100&pageID=1&chprojectID=$chprojectID#app=chteam"), 'parent'));
            return print(js::locate(inlink('view', "reportID=$reportID"), 'parent'));
        }

        if($objectType == 'testtask')
        {
            if(empty($objectID) and $extra) $productID = $extra;
            if($objectID)
            {
                $task      = $this->testtask->getById($objectID);
                $productID = $this->commonAction($task->product, 'product');
            }

            $taskPairs         = array();
            $scopeAndStatus[0] = 'local';
            $scopeAndStatus[1] = 'totalStatus';
            $tasks = $this->testtask->getProductTasks($productID, empty($objectID) ? 'all' : $task->branch, 'id_desc', null, $scopeAndStatus);
            foreach($tasks as $testTask)
            {
                if($testTask->build == 'trunk') continue;
                $taskPairs[$testTask->id] = $testTask->name;
            }
            if(empty($taskPairs)) return print(js::alert($this->lang->testreport->noTestTask) . js::locate('back'));

            if(empty($objectID))
            {
                $objectID  = key($taskPairs);
                $task      = $this->testtask->getById($objectID);
                $productID = $this->commonAction($task->product, 'product');
            }
            $this->view->taskPairs = $taskPairs;

            if($this->app->tab == 'execution') $this->execution->setMenu($task->execution);
            if($this->app->tab == 'project') $this->project->setMenu($task->project);
        }

        if(empty($objectID)) return print(js::alert($this->lang->testreport->noObjectID) . js::locate('back'));
        if($objectType == 'testtask')
        {
            if($productID != $task->product) return print(js::error($this->lang->error->accessDenied) . js::locate('back'));
            $productIdList[$productID] = $productID;

            $begin     = !empty($begin) ? date("Y-m-d", strtotime($begin)) : $task->begin;
            $end       = !empty($end) ? date("Y-m-d", strtotime($end)) : $task->end;
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
                $bugs = $this->testreport->getBugs4Test($builds, $productID, $begin, $end);
            }

            $tasks = array($task->id => $task);
            $owner = $task->owner;

            $this->setChartDatas($objectID);

            $this->view->title       = $task->name . $this->lang->testreport->create;
            $this->view->position[]  = html::a(inlink('browse', "objectID=$productID&objectType=product&extra={$task->id}"), $task->name);
            $this->view->position[]  = $this->lang->testreport->create;
            $this->view->reportTitle = date('Y-m-d') . " TESTTASK#{$task->id} {$task->name} {$this->lang->testreport->common}";
        }
        elseif($objectType == 'execution' or $objectType == 'project')
        {
            $executionID = $this->commonAction($objectID, $objectType);

            if($this->app->tab == 'chteam')
            {
                $this->loadModel('chproject')->setMenu($chprojectID);
                $this->view->chProjectID = $chprojectID;
            }

            if($executionID != $objectID) return print(js::error($this->lang->error->accessDenied) . js::locate('back'));

            $execution     = $this->execution->getById($executionID);
            $tasks         = $this->testtask->getExecutionTasks($executionID, $objectType);
            $task          = $objectID ? $this->testtask->getById($extra) : key($tasks);
            $owners        = array();
            $buildIdList   = array();
            $productIdList = array();
            foreach($tasks as $i => $testtask)
            {
                if(!empty($extra) and strpos(",{$extra},", ",{$testtask->id},") === false)
                {
                    unset($tasks[$i]);
                    continue;
                }

                $owners[$testtask->owner] = $testtask->owner;
                $productIdList[$testtask->product] = $testtask->product;
                $this->setChartDatas($testtask->id);
                if($testtask->build != 'trunk') $buildIdList[$testtask->build] = $testtask->build;
            }
            if(count($productIdList) > 1)
            {
                echo(js::alert($this->lang->testreport->moreProduct));
                return print(js::locate('back'));
            }

            if($this->app->tab == 'qa')
            {
                $productID = $this->product->saveState(key($productIdList), $this->products);
                $this->loadModel('qa')->setMenu($this->products, $productID);
            }
            elseif($this->app->tab == 'project')
            {
                $projects  = $this->project->getPairsByProgram();
                $projectID = $this->project->saveState($execution->id, $projects);
                $this->project->setMenu($projectID);
            }

            $builds  = $this->build->getByList($buildIdList);
            $stories = !empty($builds) ? $this->testreport->getStories4Test($builds) : $this->story->getExecutionStories($execution->id);;

            $begin = !empty($begin) ? date("Y-m-d", strtotime($begin)) : $task->begin;
            $end   = !empty($end) ? date("Y-m-d", strtotime($end)) : $task->end;
            $owner = current($owners);
            $bugs  = $this->testreport->getBugs4Test($builds, $productIdList, $begin, $end, 'execution');

            $this->view->title       = $execution->name . $this->lang->testreport->create;
            $this->view->reportTitle = date('Y-m-d') . ' ' . strtoupper($objectType) . "#{$execution->id} {$execution->name} {$this->lang->testreport->common}";
        }

        $cases = $this->testreport->getTaskCases($tasks, $begin, $end);

        list($bugInfo, $bugSummary) = $this->testreport->getBug4Report($tasks, $productIdList, $begin, $end, $builds);

        /* Get testtasks members. */
        $taskMembers = '';
        foreach($tasks as $testtask) $taskMembers .= ',' . $testtask->members;
        $taskMembers = explode(',', $taskMembers);

        $members     = $this->dao->select('DISTINCT lastRunner')->from(TABLE_TESTRUN)->where('task')->in(array_keys($tasks))->fetchPairs('lastRunner', 'lastRunner');
        $members     = array_merge($members, $taskMembers);

        $this->view->begin   = $begin;
        $this->view->end     = $end;
        $this->view->members = $members;
        $this->view->owner   = $owner;

        $this->view->stories       = $stories;
        $this->view->bugs          = $bugs;
        $this->view->execution     = $execution;
        $this->view->productIdList = join(',', array_keys($productIdList));
        $this->view->tasks         = join(',', array_keys($tasks));
        $this->view->storySummary  = $this->product->summary($stories);

        $this->view->builds  = $builds;
        $this->view->users   = $this->user->getPairs('noletter|noclosed|nodeleted');

        $this->view->cases       = $cases;
        $this->view->caseSummary = $this->testreport->getResultSummary($tasks, $cases, $begin, $end);

        $caseList = array();
        foreach($cases as $taskID => $casesList)
        {
            foreach($casesList as $caseID => $case) $caseList[$caseID] = $case;
        }
        $perCaseResult = $this->testreport->getPerCaseResult4Report($tasks, array_keys($caseList), $begin, $end);
        $perCaseRunner = $this->testreport->getPerCaseRunner4Report($tasks, array_keys($caseList), $begin, $end);
        $this->view->datas['testTaskPerRunResult'] = $this->loadModel('report')->computePercent($perCaseResult);
        $this->view->datas['testTaskPerRunner']    = $this->report->computePercent($perCaseRunner);

        $this->view->bugInfo    = $bugInfo;
        $this->view->legacyBugs = $bugSummary['legacyBugs'];
        $this->view->bugSummary = $bugSummary;

        $this->view->objectID   = $objectID;
        $this->view->objectType = $objectType;
        $this->view->extra      = $extra;
        $this->display();
    }
}
