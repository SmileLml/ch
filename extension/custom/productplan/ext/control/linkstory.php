<?php
helper::importControl('productplan');
class myProductplan extends productplan
{
    /**
     * Link stories.
     *
     * @param int    $planID
     * @param string $browseType
     * @param int    $param
     * @param string $orderBy
     * @param int    $recTotal
     * @param int    $recPerPage
     * @param int    $pageID
     *
     * @access public
     * @return void
     */
    public function linkStory($planID = 0, $browseType = '', $param = 0, $orderBy = 'order_desc', $recTotal = 0, $recPerPage = 100, $pageID = 1)
    {
        if(!empty($_POST['stories']))
        {
            $this->productplan->linkStory($planID);
            if($this->viewType == 'json') return $this->send(array('result' => 'success'));
            return print(js::locate(inlink('view', "planID=$planID&type=story&orderBy=$orderBy"), 'parent'));
        }

        $this->session->set('storyList', inlink('view', "planID=$planID&type=story&orderBy=$orderBy&link=true&param=" . helper::safe64Encode("&browseType=$browseType&queryID=$param")), 'product');

        $this->loadModel('story');
        $this->loadModel('tree');
        $plan = $this->productplan->getByID($planID);
        $this->commonAction($plan->product, $plan->branch);
        $products = $this->product->getProductPairsByProject($this->session->project);

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        /* Build search form. */
        $queryID = ($browseType == 'bySearch') ? (int)$param : 0;
        unset($this->config->product->search['fields']['product']);
        $this->config->product->search['actionURL'] = $this->createLink('productplan', 'view', "planID=$planID&type=story&orderBy=$orderBy&link=true&param=" . helper::safe64Encode('&browseType=bySearch&queryID=myQueryID'));
        $this->config->product->search['queryID']   = $queryID;
        $this->config->product->search['style']     = 'simple';
        $this->config->product->search['params']['product']['values'] = $products + array('all' => $this->lang->product->allProductsOfProject);
        $this->config->product->search['params']['plan']['values'] = $this->productplan->getPairsForStory($plan->product, $plan->branch, 'skipParent|withMainPlan');
        $this->config->product->search['params']['module']['values'] = $this->loadModel('tree')->getOptionMenu($plan->product, 'story', 0, 'all');
        $storyStatusList = $this->lang->story->statusList;
        unset($storyStatusList['closed']);
        $this->config->product->search['params']['status'] = array('operator' => '=', 'control' => 'select', 'values' => $storyStatusList);
        if($this->session->currentProductType == 'normal')
        {
            unset($this->config->product->search['fields']['branch']);
            unset($this->config->product->search['params']['branch']);
        }
        else
        {
            $this->config->product->search['fields']['branch'] = $this->lang->product->branch;

            $branchPairs = $this->dao->select('id, name')->from(TABLE_BRANCH)->where('id')->in($plan->branch)->fetchPairs();
            $branches   = array('' => '', BRANCH_MAIN => $this->lang->branch->main) + $branchPairs;
            $this->config->product->search['params']['branch']['values'] = $branches;
        }
        $this->loadModel('search')->setSearchParams($this->config->product->search);

        $planStories = $this->story->getPlanStories($planID);

        if($browseType == 'bySearch')
        {
            $allStories = $this->story->getBySearch($plan->product, "0,{$plan->branch}", $queryID, 'id', '', 'story', array_keys($planStories), '', $pager);
        }
        else
        {
            $allStories = $this->story->getProductStories($this->view->product->id, $plan->branch ? "0,{$plan->branch}" : 0, '0', 'draft,reviewing,active,changing', 'story', 'id_desc', $hasParent = false, array_keys($planStories), $pager);
        }

        $modules    = $this->loadModel('tree')->getOptionMenu($plan->product, 'story', 0, 'all');
        $projects   = $this->loadModel('project')->getStoryIdAndName();
        $projectMap = [];
        foreach ($projects as $projectItem)
        {
            $projectMap[$projectItem->story] = $projectItem->projectNames;
        }

        foreach($allStories as $story)
        {
            if(!isset($modules[$story->module])) $modules += $this->tree->getModulesName($story->module);
            foreach ($allStories as $story)
            {
                if (isset($projectMap[$story->id]))
                {
                    $story->projectName = $projectMap[$story->id];
                }
                else
                {
                    $story->projectName = '';
                }
            }
        }

        $this->view->allStories  = $allStories;
        $this->view->planStories = $planStories;
        $this->view->products    = $products;
        $this->view->plan        = $plan;
        $this->view->plans       = $this->dao->select('id, end')->from(TABLE_PRODUCTPLAN)->fetchPairs();
        $this->view->users       = $this->loadModel('user')->getPairs('noletter');
        $this->view->browseType  = $browseType;
        $this->view->modules     = $modules;
        $this->view->param       = $param;
        $this->view->orderBy     = $orderBy;
        $this->view->pager       = $pager;
        $this->display();
    }
}
