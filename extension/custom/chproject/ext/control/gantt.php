<?php
helper::importControl('chproject');
class myChproject extends chproject
{
    /**
     * Gantt
     *
     * @param  int    $projrctID
     * @param  int    $intanceProjectID
     * @param  string $type
     * @param  string $orderBy
     * @access public
     * @return void
     */
    public function gantt($projectID, $intanceProjectID = 0, $type = '', $orderBy = '')
    {
        $this->app->loadLang('task');
        $this->app->loadLang('programplan');
        $this->app->loadLang('bug');
        $this->loadModel('execution');

        if(empty($type))
        {
            $type = $this->cookie->ganttType;
            if(empty($type)) $type = 'type';
            if($this->config->vision == 'lite') $type = 'assignedTo';
        }
        setcookie('ganttType', $type, $this->config->cookieLife, $this->config->webRoot, '', false, true);

        $project  = $this->chproject->getById($projectID);
        $intances = $this->chproject->getIntances($projectID);

        $this->chproject->setMenu($projectID);
        if($project->lifetime == 'ops' or in_array($project->attribute, array('request', 'review'))) unset($this->lang->execution->gantt->browseType['story']);

        $users    = $this->loadModel('user')->getPairs('noletter');
        $userList = array();
        foreach($users as $account => $realname)
        {
            $user = array();
            $user['key']   = $account;
            $user['label'] = $realname;
            $userList[]    = $user;
        }
        $this->view->userList = $userList;
        $executionIDlist = $intanceProjectID ? $this->execution->getPairsByProjectID($intanceProjectID, 'id') : [];
        $executionData   = $this->execution->getDataForGantt($executionIDlist ? array_intersect($intances, $executionIDlist) : $intances, $type, $orderBy);
        $executions      = $this->loadModel('chproject')->getIntancesProjectOptions($projectID);

        /* The header and position. */
        $this->view->title            = $project->name . $this->lang->colon . $this->lang->execution->gantt->common;
        $this->view->position[]       = $this->lang->execution->gantt->common;
        $this->view->executionName    = $project->name;
        $this->view->executionData    = $executionData;
        $this->view->ganttType        = $type;
        $this->view->orderBy          = $orderBy;
        $this->view->projectID        = $projectID;
        $this->view->intanceProjectID = $intanceProjectID;
        $this->view->executions       = $executions;
        $this->view->intanceProjects  = $this->chproject->getIntancesProjectOptions($projectID, 'projectID', 'projectName');
        $this->view->zooming          = $this->loadModel('setting')->getItem("owner={$this->app->user->account}&module=chproject&section=ganttCustom&key=zooming");

        $this->display();
    }
}
