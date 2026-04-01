<?php
class demandpoolModel extends model
{
    /**
     * Get demandpool list.
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
        $demandpoolQuery = '';
        if($browseType == 'bysearch')
        {
            $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';
            if($query)
            {
                $this->session->set('demandpoolQuery', $query->sql);
                $this->session->set('demandpoolForm', $query->form);
            }

            if($this->session->demandpoolQuery == false) $this->session->set('demandpoolQuery', ' 1 = 1');
            $demandpoolQuery = $this->session->demandpoolQuery;
        }

        $isAdmin = $this->app->user->admin ? 1 : 0;

        /* 创建SQL查询数据。*/
        $demandpools = $this->dao->select('*')->from(TABLE_DEMANDPOOL)
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
            ->beginIF($browseType == 'involved')->andWhere("CONCAT(',', participant, ',')")->like("%,$account,%")->fi()
            ->beginIF($browseType == 'bysearch')->andWhere($demandpoolQuery)->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');

        /* 保存查询条件并查询子需求条目。*/
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'demandpool', $browseType != 'bysearch');
        return $demandpools;
    }

    public function getPairs($orderBy = 'id_desc')
    {
        $account = $this->app->user->account;
        $isAdmin = $this->app->user->admin ? 1 : 0;
        $demandpools = $this->dao->select('id,name')->from(TABLE_DEMANDPOOL)
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

        return $demandpools;
    }

    /**
     * Project: chengfangjinke
     * Method: getByID
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:50
     * Desc: This is the code comment. This method is called getByID.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param $poolID
     * @return mixed
     */
    public function getByID($poolID)
    {
        /* 查询需求意向的子需求条目、需求意向信息和需求意向相关附件然后返回数据。*/
        $demandpool = $this->dao->findByID($poolID)->from(TABLE_DEMANDPOOL)->fetch();
        $demandpool->files = $this->loadModel('file')->getByObject('demandpool', $poolID);

        $demandpool = $this->loadModel('file')->replaceImgURL($demandpool, 'background,overview,desc');

        return $demandpool;
    }

    /**
     * Create a demandpool.
     *
     * @access public
     * @return void
     */
    public function create()
    {
        /* 获取post数据并处理数据。*/
        $demandpool = fixer::input('post')
            ->add('status', 'normal')
            ->add('createdBy', $this->app->user->account)
            ->add('createdDate', helper::today())
            ->remove('uid,files,labels,contactListMenu')
            ->join('participant', ',')
            ->stripTags($this->config->demandpool->editor->create['id'], $this->config->allowedTags)
            ->get();

        /* 插入数据后，判断是否有误，然后更新code参数，并保存文件。*/
        $demandpool = $this->loadModel('file')->processImgURL($demandpool, $this->config->demandpool->editor->create['id'], $this->post->uid);
        $this->dao->insert(TABLE_DEMANDPOOL)->data($demandpool)
            ->autoCheck()
            ->batchCheck($this->config->demandpool->create->requiredFields, 'notempty')->exec();

        if(!dao::isError())
        {
            $poolID = $this->dao->lastInsertID();

            $this->loadModel('file')->updateObjectID($this->post->uid, $poolID, 'demandpool');
            $this->file->saveUpload('demandpool', $poolID);

            return $poolID;
        }

        return false;
    }

    /**
     * Update a demandpool.
     *
     * @access int $poolID
     * @access public
     * @return void
     */
    public function update($poolID)
    {
        /* 获取旧的需求意向数据，并处理post请求参数。*/
        $oldRequirement = $this->getByID($poolID);
        $demandpool = fixer::input('post')
            ->join('participant', ',')
            ->remove('uid,files,labels,comment,contactListMenu')
            ->stripTags($this->config->demandpool->editor->edit['id'], $this->config->allowedTags)
            ->get();

        /* 执行SQL，处理相关附件，并获取变动的字段进行返回。*/
        $demandpool = $this->loadModel('file')->processImgURL($demandpool, $this->config->demandpool->editor->edit['id'], $this->post->uid);
        $this->dao->update(TABLE_DEMANDPOOL)->data($demandpool)->autoCheck()
            ->batchCheck($this->config->demandpool->edit->requiredFields, 'notempty')
            ->where('id')->eq($poolID)
            ->exec();

        $this->loadModel('file')->updateObjectID($this->post->uid, $poolID, 'demandpool');
        $this->file->saveUpload('demandpool', $poolID);

        return common::createChanges($oldRequirement, $demandpool);
    }

    /**
     * Review a demandpool.
     *
     * @param  int    $poolID
     * @access public
     * @return void
     */
    public function close($poolID)
    {
        $oldRequirement = $this->dao->findById($poolID)->from(TABLE_DEMANDPOOL)->fetch();
        $demandpool = fixer::input('post')
            ->add('status', 'closed')
            ->remove('uid,comment')
            ->get();

        $this->dao->update(TABLE_DEMANDPOOL)->data($demandpool)->autoCheck()->where('id')->eq((int)$poolID)->exec();
        if(!dao::isError()) return common::createChanges($oldRequirement, $demandpool);
        return false;
    }

    public function setMenu($poolID)
    {
        $moduleName = $this->app->rawModule;
        $methodName = $this->app->rawMethod;

        $this->lang->switcherMenu = $this->getSwitcher($poolID, $moduleName, $methodName);

        $this->saveState($poolID, $this->getPairs());

        common::setMenuVars('demandpool', $poolID);
        return $poolID;
    }

    public function saveState($poolID = 0, $demandpools = array())
    {
        if($poolID == 0 and $this->cookie->lastDemandpool) $poolID = $this->cookie->lastDemandpool;
        if($poolID == 0 and (int)$this->session->demandpool == 0) $poolID = key($demandpools);
        if($poolID == 0) $poolID = key($demandpools);

        $this->session->set('demandpool', (int)$poolID, $this->app->tab);

        if(!isset($demandpools[$this->session->demandpool]))
        {
            if($poolID and strpos(",{$this->app->user->view->demandpools},", ",{$this->session->demandpool},") === false and !empty($demandpools))
            {
                $this->session->set('demandpool', key($demandpools), $this->app->tab);
                $this->accessDenied();
            }
        }

        return $this->session->demandpool;
    }

    public function getSwitcher($poolID, $currentModule, $currentMethod)
    {
        if($currentModule == 'demandpool' and $currentMethod == 'browse') return;

        $currentDemandpoolName = $this->lang->demandpool->common;
        if($poolID)
        {
            $currentDemandpool     = $this->getById($poolID);
            $currentDemandpoolName = $currentDemandpool->name;
        }

        if($this->app->viewType == 'mhtml' and $poolID)
        {
            $output  = $this->lang->demandpool->common . $this->lang->colon;
            $output .= "<a id='currentItem' href=\"javascript:showSearchMenu('demandpool', '$poolID', '$currentModule', '$currentMethod', '')\">{$currentDemandpoolName} <span class='icon-caret-down'></span></a><div id='currentItemDropMenu' class='hidden affix enter-from-bottom layer'></div>";
            return $output;
        }

        $dropMenuLink = helper::createLink('demandpool', 'ajaxGetDropMenu', "objectID=$poolID&module=$currentModule&method=$currentMethod");
        $output  = "<div class='btn-group header-btn' id='swapper'><button data-toggle='dropdown' type='button' class='btn' id='currentItem' title='{$currentDemandpoolName}'><span class='text'>{$currentDemandpoolName}</span> <span class='caret' style='margin-bottom: -1px'></span></button><div id='dropMenu' class='dropdown-menu search-list' data-ride='searchList' data-url='$dropMenuLink'>";
        $output .= '<div class="input-control search-box has-icon-left has-icon-right search-example"><input type="search" class="form-control search-input" /><label class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label><a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a></div>';
        $output .= "</div></div>";

        return $output;
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
        $this->config->demandpool->search['actionURL'] = $actionURL;
        $this->config->demandpool->search['queryID']   = $queryID;
        $this->config->demandpool->search['params']['product']['values'] = array('' => '') + $this->loadModel('product')->getPairs();
        $this->config->demandpool->search['params']['dept']['values']    = array('' => '') + $this->loadModel('dept')->getOptionMenu();

        $this->loadModel('search')->setSearchParams($this->config->demandpool->search);
    }

    public function printAssignedHtml($demandpool, $users)
    {
        $this->loadModel('task');
        $btnTextClass   = '';
        $assignedToText = zget($users, $demandpool->assignedTo);

        if(empty($demandpool->assignedTo))
        {
            $btnTextClass   = 'text-primary';
            $assignedToText = $this->lang->task->noAssigned;
        }
        if($demandpool->assignedTo == $this->app->user->account) $btnTextClass = 'text-red';

        $btnClass     = $demandpool->assignedTo == 'closed' ? ' disabled' : '';
        $btnClass     = "iframe btn btn-icon-left btn-sm {$btnClass}";
        $assignToLink = helper::createLink('demandpool', 'assignTo', "poolID=$demandpool->id", '', true);
        $assignToHtml = html::a($assignToLink, "<i class='icon icon-hand-right'></i> <span class='{$btnTextClass}'>{$assignedToText}</span>", '', "class='$btnClass'");

        echo !common::hasPriv('demandpool', 'assignTo', $demandpool) ? "<span style='padding-left: 21px' class='{$btnTextClass}'>{$assignedToText}</span>" : $assignToHtml;
    }

    /**
     * Project: demandpool ext
     * Method: isClickable
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:50
     * Desc: This is the code comment. This method is called isClickable.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param $demandpool
     * @param $action
     * @return bool
     */
    public static function isClickable($demandpool, $action)
    {
        global $app;
        /* 对操作转换成小写，根据状态判断当前操作是否允许高亮。*/
        $action = strtolower($action);

        if($action == 'delete') return ($app->user->account == $demandpool->createdBy) or ($app->user->account == $demandpool->owner);
        if($action == 'close')  return $demandpool->status != 'closed';

        return true;
    }

    /**
     * Access denied.
     *
     * @access public
     * @return mixed
     */
    public function accessDenied()
    {
        if(defined('TUTORIAL')) return true;

        echo(js::alert($this->lang->demandpool->accessDenied));
        $this->session->set('demandpool', '');

        return print(js::locate(helper::createLink('demandpool', 'browse')));
    }
}
