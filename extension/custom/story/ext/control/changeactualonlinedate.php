<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Change history data.
     */
    public function changeActualonlinedate()
    {
        $this->story->changeActualonlinedate();
    }
}