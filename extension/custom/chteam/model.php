<?php
class chteamModel extends model
{
    /**
     * Get chteam by id.
     *
     * @param  int    $chteamID
     * @param  bool   $project
     * @access public
     * @return object
     */
    public function getByID($chteamID = 0, $getProject = false)
    {
        $team = $this->dao->select('*')->from(TABLE_CHTEAM)->where('id')->eq($chteamID)->fetch();

        if($team)
        {
            $team->memberPairs = $this->loadModel('user')->getPairs('noclosed|nodeleted', '', 0, $team->members);

            if($getProject) $team->projectIdList = $this->dao->select('project')->from(TABLE_CHPROJECTTEAM)->where('team')->eq($chteamID)->fetchPairs();
        }

        return $team;
    }

    /**
     * Get chteam list.
     *
     * @param  string $browseType
     * @param  int    $queryID
     * @param  string $orderBy
     * @param  int    $pager
     * @access public
     * @return array
     */
    public function getList($browseType = 'all', $queryID = 0, $orderBy = 'id_desc', $pager = null)
    {
        $this->loadModel('user');
        $this->loadModel('chproject');
        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                $this->session->set('chteamQuery', $query->sql);
                $this->session->set('chteamForm', $query->form);
            }
            else
            {
                $this->session->set('chteamQuery', ' 1 = 1');
            }
        }
        else
        {
            if($browseType == 'bySearch' and $this->session->chteamQuery == false) $this->session->set('chteamQuery', ' 1 = 1');
        }

        $currentUser           = $this->app->user->account;
        $PMOUsers              = $this->user->getUsersByUserGroupName($this->lang->chproject->group->PMO);
        $QAUsers               = $this->user->getUsersByUserGroupName($this->lang->chproject->group->QA);
        $seniorExecutiveUsers  = $this->user->getUsersByUserGroupName($this->lang->chproject->group->seniorExecutive);

        return $this->dao->select('*')->from(TABLE_CHTEAM)
            ->where('deleted')->eq('0')
            ->beginIF($browseType == 'bysearch' and $this->session->chteamQuery)->andWhere($this->session->chteamQuery)->fi()
            ->beginIF($browseType == 'myInvolved' && (!$this->app->user->admin && !isset($seniorExecutiveUsers[$currentUser]) && !isset($PMOUsers[$currentUser]) && !isset($QAUsers[$currentUser])))->andWhere("FIND_IN_SET('{$this->app->user->account}', members)")->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();
    }

    /*
     * Get team swapper.
     *
     * @param  int     $teamID
     * @param  string  $currentModule
     * @param  string  $currentMethod
     * @access public
     * @return string
     */
    public function getSwitcher($teamID, $currentModule, $currentMethod)
    {
        if($currentModule == 'chteam' and $currentMethod == 'browse') return;

        $currentTeamName = $this->lang->chteam->common;
        if($teamID)
        {
            $currentTeam     = $this->getByID($teamID);
            $currentTeamName = $currentTeam->name;
        }

        if($this->app->viewType == 'mhtml' and $teamID)
        {
            $output  = $this->lang->chteam->common . $this->lang->colon;
            $output .= "<a id='currentItem' href=\"javascript:showSearchMenu('chteam', '$teamID', '$currentModule', '$currentMethod', '')\">{$currentTeamName} <span class='icon-caret-down'></span></a><div id='currentItemDropMenu' class='hidden affix enter-from-bottom layer'></div>";
            return $output;
        }

        $dropMenuLink = helper::createLink('chteam', 'ajaxGetDropMenu', "objectID=$teamID&module=$currentModule&method=$currentMethod");
        $output  = "<div class='btn-group header-btn' id='swapper'><button data-toggle='dropdown' type='button' class='btn' id='currentItem' title='{$currentTeamName}'><span class='text'>{$currentTeamName}</span> <span class='caret' style='margin-bottom: -1px'></span></button><div id='dropMenu' class='dropdown-menu search-list' data-ride='searchList' data-url='$dropMenuLink'>";
        $output .= '<div class="input-control search-box has-icon-left has-icon-right search-example"><input type="search" class="form-control search-input" /><label class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label><a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a></div>';
        $output .= "</div></div>";
        return $output;
    }

    /**
     * Create the link from module,method.
     *
     * @param  string $module
     * @param  string $method
     * @param  int    $teamID
     * @access public
     * @return string
     */
    public function getLink($module, $method, $teamID)
    {
        $link = helper::createLink('chproject', 'browse', "teamID=%s");

        if($module == 'chproject' and $method == 'create') $link = helper::createLink($module, $method, "teamID=%s");

        return $link;
    }

    /**
     * Create a chteam.
     *
     * @access public
     * @return int|bool
     */
    public function create()
    {
        $chteam = fixer::input('post')
            ->stripTags($this->config->chteam->editor->edit['id'], $this->config->allowedTags)
            ->callFunc('name', 'trim')
            ->setDefault('createdBy', $this->app->user->account)
            ->setDefault('createdDate', helper::now())
            ->join('members', ',')
            ->get();

        if(strpos(',' . $chteam->members . ',', ',' . $chteam->leader . ',') === false) $chteam->members .= ',' . $chteam->leader;
        $chteam->members = trim($chteam->members, ',');

        $this->dao->insert(TABLE_CHTEAM)->data($chteam)
            ->autoCheck()
            ->batchCheck($this->config->chteam->create->requiredFields, 'notempty')
            ->exec();

        if(!dao::isError()) return $this->dao->lastInsertID();

        return false;
    }

    /**
     * update a chteam.
     *
     * @param  int    $chteamID
     * @access public
     * @return void
     */
    public function update($chteamID)
    {
        $chteam = fixer::input('post')
            ->stripTags($this->config->chteam->editor->edit['id'], $this->config->allowedTags)
            ->callFunc('name', 'trim')
            ->join('members', ',')
            ->get();

        if(strpos(',' . $chteam->members . ',', ',' . $chteam->leader . ',') === false) $chteam->members .= ',' . $chteam->leader;
        $chteam->members = trim($chteam->members, ',');

        $this->dao->update(TABLE_CHTEAM)->data($chteam)
            ->autoCheck()
            ->batchCheck($this->config->chteam->edit->requiredFields, 'notempty')
            ->where('id')->eq($chteamID)
            ->exec();

        $projectIdList = $this->dao->select('project')->from(TABLE_CHPROJECTTEAM)->where('team')->eq($chteamID)->fetchPairs();

        $intances = $this->loadModel('chproject')->getIntances($projectIdList);

        $this->updateIntancesTeam($intances, $chteam->members);
    }

    /**
     * delete a chteam.
     *
     * @param  string $table  the table name
     * @param  int    $chteamID
     * @access public
     * @return bool
     */
    public function delete($table, $chteamID)
    {
        $this->dao->update($table)->set('deleted')->eq(1)->where('id')->eq($chteamID)->exec();
        $this->loadModel('action')->create('chteam', $chteamID, 'deleted', '', $extra = ACTIONMODEL::CAN_UNDELETED);

        return true;
    }

    /**
     * Set menu of project module.
     *
     * @param  int    $objectID  projectID
     * @access public
     * @return int
     */
    public function setMenu($objectID)
    {
        global $lang;
        $this->loadModel('user');
        $this->loadModel('chproject');

        $model = 'scrum';

        $currentUser           = $this->app->user->account;
        $PMOUsers              = $this->user->getUsersByUserGroupName($this->lang->chproject->group->PMO);
        $QAUsers               = $this->user->getUsersByUserGroupName($this->lang->chproject->group->QA);
        $seniorExecutiveUsers  = $this->user->getUsersByUserGroupName($this->lang->chproject->group->seniorExecutive);

        $chteam = $this->dao->select('id')->from(TABLE_CHTEAM)
            ->where('deleted')->eq(0)
            ->beginIF(!$this->app->user->admin && !isset($seniorExecutiveUsers[$currentUser]) && !isset($PMOUsers[$currentUser]) && !isset($QAUsers[$currentUser]))->andWhere("FIND_IN_SET('{$this->app->user->account}', members)")->fi()
            ->fetch('id');

        $objectID = empty($objectID) ? $chteam : $objectID;
        $team     = $this->getByID($objectID);
        if(!$team) return '';

        /* Reset project priv. */
        $moduleName = $this->app->rawModule;
        $methodName = $this->app->rawMethod;
        if(!$this->loadModel('common')->isOpenMethod($moduleName, $methodName) and !commonModel::hasPriv($moduleName, $methodName)) $this->common->deny($moduleName, $methodName, false);

        // @Todo: Check team priv.

        $lang->switcherMenu = $this->getSwitcher($objectID, $moduleName, $methodName);

        common::setMenuVars('project', $objectID);

        return $objectID;
    }

    /**
     * Build chteam build search form.
     *
     * @param  int    $queryID
     * @param  string $actionURL
     * @access public
     * @return void
     */
    public function buildSearchForm($queryID, $actionURL)
    {
        $this->config->chteam->search['queryID']   = $queryID;
        $this->config->chteam->search['actionURL'] = $actionURL;
        $this->loadModel('search')->setSearchParams($this->config->chteam->search);
    }

    /**
     * Print datatable cell.
     *
     * @param  object $col
     * @param  object $chteam
     * @param  array  $users
     * @access public
     * @return void
     */
    public function printCell($col, $chteam, $users)
    {
        $id = $col->id;
        if($col->show)
        {
            $class = "c-$id ";
            $title = '';
        }

        if ($id == 'desc') $class .= 'content';

        echo "<td class='$class' $title>";

        switch ($id)
        {
        case 'id':
            printf('%03d', $chteam->id);
            break;
        case 'name':
            echo $chteam->name;
            break;
        case 'leader':
            echo zget($users, $chteam->leader);
            break;
        case 'members':
            $membersText = '';
            foreach(explode(',', $chteam->members) as $member) $membersText .= zget($users, $member) . ',';
            $membersText = trim($membersText, ',');
            echo "<span title='{$membersText}'>" . $membersText . '</span>';
            break;
        case 'desc':
            $desc = trim(strip_tags(str_replace(array('</p>', '<br />', '<br>', '<br/>'), "\n", str_replace(array("\n", "\r"), '', $chteam->desc)), '<img>'));
            echo "<div title='{$desc}'>" . nl2br($desc) . "</div>";
            break;
        case 'createdBy':
            echo zget($users, $chteam->createdBy);
            break;
        case 'createdDate':
            echo $chteam->createdDate;
            break;
        case 'actions':
            echo $this->buildOperateMenu($chteam, 'browse');
            break;
        }

        echo '</td>';
    }

    /**
     * Build project action menu.
     *
     * @param  object $chteam
     * @param  string $type
     * @access public
     * @return string
     */
    public function buildOperateMenu($chteam)
    {
        $menu       = '';
        $params     = "chteamID=$chteam->id";
        $moduleName = "chteam";

        $menu .= $this->buildMenu($moduleName, 'edit', $params, $chteam, 'browse', 'edit', '', 'iframe', '', '');
        $menu .= $this->buildMenu($moduleName, "delete", $params, $chteam, 'browse', 'trash', 'hiddenwin', 'btn-action');

        return $menu;
    }

    /**
     * Unbind project from chteam.
     *
     * @param  int    $chteamID
     * @access public
     * @return mixed
     */
    public function unbind($chteamID)
    {
        $team = $this->getByID($chteamID, true);

        $this->dao->delete()->from(TABLE_CHPROJECTTEAM)->where('team')->eq($chteamID)->exec();
        $this->dao->delete()->from(TABLE_CHPROJECTINTANCES)->where('ch')->in($team->projectIdList)->exec();
    }

    /**
     * Update intances team.
     *
     * @param  array  $intances
     * @param  array  $members
     * @access public
     * @return mixed
     */
    public function updateIntancesTeam($intances, $members)
    {
        $this->loadModel('execution');

        $members = explode(',', $members);

        foreach($intances as $intance)
        {
            $execution = $this->dao->findById($intance)->from(TABLE_EXECUTION)->fetch();

            /* Get team and language item. */
            $team  = $this->user->getTeamMemberPairs($intance, 'execution');

            $roles = $this->user->getUserRoles($members);

            $changedAccounts = array();
            $teamMembers     = array();
            foreach($members as $account)
            {
                if(empty($account) or isset($team[$account])) continue;

                $member = new stdclass();
                $member->root    = (int)$intance;
                $member->account = $account;
                $member->join    = helper::today();
                $member->role    = zget($roles, $account, '');
                $member->days    = zget($execution, 'days', 0);
                $member->type    = 'execution';
                $member->hours   = $this->config->execution->defaultWorkhours;
                $this->dao->replace(TABLE_TEAM)->data($member)->exec();

                $changedAccounts[$account] = $account;
                $teamMembers[$account]     = $member;
            }

            $this->dao->delete()->from(TABLE_TEAM)
                ->where('root')->eq((int)$intance)
                ->andWhere('type')->eq('execution')
                ->andWhere('account')->in(array_keys($team))
                ->andWhere('account')->notin($members)
                ->andWhere('account')->ne($execution->openedBy)
                ->exec();

            $this->execution->addProjectMembers($execution->project, $teamMembers);

            if($execution->acl != 'open') $this->execution->updateUserView($intance, 'sprint', $changedAccounts);
        }
    }

    public function getTeamIDByChProject($chProjectID = 0)
    {
        return $this->dao->select('team as teamID')->from(TABLE_CHPROJECTTEAM)->where('project')->eq($chProjectID)->fetch('teamID');
    }
}
