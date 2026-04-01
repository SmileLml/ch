<?php
helper::importControl('projectrelease');
class myprojectrelease extends projectrelease
{
    /**
     * Link stories
     *
     * @param  int    $releaseID
     * @param  string $browseType
     * @param  int    $param
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function linkStory($releaseID = 0, $browseType = '', $param = 0, $recTotal = 0, $recPerPage = 100, $pageID = 1)
    {
        if(!empty($_POST['stories']))
        {
            $this->projectrelease->linkStory($releaseID);
            $this->loadModel('story')->changeRequirementStatusByStoryStage($_POST['stories']);
            return print(js::locate(inlink('view', "releaseID=$releaseID&type=story"), 'parent'));
        }
        $this->session->set('storyList', inlink('view', "releaseID=$releaseID&type=story&link=true&param=" . helper::safe64Encode("&browseType=$browseType&queryID=$param")), $this->app->tab);

        $release = $this->projectrelease->getByID($releaseID);
        if(!$this->session->project)
        {
            $releaseProject = explode(',', $release->project);
            $this->session->set('project', $releaseProject[0], 'project');
        }

        $builds  = $this->loadModel('build')->getByList($release->build);
        $project = $this->loadModel('project')->getByID($this->session->project);
        $this->commonAction($this->session->project, $release->product);
        $this->loadModel('story');
        $this->loadModel('tree');
        $this->loadModel('product');

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        /* Build search form. */
        $queryID = ($browseType == 'bySearch') ? (int)$param : 0;
        unset($this->config->product->search['fields']['product']);
        unset($this->config->product->search['fields']['project']);
        if(!$project->hasProduct and !$project->multiple) unset($this->config->product->search['fields']['plan']);
        $this->config->product->search['actionURL'] = $this->createLink('projectrelease', 'view', "releaseID=$releaseID&type=story&link=true&param=" . helper::safe64Encode('&browseType=bySearch&queryID=myQueryID'));
        $this->config->product->search['queryID']   = $queryID;
        $this->config->product->search['style']     = 'simple';
        $this->config->product->search['params']['plan']['values'] = $this->loadModel('productplan')->getPairsForStory($release->product, $release->branch, 'skipParent|withMainPlan');
        $this->config->product->search['params']['status']         = array('operator' => '=', 'control' => 'select', 'values' => $this->lang->story->statusList);

        $searchModules = array();
        $moduleGroups  = $this->loadModel('tree')->getOptionMenu($release->product, 'story', 0, explode(',', $release->branch));
        foreach($moduleGroups as $modules) $searchModules += $modules;
        $this->config->product->search['params']['module']['values'] = $searchModules;

        if($release->productType == 'normal')
        {
            unset($this->config->product->search['fields']['branch']);
            unset($this->config->product->search['params']['branch']);
        }
        else
        {
            $this->config->product->search['fields']['branch'] = sprintf($this->lang->product->branch, $this->lang->product->branchName[$release->productType]);
            $allBranchs = $this->loadModel('branch')->getPairs($release->product);
            $branches   = array('' => '', BRANCH_MAIN => $this->lang->branch->main);
            foreach(explode(',', trim($release->branch, ',')) as $branchID) $branches[$branchID] = zget($allBranchs, $branchID);
            $this->config->product->search['params']['branch']['values'] = $branches;
        }
        if($this->view->project->model == 'waterfall' && empty($this->view->project->hasProduct)) unset($this->config->product->search['fields']['plan']);
        $this->loadModel('search')->setSearchParams($this->config->product->search);

        $executionIdList = array();
        foreach($builds as $build) $executionIdList[] = empty($build->execution) ? $build->project : $build->execution;
        $executionIdList = array_unique($executionIdList);

        $allStories = array();
        if($browseType == 'bySearch')
        {
            $allStories = $this->story->getBySearch($release->product, $release->branch, $queryID, 'id', $executionIdList, 'story', $release->stories, 'draft,reviewing,changing', $pager);
        }
        else
        {
            $allStories = $this->story->getExecutionStories($executionIdList, $release->product, 0, 't1.`order`_desc', 'byBranch', $release->branch, 'story', $release->stories, 'draft,reviewing,changing', $pager);
        }

        $this->view->allStories     = $allStories;
        $this->view->release        = $release;
        $this->view->releaseStories = empty($release->stories) ? array() : $this->story->getByList($release->stories);
        $this->view->users          = $this->loadModel('user')->getPairs('noletter');
        $this->view->browseType     = $browseType;
        $this->view->param          = $param;
        $this->view->pager          = $pager;
        $this->display();
    }
}