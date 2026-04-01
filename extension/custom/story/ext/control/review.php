<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Review a story.
     *
     * @param  int    $storyID
     * @param  string $from      product|project
     * @param  string $storyType story|requirement
     * @access public
     * @return void
     */
    public function review($storyID, $from = 'product', $storyType = 'story')
    {
        if(!empty($_POST))
        {
            $this->story->review($storyID);
            if(dao::isError()) return print(js::error(dao::getError()));

            $this->executeHooks($storyID);

            if(isonlybody())
            {
                $execution = $this->execution->getByID($this->session->execution);
                if($this->app->tab == 'execution')
                {
                    $this->loadModel('kanban')->updateLane($this->session->execution, 'story', $storyID);

                    $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                    $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                    if($execution->type == 'kanban')
                    {
                        $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                        $kanbanData    = $this->loadModel('kanban')->getRDKanban($this->session->execution, $execLaneType, 'id_desc', 0, $execGroupBy, $rdSearchValue);
                        $kanbanData    = json_encode($kanbanData);
                        return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban($kanbanData)"));
                    }
                    if($from == 'taskkanban')
                    {
                        $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                        $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($this->session->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                        $kanbanType      = $execLaneType == 'all' ? 'story' : key($kanbanData);
                        $kanbanData      = $kanbanData[$kanbanType];
                        $kanbanData      = json_encode($kanbanData);
                        return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban(\"story\", $kanbanData)"));
                    }
                }
                else
                {
                    return print(js::closeModal('parent.parent', 'this', "function(){parent.parent.location.reload();}"));
                }
            }

            if(defined('RUN_MODE') and RUN_MODE == 'api') return $this->send(array('status' => 'success', 'data' => $storyID));

            if($this->app->tab == 'chteam')
            {
                $module = 'story';
                $method = 'view';
                $params = "storyID=$storyID";
            }
            elseif($from == 'project')
            {
                $project = $this->execution->getByID($this->session->project);
                if(empty($project->multiple))
                {
                    $module = 'execution';
                    $method = 'storyView';
                    $params = "storyID=$storyID";
                }
                else
                {
                    $module = 'projectstory';
                    $method = 'view';
                    $params = "storyID=$storyID";
                }
            }
            elseif($from == 'execution')
            {
                $execution = $this->execution->getByID($this->session->execution);
                if($execution->multiple)
                {
                    $module = 'execution';
                    $method = 'storyView';
                    $params = "storyID=$storyID";
                }
                else
                {
                    $module = 'story';
                    $method = 'view';
                    $params = "storyID=$storyID&version=0&param={$this->session->execution}&storyType=$storyType";
                }
            }
            else
            {
                $module = 'story';
                $method = 'view';
                $params = "storyID=$storyID&version=0&param=0&storyType=$storyType";
            }
            return print(js::locate($this->createLink($module, $method, $params), 'parent'));
        }

        /* Get story and product. */
        $story   = $this->story->getById($storyID);
        $product = $this->dao->findById($story->product)->from(TABLE_PRODUCT)->fields('name, id')->fetch();

        $this->story->replaceURLang($story->type);

        /* Set menu. */
        if($this->app->tab == 'project')
        {
            $this->loadModel('project')->setMenu($this->session->project);
        }
        elseif($this->app->tab == 'product')
        {
            $this->product->setMenu($product->id, $story->branch);
        }
        elseif($this->app->tab == 'execution')
        {
            $this->loadModel('execution')->setMenu($this->session->execution);
        }
        elseif($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject')->setMenu($this->session->chproject);
        }

        /* Set the review result options. */
        $reviewers = $this->story->getReviewerPairs($storyID, $story->version);
        $this->lang->story->resultList = $this->lang->story->reviewResultList;

        if($story->status == 'reviewing')
        {
            if($story->version == 1) unset($this->lang->story->resultList['revert']);
            if($story->version > 1)  unset($this->lang->story->resultList['reject']);
        }

        $this->view->title      = $this->lang->story->review . "STORY" . $this->lang->colon . $story->title;
        $this->view->position[] = html::a($this->createLink('product', 'browse', "product=$product->id&branch=$story->branch"), $product->name);
        $this->view->position[] = $this->lang->story->common;
        $this->view->position[] = $this->lang->story->review;

        $this->view->product   = $product;
        $this->view->story     = $story;
        $this->view->actions   = $this->action->getList('story', $storyID);
        $this->view->users     = $this->loadModel('user')->getPairs('nodeleted|noclosed', "$story->lastEditedBy,$story->openedBy");
        $this->view->reviewers = $reviewers;
        $this->view->isLastOne = count(array_diff(array_keys($reviewers), explode(',', $story->reviewedBy))) == 1 ? true : false;

        /* Get the affcected things. */
        $this->story->getAffectedScope($this->view->story);
        $this->app->loadLang('task');
        $this->app->loadLang('bug');
        $this->app->loadLang('testcase');
        $this->app->loadLang('execution');

        $this->display();
    }
}
