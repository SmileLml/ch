<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Batch assign to.
     *
     * @param  string $storyType story|requirement
     * @access public
     * @return void
     */
    public function batchAssignTo($storyType = 'story')
    {
        if(!empty($_POST) && isset($_POST['storyIdList']))
        {
            $allChanges = $this->story->batchAssignTo();
            if(dao::isError()) return print(js::error(dao::getError()));

            $assignedTwins = array();
            $oldStories       = $this->story->getByList($this->post->storyIdList);
            foreach($allChanges as $storyID => $changes)
            {
                $actionID = $this->action->create('story', $storyID, 'Assigned', '', $this->post->assignedTo);
                $this->action->logHistory($actionID, $changes);

                /* Sync twins. */
                if(!empty($oldStories[$storyID]->twins))
                {
                    $twins = $oldStories[$storyID]->twins;
                    foreach(explode(',', $twins) as $twinID)
                    {
                        if(in_array($twinID, $this->post->storyIdList) or isset($assignedTwins[$twinID])) $twins = str_replace(",$twinID,", ',', $twins);
                    }
                    $this->story->syncTwins($storyID, trim($twins, ','), $changes, 'Assigned');
                    foreach(explode(',', trim($twins, ',')) as $assignedID) $assignedTwins[$assignedID] = $assignedID;
                }
            }
        }
        if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchOther');

        $link = $this->app->tab == 'chteam' ? $this->session->teamStoryList : $this->session->storyList;
        echo js::locate($link, 'parent');
    }
}
