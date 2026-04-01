<?php
helper::importControl('product');
class myproduct extends product
{
    /**
     * Browse a product.
     *
     * @param  int         $productID
     * @param  int|stirng  $branch
     * @param  string      $browseType
     * @param  int         $param
     * @param  string      $storyType requirement|story
     * @param  string      $orderBy
     * @param  int         $recTotal
     * @param  int         $recPerPage
     * @param  int         $pageID
     * @param  int         $projectID
     * @access public
     * @return void
     */
    public function browse($productID = 0, $branch = '', $browseType = '', $param = 0, $storyType = 'story', $orderBy = '', $recTotal = 0, $recPerPage = 20, $pageID = 1, $projectID = 0)
    {
        if($storyType == 'requirement')
        {
            unset($this->config->product->search['fields']['relatedRequirement']);
            $this->session->set('businessViewBackUrl', $this->app->getURI());
            $this->config->story->datatable->defaultField = array('id', 'title', 'pri', 'residueEstimate', 'plan', 'roadmap', 'status', 'estimate', 'reviewedBy', 'stage', 'assignedTo', 'openedBy', 'openedDate', 'actions');

            if($this->app->tab == 'project' or $this->app->tab == 'product')
            {
                $this->config->story->datatable->defaultField = array('id', 'title', 'pri', 'business', 'residueEstimate', 'plan', 'roadmap', 'status', 'estimate', 'reviewedBy', 'stage', 'assignedTo', 'openedBy', 'openedDate', 'actions');

                $this->config->story->datatable->fieldList['business']['title']    = 'business';
                $this->config->story->datatable->fieldList['business']['fixed']    = 'left';
                $this->config->story->datatable->fieldList['business']['width']    = '150';
                $this->config->story->datatable->fieldList['business']['required'] = 'yes';
                $this->config->story->datatable->fieldList['business']['type']     = 'html';
                $this->config->story->datatable->fieldList['business']['name']     = $this->lang->story->business;
            }

            $this->config->story->datatable->fieldList['residueEstimate']['title']    = 'residueEstimate';
            $this->config->story->datatable->fieldList['residueEstimate']['fixed']    = 'left';
            $this->config->story->datatable->fieldList['residueEstimate']['width']    = '100';
            $this->config->story->datatable->fieldList['residueEstimate']['required'] = 'yes';
            $this->config->story->datatable->fieldList['residueEstimate']['type']     = 'html';
            $this->config->story->datatable->fieldList['residueEstimate']['name']     = $this->lang->story->residueEstimate;

            $this->config->product->search['fields']['business'] = $this->lang->story->business;

            $this->config->product->search['params']['business'] = array('operator' => '=', 'control' => 'select', 'values' => '');
        }

        $productID = $this->app->tab != 'project' ? $this->product->saveState($productID, $this->products) : $productID;
        $product   = $this->product->getById($productID);

        if($product && !isset($this->products[$product->id])) $this->products[$product->id] = $product->name;

        if(!is_string($this->cookie->preBranch) and !is_int($this->cookie->preBranch)) $this->cookie->preBranch = (int)$this->cookie->preBranch;
        if($product and $product->type != 'normal')
        {
            $branchPairs = $this->loadModel('branch')->getPairs($productID, 'all');
            $branch      = ($this->cookie->preBranch !== '' and $branch === '' and isset($branchPairs[$this->cookie->preBranch])) ? $this->cookie->preBranch : $branch;
            $branchID    = $branch;
        }
        else
        {
            $branchID = $branch = 'all';
        }

        /* Set menu. */
        if($this->app->tab == 'project')
        {
            $this->session->set('storyList', $this->app->getURI(true), 'project');
            $this->loadModel('project')->setMenu($projectID);
        }
        else
        {
            $this->session->set('storyList',   $this->app->getURI(true), 'product');
            $this->session->set('productList', $this->app->getURI(true), 'product');

            $this->product->setMenu($productID, $branch, 0, '', "storyType=$storyType");
        }

        /* Lower browse type. */
        $browseType = strtolower($browseType);

        /* Load datatable, execution and projectstory. */
        $this->loadModel('datatable');
        $this->loadModel('execution');
        $this->app->loadLang('projectstory');

        /* Set product, module and query. */
        setcookie('preProductID', $productID, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, true);
        setcookie('preBranch', $branch, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, true);

        if($this->cookie->preProductID != $productID or $this->cookie->preBranch != $branch or $browseType == 'bybranch')
        {
            $_COOKIE['storyModule'] = 0;
            setcookie('storyModule', 0, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);
        }

        if($browseType == 'bymodule' or $browseType == '')
        {
            setcookie('storyModule', (int)$param, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);
            if($this->app->tab == 'project') setcookie('storyModuleParam', (int)$param, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);
            $_COOKIE['storyBranch'] = 'all';
            setcookie('storyBranch', 'all', 0, $this->config->webRoot, '', $this->config->cookieSecure, false);
            if($browseType == '') setcookie('treeBranch', $branch, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);
        }
        if($browseType == 'bybranch') setcookie('storyBranch', $branch, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);

        $cookieModule = $this->app->tab == 'project' ? $this->cookie->storyModuleParam : $this->cookie->storyModule;
        $moduleID = ($browseType == 'bymodule') ? (int)$param : (($browseType == 'bysearch' or $browseType == 'bybranch') ? 0 : ($cookieModule ? $cookieModule : 0));
        $queryID  = ($browseType == 'bysearch') ? (int)$param : 0;

        /* Set moduleTree. */
        $createModuleLink = $storyType == 'story' ? 'createStoryLink' : 'createRequirementLink';
        if($browseType == '')
        {
            setcookie('treeBranch', $branch, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);
            $browseType = 'unclosed';
            if($this->config->vision == 'or') $browseType = 'assignedtome';
        }
        else
        {
            $branch = $this->cookie->treeBranch;
        }

        $isProjectStory = $this->app->rawModule == 'projectstory';

        /* If in project story and not chose product, get project story mdoules. */
        if($isProjectStory and empty($productID))
        {
            $moduleTree = $this->tree->getProjectStoryTreeMenu($projectID, 0, array('treeModel', $createModuleLink), $storyType);
        }
        else
        {
            $moduleTree = $this->tree->getTreeMenu($productID, 'story', $startModuleID = 0, array('treeModel', $createModuleLink), array('projectID' => $projectID, 'productID' => $productID), $branch, "&param=$param&storyType=$storyType");
        }

        if($browseType != 'bymodule' and $browseType != 'bybranch') $this->session->set('storyBrowseType', $browseType);
        if(($browseType == 'bymodule' or $browseType == 'bybranch') and $this->session->storyBrowseType == 'bysearch') $this->session->set('storyBrowseType', 'unclosed');

        /* Process the order by field. */
        if(!$orderBy) $orderBy = $this->cookie->productStoryOrder ? $this->cookie->productStoryOrder : 'id_desc';
        setcookie('productStoryOrder', $orderBy, 0, $this->config->webRoot, '', $this->config->cookieSecure, true);

        /* Append id for secend sort. */
        $sort = common::appendOrder($orderBy);
        if(strpos($sort, 'pri_') !== false) $sort = str_replace('pri_', 'priOrder_', $sort);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'xhtml') $recPerPage = 10;
        $pager = new pager($recTotal, $recPerPage, $pageID);

