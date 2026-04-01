<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Close a story.
     *
     * @param  int    $storyID
     * @param  string $from      taskkanban
     * @param  string $storyType story|requirement
     * @access public
     * @return void
     */
    public function close($storyID, $from = '', $storyType = 'story')
    {
        $this->app->loadLang('bug');
        $story = $this->story->getById($storyID);
        $this->commonAction($storyID);

        if(!empty($_POST))
        {
            $changes = $this->story->close($storyID);
            if(dao::isError())
            {
                if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'fail', 'message' => dao::getError()));
                return print(js::error(dao::getError()));
            }
            $this->story->closeParentRequirement($storyID);
            $this->story->changeRequirementStatusByStoryStage(array($storyID));

            if($changes)
            {
                $preStatus = $story->status;
                $isChanged = $story->changedBy ? true : false;
                if($preStatus == 'reviewing') $preStatus = $isChanged ? 'changing' : 'draft';

                $actionID  = $this->action->create('story', $storyID, 'Closed', $this->post->comment, ucfirst($this->post->closedReason) . ($this->post->duplicateStory ? ':' . (int)$this->post->duplicateStory : '') . "|$preStatus");
                $this->action->logHistory($actionID, $changes);
            }

            $this->dao->update(TABLE_STORY)->set('assignedTo')->eq('closed')->where('id')->eq((int)$storyID)->exec();

            $this->executeHooks($storyID);

            if(isonlybody())
            {
                $execution    = $this->execution->getByID($this->session->execution);
                $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                if($this->app->tab == 'execution' and $execution->type == 'kanban')
                {
                    $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                    $this->loadModel('kanban')->updateLane($this->session->execution, 'story', $storyID);

                    $kanbanData = $this->loadModel('kanban')->getRDKanban($this->session->execution, $execLaneType, 'id_desc', 0, $execGroupBy, $rdSearchValue);
                    $kanbanData = json_encode($kanbanData);
                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban($kanbanData)"));
                }
                elseif($from == 'taskkanban')
                {
                    $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                    $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($this->session->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                    $kanbanType      = $execLaneType == 'all' ? 'story' : key($kanbanData);
                    $kanbanData      = $kanbanData[$kanbanType];
                    $kanbanData      = json_encode($kanbanData);
                    return print(js::closeModal('parent.parent', '', "parent.parent.updateKanban(\"story\", $kanbanData)"));
                }
                else
                {
                    return print(js::closeModal('parent.parent', 'this', "function(){parent.parent.location.reload();}"));
                }
            }

            if(defined('RUN_MODE') && RUN_MODE == 'api')
            {
                return $this->send(array('status' => 'success', 'data' => $storyID));
            }
            else
            {
                return print(js::locate(inlink('view', "storyID=$storyID&version=0&param=0&storyType=$storyType"), 'parent'));
            }
        }

        /* Get story and product. */
        $product = $this->dao->findById($story->product)->from(TABLE_PRODUCT)->fields('name, id, `type`')->fetch();

        $this->story->replaceURLang($story->type);

        /* Set the closed reason options and remove subdivided options. */
        $reasonList = $this->lang->story->reasonList;
        if($story->status == 'draft') unset($reasonList['cancel']);
        unset($reasonList['subdivided']);

        $branch         = $product->type == 'branch' ? ($story->branch > 0 ? $story->branch : '0') : 'all';
        $productStories = $this->story->getProductStoryPairs($story->product, $branch, 0, 'all', 'id_desc', 0, '', $storyType);

        $this->view->title      = $this->lang->story->close . "STORY" . $this->lang->colon . $story->title;
        $this->view->position[] = html::a($this->createLink('product', 'browse', "product=$product->id&branch=$story->branch"), $product->name);
        $this->view->position[] = $this->lang->story->common;
        $this->view->position[] = $this->lang->story->close;

        $this->view->product        = $product;
        $this->view->story          = $story;
        $this->view->productStories = $productStories;
        $this->view->actions        = $this->action->getList('story', $storyID);
        $this->view->users          = $this->loadModel('user')->getPairs();
        $this->view->reasonList     = $reasonList;
        $this->display();
    }
}