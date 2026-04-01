<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Batch close story.
     *
     * @param  int    $productID
     * @param  int    $executionID
     * @param  string $storyType
     * @param  string $from
     * @access public
     * @return void
     */
    public function batchClose($productID = 0, $executionID = 0, $storyType = 'story', $from = '')
    {
        $this->app->loadLang('bug');
        if(!$this->post->storyIdList) return print(js::locate($this->session->storyList, 'parent'));
        $storyIdList = $this->post->storyIdList;
        $storyIdList = array_unique($storyIdList);

        $this->story->replaceURLang($storyType);

        /* Get edited stories. */
        $stories = $this->story->getByList($storyIdList);
        $productList      = array();
        $ignoreTwins      = array();
        $twinsCount       = array();
        foreach($stories as $story)
        {
            if(!empty($ignoreTwins) and isset($ignoreTwins[$story->id]))
            {
                unset($stories[$story->id]);
                continue;
            }

            if($story->parent == -1)
            {
                $skipStory[] = $story->id;
                unset($stories[$story->id]);
            }
            if($story->status == 'closed')
            {
                $closedStory[] = $story->id;
                unset($stories[$story->id]);
            }

            $storyProduct = isset($productList[$story->product]) ? $productList[$story->product] : $this->product->getByID($story->product);
            $branch       = $storyProduct->type == 'branch' ? ($story->branch > 0 ? $story->branch : '0') : 'all';

            if(!empty($story->twins))
            {
                $twinsCount[$story->id] = 0;
                foreach(explode(',', trim($story->twins, ',')) as $twinID)
                {
                    $twinsCount[$story->id] ++;
                    $ignoreTwins[$twinID] = $twinID;
                }
            }
        }

        if($this->post->comments)
        {
            $data       = fixer::input('post')->get();
            $allChanges = $this->story->batchClose();

            if($allChanges)
            {
                foreach($allChanges as $storyID => $changes)
                {
                    $preStatus = $stories[$storyID]->status;
                    $isChanged = $stories[$storyID]->changedBy ? true : false;
                    if($preStatus == 'reviewing') $preStatus = $isChanged ? 'changing' : 'draft';

                    $actionID = $this->action->create('story', $storyID, 'Closed', htmlSpecialString($this->post->comments[$storyID]), ucfirst($this->post->closedReasons[$storyID]) . ($this->post->duplicateStoryIDList[$storyID] ? ':' . (int)$this->post->duplicateStoryIDList[$storyID] : '') . "|$preStatus");
                    $this->action->logHistory($actionID, $changes);

                    if(!empty($stories[$storyID]->twins)) $this->story->syncTwins($storyID, $stories[$storyID]->twins, $changes, 'Closed');
                }

                $this->dao->update(TABLE_STORY)->set('assignedTo')->eq('closed')->where('id')->in(array_keys($allChanges))->exec();
            }

            if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchOther');

            $link = $this->app->tab == 'chteam' ? $this->session->teamStoryList : $this->session->storyList;
            return print(js::locate($link, 'parent'));
        }

        $errorTips = '';
        if(isset($closedStory)) $errorTips .= sprintf($this->lang->story->closedStory, join(',', $closedStory));
        if(isset($skipStory))   $errorTips .= sprintf($this->lang->story->skipStory, join(',', $skipStory));
        if(isset($skipStory) || isset($closedStory)) echo js::alert($errorTips);

        /* The stories of a product. */
        if($this->app->tab == 'product')
        {
            $this->product->setMenu($productID);
            $product = $this->product->getByID($productID);
            $this->view->position[] = html::a($this->createLink('product', 'browse', "product=$product->id"), $product->name);
            $this->view->title      = $product->name . $this->lang->colon . $this->lang->story->batchClose;
        }
        /* The stories of a execution. */
        elseif($executionID)
        {
            $this->lang->story->menu      = $this->lang->execution->menu;
            $this->lang->story->menuOrder = $this->lang->execution->menuOrder;
            $this->execution->setMenu($executionID);
            $execution = $this->execution->getByID($executionID);
            $this->view->position[] = html::a($this->createLink('execution', 'story', "executionID=$execution->id"), $execution->name);
            $this->view->title      = $execution->name . $this->lang->colon . $this->lang->story->batchClose;
        }
        else
        {
            if($this->app->tab == 'project')
            {
                $this->project->setMenu($this->session->project);
                $this->view->title = $this->lang->story->batchEdit;
            }
            else
            {
                $this->lang->story->menu      = $this->lang->my->menu;
                $this->lang->story->menuOrder = $this->lang->my->menuOrder;

                if($from == 'work')       $this->lang->my->menu->work['subModule']       = 'story';
                if($from == 'contribute') $this->lang->my->menu->contribute['subModule'] = 'story';

                $this->view->title      = $this->lang->story->batchEdit;
            }
        }

        /* Judge whether the editedStories is too large and set session. */
        $countInputVars  = count($stories) * $this->config->story->batchClose->columns;
        $showSuhosinInfo = common::judgeSuhosinSetting($countInputVars);
        if($showSuhosinInfo) $this->view->suhosinInfo = extension_loaded('suhosin') ? sprintf($this->lang->suhosinInfo, $countInputVars) : sprintf($this->lang->maxVarsInfo, $countInputVars);

        unset($this->lang->story->reasonList['subdivided']);

        $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'story');
        $this->view->plans            = $this->loadModel('productplan')->getPairs($productID);
        $this->view->productID        = $productID;
        $this->view->stories          = $stories;
        $this->view->storyIdList      = $storyIdList;
        $this->view->storyType        = $storyType;
        $this->view->reasonList       = $this->lang->story->reasonList;
        $this->view->twinsCount       = $twinsCount;

        $this->display();
    }
}
