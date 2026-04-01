<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Batch change the stage of story.
     *
     * @param  string    $stage
     * @access public
     * @return void
     */
    public function batchChangeStage($stage)
    {
        $storyIdList = $this->post->storyIdList;
        if(empty($storyIdList)) return print(js::locate($this->session->storyList, 'parent'));

        $storyIdList = array_unique($storyIdList);
        $allChanges  = $this->story->batchChangeStage($storyIdList, $stage);
        if(dao::isError()) return print(js::error(dao::getError()));

        $action = $stage == 'verified' ? 'Verified' : 'Edited';
        foreach($allChanges as $storyID => $changes)
        {
            $actionID = $this->action->create('story', $storyID, $action);
            $this->action->logHistory($actionID, $changes);
        }
        if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchOther');

        $link = $this->app->tab == 'chteam' ? $this->session->teamStoryList : $this->session->storyList;
        echo js::locate($link, 'parent');
    }
}
