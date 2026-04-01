<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * If type is linkStories, link related stories else link child stories.
     *
     * @param  int    $storyID
     * @param  string $type
     * @param  string $browseType
     * @param  int    $queryID
     * @param  string $storyType story|requirement
     * @access public
     * @return void
     */
    public function linkStory($storyID, $type = 'linkStories', $linkedStoryID = 0, $browseType = '', $queryID = 0, $storyType = 'story')
    {
        $this->commonAction($storyID);

        if($type == 'remove')
        {
            $this->story->unlinkStory($storyID, $linkedStoryID);
            if($storyType == 'requirement')
            {
                $linkStoryIdList  = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->eq($storyID)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');
                $this->story->changeRequirementStatusByStoryStage($linkStoryIdList);
            }
            helper::end();
        }

        if($_POST)
        {
            if($storyType == 'requirement')
            {
                list($result, $message) = $this->story->checkRequirementResidueEstimate($storyID);

                if(!$result) return print(js::error($message));
            }
            $this->story->linkStories($storyID);

            if(dao::isError()) return print(js::error(dao::getError()));
            return print(js::closeModal('parent.parent', 'this'));
        }

        /* Get story, product, products, and queryID. */
        $story    = $this->story->getById($storyID);
        $products = $this->product->getPairs('', 0, '', 'all');
        $product  = $this->product->getByID($story->product);

        /* Change for requirement story title. */
        if($story->type == 'story')
        {
            $this->lang->story->title  = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->title);
            $this->lang->story->create = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->create);
            $this->config->product->search['fields']['title'] = $this->lang->story->title;
            unset($this->config->product->search['fields']['stage']);
        }
        else
        {
            $this->lang->story->title = str_replace($this->lang->URCommon, $this->lang->SRCommon, $this->lang->story->title);
        }

        if(!empty($product->shadow))
        {
            unset($this->config->product->search['fields']['plan']);
            unset($this->config->product->search['fields']['product']);
        }

        /* Build search form. */
        $actionURL = $this->createLink('story', 'linkStory', "storyID=$storyID&type=$type&linkedStoryID=$linkedStoryID&browseType=bySearch&queryID=myQueryID&storyType=$storyType", '', true);
        $this->product->buildSearchForm($story->product, $products, $queryID, $actionURL);

        /* Get stories to link. */
        $storyType    = $story->type;
        $stories2Link = $this->story->getStories2Link($storyID, $type, $browseType, $queryID, $storyType);

        /* Assign. */
        $this->view->title        = $this->lang->story->linkStory . "STORY" . $this->lang->colon .$this->lang->story->linkStory;
        $this->view->position[]   = $this->lang->story->linkStory;
        $this->view->type         = $type;
        $this->view->stories2Link = $stories2Link;
        $this->view->users        = $this->loadModel('user')->getPairs('noletter');
        $this->view->storyType    = $storyType;

        $this->display();
    }
}