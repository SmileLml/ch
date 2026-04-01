<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * AJAX: get stories of a product in html select.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  int    $moduleID
     * @param  int    $storyID
     * @param  string $onlyOption
     * @param  string $status
     * @param  int    $limit
     * @param  string $type
     * @param  bool   $hasParent
     * @param  int    $objectID projectID || executionID
     * @param  int    $number
     * @param  string $source
     * @access public
     * @return void
     */
    public function ajaxGetProductStories($productID, $branch = 0, $moduleID = 0, $storyID = 0, $onlyOption = 'false', $status = '', $limit = 0, $type = 'full', $hasParent = 1, $objectID = 0, $number = '', $source = '')
    {
        $hasParent = (bool)$hasParent;
        if($moduleID)
        {
            $moduleID = $this->loadModel('tree')->getStoryModule($moduleID);
            $moduleID = $this->tree->getAllChildID($moduleID);
        }

        $storyStatus = $this->story->getStatusList($status);

        if($objectID)
        {
            $stories = $this->story->getExecutionStoryPairs($objectID, $productID, $branch, $moduleID, $type);
        }
        else
        {
            $stories = $this->story->getProductStoryPairs($productID, $branch, $moduleID, $storyStatus, 'id_desc', $limit, $type, 'story', $hasParent);
        }
        if(!in_array($this->app->tab, array('execution', 'project', 'chteam')) and empty($stories)) $stories = $this->story->getProductStoryPairs($productID, $branch, 0, $storyStatus, 'id_desc', $limit, $type, 'story', $hasParent);

        $storyID = isset($stories[$storyID]) ? $storyID : 0;

        if($source == 'testcase')
        {
            if(!$storyID && $number) $storyID = 'ditto';
            $stories['ditto'] = $this->lang->story->ditto;
        }

        $select  = html::select('story' . $number, empty($stories) ? array('' => '') : $stories, $storyID, "class='form-control'");

        /* If only need options, remove select wrap. */
        if($onlyOption == 'true') return print(substr($select, strpos($select, '>') + 1, -10));
        echo $select;
    }
}
