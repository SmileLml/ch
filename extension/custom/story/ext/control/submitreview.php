<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Submit review.
     *
     * @param  int    $storyID
     * @param  string $storyType story|requirement
     * @param  string $type      PRD|business
     * @access public
     * @return void
     */
    public function submitReview($storyID, $storyType = 'story', $type = 'PRD')
    {
        if($_POST)
        {
            $changes = $this->story->submitReview($storyID, $type);
            if(dao::isError()) return print(js::error(dao::getError()));
            
            $this->loadModel('apiRequest');
            /* send openmessage */
            if(SX_ENABLE)
            {
                $review_url = helper::createLink('my','audit','browseType=story&param=&orderBy=time_desc');
                $story = $this->dao->findById((int)$storyID)->from(TABLE_STORY)->fetch();

                $msgContent = sprintf($this->lang->story->openMessageTemplate,$review_url,$storyID,$story->title,$_SESSION['user']->account);
                
                if(is_array($_POST['reviewer']) && count($_POST['reviewer'])) 
                {
                    foreach($_POST['reviewer'] as $value) 
                    {
                        try {
                            $this->apiRequest->age($value,$msgContent);
                        } catch(Exception $e) {
                            
                        }
                    }
                }
            }   

            $story = $this->story->getById($storyID);
            $this->story->syncUpdateLinkStoryStatus($storyID);
            $operate = $story->status == 'PRDReviewing' ? 'submitPRDReview' : 'submitBusinessReview';
            $actionID = $this->loadModel('action')->create('story', $storyID, $operate);
            $this->action->logHistory($actionID, $changes);

            $linkStoryIdList = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->eq($storyID)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');
            foreach($linkStoryIdList as $linkStoryID)
            {
                $actionID = $this->loadModel('action')->create('story', $linkStoryID, $operate);
                $this->action->logHistory($actionID, $changes);
            }

            if(isonlybody()) return print(js::closeModal('parent.parent', 'this'));
            return print(js::locate($this->createLink('story', 'view', "storyID=$storyID&version=0&param=0&storyType=$storyType"), 'parent'));
        }

        /* Get story and product. */
        $story   = $this->story->getById($storyID);
        $product = $this->product->getById($story->product);

        /* Get reviewers. */
        $reviewers = $product->reviewer;
        if(!$reviewers and $product->acl != 'open') $reviewers = $this->loadModel('user')->getProductViewListUsers($product, '', '', '', '');

        /* Get story reviewer. */
        $reviewerList = $this->story->getReviewerPairs($story->id, $story->version);
        $story->reviewer = array_keys($reviewerList);

        $this->view->story        = $story;
        $this->view->actions      = $this->action->getList('story', $storyID);
        $this->view->reviewers    = $this->user->getPairs('noclosed|nodeleted', '', 0, $reviewers);
        $this->view->users        = $this->user->getPairs('noclosed|noletter');
        $this->view->needReview   = (($this->app->user->account == $product->PO or $this->config->story->needReview == 0 or !$this->story->checkForceReview()) and empty($story->reviewer)) ? "checked='checked'" : "";
        $this->view->lastReviewer = $this->story->getLastReviewer($story->id);

        $this->display();
    }
}
