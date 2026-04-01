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
    public function ajaxGetProject($projectID)
    {
        $project = $this->loadModel('project')->getById($projectID);

        return print(json_encode($project));
    }
}