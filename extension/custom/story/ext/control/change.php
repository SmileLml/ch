<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Change a story.
     *
     * @param  int    $storyID
     * @param  string $from
     * @param  string $storyType story|requirement
     * @access public
     * @return void
     */
    public function change($storyID, $from = '', $storyType = 'story')
    {
        $this->loadModel('file');
        if(!empty($_POST))
        {
            $changes = $this->story->change($storyID);


            if(dao::isError())
            {
                if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'fail', 'message' => dao::getError()));
                return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            }

            if($this->post->comment != '' or !empty($changes))
            {
                $action   = !empty($changes) ? 'Changed' : 'Commented';
                $actionID = $this->action->create('story', $storyID, $action, $this->post->comment);
                $this->action->logHistory($actionID, $changes);

                /* Record submit review action. */
                $story = $this->dao->findById((int)$storyID)->from(TABLE_STORY)->fetch();
                if($story->status == 'reviewing') $this->action->create('story', $storyID, 'submitReview');
            }

            $this->executeHooks($storyID);

            $module = $this->app->tab == 'project' ? 'projectstory' : 'story';

            if(isonlybody())
            {
                $execution = $this->execution->getByID($this->session->execution);
                if($this->app->tab == 'execution')
                {
                    $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                    $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                    if($execution->type == 'kanban')
                    {
                        $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                        $kanbanData    = $this->loadModel('kanban')->getRDKanban($this->session->execution, $execLaneType, 'id_desc', 0, $execGroupBy, $rdSearchValue);
                        $kanbanData    = json_encode($kanbanData);
                        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.parent.updateKanban($kanbanData)"));
                    }
                    if($from == 'taskkanban')
                    {
                        $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                        $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($execution->id, $execLaneType, $execGroupBy, $taskSearchValue);
                        $kanbanType      = $execLaneType == 'all' ? 'story' : key($kanbanData);
                        $kanbanData      = $kanbanData[$kanbanType];
                        $kanbanData      = json_encode($kanbanData);
                        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.parent.updateKanban(\"story\", $kanbanData)"));
                    }
                }
                else
                {
                    return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => 'reloadByAjaxForm()'));
                }
            }

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            if($this->app->tab == 'chteam') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $this->createLink('story', 'view', "storyID=$storyID&version=0&param=0&storyType=$storyType")));

            if($this->app->tab == 'project')
            {
                $module  = 'projectstory';
                $method  = 'view';
                $params  = "storyID=$storyID";
                if(!$this->session->multiple)
                {
                    $module  = 'execution';
                    $method  = 'storyView';
                    $params .= "storyID=$storyID";
                }
            }
            elseif($this->app->tab == 'execution')
            {
                $module = 'execution';
                $method = 'storyView';
                $params = "storyID=$storyID";
            }
            else
            {
                $module = 'story';
                $method = 'view';
                $params = "storyID=$storyID&version=0&param=0&storyType=$storyType";
            }

            /* send openMessage */
            if(SX_ENABLE) 
            {
                $review_url = helper::createLink('my','audit','browseType=story&param=&orderBy=time_desc');
                $story = $this->dao->findById((int)$storyID)->from(TABLE_STORY)->fetch();

                $msgContent = sprintf($this->lang->story->openMessageTemplate,$review_url,$storyID,$story->title,$_SESSION['user']->account);
                $this->loadModel('apiRequest');
                if(is_array($_POST['reviewer'] && count($_POST['reviewer']))) 
                {
                    foreach($_POST['reviewer'] as $value) 
                    {
                        if(!empty($value))
                        {
                            try {
                                $this->apiRequest->sendOpenMessage($value,$msgContent);
                            } catch(Exception $e) {
    
                            }
                            
                        }
                    }
                }
            }



            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $this->createLink($module, $method, $params)));
        }

        $this->commonAction($storyID);
        $story = $this->view->story;
        if(!in_array($story->status, array('active', 'launched', 'developing', 'reviewing'))) return print(js::locate($this->session->storyList, 'parent'));
        $this->story->getAffectedScope($story);

        if($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject');
            $chprojectID = $this->app->tab == 'chteam' ? $this->session->chproject : 0;
            $this->chproject->setMenu($chprojectID);
        }

        $this->app->loadLang('task');
        $this->app->loadLang('bug');
        $this->app->loadLang('testcase');
        $this->app->loadLang('execution');

        $reviewer = $this->story->getReviewerPairs($storyID, $story->version);
        $product  = $this->loadModel('product')->getByID($story->product);

        /* Get users in team. */
        $productReviewers = $product->reviewer;
        if(!$productReviewers and $product->acl != 'open') $productReviewers = $this->loadModel('user')->getProductViewListUsers($product, '', '', '', '');

        /* Assign. */
        $this->view->title            = $this->lang->story->change . "STORY" . $this->lang->colon . $this->view->story->title;
        $this->view->twins            = empty($story->twins) ? array() : $this->story->getByList($story->twins);
        $this->view->branches         = $this->loadModel('branch')->getPairs($story->product);
        $this->view->users            = $this->user->getPairs('pofirst|nodeleted|noclosed', $this->view->story->assignedTo);
        $this->view->position[]       = $this->lang->story->change;
        $this->view->needReview       = (($this->app->user->account == $this->view->product->PO or $this->config->story->needReview == 0 or !$this->story->checkForceReview()) and empty($reviewer)) ? "checked='checked'" : "";
        $this->view->reviewer         = implode(',', array_keys($reviewer));
        $this->view->productReviewers = $this->user->getPairs('noclosed|nodeleted', $reviewer, 0, $productReviewers);
        $this->view->lastReviewer     = $this->story->getLastReviewer($story->id);

        $this->display();
    }
}
