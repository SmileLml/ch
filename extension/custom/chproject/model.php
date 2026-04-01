<?php
/**
 * The model file of chproject module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     chproject
 * @version     $Id: model.php 5118 2013-07-12 07:41:41Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class chprojectModel extends model
{
    /**
     * Get ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return object
     */
    public function getByID($projectID)
    {
        return $this->dao->select('t1.*,t2.team as chteam')->from(TABLE_CHPROJECT)->alias('t1')
            ->leftJoin(TABLE_CHPROJECTTEAM)->alias('t2')->on('t1.id = t2.project')
            ->where('t1.id')->eq($projectID)
            ->fetch();
    }

    /**
     * Get ch project pairs.
     *
     * @access public
     * @return array
     */
    public function getPairs()
    {
        return $this->dao->select('id,name')->from(TABLE_CHPROJECT)->fetchPairs();
    }

    /**
     * Get execution stat data.
     *
     * @param  int        $teamID
     * @param  int        $intanceProjectID
     * @param  string     $browseType all|undone|wait|doing|suspended|closed|involved|bySearch|review
     * @param  string|int $param
     * @param  string     $orderBy
     * @param  object     $pager
     * @access public
     * @return array
     */
    public function getStatData($teamID = 0, $intanceProjectID = 0, $browseType = 'undone', $param = '', $orderBy = 'id_asc', $pager = null)
    {
        $this->loadModel('user');
        $executionIDList = [];
        if($intanceProjectID)
        {
            $executionIDList = $this->dao->select('ch')->from(TABLE_CHPROJECTINTANCES)->alias('t1')
                ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.zentao = t2.id')
                ->where('t2.project')->eq($intanceProjectID)
                ->fetchPairs();
        }

        //查看所有有权限的数据
        $projects = $this->getObtainPermissionIntances();

        $projectIDList = [];
        if(in_array($browseType, ['undone', 'wait', 'doing', 'suspended', 'closed'])) $projectIDList = $this->getIntanceByStatus($browseType, $executionIDList);

        $currentUser           = $this->app->user->account;
        $PMOUsers              = $this->user->getUsersByUserGroupName($this->lang->chproject->group->PMO);
        $QAUsers               = $this->user->getUsersByUserGroupName($this->lang->chproject->group->QA);
        $seniorExecutiveUsers  = $this->user->getUsersByUserGroupName($this->lang->chproject->group->seniorExecutive);

        $executions = $this->dao->select('t1.*')->from(TABLE_CHPROJECT)->alias('t1')
            ->leftJoin(TABLE_CHPROJECTTEAM)->alias('t2')->on('t1.id = t2.project')
            ->where('type')->in('sprint,stage,kanban')
            ->andWhere('t2.team')->eq($teamID)
            ->andWhere('deleted')->eq('0')
            ->andWhere('vision')->eq($this->config->vision)
            ->andWhere('multiple')->eq('1')
            ->andWhere('grade')->eq('1')
            ->beginIF(!$this->app->user->admin && !isset($seniorExecutiveUsers[$currentUser]) && !isset($PMOUsers[$currentUser]) && !isset($QAUsers[$currentUser]))->andWhere('t1.id')->in($projects)->fi()
            ->beginIF($intanceProjectID)->andWhere('t1.id')->in($executionIDList)->fi()
            ->beginIF(in_array($browseType, ['undone', 'wait', 'doing', 'suspended', 'closed']))->andWhere('t1.id')->in($projectIDList)->fi()
            ->beginIF($browseType == 'undone')->andWhere('status')->notIN('done,closed')->fi()
            ->beginIF($browseType == 'review')
            ->andWhere("FIND_IN_SET('{$this->app->user->account}', reviewers)")
            ->andWhere('reviewStatus')->eq('doing')
            ->fi()
            ->orderBy($orderBy)
            ->page($pager, 'id')
            ->fetchAll('id');

        /* Process executions. */
        foreach($executions as $execution) $execution = $this->processExecution($execution);

        return $executions;
    }

    /**
     * Get intance by status.
     *
     * @param  int    $status
     * @param  array  $executionIDList
     * @access public
     * @return array
     */
    public function getIntanceByStatus($status, $executionIDList = [])
    {
        $chprojects = $this->dao->select('*')->from(TABLE_CHPROJECTINTANCES)
            ->beginIF($executionIDList)->where('ch')->in($executionIDList)->fi()
            ->fetchGroup('ch', 'zentao');

        $projectIdList = [];
        foreach($chprojects as $projectID => $intances)
        {
            $intanceIdList = array_keys($intances);
            $chStatus      = $this->getIntanceStatus($intanceIdList);

            if($status != 'undone' && $status == $chStatus) $projectIdList[] = $projectID;
            if($status == 'undone' && $status != 'closed')  $projectIdList[] = $projectID;
        }

        return $projectIdList;
    }

    /**
     * Process execution data for browse include: status, progress, projectName, productName, end, delayed.
     *
     * @param  object $execution
     * @access private
     * @return mixed
     */
    private function processExecution($execution)
    {
        $intanceProjectIDList   = $this->getIntanceProjectPairs($execution->id, 'id,project');
        $intanceProjects        = $this->dao->select('id,name')->from(TABLE_PROJECT)->where('id')->in($intanceProjectIDList)->fetchPairs();
        $execution->projectName = implode(',', $intanceProjects);

        $intanceProductIDList   = $this->getIntanceProductPairs($execution->id);
        $intanceProducts        = $this->dao->select('id,name')->from(TABLE_PRODUCT)->where('id')->in($intanceProductIDList)->andWhere('shadow')->eq('0')->fetchPairs();
        $execution->productName = implode(',', $intanceProducts);

        /* Process the end time. */
        $execution->end = date(DT_DATE1, strtotime($execution->end));

        /* Judge whether the execution is delayed. */
        if($execution->status != 'done' and $execution->status != 'closed' and $execution->status != 'suspended')
        {
            $delay = helper::diffDate(helper::today(), $execution->end);
            if($delay > 0) $execution->delay = $delay;
        }

        $execution->intances = $this->getIntances($execution->id);
        $execution->progress = $this->getProgress($execution);
        $execution->status   = $this->getIntanceStatus($execution->intances);

        return $execution;
    }

    /**
     * Get intances of ch project.
     *
     * @param  int|array $projectID
     * @param  bool      $noProduct
     * @access public
     * @return array
     */
    public function getIntances($projectID, $noProduct = false)
    {
        $intances = $this->dao->select('zentao')->from(TABLE_CHPROJECTINTANCES)->alias('t1')
            ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.zentao = t2.id')
            ->where('ch')->in($projectID)
            ->andWhere('t2.deleted')->eq(0)
            ->fetchPairs();

        if($noProduct)
        {
            $intances = $this->dao->select('id')->from(TABLE_PROJECT)
                ->where('id')->in($intances)
                ->andWhere('hasProduct')->eq(1)
                ->fetchPairs();
        }

        return $intances;
    }

    /**
     * Get intance pairs of ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return array
     */
    public function getIntancePairs($projectID)
    {
        return $this->dao->select('zentao,t2.name')->from(TABLE_CHPROJECTINTANCES)->alias('t1')
            ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.zentao = t2.id')
            ->where('ch')->eq($projectID)
            ->fetchPairs();
    }

    /**
     * Get intance product pairs of ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return array
     */
    public function getIntanceProductPairs($projectID)
    {
        $intances = $this->getIntances($projectID);

        return $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->in($intances)->fetchPairs();
    }

    /**
     * Get intance project pairs of ch project.
     *
     * @param  int    $projectID
     * @param  string $fields
     * @access public
     * @return array
     */
    public function getIntanceProjectPairs($projectID, $fields)
    {
        $intances = $this->getIntances($projectID);

        return $this->dao->select($fields)->from(TABLE_PROJECT)->where('id')->in($intances)->fetchPairs();
    }

    /**
     * Get intances project options.
     *
     * @param  int    $projectID
     * @param  string $keyField   executionID|projectID|executionName|projectName|fullName
     * @param  string $valueField executionID|projectID|executionName|projectName|fullName
     * @access public
     * @return array
     */
    public function getIntancesProjectOptions($projectID, $keyField = 'executionID', $valueField = 'fullName', $intanceProjectID = 0)
    {
        $intances = $this->getIntances($projectID);

        return $this->dao->select("e.id as executionID, p.id as projectID, e.name as executionName, p.name as projectName, concat(p.name, '/', e.name) as fullName")
            ->from(TABLE_EXECUTION)->alias('e')
            ->leftJoin(TABLE_PROJECT)->alias('p')->on('e.parent = p.id')
            ->where('e.id')->in($intances)
            ->beginIF($intanceProjectID)->andWhere('e.project')->eq($intanceProjectID)->fi()
            ->fetchPairs($keyField, $valueField);
    }

    /**
     * Get module pairs.
     *
     * @param  int    $projectID
     * @param  string $showAllModule
     * @access public
     * @return array
     */
    public function getModulePairs($projectID, $showAllModule)
    {
        $intances            = $this->getIntances($projectID);
        $intanceProjectPairs = $this->getIntanceProjectPairs($projectID, 'id,parent');
        $projectPairs        = $this->loadModel('project')->getPairsByIdList($intanceProjectPairs);

        $this->loadModel('tree');

        $modules = [];
        foreach($intances as $intance)
        {
            $intanceProjectName = zget($projectPairs, zget($intanceProjectPairs, $intance));
            $intanceModules     = $this->tree->getTaskOptionMenu($intance, 0, 0, $showAllModule ? 'allModule' : '');

            foreach($intanceModules as &$module)
            {
                if(empty($intanceProjectName)) continue;
                if($module == '/')             continue;

                $module = $intanceProjectName . ' / ' . trim($module, '/');
            }

            $modules += $intanceModules;
        }

        return $modules;
    }

    /**
     * Get member pairs by project.
     *
     * @param  int    $projectID
     * @access public
     * @return array
     */
    public function getMemberPairsByProject($projectID)
    {
        $intances = $this->getIntances($projectID);

        return $this->dao->select('t1.account,t2.realname')->from(TABLE_TEAM)->alias('t1')
            ->leftJoin(TABLE_USER)->alias('t2')->on('t1.account = t2.account')
            ->where('t1.root')->in($intances)
            ->andWhere('t2.deleted')->eq('0')
            ->fetchPairs();
    }

    /**
     * Get tasks.
     *
     * @param  int    $productID
     * @param  int    $projectID
     * @param  int    $intanceProjectID
     * @param  string $browseType
     * @param  int    $queryID
     * @param  int    $moduleID
     * @param  string $sort
     * @param  object $pager
     * @access public
     * @return array
     */
    public function getTasks($productID, $projectID, $intanceProjectID = 0, $browseType, $queryID, $moduleID, $sort, $pager)
    {
        $intances = $this->getIntances($projectID);

        $this->loadModel('task');
        $this->loadModel('execution');

        /* Set modules and $browseType. */
        $modules = array();
        if($moduleID) $modules = $this->loadModel('tree')->getAllChildID($moduleID);
        if($browseType == 'bymodule' or $browseType == 'byproduct')
        {
            if(($this->session->taskBrowseType) and ($this->session->taskBrowseType != 'bysearch')) $browseType = $this->session->taskBrowseType;
        }

        /* Get tasks. */
        $tasks = array();
        if($browseType != "bysearch")
        {
            $queryStatus = $browseType == 'byexecution' ? 'all' : $browseType;
            if($queryStatus == 'unclosed')
            {
                $queryStatus = $this->lang->task->statusList;
                unset($queryStatus['closed']);
                $queryStatus = array_keys($queryStatus);
            }

            $tasks = $this->task->getExecutionTasks($intances, $productID, $queryStatus, $modules, $sort, $pager, $intanceProjectID);
        }
        else
        {
            if($queryID)
            {
                $query = $this->loadModel('search')->getQuery($queryID);
                if($query)
                {
                    $this->session->set('taskQuery', $query->sql);
                    $this->session->set('taskForm', $query->form);
                }
                else
                {
                    $this->session->set('taskQuery', ' 1 = 1');
                }
            }
            else
            {
                if($this->session->taskQuery == false) $this->session->set('taskQuery', ' 1 = 1');
            }

            if(strpos($this->session->taskQuery, "deleted =") === false) $this->session->set('taskQuery', $this->session->taskQuery . " AND deleted = '0'");

            $taskQuery = $this->session->taskQuery;
            /* Limit current execution when no execution. */
            if(strpos($taskQuery, "`execution` =") === false) $taskQuery = $taskQuery . " AND `execution` in ('" . implode("','", $intances) . "')";
            if($intanceProjectID) $taskQuery .= " AND project = '$intanceProjectID'";
            $taskQuery = str_replace("`execution` = 'all'", $executionQuery, $taskQuery); // Search all execution.
            $this->session->set('taskQueryCondition', $taskQuery, $this->app->tab);
            $this->session->set('taskOnlyCondition', true, $this->app->tab);

            $tasks = $this->execution->getSearchTasks($taskQuery, $pager, $sort);
        }

        return $tasks;
    }

    /*
     * Get project switcher.
     *
     * @param  int     $projectID
     * @param  string  $currentModule
     * @param  string  $currentMethod
     * @access public
     * @return string
     */
    public function getSwitcher($projectID, $currentModule, $currentMethod)
    {
        if($currentModule == 'chproject' and in_array($currentMethod,  array('index', 'all', 'batchedit', 'create'))) return;

        $currentProjectName = $this->lang->chproject->common;
        if($projectID)
        {
            $currentProject     = $this->getById($projectID);
            $currentProjectName = $currentProject->name;
        }

        if($this->app->viewType == 'mhtml' and $projectID)
        {
            $output  = $this->lang->chproject->common . $this->lang->colon;
            $output .= "<a id='currentItem' href=\"javascript:showSearchMenu('chproject', '$projectID', '$currentModule', '$currentMethod', '')\">{$currentProjectName} <span class='icon-caret-down'></span></a><div id='currentItemDropMenu' class='hidden affix enter-from-bottom layer'></div>";
            return $output;
        }

        $dropMenuLink = helper::createLink('chproject', 'ajaxGetDropMenu', "projectID=$projectID&module=$currentModule&method=$currentMethod");
        $output  = "<div class='btn-group header-btn' id='swapper'><button data-toggle='dropdown' type='button' class='btn' id='currentItem' title='{$currentProjectName}'><span class='text'>{$currentProjectName}</span> <span class='caret' style='margin-bottom: -1px'></span></button><div id='dropMenu' class='dropdown-menu search-list' data-ride='searchList' data-url='$dropMenuLink'>";
        $output .= '<div class="input-control search-box has-icon-left has-icon-right search-example"><input type="search" class="form-control search-input" /><label class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label><a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a></div>';
        $output .= "</div></div>";

        return $output;
    }

    /**
     * Get execution hours.
     *
     * @param  object $execution
     * @access public
     * @return object
     */
    public function getProgress($execution)
    {
        $progress = new stdClass();
        foreach($execution->intances as $intance)
        {
            $intanceExecution = $this->dao->select('estimate,`left`,consumed')->from(TABLE_EXECUTION)->where('id')->eq($intance)->fetch();

            $progress->estimate += $intanceExecution->estimate;
            $progress->left     += $intanceExecution->left;
            $progress->consumed += $intanceExecution->consumed;
        }

        $progress->percent = ($progress->consumed + $progress->left) ? $progress->consumed / ($progress->consumed + $progress->left) * 100 : 0;

        return $progress;
    }

    /**
     * Get execution status.
     *
     * @param  array  $intances
     * @access public
     * @return string
     */
    public function getIntanceStatus($intances)
    {
        $this->app->loadLang('execution');

        $statusList      = $this->dao->select('id,status')->from(TABLE_EXECUTION)->where('id')->in($intances)->fetchPairs();
        $statusCountList = array_count_values($statusList);
        $statusCount     = count($statusList);

        if(array_key_exists('wait',      $statusCountList) && $statusCountList['wait']      == $statusCount) return 'wait';
        if(array_key_exists('closed',    $statusCountList) && $statusCountList['closed']    == $statusCount) return 'closed';
        if(array_key_exists('suspended', $statusCountList) && $statusCountList['suspended'] == $statusCount) return 'suspended';

        if(array_key_exists('suspended', $statusCountList) && array_key_exists('wait', $statusCountList) && ($statusCountList['suspended'] + $statusCountList['wait'] == $statusCount))
        {
             return 'suspended';
        }

        return 'doing';
    }

    /**
     * Get products need to bind.
     *
     * @param  array $projectProducts
     * @param  array $products
     * @param  array $branches
     * @access public
     * @return array
     */
    public function getProductsToBind($projectID, $projectProducts, $products, $branches = [])
    {
        if(!$projectID || !$projectProducts || !$products) return [];

        $otherProducts = [];
        foreach($products as $index => $productID)
        {
            if(isset($branches[$index]))
            {
                foreach($branches[$index] as $branchID)
                {
                    $productIndex = "{$projectID}_{$productID}_{$branchID}";
                    if(!isset($projectProducts[$productIndex])) $otherProducts[] = "{$productID}_{$branchID}";
                }
            }
            else
            {
                $productIndex = "{$projectID}_{$productID}_0";
                if(!isset($projectProducts[$productIndex])) $otherProducts[] = $productID;
            }
        }

        return $otherProducts;
    }

    /**
     * Create the link from module,method.
     *
     * @param  string $module
     * @param  string $method
     * @param  int    $projectID
     * @access public
     * @return string
     */
    public function getLink($module, $method, $projectID)
    {
        if($module == 'task' && in_array($method, array('view', 'edit', 'batchedit', 'batchcreate')))
        {
            $module = 'chproject';
            $method = 'task';
        }
        if($module == 'testcase' and ($method == 'view' or $method == 'edit' or $method == 'batchedit'))
        {
            $module = 'chproject';
            $method = 'testcase';
        }
        if($module == 'execution' and ($method == 'relation' or $method == 'maintainrelation'))
        {
            $module = 'chproject';
            $method = 'gantt';
        }
        if($module == 'testtask')
        {
            $module = 'chproject';
            $method = 'testtask';
        }
        if($module == 'build' and ($method == 'edit' or $method == 'view'))
        {
            $module = 'execution';
            $method = 'build';
        }
        if($module == 'story' && (!in_array($method, array('create', 'review'))))
        {
            $module = 'execution';
            $method = 'story';
        }
        if($module == 'product' and $method == 'showerrornone')
        {
            $module = 'execution';
            $method = 'task';
        }

        $link = helper::createLink($module, $method, "projectID=%s") . '#app=chteam';

        if($module == 'execution' and $method == 'storyview')
        {
            $link = helper::createLink($module, 'story', "projectID=%s");
        }
        elseif($module == 'execution' and ($method == 'index' or $method == 'all'))
        {
            $link = helper::createLink($module, 'task', "projectID=%s");
        }
        elseif($module == 'testcase' && $method == 'create')
        {
            $link = helper::createLink('testcase', 'create', "productID=0&branch=0&moduleID=0&from=&param=&storyID=0&extras=&projectID=%s") . '#app=chteam';
        }
        elseif($module == 'bug' && strpos(',view,edit,', ",$method,") !== false)
        {
            $link = helper::createLink('chproject', 'bug', "projectID=%s&intanceProjectID=0&productID=0&branch=all&orderBy=&build=&type=bysearch&param=myQueryID") . '#app=chteam';
        }
        elseif($module == 'bug' && $method == 'create')
        {
            $link = helper::createLink($module, $method, "productID=0&branch=0&extra=&projectID=%s") . '#app=chteam';
        }
        elseif(in_array($module, array('bug', 'case', 'testtask', 'testreport')) and strpos(',view,edit,', ",$method,") !== false)
        {
            $link = helper::createLink('execution', $module, "projectID=%s");
        }
        elseif($module == 'repo' && $method == 'review')
        {
            $link = helper::createLink('repo', 'review', "repoID=0&browseType=all&projectID=%s") . '#app=execution';
        }
        elseif($module == 'mr')
        {
            $link = helper::createLink('mr', 'browse', "repoID=0&mode=status&param=opened&objectID=%s") . '#app=execution';
        }
        elseif($module == 'repo')
        {
            $link = helper::createLink('repo', 'browse', "repoID=0&branchID=&projectID=%s") . '#app=execution';
        }
        elseif($module == 'doc')
        {
            $link = helper::createLink('doc', $method, "type=execution&objectID=%s&from=execution");
        }
        elseif(in_array($module, array('issue', 'risk', 'opportunity', 'pssp', 'auditplan', 'nc', 'meeting')))
        {
            $link = helper::createLink($module, 'browse', "projectID=%s&from=execution");
        }
        elseif($module == 'testreport' and $method == 'create')
        {
            $link = helper::createLink('execution', 'testtask', "projectID=%s");
        }
        elseif($module == 'task' && $method == 'create')
        {
            $link = helper::createLink('task', 'create', "executionID=0&storyID=0&moduleID=0&taskID=0&todoID=0&extra=&bugID=0&chProjectID=%s") . '#app=chteam';
        }
        elseif($module == 'story' && $method == 'create')
        {
            $productIdList = $this->getIntanceProductPairs($projectID);
            $productID     = key($productIdList);

            $link = helper::createLink('story', 'create', "productID=$productID&branch=0&moduleID=0&story=0&execution=0&bugID=0&planID=0&todoID=0&extra=&storyType=story&chproject=%s") . '#app=chteam';
        }
        elseif($module == 'story' && $method == 'review')
        {
            $link = helper::createLink('chproject', 'story', "projectID=%s");
        }
        elseif($module == 'execution' && in_array($method, array('linkstory', 'story')))
        {
            $link = helper::createLink('chproject', 'story', "projectID=%s");
        }
        elseif($module == 'chproject' && $method == 'bug')
        {
            $link = helper::createLink('chproject', 'bug', "projectID=%s&intanceProjectID=0&productID=0&branch=all&orderBy=&build=&type=bysearch&param=myQueryID") . '#app=chteam';
        }

        return $link;
    }

    /**
     * Get branches by project id.
     *
     * @param  int    $projectID
     * @access public
     * @return array
     */
    public function getBranchesByProject($projectID)
    {
        $intanceProjects = $this->getIntanceProjectPairs($projectID, 'project,project');

        return $this->dao->select('*')->from(TABLE_PROJECTPRODUCT)
            ->where('project')->in($intanceProjects)
            ->fetchGroup('product', 'branch');
    }

    /**
     * Get team members pairs by project.
     *
     * @param  int    $objectID
     * @param  string $type
     * @access public
     * @return array
     */
    public function getProjectTeam($projectID, $type = 'project')
    {
        $intances = $this->getIntances($projectID);

        $users = $this->dao->select("t2.id, t2.account, t2.realname")->from(TABLE_TEAM)->alias('t1')
            ->leftJoin(TABLE_USER)->alias('t2')->on('t1.account = t2.account')
            ->where('t1.root')->in($intances)
            ->andWhere('t1.type')->eq($type)
            ->andWhere('t2.deleted')->eq(0)
            ->fetchAll('account');

        if(!$users) return array('' => '');

        foreach($users as $account => $user)
        {
            $firstLetter = ucfirst(substr($user->account, 0, 1)) . ':';
            if(!empty($this->config->isINT)) $firstLetter = '';
            $users[$account] =  $firstLetter . ($user->realname ? $user->realname : $user->account);
        }

        return array('' => '') + $users;
    }

    /**
     * Get linked branch list used to edit ch project.
     *
     * @param  array  $linkedProducts
     * @param  array  $branches
     * @param  array  $plans
     * @param  array  $executionStories
     * @access public
     * @return array
     */
    public function getLinkedBranchList($linkedProducts, $branches, $plans, $executionStories)
    {
        $productPlans         = [0 => ''];
        $linkedBranches       = [];
        $linkedBranchList     = [];
        $unmodifiableProducts = [];
        $unmodifiableBranches = [];
        $linkedStoryIdList    = [];

        foreach($linkedProducts as $productID => $linkedProduct)
        {
            if(!isset($allProducts[$productID])) $allProducts[$productID] = $linkedProduct->deleted ? $linkedProduct->name . "({$this->lang->product->deleted})" : $linkedProduct->name;
            $productPlans[$productID] = [];

            foreach($branches[$productID] as $branchID => $branch)
            {
                $productPlans[$productID] += isset($plans[$productID][$branchID]) ? $plans[$productID][$branchID] : array();

                $linkedBranchList[$branchID]           = $branchID;
                $linkedBranches[$productID][$branchID] = $branchID;
                if($branchID != BRANCH_MAIN and isset($plans[$productID][BRANCH_MAIN])) $productPlans[$productID] += $plans[$productID][BRANCH_MAIN];
                if(!empty($executionStories[$productID][$branchID]))
                {
                    array_push($unmodifiableProducts, $productID);
                    array_push($unmodifiableBranches, $branchID);
                    $linkedStoryIdList[$productID][$branchID] = $executionStories[$productID][$branchID]->storyIDList;
                }
            }
        }

        return [$productPlans, $linkedBranchList, $linkedBranches, $unmodifiableProducts, $unmodifiableBranches, $linkedStoryIdList];
    }

    /**
     * Get deleted tips.
     *
     * @param  array  $intances
     * @access public
     * @return string
     */
    public function getDeletedTips($intances)
    {
        /* Get the number of unfinished tasks and unresolved bugs. */
        $unfinishedTasks = $this->dao->select('COUNT(id) AS count')->from(TABLE_TASK)
            ->where('execution')->in($intances)
            ->andWhere('deleted')->eq(0)
            ->andWhere('status')->in('wait,doing,pause')
            ->fetch();

        $unresolvedBugs = $this->dao->select('COUNT(id) AS count')->from(TABLE_BUG)
            ->where('execution')->in($intances)
            ->andWhere('deleted')->eq(0)
            ->andWhere('status')->eq('active')
            ->fetch();

        /* Set prompt information. */
        $tips = '';
        if($unfinishedTasks->count) $tips  = sprintf($this->lang->execution->unfinishedTask, $unfinishedTasks->count);
        if($unresolvedBugs->count)  $tips .= sprintf($this->lang->execution->unresolvedBug,  $unresolvedBugs->count);
        if($tips)                   $tips  = $this->lang->execution->unfinishedExecution . $tips;

        return $tips;
    }

    /**
     * Get obtain permission intances.
     *
     * @access public
     * @return array
     */
    public function getObtainPermissionIntances()
    {
        $intances = $this->loadModel('execution')->fetchPairs();

        return $this->dao->select('ch')->from(TABLE_CHPROJECTINTANCES)->where('zentao')->in(array_keys($intances))->fetchPairs();
    }

    /**
     * Get kanban column ID.
     *
     * @param  int    $fromLaneID
     * @param  int    $cardID
     * @param  int    $fromColID
     * @access public
     * @return mixed
     */
    public function getKanbanColumnID($fromLaneID, $cardID, $fromColID)
    {
        $kanbanLane = $this->dao->select('*')->from(TABLE_KANBANLANE)->where('id')->eq($fromLaneID)->fetch();
        $kanbanCol  = $this->dao->select('*')->from(TABLE_KANBANCOLUMN)->where('id')->eq($fromColID)->fetch();

        $colIdList = $this->dao->select('`column`')->from(TABLE_KANBANCELL)
            ->where('lane')->eq($kanbanLane->id)
            ->andWhere('kanban')->eq($kanbanLane->execution)
            ->andWhere('type')->eq($kanbanLane->type)
            ->fetchPairs();

        $colID = $this->dao->select('id')->from(TABLE_KANBANCOLUMN)
            ->where('id')->in($colIdList)
            ->andWhere('deleted')->eq(0)
            ->andWhere('type')->eq($kanbanCol->type)
            ->fetch('id');

        return $colID;
    }

    /**
     * Create a ch project.
     *
     * @param  int    $teamID
     * @access public
     * @return bool|int
     */
    public function create($teamID)
    {
        if(empty($_POST['project']))
        {
            dao::$errors['message'][] = $this->lang->execution->projectNotEmpty;
            return false;
        }

        $this->checkProduct($_POST['project'], $_POST['products']);
        if(dao::isError()) return false;

        $this->checkBeginAndEndDate($_POST['project'], $_POST['begin'], $_POST['end']);
        if(dao::isError()) return false;

        if($_POST['products'])
        {
            $this->app->loadLang('project');
            $multipleProducts = $this->loadModel('product')->getMultiBranchPairs();
            foreach($_POST['products'] as $index => $productID)
            {
                if(isset($multipleProducts[$productID]) and !isset($_POST['branch'][$index]))
                {
                    dao::$errors[] = $this->lang->project->emptyBranch;
                    return false;
                }
            }
        }

        /* Judge workdays is legitimate. */
        $workdays = helper::diffDate($_POST['end'], $_POST['begin']) + 1;
        if(isset($_POST['days']) and $_POST['days'] > $workdays)
        {
            dao::$errors['days'] = sprintf($this->lang->project->workdaysExceed, $workdays);
            return false;
        }

        /* Get the data from the post. */
        $sprint = fixer::input('post')
            ->setDefault('status', 'wait')
            ->setDefault('openedBy', $this->app->user->account)
            ->setDefault('openedDate', helper::now())
            ->setDefault('openedVersion', $this->config->version)
            ->setDefault('lastEditedBy', $this->app->user->account)
            ->setDefault('lastEditedDate', helper::now())
            ->setDefault('days', '0')
            ->setDefault('grade', '1')
            ->setDefault('team', $this->post->name)
            ->setIF($this->post->parent, 'parent', $this->post->parent)
            ->setIF($this->post->heightType == 'auto', 'displayCards', 0)
            ->setIF(!isset($_POST['whitelist']), 'whitelist', '')
            ->setIF($this->post->acl == 'open', 'whitelist', '')
            ->join('whitelist', ',')
            ->setDefault('type', 'sprint') /* Determine whether to add a sprint or a stage according to the model of the execution. 执行模型只有融合敏捷 */
            ->stripTags($this->config->chproject->editor->create['id'], $this->config->allowedTags)
            ->remove('project, products, workDays, delta, branch, uid, plans, teams, teamMembers, contactListMenu, heightType')
            ->get();

        /* Set planDuration and realDuration. */
        if($this->config->edition == 'max' or $this->config->edition == 'ipd')
        {
            $this->loadModel('programplan');
            $sprint->planDuration = $this->programplan->getDuration($sprint->begin, $sprint->end);
            if(!empty($sprint->realBegan) and !empty($sprint->realEnd)) $sprint->realDuration = $this->programplan->getDuration($sprint->realBegan, $sprint->realEnd);
        }

        $sprint = $this->loadModel('file')->processImgURL($sprint, $this->config->chproject->editor->create['id'], $this->post->uid);

        $this->lang->error->unique = $this->lang->error->repeat;
        $this->dao->insert(TABLE_CHPROJECT)->data($sprint)
            ->autoCheck($skipFields = 'begin,end')
            ->batchcheck($this->config->chproject->create->requiredFields, 'notempty')
            ->checkIF(!empty($sprint->name), 'name', 'unique', "`type` in ('sprint', 'stage', 'kanban') and `deleted` = '0'")
            ->checkIF(!empty($sprint->code), 'code', 'unique', "`type` in ('sprint', 'stage', 'kanban') and `deleted` = '0'")
            ->checkIF($sprint->begin != '', 'begin', 'date')
            ->checkIF($sprint->end != '', 'end', 'date')
            ->checkIF($sprint->end != '', 'end', 'ge', $sprint->begin)
            ->checkFlow()
            ->exec();

        /* Add the creater to the team. */
        if(!dao::isError())
        {
            $projectID = $this->dao->lastInsertId();
            $this->createIntances($projectID);

            $projectTeam = new stdClass();
            $projectTeam->project = $projectID;
            $projectTeam->team    = $teamID;
            $this->dao->insert(TABLE_CHPROJECTTEAM)->data($projectTeam)->exec();

            /* Save order. */
            $this->dao->update(TABLE_CHPROJECT)->set('`order`')->eq($projectID * 5)->where('id')->eq($projectID)->exec();
            $this->file->updateObjectID($this->post->uid, $projectID, 'chproject');

            //$whitelist = explode(',', $sprint->whitelist);
            //$this->loadModel('personnel')->updateWhitelist($whitelist, 'sprint', $executionID);
            //if($sprint->acl != 'open') $this->updateUserView($executionID);

            //if(!dao::isError()) $this->loadModel('score')->create('program', 'createguide', $executionID);
            return $projectID;
        }
    }

    /**
     * Create intances of ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return viod
     */
    public function createIntances($projectID)
    {
        $this->loadModel('project');
        $this->loadModel('execution');

        /* Get the data from the post. */
        $sprint = fixer::input('post')
            ->setDefault('status', 'wait')
            ->setDefault('openedBy', $this->app->user->account)
            ->setDefault('openedDate', helper::now())
            ->setDefault('openedVersion', $this->config->version)
            ->setDefault('lastEditedBy', $this->app->user->account)
            ->setDefault('lastEditedDate', helper::now())
            ->setDefault('days', '0')
            ->setDefault('team', $this->post->name)
            ->cleanINT('project')
            ->setIF($this->post->parent, 'parent', $this->post->parent)
            ->setIF($this->post->heightType == 'auto', 'displayCards', 0)
            ->setIF(!isset($_POST['whitelist']), 'whitelist', '')
            ->setIF($this->post->acl == 'open', 'whitelist', '')
            ->join('whitelist', ',')
            ->setDefault('type', 'sprint') /* Determine whether to add a sprint or a stage according to the model of the execution. 执行模型只有融合敏捷 */
            ->stripTags($this->config->execution->editor->create['id'], $this->config->allowedTags)
            ->remove('newProject, project, products, workDays, delta, branch, uid, plans, teams, teamMembers, contactListMenu, heightType')
            ->get();

        /* Set planDuration and realDuration. */
        if($this->config->edition == 'max' or $this->config->edition == 'ipd')
        {
            $this->loadModel('programplan');
            $sprint->planDuration = $this->programplan->getDuration($sprint->begin, $sprint->end);
            if(!empty($sprint->realBegan) and !empty($sprint->realEnd)) $sprint->realDuration = $this->programplan->getDuration($sprint->realBegan, $sprint->realEnd);
        }

        $sprint = $this->loadModel('file')->processImgURL($sprint, $this->config->execution->editor->create['id'], $this->post->uid);
        $projectProducts = $this->dao->select("concat(project, '_', product, '_', branch) as `index`")->from(TABLE_PROJECTPRODUCT)->where('project')->in($_POST['project'])->fetchPairs();

        foreach($_POST['project'] as $intanceProjectID)
        {
            if(empty($intanceProjectID)) continue;

            $project = $this->project->getByID($intanceProjectID);
            if(!$project) continue;

            $sprint->parent     = $intanceProjectID;
            $sprint->project    = $intanceProjectID;
            $sprint->hasProduct = $project->hasProduct;

            $this->dao->insert(TABLE_EXECUTION)->data($sprint)->exec();

            /* Add the creater to the team. */
            if(!dao::isError())
            {
                $executionID   = $this->dao->lastInsertId();
                $today         = helper::today();
                $teamMembers   = [];

                /* 将表单中的产品关联到项目，项目型项目不需要关联 */
                if($project->hasProduct == 1)
                {
                    $_POST['otherProducts'] = $this->getProductsToBind($intanceProjectID, $projectProducts, $_POST['products'], $_POST['branch']);
                    if($_POST['otherProducts']) $this->project->updateProducts($intanceProjectID);
                    unset($_POST['otherProducts']);
                }

                $projectIntances = new stdClass();
                $projectIntances->zentao = $executionID;
                $projectIntances->ch     = $projectID;
                $this->dao->insert(TABLE_CHPROJECTINTANCES)->data($projectIntances)->exec();

                /* Save order. */
                $this->dao->update(TABLE_EXECUTION)->set('`order`')->eq($executionID * 5)->where('id')->eq($executionID)->exec();
                $this->file->updateObjectID($this->post->uid, $executionID, 'execution');

                /* Update the path. */
                $this->execution->setTreePath($executionID);

                /* 当为项目型项目时，将迭代关联到项目绑定的影子产品上，并删除表单中的产品 */
                if($project->hasProduct == 0)
                {
                    $_POST['products']['shadow'] = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($intanceProjectID)->fetch('product');

                    $this->execution->updateProducts($executionID);

                    $this->dao->delete()->from(TABLE_PROJECTPRODUCT)->where('project')->eq($executionID)->andWhere('product')->ne($_POST['products']['shadow'])->exec();

                    unset($_POST['products']['shadow']);
                }
                else
                {
                    $this->execution->updateProducts($executionID);
                }

                /* Set team of execution. */
                $members = isset($_POST['teamMembers']) ? $_POST['teamMembers'] : array();
                array_push($members, $sprint->PO, $sprint->QD, $sprint->PM, $sprint->RD, $sprint->openedBy);
                $members = array_unique($members);
                $roles   = $this->loadModel('user')->getUserRoles(array_values($members));
                foreach($members as $account)
                {
                    if(empty($account)) continue;

                    $member = new stdClass();
                    $member->root    = $executionID;
                    $member->type    = 'execution';
                    $member->account = $account;
                    $member->role    = zget($roles, $account, '');
                    $member->join    = $today;
                    $member->days    = $sprint->days;
                    $member->hours   = $this->config->execution->defaultWorkhours;
                    $this->dao->insert(TABLE_TEAM)->data($member)->exec();
                    $teamMembers[$account] = $member;
                }
                $this->execution->addProjectMembers($sprint->project, $teamMembers);

                /* Create doc lib. */
                $this->app->loadLang('doc');
                $lib = new stdclass();
                $lib->project   = $intanceProjectID;
                $lib->execution = $executionID;
                $lib->name      = $this->lang->doclib->main['execution'];
                $lib->type      = 'execution';
                $lib->main      = '1';
                $lib->acl       = 'default';
                $lib->addedBy   = $this->app->user->account;
                $lib->addedDate = helper::now();
                $this->dao->insert(TABLE_DOCLIB)->data($lib)->exec();

                $whitelist = explode(',', $sprint->whitelist);
                $this->loadModel('personnel')->updateWhitelist($whitelist, 'sprint', $executionID);
                if($sprint->acl != 'open') $this->execution->updateUserView($executionID);

                if(!dao::isError()) $this->loadModel('score')->create('program', 'createguide', $executionID);

                $comment = $project->hasProduct ? join(',', $_POST['products']) : '';
                $this->loadModel('action')->create('execution', $executionID, 'opened', '', $comment);

                $this->loadModel('programplan')->computeProgress($executionID, 'create');
            }
        }
    }

    /**
     * Update a ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return array|bool
     */
    public function update($projectID)
    {
        /* Convert executionID format and get oldExecution. */
        $projectID            = (int)$projectID;
        $oldProject           = $this->dao->findById($projectID)->from(TABLE_CHPROJECT)->fetch();
        $intanceProjectIdList = $this->getIntanceProjectPairs($projectID, 'project,project');

        $this->checkProduct($intanceProjectIdList, $_POST['products']);
        if(dao::isError()) return false;

        $this->checkBeginAndEndDate($intanceProjectIdList, $_POST['begin'], $_POST['end']);
        if(dao::isError()) return false;

        /* Judgment of required items. */
        if($this->post->code == '' and isset($this->config->setCode) and $this->config->setCode == 1)
        {
            dao::$errors['code'] = sprintf($this->lang->error->notempty, $this->lang->execution->code);
            return false;
        }

        /* Judge workdays is legitimate. */
        $this->app->loadLang('project');
        $workdays = helper::diffDate($_POST['end'], $_POST['begin']) + 1;
        if(isset($_POST['days']) and $_POST['days'] > $workdays)
        {
            dao::$errors['days'] = sprintf($this->lang->project->workdaysExceed, $workdays);
            return false;
        }

        if($_POST['products'])
        {
            $this->app->loadLang('project');
            $multipleProducts = $this->loadModel('product')->getMultiBranchPairs();
            if(isset($_POST['branch']) and is_string($_POST['branch']) !== false) $_POST['branch'] = json_decode($_POST['branch'], true);
            foreach($_POST['products'] as $index => $productID)
            {
                if(isset($multipleProducts[$productID]) and !isset($_POST['branch'][$index]))
                {
                    dao::$errors[] = $this->lang->project->emptyBranch;
                    return false;
                }
            }
        }

        /* Get the data from the post. */
        $project = fixer::input('post')
            ->add('id', $projectID)
            ->setDefault('lastEditedBy', $this->app->user->account)
            ->setDefault('lastEditedDate', helper::now())
            ->setIF($this->post->heightType == 'auto', 'displayCards', 0)
            ->setIF(helper::isZeroDate($this->post->begin), 'begin', '')
            ->setIF(helper::isZeroDate($this->post->end), 'end', '')
            ->setIF(!isset($_POST['whitelist']), 'whitelist', '')
            ->setIF($this->post->status == 'closed' and $oldProject->status != 'closed', 'closedDate', helper::now())
            ->setIF($this->post->status == 'suspended' and $oldProject->status != 'suspended', 'suspendedDate', helper::today())
            ->setDefault('days', '0')
            ->cleanINT('project')
            ->setDefault('team', $this->post->name)
            ->join('whitelist', ',')
            ->stripTags($this->config->execution->editor->edit['id'], $this->config->allowedTags)
            ->remove('newProject, project, products, branch, uid, plans, syncStories, contactListMenu, teamMembers, heightType')
            ->get();

        //if(in_array($project->status, array('closed', 'suspended'))) $this->computeBurn($executionID);

        if(dao::isError()) return false;

        /* Child stage inherits parent stage permissions. */
        if(!isset($project->acl)) $project->acl = $oldProject->acl;

        $project = $this->loadModel('file')->processImgURL($project, $this->config->chproject->editor->edit['id'], $this->post->uid);

        /* Check the workload format and total. */
        if(!empty($oldProject->percent) and isset($this->config->setPercent) and $this->config->setPercent == 1) $this->checkWorkload('update', $project->percent, $oldProject);

        /* Set planDuration and realDuration. */
        if($this->config->edition == 'max' or $this->config->edition == 'ipd')
        {
            $project->planDuration = $this->loadModel('programplan')->getDuration($project->begin, $project->end);
            if(!empty($project->realBegan) and !empty($project->realEnd)) $project->realDuration = $this->programplan->getDuration($project->realBegan, $project->realEnd);
        }

        /* Redefines the language entries for the fields in the project table. */
        foreach(explode(',', $this->config->execution->edit->requiredFields) as $field)
        {
            if(isset($this->lang->execution->$field)) $this->lang->project->$field = $this->lang->execution->$field;
        }

        /* Update data. */
        $this->lang->error->unique = $this->lang->error->repeat;
        $projectProject = isset($project->project) ? (int)$project->project : $oldProject->project;
        $this->dao->update(TABLE_CHPROJECT)->data($project)
            ->autoCheck($skipFields = 'begin,end')
            ->batchcheck($this->config->execution->edit->requiredFields, 'notempty')
            ->checkIF($project->begin != '', 'begin', 'date')
            ->checkIF($project->end != '', 'end', 'date')
            ->checkIF($project->end != '', 'end', 'ge', $project->begin)
            ->checkIF(!empty($project->name), 'name', 'unique', "id != $projectID and `deleted` = '0'")
            ->checkIF(!empty($project->code), 'code', 'unique', "id != $projectID and `deleted` = '0'")
            ->checkFlow()
            ->where('id')->eq($projectID)
            ->exec();

        if(dao::isError()) return false;

        if(!dao::isError())
        {
            $this->updateIntances($projectID);

            if($_POST['newProject'])
            {
                $_POST['project'] = $_POST['newProject'];
                $this->createIntances($projectID);
            }

            $this->file->updateObjectID($this->post->uid, $projectID, 'chproject');
            return common::createChanges($oldProject, $project);
        }
    }

    /**
     * Update intances of ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return array|bool
     */
    public function updateIntances($projectID)
    {
        $this->loadModel('user');
        $this->loadModel('execution');
        $this->loadModel('project');

        $intances = $this->getIntances($projectID);
        foreach($intances as $intance)
        {
            $oldExecution = $this->dao->findById($intance)->from(TABLE_EXECUTION)->fetch();

            /* Get the data from the post. */
            $execution = fixer::input('post')
                ->add('id', $intance)
                ->setDefault('lastEditedBy', $this->app->user->account)
                ->setDefault('lastEditedDate', helper::now())
                ->setIF($this->post->heightType == 'auto', 'displayCards', 0)
                ->setIF(helper::isZeroDate($this->post->begin), 'begin', '')
                ->setIF(helper::isZeroDate($this->post->end), 'end', '')
                ->setIF(!isset($_POST['whitelist']), 'whitelist', '')
                ->setIF($this->post->status == 'closed' and $oldExecution->status != 'closed', 'closedDate', helper::now())
                ->setIF($this->post->status == 'suspended' and $oldExecution->status != 'suspended', 'suspendedDate', helper::today())
                ->setDefault('days', '0')
                ->cleanINT('project')
                ->setDefault('team', $this->post->name)
                ->join('whitelist', ',')
                ->stripTags($this->config->execution->editor->edit['id'], $this->config->allowedTags)
                ->remove('newProject, products, branch, uid, plans, syncStories, contactListMenu, teamMembers, heightType')
                ->get();

            if(in_array($execution->status, array('closed', 'suspended'))) $this->computeBurn($intance);

            if(dao::isError()) return false;

            /* Child stage inherits parent stage permissions. */
            if(!isset($execution->acl)) $execution->acl = $oldExecution->acl;

            $execution = $this->loadModel('file')->processImgURL($execution, $this->config->execution->editor->edit['id'], $this->post->uid);

            /* Check the workload format and total. */
            if(!empty($execution->percent) and isset($this->config->setPercent) and $this->config->setPercent == 1) $this->checkWorkload('update', $execution->percent, $oldExecution);

            /* Set planDuration and realDuration. */
            if($this->config->edition == 'max' or $this->config->edition == 'ipd')
            {
                $execution->planDuration = $this->loadModel('programplan')->getDuration($execution->begin, $execution->end);
                if(!empty($execution->realBegan) and !empty($execution->realEnd)) $execution->realDuration = $this->programplan->getDuration($execution->realBegan, $execution->realEnd);
            }

            /* Redefines the language entries for the fields in the project table. */
            foreach(explode(',', $this->config->execution->edit->requiredFields) as $field)
            {
                if(isset($this->lang->execution->$field)) $this->lang->project->$field = $this->lang->execution->$field;
            }

            $relatedExecutionsID = $this->execution->getRelatedExecutions($intance);
            $relatedExecutionsID = !empty($relatedExecutionsID) ? implode(',', array_keys($relatedExecutionsID)) : '0';

            /* Update data. */
            $this->lang->error->unique = $this->lang->error->repeat;
            $executionProject = isset($execution->project) ? (int)$execution->project : $oldExecution->project;
            $this->dao->update(TABLE_EXECUTION)->data($execution)->where('id')->eq($intance)->exec();

            if(dao::isError()) return false;

            if(isset($_POST['parent'])) $this->loadModel('programplan')->setTreePath($intance);

            /* Get team and language item. */
            $team    = $this->user->getTeamMemberPairs($intance, 'execution');
            $members = isset($_POST['teamMembers']) ? $_POST['teamMembers'] : array();
            array_push($members, $execution->PO, $execution->QD, $execution->PM, $execution->RD);
            $members = array_unique($members);
            $roles   = $this->user->getUserRoles(array_values($members));

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

                $changedAccounts[$account]  = $account;
                $teamMembers[$account] = $member;
            }

            $this->dao->delete()->from(TABLE_TEAM)
                ->where('root')->eq((int)$intance)
                ->andWhere('type')->eq('execution')
                ->andWhere('account')->in(array_keys($team))
                ->andWhere('account')->notin(array_values($members))
                ->andWhere('account')->ne($oldExecution->openedBy)
                ->exec();

            $this->execution->addProjectMembers($oldExecution->project, $teamMembers);

            $whitelist = explode(',', $execution->whitelist);
            $this->loadModel('personnel')->updateWhitelist($whitelist, 'sprint', $intance);

            /* Fix bug#3074, Update views for team members. */
            if($execution->acl != 'open') $this->execution->updateUserView($intance, 'sprint', $changedAccounts);

            if(!dao::isError())
            {
                if($oldExecution->hasProduct)
                {
                    $this->execution->updateProducts($intance);

                    $projectProducts = $this->dao->select("concat(project, '_', product, '_', branch) as `index`")->from(TABLE_PROJECTPRODUCT)->where('project')->eq($oldExecution->project)->fetchPairs();
                    /* Create project product */
                    if($_POST['products'])
                    {
                        $_POST['otherProducts'] = $this->getProductsToBind($oldExecution->project, $projectProducts, $_POST['products'], $_POST['branch']);
                        if($_POST['otherProducts']) $this->project->updateProducts($oldExecution->project);
                        unset($_POST['otherProducts']);
                    }
                }

                $this->file->updateObjectID($this->post->uid, $intance, 'execution');
                $changes = common::createChanges($oldExecution, $execution);

                if($changes)
                {
                    $actionID = $this->loadModel('action')->create('execution', $intance, 'edited');
                    $this->action->logHistory($actionID, $changes);
                }
            }
        }
    }

    /**
     * Check begin and end date.
     *
     * @param  array  $projectIdList
     * @param  string $begin
     * @param  string $end
     * @param  object $execution
     * @access public
     * @return void
     */
    public function checkBeginAndEndDate($projectIdList, $begin, $end, $execution = null)
    {
        $projects = $this->dao->select('begin,end')->from(TABLE_PROJECT)->where('id')->in($projectIdList)->fetchAll();
        if(empty($projects)) return;

        $project = new stdclass();
        $project->begin = max(array_column($projects, 'begin'));
        $project->end   = min(array_column($projects, 'end'));

        $isValidDate = ($begin >= $project->begin) && ($end <= $project->end);
        if(!$isValidDate) dao::$errors['end'] = sprintf($this->lang->chproject->termError, $project->begin, $project->end);
    }

    /**
     * Check if the product is empty
     *
     * @param  array  $intanceProjectIdList
     * @param  array  $products
     * @access public
     * @return bool
     */
    public function checkProduct($intanceProjectIdList, $products)
    {
        $hasProduct = $this->dao->select('count(id) as hasProduct')->from(TABLE_PROJECT)->where('id')->in($intanceProjectIdList)->andWhere('hasProduct')->eq(1)->fetch('hasProduct');

        if($hasProduct > 0 && $products[0] == 0)
        {
            dao::$errors['message'][] = $this->lang->chproject->emptyProduct;
            return false;
        }
    }

    /**
     * Sync intances of ch project.
     *
     * @param  int    $projectID
     * @param  object $data
     * @access public
     * @return void
     */
    public function syncIntances($projectID, $data)
    {
        $this->dao->update(TABLE_CHPROJECT)->data($data)->where('id')->eq($projectID)->exec();

        $intances = $this->getIntances($projectID);
        if(!empty($intances))
        {
            $this->dao->update(TABLE_PROJECT)->data($data)->where('id')->in($intances)->exec();

            // @Todo: Save operation log.
        }
    }

    /**
     * Set menu.
     *
     * @param  int $projectID
     * @access public
     * @return void
     */
    public function setMenu($projectID)
    {
        $projectID = empty($projectID) ? $this->dao->select('id')->from(TABLE_CHPROJECT)->fetch('id') : $projectID;

        $project = $this->getByID($projectID);
        if(!$project) return;

        $this->session->set('chproject', $projectID, $this->app->tab);

        // @Todo: Check project priv.

        $this->lang->switcherMenu = $this->getSwitcher($projectID, $this->app->rawModule, $this->app->rawMethod);

        common::setMenuVars('chteam', $projectID);

        return $projectID;
    }

    /**
     * Print execution nested list.
     *
     * @param  object $chproject
     * @param  array  $users
     * @access public
     * @return void
     */
    public function printNestedList($chproject, $users)
    {
        $this->loadModel('execution');

        $today = helper::today();

        $trClass = 'is-top-level table-nest-child-hide';
        $trAttrs = "data-id='$chproject->id' data-order='$chproject->order' data-nested='true' data-status={$chproject->status}";

        echo "<tr $trAttrs class='$trClass'>";
        echo "<td class='c-name text-left flex sort-handler'>";
        if(common::hasPriv('execution', 'batchEdit')) echo "<span id=$chproject->id class='table-nest-icon icon table-nest-toggle'></span>";
        $spanClass = $chproject->type == 'stage' ? 'label-warning' : 'label-info';
        echo html::a(helper::createLink('chproject', 'task', "chproject=$chproject->id"), $chproject->name, '', "class='text-ellipsis' title='{$chproject->name}'");
        if(!helper::isZeroDate($chproject->end))
        {
            if($chproject->status != 'closed')
            {
                echo strtotime($today) > strtotime($chproject->end) ? '<span class="label label-danger label-badge">' . $this->lang->execution->delayed . '</span>' : '';
            }
        }
        echo '</td>';
        echo "<td class='text-left' title='{$chproject->projectName}'>{$chproject->projectName}</td>";
        echo "<td class='text-left' title='{$chproject->productName}'>{$chproject->productName}</td>";
        echo "<td class='status-{$chproject->status} text-center'>" . zget($this->lang->project->statusList, $chproject->status) . '</td>';
        echo '<td>' . zget($users, $chproject->PM) . '</td>';
        echo helper::isZeroDate($chproject->begin) ? '<td class="c-date"></td>' : '<td class="c-date">' . $chproject->begin . '</td>';
        echo helper::isZeroDate($chproject->end) ? '<td class="endDate c-date"></td>' : '<td class="endDate c-date">' . $chproject->end . '</td>';
        echo "<td class='hours text-right' title='{$chproject->progress->estimate}{$this->lang->execution->workHour}'>" . $chproject->progress->estimate . $this->lang->execution->workHourUnit . '</td>';
        echo "<td class='hours text-right' title='{$chproject->progress->consumed}{$this->lang->execution->workHour}'>" . $chproject->progress->consumed . $this->lang->execution->workHourUnit . '</td>';
        echo "<td class='hours text-right' title='{$chproject->progress->left}{$this->lang->execution->workHour}'>" . $chproject->progress->left . $this->lang->execution->workHourUnit . '</td>';
        echo '<td>' . html::ring($chproject->progress->percent) . '</td>';
        echo '<td class="c-actions text-left">';

        if($chproject->status == 'closed') echo $this->buildMenu('chproject', 'activate', "projectID=$chproject->id", $chproject, 'browse', '', '', 'iframe', true);

        if($chproject->status != 'closed')
        {
            echo $this->buildMenu('chproject', 'edit', "projectID=$chproject->id", $chproject, 'browse');
            echo $this->buildMenu('chproject', 'close', "projectID=$chproject->id", $chproject, 'browse', '', '', 'iframe', true);
        }
        echo $this->buildMenu('chproject', 'delete', "projectID=$chproject->id&confirm=no", $chproject, 'browse', 'trash', 'hiddenwin' , '', '', '', $this->lang->delete);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Delete a ch project.
     *
     * @param  int    $projectID
     * @param  array  $intances
     * @access public
     * @return mixed
     */
    public function delete($projectID, $intances)
    {
        $this->dao->update(TABLE_CHPROJECT)->set('deleted')->eq(1)->where('id')->eq($projectID)->exec();
        $this->loadModel('action')->create('chproject', $projectID, 'deleted', '', ACTIONMODEL::CAN_UNDELETED);

        $this->loadModel('execution');
        foreach($intances as $executionID)
        {
            $this->dao->update(TABLE_EXECUTION)->set('deleted')->eq(1)->where('id')->eq($executionID)->exec();
            $this->loadModel('action')->create('execution', $executionID, 'deleted', '', ACTIONMODEL::CAN_UNDELETED);
            $this->execution->updateUserView($executionID);
            $this->loadModel('common')->syncPPEStatus($executionID);
        }
    }

    /**
     * Close a ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return mixed
     */
    public function close($projectID)
    {
        $this->loadModel('execution');

        $oldProject = $this->getById($projectID);
        $now        = helper::now();

        $project = fixer::input('post')
            ->setDefault('status', 'closed')
            ->setDefault('closedBy', $this->app->user->account)
            ->setDefault('closedDate', $now)
            ->setDefault('lastEditedBy', $this->app->user->account)
            ->setDefault('lastEditedDate', $now)
            ->stripTags($this->config->chproject->editor->close['id'], $this->config->allowedTags)
            ->remove('comment')
            ->get();

        $execution = $project;

        $this->lang->error->ge = $this->lang->execution->ge;

        $project = $this->loadModel('file')->processImgURL($project, $this->config->project->editor->close['id'], $this->post->uid);
        $this->dao->update(TABLE_CHPROJECT)->data($project)
            ->autoCheck()
            ->check($this->config->execution->close->requiredFields,'notempty')
            ->checkIF($project->realEnd != '', 'realEnd', 'le', helper::today())
            ->checkIF($project->realEnd != '', 'realEnd', 'ge', $oldProject->realBegan)
            ->checkFlow()
            ->where('id')->eq((int)$projectID)
            ->exec();

        /* When it has multiple errors, only the first one is prompted */
        if(dao::isError() and count(dao::$errors['realEnd']) > 1) dao::$errors['realEnd'] = dao::$errors['realEnd'][0];

        if(!dao::isError())
        {

            $changes = common::createChanges($oldProject, $project);
            if($this->post->comment != '' or !empty($changes))
            {
                $this->loadModel('action');
                $actionID = $this->action->create('chproject', $projectID, 'Closed', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $intances = $this->getIntances($projectID);

            foreach($intances as $intance) $this->closeExecution($intance, $execution);

            return $changes;
        }
    }

    /**
     * Close execution.
     *
     * @param  int    $executionID
     * @param  object $execution
     * @access public
     * @return mixed
     */
    public function closeExecution($executionID, $execution)
    {
        $this->loadModel('execution')->computeBurn($executionID);

        $oldExecution  = $this->getById($executionID);
        $now           = helper::now();
        $execution->id = $executionID;

        $this->lang->error->ge = $this->lang->execution->ge;

        $execution = $this->loadModel('file')->processImgURL($execution, $this->config->execution->editor->close['id'], $this->post->uid);

        $this->dao->update(TABLE_EXECUTION)->data($execution)->where('id')->eq((int)$executionID)->exec();

        /* When it has multiple errors, only the first one is prompted */
        if(dao::isError() and count(dao::$errors['realEnd']) > 1) dao::$errors['realEnd'] = dao::$errors['realEnd'][0];

        if(!dao::isError())
        {

            $changes = common::createChanges($oldExecution, $execution);
            if($this->post->comment != '' or !empty($changes))
            {
                $this->loadModel('action');
                $actionID = $this->action->create('execution', $executionID, 'Closed', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $this->loadModel('score')->create('execution', 'close', $oldExecution);
        }
    }

    /**
     * Activate ch project.
     *
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function activate($projectID)
    {
        $this->loadModel('execution');

        $oldProject = $this->getById($executionID);
        $now        = helper::now();

        $project = fixer::input('post')
            ->setDefault('realEnd', '')
            ->setDefault('status', 'doing')
            ->setDefault('lastEditedBy', $this->app->user->account)
            ->setDefault('lastEditedDate', $now)
            ->setDefault('closedBy', '')
            ->setDefault('closedDate', '')
            ->stripTags($this->config->chproject->editor->activate['id'], $this->config->allowedTags)
            ->remove('comment,readjustTime,readjustTask')
            ->get();

        if(empty($oldProject->totalConsumed) and helper::isZeroDate($oldProject->realBegan)) $project->status = 'wait';

        if(!$this->post->readjustTime)
        {
            unset($project->begin);
            unset($project->end);
        }

        if($this->post->readjustTime)
        {
            $begin = $project->begin;
            $end   = $project->end;

            if($begin > $end) dao::$errors["message"][] = sprintf($this->lang->execution->errorLetterPlan, $end, $begin);

            if($oldProject->grade > 1)
            {
                $parent      = $this->dao->select('begin,end')->from(TABLE_PROJECT)->where('id')->eq($oldProject->parent)->fetch();
                $parentBegin = $parent->begin;
                $parentEnd   = $parent->end;
                if($begin < $parentBegin)
                {
                    dao::$errors["message"][] = sprintf($this->lang->execution->errorLetterParent, $parentBegin);
                }

                if($end > $parentEnd)
                {
                    dao::$errors["message"][] = sprintf($this->lang->execution->errorGreaterParent, $parentEnd);
                }
            }
        }

        if(dao::isError()) return false;

        $execution = $project;

        $project = $this->loadModel('file')->processImgURL($project, $this->config->chproject->editor->activate['id'], $this->post->uid);

        $this->dao->update(TABLE_CHPROJECT)->data($project)
            ->autoCheck()
            ->checkFlow()
            ->where('id')->eq((int)$projectID)
            ->exec();

        $changes = common::createChanges($oldProject, $project);

        if($this->post->comment != '' or !empty($changes))
        {
            $this->loadModel('action');
            $actionID = $this->action->create('chproject', $projectID, 'Activated', $this->post->comment);
            $this->action->logHistory($actionID, $changes);
        }

        $intances = $this->getIntances($projectID);

        foreach($intances as $intance) $this->activateExecution($intance, $execution);

        return $changes;
    }

    /**
     * Activate execution.
     *
     * @param  int    $executionID
     * @param  string $executionID
     * @access public
     * @return mixed
     */
    public function activateExecution($executionID, $execution)
    {
        $this->loadModel('execution');

        $oldExecution = $this->execution->getById($executionID);
        $now          = helper::now();

        $execution->status = 'doing';

        if(empty($oldExecution->totalConsumed) and helper::isZeroDate($oldExecution->realBegan)) $execution->status = 'wait';

        if(dao::isError()) return false;

        $execution = $this->loadModel('file')->processImgURL($execution, $this->config->execution->editor->activate['id'], $this->post->uid);

        $this->dao->update(TABLE_EXECUTION)->data($execution)->where('id')->eq((int)$executionID)->exec();

        /* Readjust task. */
        if($this->post->readjustTime && $this->post->readjustTask)
        {
            $beginTimeStamp = strtotime($execution->begin);
            $tasks = $this->dao->select('id,estStarted,deadline,status')->from(TABLE_TASK)
                ->where('deadline')->ne('0000-00-00')
                ->andWhere('status')->in('wait,doing')
                ->andWhere('execution')->eq($executionID)
                ->fetchAll();

            foreach($tasks as $task)
            {
                if($task->status == 'wait' and !helper::isZeroDate($task->estStarted))
                {
                    $taskDays   = helper::diffDate($task->deadline, $task->estStarted);
                    $taskOffset = helper::diffDate($task->estStarted, $oldExecution->begin);

                    $estStartedTimeStamp = $beginTimeStamp + $taskOffset * 24 * 3600;
                    $estStarted = date('Y-m-d', $estStartedTimeStamp);
                    $deadline   = date('Y-m-d', $estStartedTimeStamp + $taskDays * 24 * 3600);

                    if($estStarted > $execution->end) $estStarted = $execution->end;
                    if($deadline > $execution->end)   $deadline   = $execution->end;
                    $this->dao->update(TABLE_TASK)->set('estStarted')->eq($estStarted)->set('deadline')->eq($deadline)->where('id')->eq($task->id)->exec();
                }
                else
                {
                    $taskOffset = helper::diffDate($task->deadline, $oldExecution->begin);
                    $deadline   = date('Y-m-d', $beginTimeStamp + $taskOffset * 24 * 3600);

                    if($deadline > $execution->end) $deadline = $execution->end;
                    $this->dao->update(TABLE_TASK)->set('deadline')->eq($deadline)->where('id')->eq($task->id)->exec();
                }
            }
        }

        $changes = common::createChanges($oldExecution, $execution);
        if($this->post->comment != '' or !empty($changes))
        {
            $this->loadModel('action');
            $actionID = $this->action->create('execution', $executionID, 'Activated', $this->post->comment);
            $this->action->logHistory($actionID, $changes);
        }
    }

    /**
     * Build bug search form.
     *
     * @param  array  $products
     * @param  array  $projects
     * @param  int    $queryID
     * @param  string $actionURL
     * @param  array  $intances
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function buildBugSearchForm($products, $projects, $queryID, $actionURL, $intances, $projectID)
    {
        $modules = array();
        $builds  = array('' => '', 'trunk' => $this->lang->trunk);

        $allProducts = $this->getIntanceProductsPairs($projectID);

        foreach($allProducts as $productID => $productName)
        {
            $productModules = $this->loadModel('tree')->getOptionMenu($productID, 'bug');
            $productBuilds  = $this->loadModel('build')->getBuildPairs($productID, 'all', $params = 'noempty|notrunk|withbranch', $intances, 'execution');
            foreach($productModules as $moduleID => $moduleName)
            {
                $modules[$moduleID] = ((count($allProducts) >= 2 and $moduleID) ? $productName : '') . $moduleName;
            }
            foreach($productBuilds as $buildID => $buildName)
            {
                $builds[$buildID] = ((count($allProducts) >= 2 and $buildID) ? $productName . '/' : '') . $buildName;
            }
        }

        $branchGroups = $this->loadModel('branch')->getByProducts(array_keys($products));
        $branchPairs  = array();
        $productType  = 'normal';
        $productNum   = count($products);
        $productPairs = array(0 => '');
        foreach($products as $product)
        {
            $productPairs[$product->id] = $product->name;
            if($product->type != 'normal')
            {
                $productType = $product->type;
                if(isset($product->branches))
                {
                    foreach($product->branches as $branch)
                    {
                        if(isset($branchGroups[$product->id][$branch])) $branchPairs[$branch] = (count($products) > 1 ? $product->name . '/' : '') . $branchGroups[$product->id][$branch];
                    }
                }
                else
                {
                    $productBranches = isset($branchGroups[$product->id]) ? $branchGroups[$product->id] : array(0);
                    if(count($products) > 1)
                    {
                        foreach($productBranches as $branchID => $branchName) $productBranches[$branchID] = $product->name . '/' . $branchName;
                    }
                    $branchPairs += $productBranches;
                }
            }
        }

        $this->config->bug->search['module']    = 'chprojectBug';
        $this->config->bug->search['actionURL'] = $actionURL;
        $this->config->bug->search['queryID']   = $queryID;

        unset($this->config->bug->search['fields']['execution']);
        $this->config->bug->search['params']['product']['values']       = $productPairs + array('all' => $this->lang->product->allProductsOfProject);
        $this->config->bug->search['params']['plan']['values']          = $this->loadModel('productplan')->getForProducts($products);
        $this->config->bug->search['params']['module']['values']        = $modules;
        $this->config->bug->search['params']['openedBuild']['values']   = $builds;
        $this->config->bug->search['params']['resolvedBuild']['values'] = $this->config->bug->search['params']['openedBuild']['values'];
        $this->config->bug->search['params']['project']['values']       = $projects + array('all' => $this->lang->bug->allProject);

        if($productType == 'normal')
        {
            unset($this->config->bug->search['fields']['branch']);
            unset($this->config->bug->search['params']['branch']);
        }
        else
        {
            $this->config->bug->search['fields']['branch']           = sprintf($this->lang->product->branch, $this->lang->product->branchName[$productType]);
            $this->config->bug->search['params']['branch']['values'] = array('' => '') + $branchPairs;
        }
        $this->config->bug->search['params']['status'] = array('operator' => '=', 'control' => 'select', 'values' => $this->lang->bug->statusList);

        $this->loadModel('search')->setSearchParams($this->config->bug->search);
    }

    /**
     * Set Kanban.
     *
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function setKanban($projectID)
    {
        $project = fixer::input('post')
            ->setIF($this->post->heightType == 'auto', 'displayCards', 0)
            ->remove('heightType')
            ->get();

        if(isset($_POST['heightType']) and $this->post->heightType == 'custom' and !$this->loadModel('kanban')->checkDisplayCards($project->displayCards)) return;

        $this->app->loadLang('kanban');
        $this->lang->project->colWidth    = $this->lang->kanban->colWidth;
        $this->lang->project->minColWidth = $this->lang->kanban->minColWidth;
        $this->lang->project->maxColWidth = $this->lang->kanban->maxColWidth;
        $this->dao->update(TABLE_CHPROJECT)->data($project)
            ->autoCheck()
            ->batchCheck($this->config->kanban->edit->requiredFields, 'notempty')
            ->checkIF(!$project->fluidBoard, 'colWidth', 'ge', $this->config->minColWidth)
            ->batchCheckIF($project->fluidBoard, 'minColWidth', 'ge', $this->config->minColWidth)
            ->checkIF($project->minColWidth >= $this->config->minColWidth and $project->fluidBoard, 'maxColWidth', 'gt', $project->minColWidth)
            ->where('id')->eq((int)$projectID)
            ->exec();
    }

    public function getProjectPairs($teamID = 0)
    {
        $products = $this->getIntanceProductPairs($this->session->chproject);
        $projects = $this->dao->select('t1.id,t1.name')->from(TABLE_PROJECT)->alias('t1')
            ->leftJoin(TABLE_PROJECTPRODUCT)->alias('t2')
            ->on('t1.id = t2.project')
            ->where('t1.type')->eq('project')
            ->andWhere('t1.deleted')->eq('0')
            ->andWhere('t2.product')->in($products)
            ->andWhere('t1.project')->eq(0)
            ->fetchPairs('id');

        return $projects;
    }
}
