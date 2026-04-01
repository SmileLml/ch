<?php
helper::importControl('project');
class myProject extends project
{
    /**
     * Create a project.
     *
     * @param  string $model
     * @param  int    $programID
     * @param  int    $copyProjectID
     * @param  string $extra
     * @access public
     * @return void
     */
    public function create($model = 'scrum', $programID = 0, $copyProjectID = 0, $extra = '')
    {
        $this->loadModel('execution');
        $this->loadModel('product');

        if($model == 'kanban') unset($this->lang->project->authList['reset']);

        if($_POST)
        {
            $projectID = $this->project->create();
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->loadModel('action')->create('project', $projectID, 'opened');

            /* Link the plan stories. */
            if(!empty($_POST['hasProduct']) && !empty($_POST['plans']))
            {
                $planIdList = array();
                foreach($_POST['plans'] as $plans)
                {
                    foreach($plans as $planID)
                    {
                        $planIdList[$planID] = $planID;
                    }
                }

                $planStoryGroup = $this->loadModel('story')->getStoriesByPlanIdList($planIdList);
                foreach($planIdList as $planID)
                {
                    $planStories = $planProducts = array();
                    $planStory   = isset($planStoryGroup[$planID]) ? $planStoryGroup[$planID] : array();
                    if(!empty($planStory))
                    {
                        foreach($planStory as $id => $story)
                        {
                            if($story->status != 'active')
                            {
                                unset($planStory[$id]);
                                continue;
                            }
                            $planProducts[$story->id] = $story->product;
                        }
                        $planStories = array_keys($planStory);
                        $this->execution->linkStory($projectID, $planStories, $planProducts);
                    }
                }
            }

            $message = $this->executeHooks($projectID);
            if($message) $this->lang->saveSuccess = $message;

            if($this->viewType == 'json') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'id' => $projectID));

            if($this->app->tab != 'project' and $this->session->createProjectLocate)
            {
                return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $this->session->createProjectLocate));
            }
            else
            {
                if($model == 'waterfall' or $model == 'waterfallplus')
                {
                    $productID = $this->product->getProductIDByProject($projectID, true);
                    $this->session->set('projectPlanList', $this->createLink('programplan', 'browse', "projectID=$projectID&productID=$productID&type=lists", '', '', $projectID), 'project');
                    return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $this->createLink('programplan', 'create', "projectID=$projectID", '', '', $projectID)));
                }

                $parent = isset($_POST['parent']) ? $_POST['parent'] : 0;
                $systemMode = $this->loadModel('setting')->getItem('owner=system&module=common&section=global&key=mode');
                if(!empty($systemMode) and $systemMode == 'light') $parent = 0;
                return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $this->createLink('project', 'browse', "programID=$parent&browseType=all", '', '', $projectID)));
            }
        }

        $extra = str_replace(array(',', ' '), array('&', ''), $extra);
        parse_str($extra, $output);

        if($this->app->tab == 'program' and $programID)                   $this->loadModel('program')->setMenu($programID);
        if($this->app->tab == 'product' and !empty($output['productID'])) $this->loadModel('product')->setMenu($output['productID']);
        if($this->app->tab == 'doc') unset($this->lang->doc->menu->project['subMenu']);
        $this->session->set('projectModel', $model);

        $name          = '';
        $code          = '';
        $team          = '';
        $whitelist     = '';
        $acl           = 'open';
        $auth          = 'extend';
        $multiple      = 1;
        $hasProduct    = 1;
        $shadow        = 0;
        $products      = array();
        $productPlans  = array();
        $parentProgram = $this->loadModel('program')->getByID($programID);

        if($copyProjectID)
        {
            $copyProject = $this->dao->select('*')->from(TABLE_PROJECT)->where('id')->eq($copyProjectID)->fetch();
            $name        = $copyProject->name;
            $code        = $copyProject->code;
            $team        = $copyProject->team;
            $whitelist   = $copyProject->whitelist;
            $acl         = $copyProject->acl;
            $auth        = $copyProject->auth;
            $multiple    = $copyProject->multiple;
            $hasProduct  = $copyProject->hasProduct;
            $programID   = $copyProject->parent;
            $products    = $this->product->getProducts($copyProjectID);

            if(!$copyProject->hasProduct) $shadow = 1;
            foreach($products as $product)
            {
                $branches = implode(',', $product->branches);
                $productPlans[$product->id] = $this->loadModel('productplan')->getPairs($product->id, $branches, 'noclosed', true);
            }
        }

        if($this->view->globalDisableProgram) $programID = $this->config->global->defaultProgram;
        $topProgramID = $this->program->getTopByID($programID);

        if($model == 'kanban')
        {
            $this->lang->project->aclList    = $this->lang->project->kanbanAclList;
            $this->lang->project->subAclList = $this->lang->project->kanbanSubAclList;
        }

        $sprintConcept = empty($this->config->custom->sprintConcept) ?
        $this->config->executionCommonList[$this->app->getClientLang()][0] :
        $this->config->executionCommonList[$this->app->getClientLang()][1];

        $withProgram = $this->config->systemMode == 'ALM' ? true : false;
        $allProducts = array('0' => '') + $this->program->getProductPairs($programID, 'all', 'noclosed', '', $shadow, $withProgram);

        $this->view->title               = $this->lang->project->create;
        $this->view->gobackLink          = (isset($output['from']) and $output['from'] == 'global') ? $this->createLink('project', 'browse') : '';
        $this->view->pmUsers             = $this->loadModel('user')->getPairs('noclosed|nodeleted|pmfirst');
        $this->view->users               = $this->user->getPairs('noclosed|nodeleted');
        $this->view->copyProjects        = $this->project->getPairsByModel($model);
        $this->view->products            = $products;
        $this->view->allProducts         = $allProducts;
        $this->view->productPlans        = array('0' => '') + $productPlans;
        $this->view->branchGroups        = $this->loadModel('branch')->getByProducts(array_keys($products), 'noclosed');
        $this->view->programID           = $programID;
        $this->view->productID           = isset($output['productID']) ? $output['productID'] : 0;
        $this->view->branchID            = isset($output['branchID']) ? $output['branchID'] : 0;
        $this->view->multiBranchProducts = $this->product->getMultiBranchPairs($topProgramID);
        $this->view->model               = $model;
        $this->view->name                = $name;
        $this->view->code                = $code;
        $this->view->team                = $team;
        $this->view->acl                 = $acl;
        $this->view->auth                = $auth;
        $this->view->whitelist           = $whitelist;
        $this->view->multiple            = $multiple;
        $this->view->hasProduct          = $hasProduct;
        $this->view->copyProjectID       = $copyProjectID;
        $this->view->programList         = $this->program->getParentPairs();
        $this->view->parentProgram       = $parentProgram;
        $this->view->URSRPairs           = $this->loadModel('custom')->getURSRPairs();
        $this->view->availableBudget     = $this->program->getBudgetLeft($parentProgram);
        $this->view->budgetUnitList      = $this->project->getBudgetUnitList();
        $this->view->programListSet      = $this->program->getParentPairs();

        $this->display('project', 'create');
    }
}
