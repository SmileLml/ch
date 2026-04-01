<?php
class mytesttask extends testtask
{
    /**
     * The report page.
     *
     * @param  int    $productID
     * @param  string $browseType
     * @param  int    $branchID
     * @param  int    $moduleID
     * @access public
     * @return void
     */
    public function report($productID, $taskID, $browseType, $branchID, $moduleID = 0, $chartType = 'pie', $chprojectID = 0)
    {
        $this->loadModel('report');
        $this->view->charts = array();

        $task = $this->testtask->getById($taskID);
        $this->checkAccess($task);

        if(!empty($_POST))
        {
            $this->app->loadLang('testcase');
            $bugInfo = $this->testtask->getBugInfo($taskID, $productID);
            foreach($this->post->charts as $chart)
            {
                $chartFunc   = 'getDataOf' . $chart;
                $chartData   = isset($bugInfo[$chart]) ? $bugInfo[$chart] : $this->testtask->$chartFunc($taskID);
                $chartOption = $this->testtask->mergeChartOption($chart);
                if(!empty($chartType)) $chartOption->type = $chartType;

                $this->view->charts[$chart] = $chartOption;
                $this->view->datas[$chart]  = $this->report->computePercent($chartData);
            }
        }

        if($this->app->tab == 'project')
        {
            $this->loadModel('project')->setMenu($task->project);
            $this->lang->modulePageNav = $this->testtask->select($productID, $taskID, 'project', $task->project);
        }
        elseif($this->app->tab == 'execution')
        {
            $this->loadModel('execution')->setMenu($task->execution);
            $this->lang->modulePageNav = $this->testtask->select($productID, $taskID, 'execution', $task->execution);
        }
        elseif($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($chprojectID);
            $this->view->chprojectID = $chprojectID;
        }
        else
        {
            $this->testtask->setMenu($this->products, $productID, $branchID, $taskID);
        }
        unset($this->lang->testtask->report->charts['bugStageGroups']);
        unset($this->lang->testtask->report->charts['bugHandleGroups']);

        if(!isset($this->products[$productID]))
        {
            $product = $this->product->getByID($productID);
            $this->products[$productID] = $product->name;
        }

        $this->view->title         = $this->products[$productID] . $this->lang->colon . $this->lang->testtask->common . $this->lang->colon . $this->lang->testtask->reportChart;
        $this->view->position[]    = html::a($this->createLink('testtask', 'cases', "taskID=$taskID"), $this->products[$productID]);
        $this->view->position[]    = $this->lang->testtask->reportChart;
        $this->view->productID     = $productID;
        $this->view->taskID        = $taskID;
        $this->view->browseType    = $browseType;
        $this->view->moduleID      = $moduleID;
        $this->view->branchID      = $branchID;
        $this->view->chartType     = $chartType;
        $this->view->checkedCharts = $this->post->charts ? join(',', $this->post->charts) : '';

        $this->display();
    }

    /**
     * Check access.
     *
     * @param  object $testtask
     * @access private
     * @return bool
     */
    private function checkAccess($testtask)
    {
        $canAccess = true;

        $view = $this->app->user->view;

        if(!$this->app->user->admin)
        {
            if($testtask->product   && strpos(",{$view->products},", ",$testtask->product,") === false)   $canAccess = false;
            if($testtask->project   && strpos(",{$view->projects},", ",$testtask->project,") === false)   $canAccess = false;
            if($testtask->execution && strpos(",{$view->sprints},",  ",$testtask->execution,") === false) $canAccess = false;
        }

        if($canAccess) return true;

        echo(js::alert($this->lang->testtask->accessDenied));
        echo js::locate(helper::createLink('testtask', 'browse'));

        return false;
    }
}
