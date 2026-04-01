<?php
helper::importControl('action');
class myAction extends action
{
    /**
     * Trash.
     *
     * @param  string $browseType
     * @param  string $type all|hidden
     * @param  bool   $byQuery
     * @param  int    $queryID
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function trash($browseType = 'all', $type = 'all', $byQuery = false, $queryID = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('backup');

        /* Save session. */
        $uri = $this->app->getURI(true);
        $this->session->set('productList',        $uri, 'product');
        $this->session->set('productPlanList',    $uri, 'product');
        $this->session->set('storyList',          $uri, 'product');
        $this->session->set('releaseList',        $uri, 'product');
        $this->session->set('programList',        $uri, 'program');
        $this->session->set('projectList',        $uri, 'project');
        $this->session->set('executionList',      $uri, 'execution');
        $this->session->set('taskList',           $uri, 'execution');
        $this->session->set('buildList',          $uri, 'execution');
        $this->session->set('bugList',            $uri, 'qa');
        $this->session->set('caseList',           $uri, 'qa');
        $this->session->set('testtaskList',       $uri, 'qa');
        $this->session->set('docList',            $uri, 'doc');
        $this->session->set('opportunityList',    $uri, 'project');
        $this->session->set('riskList',           $uri, 'project');
        $this->session->set('trainplanList',      $uri, 'project');
        $this->session->set('roomList',           $uri, 'admin');
        $this->session->set('researchplanList',   $uri, 'project');
        $this->session->set('researchreportList', $uri, 'project');
        $this->session->set('meetingList',        $uri, 'project');
        $this->session->set('designList',         $uri, 'project');
        $this->session->set('storyLibList',       $uri, 'assetlib');
        $this->session->set('issueLibList',       $uri, 'assetlib');
        $this->session->set('riskLibList',        $uri, 'assetlib');
        $this->session->set('opportunityLibList', $uri, 'assetlib');
        $this->session->set('practiceLibList',    $uri, 'assetlib');
        $this->session->set('componentLibList',   $uri, 'assetlib');

        /* Save the object name used to replace the search language item. */
        $this->session->set('objectName', zget($this->lang->action->objectTypes, $browseType, ''), 'admin');

        /* Build the search form. */
        $queryID   = (int)$queryID;
        $actionURL = $this->createLink('action', 'trash', "browseType=$browseType&type=$type&byQuery=true&queryID=myQueryID");
        $this->action->buildTrashSearchForm($queryID, $actionURL);

        /* Get deleted objects. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Append id for secend sort. */
        $sort           = common::appendOrder($orderBy);
        $trashes        = $byQuery ? $this->action->getTrashesBySearch($browseType, $type, $queryID, $sort, $pager) : $this->action->getTrashes($browseType, $type, $sort, $pager);
        $objectTypeList = $this->action->getTrashObjectTypes($type);
        $objectTypeList = array_keys($objectTypeList);

        $preferredType       = array();
        $moreType            = array();
        $preferredTypeConfig = $this->config->action->preferredType->ALM;
        if($this->config->systemMode == 'light') $preferredTypeConfig = $this->config->action->preferredType->light;
        foreach($objectTypeList as $objectType)
        {
            if(!isset($this->config->objectTables[$objectType])) continue;
            in_array($objectType, $preferredTypeConfig) ? $preferredType[$objectType] = $objectType : $moreType[$objectType] = $objectType;
        }
        if(count($preferredType) < $this->config->action->preferredTypeNum)
        {
            $toPreferredType = array_splice($moreType, 0, $this->config->action->preferredTypeNum - count($preferredType));
            $preferredType   = $preferredType + $toPreferredType;
        }

        /* Get the projects name of executions. */
        if($browseType == 'execution')
        {
            $this->loadModel('project');
            $projectIdList = array();
            foreach($trashes as $trash) $projectIdList[] = $trash->project;
            $this->view->projectList = $this->project->getByIdList($projectIdList, 'all');
        }

        /* Get the products name of story. */
        if(strpos(',story,requirement,', ",$browseType,") !== false)
        {
            $this->loadModel('story');
            $storyIdList = array();
            foreach($trashes as $trash) $storyIdList[] = $trash->objectID;
            $this->view->productList = $this->story->getByList($storyIdList, 'story', 'all');
        }

        /* Get the executions name of task. */
        if($browseType == 'task')
        {
            $this->app->loadLang('task');
            $this->loadModel('execution');
            $executionIdList = array();
            foreach($trashes as $trash) $executionIdList[] = $trash->execution;
            $this->view->executionList = $this->execution->getByIdList($executionIdList, 'all');
        }

        /* Process pivot name. */
        foreach($trashes as $trash)
        {
            if($trash->objectType == 'pivot')
            {
                $pivotNames = json_decode($trash->objectName, true);
                $trash->objectName = zget($pivotNames, $this->app->getClientLang(), '');
                if(empty($trash->objectName))
                {
                    $pivotNames = array_filter($pivotNames);
                    $trash->objectName = reset($pivotNames);
                }
            }
        }

        /* Title and position. */
        $this->view->title      = $this->lang->action->trash;
        $this->view->position[] = $this->lang->action->trash;

        $this->view->trashes             = $trashes;
        $this->view->type                = $type;
        $this->view->currentObjectType   = $browseType;
        $this->view->orderBy             = $orderBy;
        $this->view->pager               = $pager;
        $this->view->users               = $this->loadModel('user')->getPairs('noletter');
        $this->view->preferredType       = $preferredType;
        $this->view->moreType            = $moreType;
        $this->view->preferredTypeConfig = $preferredTypeConfig;
        $this->view->byQuery             = $byQuery;
        $this->view->queryID             = $queryID;

        $this->display();
    }
}
