<?php
helper::importControl('project');
class myProject extends project
{
    /**
     * View a project.
     *
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function view($projectID = 0)
    {
        if(!defined('RUN_MODE') || RUN_MODE != 'api') $projectID = $this->project->saveState((int)$projectID, $this->project->getPairsByProgram());

        $this->session->set('teamList', $this->app->getURI(true), 'project');

        $projectID = $this->project->setMenu($projectID);
        $project   = $this->project->getById($projectID);

        if(in_array($this->config->systemMode, array('ALM', 'PLM')))
        {
            $programList = array_filter(explode(',', $project->path));
            array_pop($programList);
            $this->view->programList = $this->loadModel('program')->getPairsByList($programList);
        }

        if(empty($project) || strpos('scrum,waterfall,kanban,agileplus,waterfallplus,ipd', $project->model) === false)
        {
            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'fail', 'code' => 404, 'message' => '404 Not found'));
            return print(js::error($this->lang->notFound) . js::locate($this->createLink('project', 'browse')));
        }

        $products = $this->loadModel('product')->getProducts($projectID);
        $linkedBranches = array();
        foreach($products as $product)
        {
            if(isset($product->branches))
            {
                foreach($product->branches as $branchID) $linkedBranches[$branchID] = $branchID;
            }
        }

        /* Check exist extend fields. */
        $isExtended = false;
        if($this->config->edition != 'open')
        {
            $extend = $this->loadModel('workflowaction')->getByModuleAndAction('project', 'view');
            if(!empty($extend) and $extend->extensionType == 'extend') $isExtended = true;
        }

        $this->executeHooks($projectID);
        $this->setFlowChild($projectID);

        $project->files = $this->loadModel('file')->getByObject('project', $projectID);

        $this->view->title        = $this->lang->project->view;
        $this->view->position     = $this->lang->project->view;
        $this->view->projectID    = $projectID;
        $this->view->project      = $project;
        $this->view->products     = $products;
        $this->view->actions      = $this->loadModel('action')->getList('project', $projectID);
        $this->view->users        = $this->loadModel('user')->getPairs('noletter');
        $this->view->teamMembers  = $this->project->getTeamMembers($projectID);
        $this->view->statData     = $this->project->getStatData($projectID);
        $this->view->workhour     = $this->project->getWorkhour($projectID);
        $this->view->planGroup    = $this->loadModel('execution')->getPlans($products);
        $this->view->branchGroups = $this->loadModel('branch')->getByProducts(array_keys($products), '', $linkedBranches);
        $this->view->dynamics     = $this->loadModel('action')->getDynamic('all', 'all', 'date_desc', 30, 'all', $projectID);
        $this->view->isExtended   = $isExtended;

        $this->display();
    }

    /**
     * Set flow child.
     *
     * @param  int    $projectID
     * @access public
     * @return mixed
     */
    public function setFlowChild($projectID)
    {
        $moduleName = 'projectapproval';
        $actionName = 'view';

        $flow   = $this->loadModel('workflow', 'flow')->getByModule($moduleName);
        $action = $this->loadModel('workflowaction', 'flow')->getByModuleAndAction($flow->module, $actionName);
        $fields = $this->loadModel('workflowaction', 'flow')->getFields($flow->module, $action->action, true, array());

        $childFields  = array();
        $childDatas   = array();
        $childModules = $this->loadModel('workflow', 'flow')->getList('browse', 'table', '', $flow->module);

        foreach($childModules as $childModule)
        {
            if($childModule->module == 'projectreviewdetails') continue;

            $key = 'sub_' . $childModule->module;

            if(isset($fields[$key]) && $fields[$key]->show)
            {
                $childData = $this->project->getDataList($childModule, '', 0, '', $projectID, 'id_asc');

                $childFields[$key] = $this->workflowaction->getFields($childModule->module, $action->action, true, $childData);
                $childDatas[$key]  = $childData;
            }
        }

        $oldBusinessIds    = array_column($childDatas['sub_projectbusiness'], 'business');
        $oldBusinessList   = $this->dao->select('id,name')->from('zt_flow_business')->where('id')->in($oldBusinessIds)->fetchPairs();

        $childFields['sub_projectbusiness']['business']->options += $oldBusinessList;

        $this->view->childFields = $childFields;
        $this->view->childDatas  = $childDatas;
        $this->view->fields      = $fields;
    }
}
