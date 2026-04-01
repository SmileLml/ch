<?php
class yearplanModel extends model
{
    /**
     * Get yearplan list.
     * @param  string  $browseType
     * @param  string  $orderBy
     * @param  object  $pager
     * @access public
     * @return void
     */
    public function getList($browseType = 'all', $queryID = 0, $orderBy = 'id_desc', $pager = null, $extra = '')
    {
        $account = $this->app->user->account;

        /* 获取搜索条件的查询SQL。*/
        $yearplanQuery = '';
        if($browseType == 'bysearch')
        {
            $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';
            if($query)
            {
                $this->session->set('yearplanQuery', $query->sql);
                $this->session->set('yearplanForm', $query->form);
            }

            if($this->session->yearplanQuery == false) $this->session->set('yearplanQuery', ' 1 = 1');
            $yearplanQuery = $this->session->yearplanQuery;
        }

        $isAdmin = $this->app->user->admin ? 1 : 0;

        /* 创建SQL查询数据。*/
        $yearplans = $this->dao->select('*')->from('zt_yearplan')
            ->where('deleted')->eq('0')
            ->andWhere('acl', true)->eq('open')
            ->orWhere('(acl')->eq('private')
            ->andWhere('createdBy', true)->eq($account)
            ->orWhere('owner')->eq($account)
            ->orWhere($isAdmin)
            ->orWhere("CONCAT(',', participant, ',')")->like("%,$account,%")
            ->markRight(1)
            ->markRight(1)
            ->markRight(1)
            ->beginIF($browseType == 'closed')->andWhere('status')->eq('closed')->fi()
            ->beginIF($browseType == 'normal')->andWhere('status')->eq('normal')->fi()
            ->beginIF($browseType == 'involved')->andWhere("CONCAT(',', participant, ',')")->like("%,$account,%")->fi()
            ->beginIF($browseType == 'bysearch')->andWhere($yearplanQuery)->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');

        /* 保存查询条件并查询子需求条目。*/
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'yearplan', $browseType != 'bysearch');
        return $yearplans;
    }

    /**
     * Create a yearplan.
     *
     * @access public
     * @return void
     */
    public function create()
    {
        /* 获取post数据并处理数据。*/
        $yearplan = fixer::input('post')
            ->add('status', 'normal')
            ->add('createdBy', $this->app->user->account)
            ->add('createdDate', helper::today())
            ->remove('uid,files,labels,contactListMenu')
            ->join('participant', ',')
            ->stripTags($this->config->yearplan->editor->create['id'], $this->config->allowedTags)
            ->get();

        /* 插入数据后，判断是否有误，然后更新code参数，并保存文件。*/
        $yearplan = $this->loadModel('file')->processImgURL($yearplan, $this->config->yearplan->editor->create['id'], $this->post->uid);
        $this->dao->insert('zt_yearplan')->data($yearplan)
            ->autoCheck()
            ->batchCheck($this->config->yearplan->create->requiredFields, 'notempty')->exec();

        if(!dao::isError())
        {
            $poolID = $this->dao->lastInsertID();

            $this->loadModel('file')->updateObjectID($this->post->uid, $poolID, 'yearplan');
            $this->file->saveUpload('yearplan', $poolID);

            return $poolID;
        }

        return false;
    }

    /**
     * Build search form.
     *
     * @param  int    $queryID
     * @param  string $actionURL
     * @access public
     * @return void
     */
    public function buildSearchForm($queryID, $actionURL)
    {
        $this->config->yearplan->search['actionURL'] = $actionURL;
        $this->config->yearplan->search['queryID']   = $queryID;

        $this->loadModel('search')->setSearchParams($this->config->yearplan->search);
    }

    /**
     * Update a yearplan.
     *
     * @param  int $poolID
     * @access public
     * @return void
     */
    public function update($poolID)
    {
        /* 获取旧的需求意向数据，并处理post请求参数。*/
        $oldRequirement = $this->getByID($poolID);
        $yearplan = fixer::input('post')
            ->join('participant', ',')
            ->remove('uid,files,labels,comment,contactListMenu')
            ->stripTags($this->config->yearplan->editor->edit['id'], $this->config->allowedTags)
            ->get();

        /* 执行SQL，处理相关附件，并获取变动的字段进行返回。*/
        $yearplan = $this->loadModel('file')->processImgURL($yearplan, $this->config->yearplan->editor->edit['id'], $this->post->uid);
        $this->dao->update('zt_yearplan')->data($yearplan)->autoCheck()
            ->batchCheck($this->config->yearplan->edit->requiredFields, 'notempty')
            ->where('id')->eq($poolID)
            ->exec();

        $this->loadModel('file')->updateObjectID($this->post->uid, $poolID, 'yearplan');
        $this->file->saveUpload('yearplan', $poolID);

        return common::createChanges($oldRequirement, $yearplan);
    }

    public function getByID($poolID)
    {
        /* 查询需求意向的子需求条目、需求意向信息和需求意向相关附件然后返回数据。*/
        $yearplan = $this->dao->findByID($poolID)->from('zt_yearplan')->fetch();
        $yearplan->files = $this->loadModel('file')->getByObject('yearplan', $poolID);

        $yearplan = $this->loadModel('file')->replaceImgURL($yearplan, 'background,overview,desc');

        return $yearplan;
    }

    public function setMenu($poolID)
    {
        $moduleName = $this->app->rawModule;
        $methodName = $this->app->rawMethod;

        $this->lang->switcherMenu = $this->getSwitcher($poolID, $moduleName, $methodName);

        $this->saveState($poolID, $this->getPairs());

        common::setMenuVars('yearplan', $poolID);
        return $poolID;
    }

    public function saveState($poolID = 0, $yearplans = array())
    {
        if($poolID == 0 and $this->cookie->lastYearplan) $poolID = $this->cookie->lastYearplan;
        if($poolID == 0 and (int)$this->session->yearplan == 0) $poolID = key($yearplans);
        if($poolID == 0) $poolID = key($yearplans);

        $this->session->set('yearplan', (int)$poolID, $this->app->tab);

        if(!isset($yearplans[$this->session->yearplan]))
        {
            if($poolID and strpos(",{$this->app->user->view->yearplans},", ",{$this->session->yearplan},") === false and !empty($yearplans))
            {
                $this->session->set('yearplan', key($yearplans), $this->app->tab);
                $this->accessDenied();
            }
        }

        return $this->session->yearplan;
    }

    public function getSwitcher($poolID, $currentModule, $currentMethod)
    {
        if($currentModule == 'yearplan' and $currentMethod == 'browse') return;

        $currentYearplanName = $this->lang->yearplan->common;
        if($poolID)
        {
            $currentYearplan     = $this->getById($poolID);
            $currentYearplanName = $currentYearplan->name;
        }

        if($this->app->viewType == 'mhtml' and $poolID)
        {
            $output  = $this->lang->yearplan->common . $this->lang->colon;
            $output .= "<a id='currentItem' href=\"javascript:showSearchMenu('yearplan', '$poolID', '$currentModule', '$currentMethod', '')\">{$currentYearplanName} <span class='icon-caret-down'></span></a><div id='currentItemDropMenu' class='hidden affix enter-from-bottom layer'></div>";
            return $output;
        }

        $dropMenuLink = helper::createLink('yearplan', 'ajaxGetDropMenu', "objectID=$poolID&module=$currentModule&method=$currentMethod");
        $output  = "<div class='btn-group header-btn' id='swapper'><button data-toggle='dropdown' type='button' class='btn' id='currentItem' title='{$currentYearplanName}'><span class='text'>{$currentYearplanName}</span> <span class='caret' style='margin-bottom: -1px'></span></button><div id='dropMenu' class='dropdown-menu search-list' data-ride='searchList' data-url='$dropMenuLink'>";
        $output .= '<div class="input-control search-box has-icon-left has-icon-right search-example"><input type="search" class="form-control search-input" /><label class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label><a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a></div>';
        $output .= "</div></div>";

        return $output;
    }

    public function getPairs($orderBy = 'id_desc')
    {
        $account = $this->app->user->account;
        $isAdmin = $this->app->user->admin ? 1 : 0;
        $yearplans = $this->dao->select('id,name')->from('zt_yearplan')
            ->where('status')->ne('deleted')
            ->andWhere('acl', true)->eq('open')
            ->orWhere('(acl')->eq('private')
            ->andWhere('createdBy', true)->eq($account)
            ->orWhere('owner')->eq($account)
            ->orWhere($isAdmin)
            ->orWhere("CONCAT(',', participant, ',')")->like("%,$account,%")
            ->markRight(1)
            ->markRight(1)
            ->markRight(1)
            ->orderBy($orderBy)
            ->fetchPairs();

        return $yearplans;
    }

    public static function isClickable($yearplan, $action)
    {
        global $app;
        /* 对操作转换成小写，根据状态判断当前操作是否允许高亮。*/
        $action = strtolower($action);

        if($action == 'delete') return ($app->user->account == $yearplan->createdBy) or ($app->user->account == $yearplan->owner);
        if($action == 'close')  return $yearplan->status != 'closed';

        return true;
    }

    public function close($poolID)
    {
        $oldRequirement = $this->dao->findById($poolID)->from('zt_yearplan')->fetch();
        $yearplan = fixer::input('post')
            ->add('status', 'closed')
            ->remove('uid,comment')
            ->get();

        $this->dao->update('zt_yearplan')->data($yearplan)->autoCheck()->where('id')->eq((int)$poolID)->exec();
        if(!dao::isError()) return common::createChanges($oldRequirement, $yearplan);
        return false;
    }
}
