<?php
class mytesttask extends testtask
{
    /**
     * Group case.
     *
     * @param  int    $taskID
     * @param  string $groupBy
     * @access public
     * @return void
     */
    public function groupCase($taskID, $groupBy = 'story', $chprojectID = 0)
    {
        /* Save the session. */
        $this->loadModel('testcase');
        $this->app->loadLang('execution');
        $this->app->loadLang('task');
        $this->session->set('caseList', $this->app->getURI(true), 'qa');
        setcookie('taskCaseModule', 0, 0, $this->config->webRoot, '', $this->config->cookieSecure, true);

        /* Get task and product info, set menu. */
        $groupBy = empty($groupBy) ? 'story' : $groupBy;
        $task    = $this->testtask->getById($taskID);
        if(!$task) return print(js::error($this->lang->notFound) . js::locate('back'));

        $productID = $task->product;
        if(!isset($this->products[$productID]))
        {
            $product = $this->product->getByID($productID);
            $this->products[$productID] = $product->name;
        }

        if($this->app->tab == 'project')
        {
            $this->loadModel('project')->setMenu($this->session->project);
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
            $this->testtask->setMenu($this->products, $productID, $task->branch, $taskID);
        }

        /* Determines whether an object is editable. */
        $canBeChanged = common::canBeChanged('testtask', $task);

        $runs = $this->testtask->getRuns($taskID, 0, $groupBy);
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', false);
        $runs = $this->testcase->appendData($runs, 'run');
        $groupCases  = array();
        $groupByList = array();
        foreach($runs as $run)
        {
            if($groupBy == 'story')
            {
                $groupCases[$run->story][] = $run;
                $groupByList[$run->story]  = $run->storyTitle;
            }
            elseif($groupBy == 'assignedTo')
            {
                $groupCases[$run->assignedTo][] = $run;
            }
        }

        if($groupBy == 'story' && $task->build)
        {
            $buildStoryIdList = $this->dao->select('stories')->from(TABLE_BUILD)->where('id')->eq($task->build)->fetch('stories');
            $buildStories     = $this->dao->select('id,title')->from(TABLE_STORY)->where('id')->in($buildStoryIdList)->andWhere('deleted')->eq(0)->andWhere('id')->notin(array_keys($groupCases))->fetchAll('id');
            foreach($buildStories as $buildStory)
            {
                $groupCases[$buildStory->id][] = $buildStory;
                $groupByList[$buildStory->id]  = $buildStory->title;
            }
        }

        $this->view->title      = $this->products[$productID] . $this->lang->colon . $this->lang->testtask->cases;
        $this->view->position[] = html::a($this->createLink('testtask', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->testtask->common;
        $this->view->position[] = $this->lang->testtask->cases;

        $this->view->users        = $this->loadModel('user')->getPairs('noletter');
        $this->view->productID    = $productID;
        $this->view->task         = $task;
        $this->view->taskID       = $taskID;
        $this->view->browseType   = 'group';
        $this->view->groupBy      = $groupBy;
        $this->view->groupByList  = $groupByList;
        $this->view->cases        = $groupCases;
        $this->view->account      = 'all';
        $this->view->canBeChanged = $canBeChanged;
        $this->display();
    }
}
