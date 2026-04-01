<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * AJAX: get businesses of a project in html select.
     *
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function ajaxGetProjectBusinesses($projectID)
    {
        $businesses  = array(0 => '');
        $businesses += $this->loadModel('project')->getBusinessPairs($projectID, 'story');
        if($this->app->getViewType() == 'json') return print(json_encode($businesses));

        return print(html::select('business', $businesses, 0, "class='form-control'"));
    }
}