        /* Display of branch label. */
        $showBranch = $this->loadModel('branch')->showBranch($productID);

        /* Get stories. */
        $projectProducts = array();
        if($isProjectStory)
        {
            $showBranch = $this->loadModel('branch')->showBranch($productID, 0, $projectID);

            if(!empty($product)) $this->session->set('currentProductType', $product->type);

            $this->products  = $this->product->getProducts($projectID, 'all', '', false);
            $projectProducts = $this->product->getProducts($projectID);
            $productPlans    = $this->execution->getPlans($projectProducts, 'skipParent,unexpired,noclosed', $projectID);

            if($browseType == 'bybranch') $param = $branchID;
            $stories = $this->story->getExecutionStories($projectID, $productID, $branchID, $sort, $browseType, $param, $storyType, '', '', $pager);
        }
        else
        {
            $stories = $this->product->getStories($productID, $branchID, $browseType, $queryID, $moduleID, $storyType, $sort, $pager);
        }
        $queryCondition = $this->story->dao->get();

        /* Display status of branch. */
        $branchOption    = array();
        $branchTagOption = array();
        if(!$product and $isProjectStory)
        {
            /* Get branch display under multiple products. */
            $branchOptions = array();
            foreach($projectProducts as $projectProduct)
            {
                if($projectProduct and $projectProduct->type != 'normal')
                {
                    $branches = $this->loadModel('branch')->getList($projectProduct->id, $projectID, 'all');
                    foreach($branches as $branchInfo) $branchOptions[$projectProduct->id][$branchInfo->id] = $branchInfo->name;
                }
            }

            $this->view->branchOptions = $branchOptions;
        }
        else
        {
            if($product and $product->type != 'normal')
            {
                $branches = $this->loadModel('branch')->getList($productID, $projectID, 'all');
                foreach($branches as $branchInfo)
                {
                    $branchOption[$branchInfo->id]    = $branchInfo->name;
                    $branchTagOption[$branchInfo->id] = $branchInfo->name . ($branchInfo->status == 'closed' ? ' (' . $this->lang->branch->statusList['closed'] . ')' : '');
                }
            }
        }

