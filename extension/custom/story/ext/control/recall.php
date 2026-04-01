<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Recall the story review or story change.
     *
     * @param  int    $storyID
     * @param  string $from      list
     * @param  string $confirm   no|yes
     * @param  string $storyType story|requirement
     * @param  string $type      PRD|business
     * @access public
     * @return void
     */
    public function recall($storyID, $from = 'list', $confirm = 'no', $storyType = 'story', $type = '')
    {
        $story = $this->story->getById($storyID);

        if($confirm == 'no')
        {
            $confirmTips = $story->status == 'changing' ? $this->lang->story->confirmRecallChange : $this->lang->story->confirmRecallReview;
            return print(js::confirm($confirmTips, $this->createLink('story', 'recall', "storyID=$storyID&from=$from&confirm=yes&storyType=$storyType&type=$type")));
        }
        else
        {
            if($story->status == 'changing') $this->story->recallChange($storyID);

            if(in_array($story->status, array('PRDReviewing', 'confirming', 'reviewing'))) $this->story->recallReview($storyID, $type);

            if(in_array($story->status, array('PRDReviewing', 'confirming'))) $this->story->syncUpdateLinkStoryStatus($storyID);
            
            $action = $story->status == 'changing' ? 'recalledChange' : 'Recalled';
            $this->loadModel('action')->create('story', $storyID, $action);

            $linkStoryIdList = $this->dao->select('id,BID')->from(TABLE_RELATION)
                ->where('AID')->eq($storyID)
                ->andWhere('AType')->eq('requirement')
                ->fetchPairs('id', 'BID');
            $this->story->changeRequirementStatusByStoryStage($linkStoryIdList);
            if($from == 'view')
            {
                if($this->app->tab == 'project')
                {
                    $module = 'projectstory';
                    $method = 'view';
                    $params = "storyID=$storyID";
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
                return print(js::locate($this->createLink($module, $method, $params), 'parent'));
            }

            $locateLink = $this->session->storyList ? $this->session->storyList : $this->createLink('product', 'browse', "productID={$story->product}");

            if($this->app->tab == 'chteam') $locateLink = $this->session->teamStoryList;
            return print(js::locate($locateLink, 'parent'));
        }
    }
}
