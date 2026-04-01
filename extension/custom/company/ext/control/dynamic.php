<?php
class company extends control
{
    /**
     * Company dynamic.
     *
     * @param  string $browseType
     * @param  string $param
     * @param  int    $recTotal
     * @param  string $date
     * @param  string $direction    next|pre
     * @param  int    $userID
     * @param  int    $productID
     * @param  int    $projectID
     * @param  int    $executionID
     * @param  string $orderBy     date_deac|date_asc
     * @access public
     * @return void
     */
    public function dynamic($browseType = 'today', $param = '', $recTotal = 0, $date = '', $direction = 'next', $userID = '', $productID = 0, $projectID = 0, $executionID = 0, $orderBy = 'date_desc')
    {
        $this->company->setMenu();
        $this->app->loadLang('user');
        $this->app->loadLang('execution');
        $this->loadModel('action');

        /* Save session. */
        $uri = $this->app->getURI(true);
        $this->session->set('productList',     $uri, 'product');
        $this->session->set('productPlanList', $uri, 'product');
        $this->session->set('releaseList',     $uri, 'product');
        $this->session->set('storyList',       $uri, 'product');
        $this->session->set('projectList',     $uri, 'project');
        $this->session->set('riskList',        $uri, 'project');
        $this->session->set('opportunityList', $uri, 'project');
        $this->session->set('trainplanList',   $uri, 'project');
        $this->session->set('taskList',        $uri, 'execution');
        $this->session->set('buildList',       $uri, 'execution');
        $this->session->set('bugList',         $uri, 'qa');
        $this->session->set('caseList',        $uri, 'qa');
        $this->session->set('testtaskList',    $uri, 'qa');
        $this->session->set('effortList',      $uri, 'my');
        $this->session->set('meetingList',     $uri, 'my');
        $this->session->set('meetingList',     $uri, 'project');
        $this->session->set('meetingroomList', $uri, 'admin');

        /* Append id for secend sort. */
        if($direction == 'next') $orderBy = 'date_desc';
        if($direction == 'pre')  $orderBy = 'date_asc';

        $queryID = ($browseType == 'bysearch') ? (int)$param : 0;
        $date    = empty($date) ? '' : date('Y-m-d', $date);

        /* Get products' list.*/
        $products = $this->loadModel('product')->getPairs('nocode');
        $products = array($this->lang->company->product) + $products;
        $this->view->products = $products;

        /* Get projects' list.*/
        $projects = $this->loadModel('project')->getPairsByProgram();
        $this->view->projects = array($this->lang->company->project) + $projects;;

        /* Get executions' list.*/
        $executions    = $this->loadModel('execution')->getPairs(0, 'all', 'nocode|multiple');
        $executionList = $this->execution->getByIdList(array_keys($executions));
        foreach($executionList as $id => $execution)
        {
            if(isset($projects[$execution->project])) $executions[$execution->id] = $projects[$execution->project] . $executions[$execution->id];
        }

        $executions = array($this->lang->execution->common) + $executions;
        $this->view->executions = $executions;

        /* Set account and get users.*/
        $user    = $userID ? $this->loadModel('user')->getById($userID, 'id') : '';
        $account = $this->app->user->admin ? 'all' : $this->app->user->account;

        $this->loadModel('user');

        $userIdPairs = $this->app->user->admin ? $this->user->getPairs('noclosed|nodeleted|noletter|useid') : [$this->app->user->account => $this->app->user->realname];
        $userIdPairs[''] = $this->lang->company->user;
        $this->view->userIdPairs = $userIdPairs;

        $accountPairs = $this->user->getPairs('nodeleted|noletter|all');
        $accountPairs[''] = '';

        /* The header and position. */
        $this->view->title      = $this->lang->company->common . $this->lang->colon . $this->lang->company->dynamic;
        $this->view->position[] = $this->lang->company->dynamic;

        /* Get actions. */
        if($browseType != 'bysearch')
        {
            if(!$productID)   $productID   = 'all';
            if(!$projectID)   $projectID   = 'all';
            if(!$executionID) $executionID = 'all';
            $actions = $this->action->getDynamic($account, $browseType, $orderBy, 50, $productID, $projectID, $executionID, $date, $direction);
        }
        else
        {
            $actions = $this->action->getDynamicBySearch($products, $projects, $executions, $queryID, $orderBy, 50, $date, $direction);
        }

        /* Build search form. */
        $executions[0] = '';
        $products[0]   = '';
        $projects[0]   = '';
        ksort($executions);
        ksort($products);
        ksort($projects);
        $executions['all'] = $this->lang->execution->allExecutions;
        $products['all']   = $this->lang->product->allProduct;
        $projects['all']   = $this->lang->project->all;

        foreach($this->lang->action->search->label as $action => $name)
        {
            if($action) $this->lang->action->search->label[$action] .= " [ $action ]";
        }

        $this->config->company->dynamic->search['actionURL'] = $this->createLink('company', 'dynamic', "browseType=bysearch&param=myQueryID");
        $this->config->company->dynamic->search['queryID'] = $queryID;
        $this->config->company->dynamic->search['params']['action']['values']    = $this->lang->action->search->label;
        if($this->config->vision == 'rnd') $this->config->company->dynamic->search['params']['product']['values']   = $products;
        $this->config->company->dynamic->search['params']['project']['values']   = $projects;
        $this->config->company->dynamic->search['params']['execution']['values'] = $executions;
        $this->config->company->dynamic->search['params']['actor']['values']     = $accountPairs;
        $this->loadModel('search')->setSearchParams($this->config->company->dynamic->search);

        $dateGroups = $this->action->buildDateGroup($actions, $direction, $browseType, $orderBy);

        if(empty($recTotal)) $recTotal = count($dateGroups) < 2 ? count($actions) : $this->action->getDynamicCount();

        /* Assign. */
        $this->view->recTotal     = $recTotal;
        $this->view->browseType   = $browseType;
        $this->view->account      = $account;
        $this->view->accountPairs = $accountPairs;
        $this->view->productID    = $productID;
        $this->view->projectID    = $projectID;
        $this->view->executionID  = $executionID;
        $this->view->queryID      = $queryID;
        $this->view->orderBy      = $orderBy;
        $this->view->userID       = $userID;
        $this->view->param        = $param;
        $this->view->dateGroups   = $dateGroups;
        $this->view->direction    = $direction;
        $this->display();
    }
}