        /* Process the sql, get the conditon partion, save it to session. */
        $this->loadModel('common')->saveQueryCondition($queryCondition, 'story', (strpos('bysearch,reviewbyme,bymodule', $browseType) === false and !$isProjectStory));

        if(!empty($stories)) $stories = $this->story->mergeReviewer($stories);

        $relations = array();
        if($storyType == 'story')
        {
            $relations = $this->dao->select('BID,AID')->from(TABLE_RELATION)->alias('t1')
                ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.AID=t2.id')
                ->where('t1.AType')->eq('requirement')
                ->andWhere('t1.BType')->eq('story')
                ->andWhere('t1.relation')->eq('subdivideinto')
                ->andWhere('t1.BID')->in(array_keys($stories))
                ->andWhere('t2.deleted')->eq(0)
                ->fetchPairs();
        }

        /* Get related tasks, bugs, cases count of each story. */
        $storyIdList = array();
        foreach($stories as $story)
        {
            $storyIdList[$story->id] = $story->id;
            if(!empty($story->children))
            {
                if($this->config->edition == 'ipd' and $this->config->vision != 'or') $this->story->getAffectObject($story->children, 'story');
                foreach($story->children as $child) $storyIdList[$child->id] = $child->id;
            }

            if($storyType == 'story') $story->relatedRequirement = isset($relations[$story->id]) ? $relations[$story->id] : '';
        }

        $storyTasks = $this->loadModel('task')->getStoryTaskCounts($storyIdList);
        $storyBugs  = $this->loadModel('bug')->getStoryBugCounts($storyIdList);
        $storyCases = $this->loadModel('testcase')->getStoryCaseCounts($storyIdList);

