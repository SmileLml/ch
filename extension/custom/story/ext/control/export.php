<?php
helper::importControl('story');
class mystory extends story
{
        /**
     * get data to export
     *
     * @param  int    $productID
     * @param  string $orderBy
     * @param  int    $executionID
     * @param  string $browseType
     * @param  string $storyType requirement|story
     * @access public
     * @return void
     */
    public function export($productID, $orderBy, $executionID = 0, $browseType = '', $storyType = 'story')
    {
        if($storyType == 'requirement')
        {
            $this->story->replaceUserRequirementLang();
            $this->config->story->exportFields = $this->config->story->exportFields . ',residueEstimate';
        }

        if($storyType == 'story') $this->config->story->exportFields = $this->config->story->exportFields . ',relatedRequirement,actualConsumed';

        parent::export($productID, $orderBy, $executionID, $browseType, $storyType);
    }
}
