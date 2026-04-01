<?php
helper::importControl('execution');
class myExecution extends execution
{
    /**
     * Link stories to an execution.
     *
     * @param  int    $objectID
     * @param  string $browseType
     * @param  int    $param
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @param  string $extra
     * @param  string $storyType
     * @access public
     * @return void
     */
    public function linkStory($objectID = 0, $browseType = '', $param = 0, $recTotal = 0, $recPerPage = 50, $pageID = 1, $extra = '', $storyType = 'story', $chProject = 0)
    {
        $this->loadModel('story');
        $this->loadModel('product');
        $this->loadModel('tree');
        $this->loadModel('branch');

        /* Init objectID */
        $originObjectID = $objectID;

        /* Transfer object id when version lite */
        if($this->config->vision == 'lite')
        {
            $kanban   = $this->project->getByID($objectID, 'kanban');
            $objectID = $kanban->project;
        }

        /* Get projects, executions and products. */
        $object     = $this->project->getByID($objectID, 'project,sprint,stage,kanban');
        $products   = $this->product->getProducts($objectID);
        $queryID    = ($browseType == 'bySearch') ? (int)$param : 0;
        $browseLink = $this->createLink('execution', 'story', "executionID=$objectID&storyType=$storyType");
        if($this->app->tab == 'project' and $object->multiple) $browseLink = $this->createLink('projectstory', 'story', "objectID=$objectID&productID=0&branch=0&browseType=&param=0&storyType=$storyType");
        if($this->app->tab == 'chteam')
        {
            $browseLink = $this->session->teamStoryList;
            $ztProjects = $this->loadModel('chproject')->getProjectPairs($chProject);
            $this->config->product->search['fields']['project'] = $this->lang->story->affiliatedProjects;
            $this->config->product->search['params']['project'] = array('operator' => '=', 'control' => 'select', 'values' => $ztProjects);
        }
        if($object->type == 'kanban' && !$object->hasProduct) $this->lang->productCommon = $this->lang->project->common;

        $this->session->set('storyList', $this->app->getURI(true), $this->app->tab); // Save session.

        /* Only execution can have no products. */
        if(empty($products))
        {
            echo js::alert($this->lang->execution->errorNoLinkedProducts);
            return print(js::locate($this->createLink('execution', 'manageproducts', "executionID=$objectID")));
        }

        if(!empty($_POST))
        {
            if($object->type != 'project' and $object->project != 0) $this->execution->linkStory($object->project, array(), array(), '', array(), $storyType);
            $this->execution->linkStory($objectID, array(), array(), $extra, array(), $storyType);

            if(isonlybody())
            {
                if($this->app->tab == 'execution')
                {
                    $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                    $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                    if($object->type == 'kanban')
                    {
                        $kanbanData = $this->loadModel('kanban')->getRDKanban($objectID, $execLaneType, 'id_desc', 0, $execGroupBy);
                        $kanbanData = json_encode($kanbanData);
                        return print(js::closeModal('parent', '', "parent.updateKanban($kanbanData)"));
                    }
                    else
                    {
                        $kanbanData = $this->loadModel('kanban')->getExecutionKanban($objectID, $execLaneType, $execGroupBy);
                        $kanbanType = $execLaneType == 'all' ? 'story' : key($kanbanData);
                        $kanbanData = $kanbanData[$kanbanType];
                        $kanbanData = json_encode($kanbanData);
                        return print(js::closeModal('parent', '', "parent.updateKanban(\"story\", $kanbanData)"));
                    }
                }
                else
                {
                    return print(js::reload('parent'));
                }
            }

            return print(js::locate($browseLink));
        }

        if($this->app->tab == 'chteam')
        {
            if($this->lang->chteam->menu->view) unset($this->lang->chteam->menu->view);
            $this->loadModel('chproject')->setMenu($this->session->chproject);
        }
        else
        {
            if($object->type == 'project')
            {
                $this->project->setMenu($object->id);
            }
            elseif($object->type == 'sprint' or $object->type == 'stage' or $object->type == 'kanban')
            {
                $this->execution->setMenu($object->id);
            }
        }

        /* Set modules and branches. */
        $modules      = array();
        $branchIDList = array(BRANCH_MAIN);
        $branches     = $this->project->getBranchesByProject($objectID);
        $productType  = 'normal';

        if(defined('TUTORIAL'))
        {
            $modules = $this->loadModel('tutorial')->getModulePairs();
        }
        else
        {
            foreach($products as $product)
            {
                $productModules = $this->tree->getOptionMenu($product->id, 'story', 0, array_keys($branches[$product->id]));
                foreach($productModules as $branch => $branchModules)
                {
                    foreach($branchModules as $moduleID => $moduleName) $modules[$moduleID] = ((count($products) >= 2 and $moduleID != 0) ? $product->name : '') . $moduleName;
                }
                if($product->type != 'normal')
                {
                    $productType = $product->type;
                    if(isset($branches[$product->id]))
                    {
                        foreach($branches[$product->id] as $branchID => $branch) $branchIDList[$branchID] = $branchID;
                    }
                }
            }
        }

        if($storyType == 'requirement')
        {
            $this->app->loadLang('projectstory');
            $this->lang->story->title               = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->title);
            $this->lang->projectstory->whyNoStories = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->projectstory->whyNoStories);
            $this->lang->execution->linkStory       = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->linkStory);
            if(isset($this->config->product->search['fields']['stage'])) unset($this->config->product->search['fields']['stage']);
        }

        /* Build the search form. */
        if($this->app->rawModule == 'execution')
        {
            $actionURL = $this->createLink($this->app->rawModule, 'linkStory', "objectID=$objectID&browseType=bySearch&queryID=myQueryID&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID&extra=&storyType=$storyType");
        }
        else
        {
            $actionURL = $this->createLink($this->app->rawModule, 'linkStory', "objectID=$objectID&browseType=bySearch&queryID=myQueryID&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID&storyType=$storyType");
        }
        $branchGroups = $this->loadModel('branch')->getByProducts(array_keys($products));
        $this->execution->buildStorySearchForm($products, $branchGroups, $modules, $queryID, $actionURL, 'linkStory', $object, $storyType);

        if($browseType == 'bySearch')
        {
            $allStories = $this->story->getBySearch('', '', $queryID, 'id', $objectID, $storyType, '', '', null, 0, 0, true);
        }
        else
        {
            $status     = $storyType == 'story' ? 'active' : ($object->model == 'ipd' ? 'launched' : 'active,launched');
            $allStories = $this->story->getProductStories(array_keys($products), $branchIDList, $moduleID = '0', $status, $storyType, 'id_desc', $hasParent = false, '', $pager = null, $objectID, 0, true);
        }

        $linkedStories = $this->story->getExecutionStoryPairs($objectID, 0, 'all', 0, 'full', 'all', $storyType);
        foreach($allStories as $id => $story)
        {
            if(isset($linkedStories[$story->id])) unset($allStories[$id]);
            if($story->parent < 0) unset($allStories[$id]);

            if(!isset($modules[$story->module]))
            {
                $storyModule = $this->tree->getModulesName($story->module);
                $productName = count($products) > 1 ? $products[$story->product]->name : '';
                $modules[$story->module] = $productName . zget($storyModule, $story->module, '');
            }
        }

        /* Pager. */
        $this->app->loadClass('pager', $static = true);
        $recTotal   = count($allStories);
        $pager      = new pager($recTotal, $recPerPage, $pageID);
        $allStories = array_chunk($allStories, $pager->recPerPage);

        $project = $object;
        if(strpos('sprint,stage,kanban', $object->type) !== false) $project = $this->loadModel('project')->getByID($object->project);

        $linkProjects = $this->project->getStoryIdAndName();
        $projectMap   = [];
        foreach($linkProjects as $projectItem) $projectMap[$projectItem->story] = $projectItem->projectNames;

        foreach($allStories as $storiesItem)
        {
            foreach($storiesItem as $story)
            {
                if(isset($projectMap[$story->id]))
                {
                    $story->projectName = $projectMap[$story->id];
                }
                else
                {
                    $story->projectName = '';
                }
            }
        }

        /* Assign. */
        $this->view->title        = $object->name . $this->lang->colon . $this->lang->execution->linkStory;
        $this->view->position[]   = html::a($browseLink, $object->name);
        $this->view->position[]   = $this->lang->execution->linkStory;
        $this->view->objectID     = $originObjectID;
        $this->view->object       = $object;
        $this->view->products     = $products;
        $this->view->allStories   = empty($allStories) ? $allStories : $allStories[$pageID - 1];
        $this->view->pager        = $pager;
        $this->view->browseType   = $browseType;
        $this->view->productType  = $productType;
        $this->view->modules      = $modules;
        $this->view->users        = $this->loadModel('user')->getPairs('noletter');
        $this->view->branchGroups = $branchGroups;
        $this->view->browseLink   = $browseLink;
        $this->view->project      = $project;
        $this->view->storyType    = $storyType;
        if($this->config->edition == 'ipd') $this->view->roadmaps = $this->loadModel('roadmap')->getPairs(array_keys($products));

        $this->display();
    }
}