        /* Change for requirement story title. */
        if($storyType == 'requirement')
        {
            $this->lang->story->title  = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->title);
            $this->lang->story->create = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->create);
            $this->config->product->search['fields']['title'] = $this->lang->story->title;
            unset($this->config->product->search['fields']['plan']);
            unset($this->config->product->search['fields']['stage']);
        }

        if($this->config->edition != 'ipd' || ($this->config->edition == 'ipd' && $storyType == 'story')) unset($this->config->product->search['fields']['roadmap']);

        $project = $this->loadModel('project')->getByID($projectID);
        if(isset($project->hasProduct) && empty($project->hasProduct))
        {
            if($isProjectStory && !$productID && !empty($this->products)) $productID = key($this->products);    // If toggle a project by the #swapper component on the story page of the projectstory module, the $productID may be empty. Make sure it has value.
            unset($this->config->product->search['fields']['product']);                                         // The none-product project don't need display the product in the search form.
            if($project->model != 'scrum') unset($this->config->product->search['fields']['plan']);             // The none-product and none-scrum project don't need display the plan in the search form.
        }

        $linkedRoadmaps = array();
        if(!empty($project->charter)) $linkedRoadmaps = $this->loadModel('roadmap')->getPairsByProjectID($projectID);

        /* Build search form. */
        $params    = $isProjectStory ? "projectID=$projectID&" : '';
        $actionURL = $this->createLink($this->app->rawModule, $this->app->rawMethod, $params . "productID=$productID&branch=$branch&browseType=bySearch&queryID=myQueryID&storyType=$storyType");

        $this->config->product->search['onMenuBar'] = 'yes';
        if($this->app->rawModule != 'product') $this->config->product->search['module'] = $this->app->rawModule;
        $this->product->buildSearchForm($productID, $this->products, $queryID, $actionURL, $branch, $projectID);

        $showModule = !empty($this->config->datatable->productBrowse->showModule) ? $this->config->datatable->productBrowse->showModule : '';

        $productName = ($isProjectStory and empty($productID)) ? $this->lang->product->all : $this->products[$productID];

        if($this->config->edition == 'ipd' and $this->config->vision != 'or') $this->story->getAffectObject($stories, 'story');

        /* Assign. */
        $this->view->title           = $productName . $this->lang->colon . ($storyType === 'story' ? $this->lang->product->browse : $this->lang->product->requirement);
        $this->view->position[]      = $productName;
        $this->view->position[]      = $this->lang->product->browse;
        $this->view->productID       = $productID;
        $this->view->product         = $product;
        $this->view->productName     = $productName;
        $this->view->moduleID        = $moduleID;
        $this->view->stories         = $stories;
        $this->view->plans           = $this->loadModel('productplan')->getPairs($productID, ($branch === 'all' or empty($branch)) ? '' : $branch, 'unexpired,noclosed', true);
        $this->view->productPlans    = isset($productPlans) ? array(0 => '') + $productPlans : array();
        $this->view->linkedRoadmaps  = $linkedRoadmaps;
        $this->view->summary         = $this->product->summary($stories, $storyType);
        $this->view->moduleTree      = $moduleTree;
        $this->view->parentModules   = $this->tree->getParents($moduleID);
        $this->view->pager           = $pager;
        $this->view->users           = $this->user->getPairs('noletter|pofirst|nodeleted');
        $this->view->orderBy         = $orderBy;
        $this->view->browseType      = $browseType;
        $this->view->modules         = $this->tree->getOptionMenu($productID, 'story', 0, $branchID);
        $this->view->moduleID        = $moduleID;
        $this->view->moduleName      = ($moduleID and $moduleID !== 'all') ? $this->tree->getById($moduleID)->name : $this->lang->tree->all;
        $this->view->branch          = $branch;
        $this->view->branchID        = $branchID;
        $this->view->branchOption    = $branchOption;
        $this->view->branchTagOption = $branchTagOption;
        $this->view->showBranch      = $showBranch;
        $this->view->storyStages     = $this->product->batchGetStoryStage($stories);
        $this->view->setModule       = true;
        $this->view->storyTasks      = $storyTasks;
        $this->view->storyBugs       = $storyBugs;
        $this->view->storyCases      = $storyCases;
        $this->view->param           = $param;
        $this->view->projectID       = $projectID;
        $this->view->products        = $this->products;
        $this->view->projectProducts = isset($projectProducts) ? $projectProducts : array();
        $this->view->storyType       = $storyType;
        $this->view->from            = $this->app->tab;
        $this->view->isProjectStory  = $isProjectStory;
        $this->view->modulePairs     = $showModule ? $this->tree->getModulePairs($productID, 'story', $showModule) : array();
        $this->view->project         = $project;
        $this->display();
    }
}
