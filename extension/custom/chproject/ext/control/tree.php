<?php
helper::importControl('chproject');
class myChproject extends chproject
{
    /**
     * Tree view.
     * Product
     *
     * @param  int    $projrctID
     * @param  int    $intanceProjectID
     * @param  string $type
     * @access public
     * @return void
     */
    public function tree($projectID, $intanceProjectID = 0, $type = 'task')
    {
        $this->loadModel('execution');
        $project  = $this->chproject->getById($projectID);
        $intances = $this->chproject->getIntances($projectID);
        $this->chproject->setMenu($projectID);

        $executionIDlist = $intanceProjectID ? $this->execution->getPairsByProjectID($intanceProjectID, 'id') : [];
        $tree            = $this->execution->getTree($executionIDlist ? array_intersect($intances, $executionIDlist) : $intances);

        /* Save to session. */
        $uri = $this->app->getURI(true);
        if($this->app->tab == 'chteam')
        {
            $this->app->session->set('teamStoryList', $uri, 'chteam');
            $this->app->session->set('teamTestcaseList', $uri, 'chteam');
        }
        else
        {
            $this->app->session->set('taskList', $uri, 'execution');
            $this->app->session->set('storyList', $uri, 'execution');
            $this->app->session->set('executionList', $uri, 'execution');
            $this->app->session->set('caseList', $uri, 'qa');
            $this->app->session->set('bugList', $uri, 'qa');
        }

        if($type === 'json') return print(helper::jsonEncode4Parse($tree, JSON_HEX_QUOT | JSON_HEX_APOS));
        if($project->lifetime == 'ops') unset($this->lang->execution->treeLevel['story']);

        $this->view->title            = $project->name . $this->lang->colon . $this->lang->execution->tree;
        $this->view->projectID        = $projectID;
        $this->view->level            = $type;
        $this->view->tree             = $this->execution->printTree($tree, $project->hasProduct);
        $this->view->features         = $this->execution->getExecutionFeatures($execution);
        $this->view->intanceProjectID = $intanceProjectID;
        $this->view->intanceProjects  = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $this->display();
    }
}