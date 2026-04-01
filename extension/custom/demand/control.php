<?php
class demand extends control
{
    public function __construct($module = '', $method = '')
    {
        parent::__construct($module, $method);
        $this->loadModel('demandpool');
        $this->loadModel('tree');
    }

    /**
     * Browse demand list.
     *
     * @param  int    $poolID
     * @param  string $browseType
     * @param  int    $param
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function browse($poolID = 0, $browseType = 'all', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 15, $pageID = 1)
    {
        $this->loadModel('datatable');
        $poolID = $this->demandpool->setMenu($poolID);

        /* 加载demand模块，可以使用该模块的配置，语言项，model方法。*/
        $this->loadModel('demand');
        $browseType = strtolower($browseType);

        $this->session->set('demandList', $this->app->getURI(true));
        $this->session->set('businessList', $this->createLink('business', 'browse'));
        $this->session->set('demandViewBackUrl', $this->app->getURI());

        setcookie('demandModule', 0, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);

        /* By search. 构建页面搜索表单。*/
        $fields = $this->loadModel('workflowfield')->getList('demand');
        $this->config->demand->search['fields']['businessUnit'] = $fields['businessUnit']->name;

        $this->config->demand->search['params']['businessUnit'] = array('operator' => 'include', 'control' => 'select', 'values' => $fields['businessUnit']->options);;

        $this->config->demand->search['params']['module']['values'] = array('' => '') + $this->loadModel('tree')->getOptionMenu($poolID, 'demand');
        $queryID = ($browseType == 'bysearch') ? (int)$param : 0;
        $actionURL = $this->createLink('demand', 'browse', "poolID=$poolID&browseType=bySearch&param=myQueryID");
        $this->demand->buildSearchForm($queryID, $actionURL, $poolID);

        $moduleID = 0;
        if($browseType == 'bymodule')
        {
            $moduleID = $param;
            $module   = $this->tree->getById($moduleID);
        }
        elseif($browseType == 'bysearch')
        {
            $moduleID = 0;
        }
        else
        {
            $moduleID = $this->cookie->demandModule ? $this->cookie->demandModule : 0;
        }

        /* Load pager. 加载分页逻辑，获取分页对象。*/
        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        /* 将相关变量传递到页面。*/
        $this->view->title         = $this->lang->demand->browse;
        $this->view->demands       = $this->demand->getList($poolID, $browseType, $queryID, $moduleID, $orderBy, $pager);
        $this->view->orderBy       = $orderBy;
        $this->view->pager         = $pager;
        $this->view->browseType    = $browseType;
        $this->view->poolID        = $poolID;
        $this->view->moduleName    = $moduleID ? $module->name : $this->lang->demand->allModule;
        $this->view->moduleID      = $moduleID;
        $this->view->moduleTree    = $this->loadModel('tree')->getTreeMenu($poolID, 'demand', 0, array('demandModel', 'createDemandLink'), $poolID);
        $this->view->demandpools   = $this->demandpool->getPairs();
        $this->view->users         = $this->loadModel('user')->getPairs('noletter');
        $this->view->modulePairs   = $this->tree->getOptionMenu($poolID, 'demand');
        $this->display();
    }

    /**
     * Create a demand.
     *
     * @access public
     * @return void
     */
    public function create($poolID = 0)
    {
        $poolID = $this->demandpool->setMenu($poolID);

        /* 如果是post请求，就会调用model中的create方法，处理业务逻辑。根据model层返回信息，判断是否错误还是创建成功，如果创建成功会将创建操作记录到action表。*/
        if($_POST)
        {
            $demandID = $this->demand->create($poolID);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            $this->loadModel('action')->create('demand', $demandID, 'created', $this->post->comment);
            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('browse', "poolID=$poolID");

            $this->send($response);
        }

        /* 获取创建需求意向时，页面所需的变量。*/
        $this->view->title            = $this->lang->demand->create;
        $this->view->users            = $this->loadModel('user')->getPairs('noclosed');
        $this->view->pool             = $this->loadModel('demandpool')->getByID($poolID);
        $this->view->moduleOption     = $this->loadModel('tree')->getOptionMenu($poolID, 'demand');
        $this->view->projects         = $projects;
        $this->view->allProjects      = ['' => ''] + $this->demand->getBusinessProject();
        $this->view->depts            = $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        unset($this->view->depts[0]);

        $this->display();
    }

    public function batchCreate($poolID = 0)
    {
        $poolID = $this->demandpool->setMenu($poolID);

        if($_POST)
        {
            $demandID = $this->demand->batchCreate($poolID);
            if(dao::isError())
            {
                $response = array('result' => 'fail', 'message' => dao::getError());
                return $this->send($response);
            }

            $response = array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => inlink('browse', "poolID=$poolID"));
            return $this->send($response);
        }
        $this->view->title        = $this->lang->demand->batchCreate;
        $this->view->users        = $this->loadModel('user')->getPairs('nodeleted|noclosed');
        $this->view->pool         = $this->loadModel('demandpool')->getByID($poolID);
        $this->view->moduleOption = $this->loadModel('tree')->getOptionMenu($poolID, 'demand');
        $this->view->projects     = array(0 => '') + $this->loadModel('project')->getPairsByPM();
        $this->view->depts        = $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        unset($this->view->depts[0]);

        $this->display();
    }

    /**
     * Edit a demand.
     *
     * @param  int $demandID
     * @access public
     * @return void
     */
    public function edit($demandID = 0)
    {
        $demand = $this->loadModel('demand')->getByID($demandID);
        $this->demandpool->setMenu($demand->pool);

        if($_POST)
        {
            $changes = $this->demand->update($demandID);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            if($changes)
            {
                $actionID = $this->loadModel('action')->create('demand', $demandID, 'edited', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('view', "demandID=$demandID");

            $this->send($response);
        }

        $business = $this->dao->select('id,status')->from('zt_flow_business')->where('demand')->like("%$demandID%")->fetch();

        $allProject     = ['' => ''] + $this->demand->getBusinessProject();
        $currentProject = $this->dao->select('id,name')->from('zt_flow_projectapproval')->where('id')->eq($demand->project)->fetch();
        if($currentProject) $allProject[$currentProject->id] = $currentProject->name;

        /* 将需求意向相关信息变量传统到页面用于编辑。*/
        $this->view->title        = $this->lang->demand->edit;
        $this->view->users        = $this->loadModel('user')->getPairs('noclosed');
        $this->view->isChange     = ($business && $business->status == 'approvedProject') ? false : true;
        $this->view->demand       = $demand;
        $this->view->moduleOption = $this->loadModel('tree')->getOptionMenu($demand->pool, 'demand');
        $this->view->allProjects  = $allProject;
        $this->view->depts        = $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        unset($this->view->depts[0]);

        $this->display();
    }

    /**
     * View a demand.
     *
     * @param  int    $demandID
     * @access public
     * @return void
     */
    public function view($demandID = 0)
    {
        $uri = $this->app->getURI(true);
        $this->session->set('storyList', $uri, 'product');

        $demand = $this->demand->getByID($demandID);
        $this->loadModel('demandpool')->setMenu($demand->pool);

        $user = $this->loadModel('user')->getUserDisplayInfos(array($demand->createdBy));

        $allProject     = ['' => ''] + $this->demand->getBusinessProject();
        $currentProject = $this->dao->select('id,name')->from('zt_flow_projectapproval')->where('id')->eq($demand->project)->fetch();
        if($currentProject) $allProject[$currentProject->id] = $currentProject->name;

        /* 查询需求意向详情及其相关变量信息，用于详情展示。*/
        $this->view->title        = $this->lang->demand->view;
        $this->view->users        = $this->loadModel('user')->getPairs('noletter');
        $this->view->actions      = $this->loadModel('action')->getList('demand', $demandID);
        $this->view->demand       = $demand;
        $this->view->moduleOption = $this->loadModel('tree')->getOptionMenu($demand->pool, 'demand');
        $this->view->projects     = $allProject;
        $this->view->depts        = $this->loadModel('dept')->getOptionMenu();
        $this->view->demandSource = $user[$demand->createdBy]->dept;

        $this->display();
    }

    public function assignTo($demandID)
    {
        if(!empty($_POST))
        {
            $changes = $this->demand->assign($demandID);
            if(dao::isError()) die(js::error(dao::getError()));
            if($changes)
            {
                $actionID = $this->loadModel('action')->create('demand', $demandID, 'Assigned', $this->post->comment, $this->post->assignedTo);
                $this->action->logHistory($actionID, $changes);
            }

            die(js::closeModal('parent.parent'));
        }

        $this->view->demand = $this->demand->getByID($demandID);
        $this->view->actions     = $this->loadModel('action')->getList('demand', $demandID);
        $this->view->users       = $this->loadModel('user')->getPairs('nodeleted|pofirst|noletter');
        $this->display();
    }

    public function review($demandID = 0)
    {
        if($_POST)
        {
            $changes = $this->demand->review($demandID);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            if($changes || $this->post->comment != '')
            {
                $actionID = $this->loadModel('action')->create('demand', $demandID, 'reviewed', $this->post->comment, $this->post->result);
                $this->action->logHistory($actionID, $changes);
            }

            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = 'parent';

            $this->send($response);
        }

        $this->view->title       = $this->lang->demand->review;
        $this->view->demand = $this->demand->getByID($demandID);
        $this->view->users       = $this->loadModel('user')->getPairs('nodeleted');
        $this->display();
    }

    public function submit($demandID = 0)
    {
        if($_POST)
        {
            $this->dao->update(TABLE_DEMAND)->set('status')->eq('wait')->where('id')->eq($demandID)->exec();

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            $actionID = $this->loadModel('action')->create('demand', $demandID, 'submited', $this->post->comment);
            $this->action->logHistory($actionID, $changes);

            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = 'parent';

            $this->send($response);
        }

        $this->view->title       = $this->lang->demand->review;
        $this->view->demand = $this->demand->getByID($demandID);
        $this->display();
    }

    public function close($demandID = 0)
    {
        $demand = $this->demand->getByID($demandID);
        if($_POST)
        {
            $changes = $this->demand->close($demandID);

            if(dao::isError()) return print(js::error(dao::getError()));

            if($changes || $this->post->comment != '')
            {
                $actionID = $this->loadModel('action')->create('demand', $demandID, 'closed', $this->post->comment, ucfirst($this->post->closedReason) . ($this->post->duplicateDemand ? ':' . (int)$this->post->duplicateDemand : '') . "|$demand->status");
                $this->action->logHistory($actionID, $changes);
            }

            return print(js::closeModal('parent.parent'));
        }

        $demands = $this->demand->getPairs($demand->pool);

        if($demands)
        {
            if(isset($demands[$demand->id])) unset($demands[$demand->id]);
            foreach($demands as $id => $title)
            {
                $demands[$id] = "$id:$title";
            }
        }

        $this->view->title   = $this->lang->demand->close;
        $this->view->demand  = $demand;
        $this->view->demands = $demands;
        $this->view->users   = $this->loadModel('user')->getPairs('nodeleted');
        $this->view->actions = $this->loadModel('action')->getList('demand', $demandID);
        $this->display();
    }

    /**
     * Project: chengfangjinke
     * Method: delete
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:49
     * Desc: This is the code comment. This method is called delete.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param $demandID
     */
    public function delete($demandID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            echo js::confirm($this->lang->demand->confirmDelete, $this->createLink('demand', 'delete', "demand=$demandID&confirm=yes"), '');
            exit;
        }
        else
        {
            $demand = $this->demand->getByID($demandID);
            $this->demand->delete(TABLE_DEMAND, $demandID);

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            $locateLink = $this->createLink('demand', 'browse', "poolID=$demand->pool");
            die(js::locate($locateLink, 'parent'));
        }
    }

    public function tostory($demandID, $type = 'story')
    {
        $this->loadModel('story');
        $demand = $this->demand->getByID($demandID);
        $this->loadModel('demandpool')->setMenu($demand->pool);

        if($_POST)
        {
            $copyFiles = array();
            foreach($_POST as $key => $value)
            {
                if(strpos($key, 'copyFile') !== false)
                {
                    unset($_POST[$key]);
                    $copyFiles[$key] = $value;
                }
            }

            if(!$this->post->product)
            {
                dao::$errors['product'] = $this->lang->demand->errorEmptyProduct;
                $response['message']    = dao::getError();
                $response['result']     = 'fail';

                $this->send($response);
            }

            if(isset($_POST['branch']))
            {
                $_POST['branches'] = (array)$_POST['branch'];
                unset($_POST['branch']);
            }

            $result = $this->story->create();

            if(dao::isError())
            {
                $response['message'] = dao::getError();
                $response['result']  = 'fail';

                $this->send($response);
            }

            $storyID = $result['id'];

            $this->dao->update(TABLE_STORY)->set('fromDemand')->eq($demandID)->where('id')->eq($storyID)->exec();

            $demandFiles = $this->loadModel('file')->getByObject('demand', $demandID);
            $fileIdList  = '';
            if($demandFiles)
            {
                $demandFilesIdList = '';
                foreach($demandFiles as $demandFile)
                {
                    $fileKey = "copyFile$demandFile->id";
                    unset($demandFile->id);
                    if(isset($copyFiles[$fileKey]))
                    {
                        $demandFile->title      = $copyFiles[$fileKey];
                        $demandFile->objectType = $type;
                        $demandFile->objectID   = $storyID;
                        $this->dao->insert(TABLE_FILE)->data($demandFile, 'webPath,realPath')->exec();

                        $fileID = $this->dao->lastInsertID();
                        $fileIdList .= "{$fileID},";
                    }
                }
            }

            if($fileIdList) $this->dao->update(TABLE_STORYSPEC)->set('files')->eq($fileIdList)->where('story')->eq($storyID)->exec();

            $this->loadModel('action');
            $this->action->create('story', $storyID, 'openedbydemand', '', $demandID);
            $this->action->create('demand', $demandID, 'tostory', '', $storyID);

            $response['message'] = $this->lang->saveSuccess;
            $response['result']  = 'success';
            $response['locate']  = inlink('view', "demandID=$demandID");

            $this->send($response);
        }

        $this->view->title    = $this->lang->demand->tostory;
        $this->view->products = array('' => '') + $this->loadModel('product')->getPairs();
        $this->view->type     = $type;
        $this->view->demand   = $demand;
        $this->view->users    = $this->loadModel('user')->getPairs('noclosed|nodeleted');
        $this->display();
    }

    public function track($poolID = 0, $browseType = 'all', $param = 0, $recTotal = 0, $recPerPage = 20, $pageID = 1, $type = '')
    {
        $this->loadModel('demandpool')->setMenu($poolID);

        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        $queryID   = ($browseType == 'bysearch') ? (int)$param : 0;
        $actionURL = $this->createLink('demand', 'track', "poolID=$poolID&browseType=bysearch&param=myQueryID");

        $demandPools = $this->demandpool->getPairs();
        $this->demand->buildTrackSearchForm($poolID, $demandPools, $queryID, $actionURL);

        $this->view->title    = $this->lang->demand->track;
        $this->view->tracks   = $this->demand->getTracks($poolID, $pager, $browseType, $queryID);
        $this->view->products = $this->loadModel('product')->getPairs();
        $this->view->pager    = $pager;

        $this->display();
    }

    public function manageTree($poolID = 0, $currentModuleID = 0)
    {
        $this->loadModel('tree');
        $this->loadModel('demandpool')->setMenu($poolID);

        $this->view->title           = $this->lang->demand->manageTree;
        $this->view->poolID          = $poolID;
        $this->view->viewType        = 'demand';
        $this->view->sons            = $this->tree->getSons($poolID, $currentModuleID, 'demand', '');
        $this->view->currentModuleID = $currentModuleID;
        $this->view->demandpool      = $this->demandpool->getByID($poolID);
        $this->view->tree            = $this->tree->getProductStructure($poolID, 'demand', '');
        $this->view->parentModules   = $this->tree->getParents($currentModuleID);
        $this->display();
    }

    /**
     * Project: chengfangjinke
     * Method: export
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:49
     * Desc: This is the code comment. This method is called export.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param string $orderBy
     * @param string $browseType
     */
    public function export($poolID = 0, $orderBy = 'id_desc', $browseType = 'all')
    {
        $this->app->loadLang('story');
        if($this->config->edition != 'open')
        {
            $appendFields = $this->dao->select('t2.*')->from(TABLE_WORKFLOWLAYOUT)->alias('t1')
                ->leftJoin(TABLE_WORKFLOWFIELD)->alias('t2')->on('t1.field=t2.field && t1.module=t2.module')
                ->where('t1.module')->eq('demand')
                ->andWhere('t1.action')->eq('export')
                ->andWhere('t2.buildin')->eq(0)
                ->orderBy('t1.order')
                ->fetchAll();
            foreach($appendFields as $appendField)
            {
                $this->lang->demand->{$appendField->field} = $appendField->name;
                $this->config->demand->list->exportFields .= ',' . $appendField->field;
            }
        }
        /* format the fields of every demand in order to export data. */
        /* 如果是post请求将格式化需求意见的相关字段用以导出数据。*/
        if($_POST)
        {
            $this->config->demand->listFields = str_replace('dept,', '', $this->config->demand->listFields);
            $this->demand->setListValue($poolID);
            unset($_POST['deptList']);

            $this->loadModel('file');
            $demandLang   = $this->lang->demand;
            $demandConfig = $this->config->demand;

            /* Create field lists. */
            /* 处理将要导出的字段列表。*/
            $fields = $this->post->exportFields ? $this->post->exportFields : explode(',', $demandConfig->list->exportFields);
            foreach($fields as $key => $fieldName)
            {
                $fieldName = trim($fieldName);
                $fields[$fieldName] = isset($demandLang->$fieldName) ? $demandLang->$fieldName : $fieldName;
                unset($fields[$key]);
            }
            $fieldList = $this->loadModel('transfer')->initFieldList('demand', $fields);
            $list      = $this->loadModel('transfer')->setListValue('demand', $fieldList);
            if($list) foreach($list as $listName => $listValue) $this->post->set($listName, $listValue);

            /* Get demands. */
            /* 查询要导出的需求意见数据。*/
            $demands = array();

            if($this->session->demandOnlyCondition)
            {
                $demands = $this->dao->select('*')->from(TABLE_DEMAND)->where($this->session->demandQueryCondition)
                    ->beginIF($this->post->exportType == 'selected')->andWhere('id')->in($this->cookie->checkedItem)->fi()
                    ->orderBy($orderBy)->fetchAll('id');
            }
            else
            {
                $search = '(AND (1))';
                if($this->cookie->checkedItem) $search = "(AND `id` IN({$this->cookie->checkedItem}))";

                $demandSearch = $this->session->demandQueryCondition . ($this->post->exportType == 'selected' ? " $demandSearch" : '') . " ORDER BY " . strtr($orderBy, '_', ' ');
                $stmt         = $this->dbh->query($demandSearch);

                while($row = $stmt->fetch()) $demands[$row->id] = $row;
            }

            $demandIdList = array_keys($demands);

            $modules = $this->loadModel('tree')->getOptionMenu($poolID, 'demand');

            /* Get users, products and executions. */
            /* 处理需求意见导出字段的对应值。*/
            $users    = $this->loadModel('user')->getPairs('noletter');
            $projects = $this->loadModel('project')->getPairsByPM();
            $depts    = $this->loadModel('dept')->getOptionMenu();
            unset($depts[0]);

            $extendFields = $this->demand->getFlowExtendFields();
            foreach($demands as $demand)
            {
                $user = $this->loadModel('user')->getUserDisplayInfos(array($demand->createdBy));
                $demand->demandSource = zget($depts, $user[$demand->createdBy]->dept, '');

                if(isset($demandLang->priList[$demand->pri]))           $demand->pri      = $demandLang->priList[$demand->pri];
                if(isset($demandLang->stageList[$demand->stage]))       $demand->stage    = $demandLang->stageList[$demand->stage];
                if(isset($users[$demand->createdBy]))  $demand->createdBy  = $users[$demand->createdBy];
                if(isset($users[$demand->assignedTo])) $demand->assignedTo = $users[$demand->assignedTo];

                $mailtoStr = '';
                foreach(explode(',', $demand->mailto) as $mailto)
                {
                    if(isset($users[$mailto])) $mailtoStr .= $users[$mailto] . ' ';
                }

                $demand->mailto = $mailtoStr;

                $demand->project    = zget($projects, $demand->project, '');
                $demand->status     = zget($demandLang->statusList, $demand->status, '');
                $demand->createDate = substr($demand->createDate, 0, 10);
                $demand->deadline   = substr($demand->deadline, 0, 10);
                $demand->date       = substr($demand->date, 0, 10);

                $dept = '';
                foreach(explode(',', $demand->dept) as $item)
                {
                    $dept .= zget($depts, $item, '') . '   ';
                }

                $demand->dept = $dept;

                foreach($extendFields as $extendField)
                {
                    $field = $extendField->field;
                    $demand->$field = $this->loadModel('flow')->getFieldValue($extendField, $demand);
                }
            }

            /* 将字段和字段的值调用file模块的export2方法进行导出。*/
            $this->post->set('fields', $fields);
            $this->post->set('rows', $demands);
            $this->post->set('kind', 'demand');
            $this->fetch('file', 'export2' . $this->post->fileType, $_POST);
        }

        /* 将导出页面所需的相关变量传递到页面。*/
        $this->view->fileName        = $this->lang->demand->common;
        $this->view->allExportFields = $this->config->demand->list->exportFields;
        $this->view->customExport    = true;
        $this->display();
    }

    /**
     * Project: chengfangjinke
     * Method: exportTemplate
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:49
     * Desc: This is the code comment. This method is called exportTemplate.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     */
    public function exportTemplate($poolID = 0)
    {
        /* 调用导出模板页面，如果是post请求，将调用setListValue方法处理多选字段的值，然后设置导出的相关信息，调用file模块的export2方法进行导出模板处理。*/
        if($_POST)
        {
            $this->demand->setListValue($poolID);
            $this->fetch('transfer', 'exportTemplate', 'model=demand');
        }
        $this->display();
    }

    /**
     * Project: chengfangjinke
     * Method: import
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:49
     * Desc: This is the code comment. This method is called import.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     */
    public function import($poolID = 0)
    {
        if($_FILES)
        {
            /* 如果文件存在，则判断文件类型是否符合要求。*/
            $file = $this->loadModel('file')->getUpload('file');
            $file = $file[0];
            if($file['extension'] != 'xlsx') die(js::alert($this->lang->file->onlySupportXLSX));

            /* 将导入的文件存放于临时目录。*/
            $fileName = $this->file->savePath . $this->file->getSaveName($file['pathname']);
            move_uploaded_file($file['tmpname'], $fileName);

            /* 加载phpexcel库，解析excel文件内容，解析完调用showImport方法进行数据确认。*/
            $phpExcel  = $this->app->loadClass('phpexcel');
            $phpReader = new PHPExcel_Reader_Excel2007();
            if(!$phpReader->canRead($fileName))
            {
                $phpReader = new PHPExcel_Reader_Excel5();
                if(!$phpReader->canRead($fileName))die(js::alert($this->lang->excel->canNotRead));
            }
            $this->session->set('fileImport', $fileName);
            die(js::locate(inlink('showImport', "poolID=$poolID"), 'parent.parent'));
        }

        $this->display();
    }

    /**
     * integration demands.
     *
     * @access public
     * @return void
     */
    public function integration($pool, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            if($this->post->demandIDList) $this->session->set('demandIDList', $_POST['demandIDList']);
            $this->session->set('demandList', $this->createLink('demand', 'browse', "demandpoolID={$pool}"));
            $this->session->set('demandIntegration', $this->createLink('demand', 'integration', "pool={$pool}&confirm=yes"));
        }

        $this->app->rawModule = 'business';
        $this->app->rawMethod = 'create';

        $this->loadModel('demandpool')->setMenu($pool);
        echo $this->fetch('flow', 'create');
    }

    /**
     * Project: chengfangjinke
     * Method: showImport
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:49
     * Desc: This is the code comment. This method is called showImport.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param int $pagerID
     * @param int $maxImport
     * @param string $insert
     */
    public function showImport($poolID = 0, $pagerID = 1, $maxImport = 0, $insert = '')
    {
        $this->loadModel('demandpool')->setMenu($poolID);

        $this->app->loadLang('story');

        if($this->config->edition != 'open')
        {
            $appendFields = $this->dao->select('t2.*')->from(TABLE_WORKFLOWLAYOUT)->alias('t1')
                ->leftJoin(TABLE_WORKFLOWFIELD)->alias('t2')->on('t1.field=t2.field && t1.module=t2.module')
                ->where('t1.module')->eq('demand')
                ->andWhere('t1.action')->eq('showimport')
                ->andWhere('t2.buildin')->eq(0)
                ->orderBy('t1.order')
                ->fetchAll();
            foreach($appendFields as $appendField)
            {
                $this->lang->demand->{$appendField->field} = $appendField->name;
                $this->config->demand->list->exportFields .= ',' . $appendField->field;
            }
        }


        $this->lang->demand->priList = $this->lang->story->priList;
        /* 获取import方法导入的临时文件。*/
        $file    = $this->session->fileImport;
        $tmpPath = $this->loadModel('file')->getPathOfImportedFile();
        $tmpFile = $tmpPath . DS . md5(basename($file));

        /* 如果是post请求，则调用createFromImport方法保存导入的数据。如果是最后一页则跳转列表，否则跳转下一页数据。*/
        if($_POST)
        {
            $this->demand->createFromImport($poolID);
            if($this->post->isEndPage)
            {
                unlink($tmpFile);
                die(js::locate($this->createLink('demand','browse', "poolID=$poolID"), 'parent'));
            }
            else
            {
                die(js::locate(inlink('showImport', "poolID=$poolID&pagerID=" . ($this->post->pagerID + 1) . "&maxImport=$maxImport&insert=" . zget($_POST, 'insert', '')), 'parent'));
            }
        }

        /* 如果最大导入数量不为空，且导入文件存在，则获取文件内容进行序列化。*/
        if(!empty($maxImport) and file_exists($tmpFile))
        {
            $demandData = unserialize(file_get_contents($tmpFile));
        }
        else
        {
            /* 初始化变量，获取要导入的字段。*/
            $pagerID      = 1;
            $demandLang   = $this->lang->demand;
            $demandConfig = $this->config->demand;
            $fields       = $this->loadModel('transfer')->getImportFields('demand');
            $fieldList    = $this->loadModel('transfer')->initFieldList('demand', array_keys($fields), false);
            $list         = $this->loadModel('transfer')->setListValue('demand', $fieldList);

            /* 获取导入文件所有行的数据。*/
            $rows = $this->file->getRowsFromExcel($file);
            $demandData = array();
            foreach($rows as $currentRow => $row)
            {
                $demand = new stdclass();
                foreach($row as $currentColumn => $cellValue)
                {
                    /* 获取导入文件第一行标题对应的导入字段key值。*/
                    if($currentRow == 1)
                    {
                        $field = array_search($cellValue, $fields);
                        $columnKey[$currentColumn] = $field ? $field : '';
                        continue;
                    }

                    /* 判断该列是否存在于导入的列中。*/
                    if(empty($columnKey[$currentColumn]))
                    {
                        $currentColumn++;
                        continue;
                    }
                    $field = $columnKey[$currentColumn];
                    $currentColumn++;

                    // check empty data.
                    /* 判断导入字段的值是否为空，如果为空，则设置该字段值为空。*/
                    if(empty($cellValue))
                    {
                        $demand->$field = '';
                        continue;
                    }

                    //if(in_array($field, $demandConfig->import->ignoreFields)) continue;
                    /* 针对下拉选项字段进行处理，然后赋值转换。*/
                    if(in_array($field.'List', array_keys($list)))
                    {
                        if(!empty($fieldList[$field]['from']) and in_array($fieldList[$field]['control'], array('select', 'multiple')))
                        {
                            $control = $fieldList[$field]['control'];
                            if($control == 'multiple')
                            {
                                $cellValue = explode("\n", $cellValue);
                                foreach($cellValue as &$value) $value = array_search($value, $fieldList[$field]['values'], true);
                                $demand->$field = join(',', $cellValue);
                            }
                            else
                            {
                                $demand->$field = array_search($cellValue, $fieldList[$field]['values']);
                            }
                        }
                        elseif(strrpos($cellValue, '(#') === false)
                        {
                            $demand->$field = $cellValue;
                            if(!isset($demandLang->{$field . 'List'}) or !is_array($demandLang->{$field . 'List'})) continue;

                            /* when the cell value is key of list then eq the key. */
                            $listKey = array_keys($demandLang->{$field . 'List'});
                            unset($listKey[0]);
                            unset($listKey['']);
                            $fieldKey = array_search($cellValue, $demandLang->{$field . 'List'});
                            if($fieldKey) $demand->$field = $fieldKey;
                        }
                        else
                        {
                            $id = trim(substr($cellValue, strrpos($cellValue,'(#') + 2), ')');
                            $demand->$field = $id;
                        }
                    }
                    elseif($field == 'background' or $field == 'desc')
                    {
                        /* 针对富文本类型字段内容进行处理。*/
                        $demand->$field = str_replace("\n", "\n", $cellValue);
                    }
                    else
                    {
                        $demand->$field = $cellValue;
                    }
                }

                if(empty($demand->name) || empty($demand->businessDesc) || empty($demand->businessObjective)) continue;
                $demandData[$currentRow] = $demand;
                unset($demand);
            }
            /* 获取处理好的数据后，写入临时文件中。*/
            file_put_contents($tmpFile, serialize($demandData));
        }

        /* 当导入文件的内容处理完成后，删除临时文件，并刷新列表页面。*/
        if(empty($demandData))
        {
            unlink($this->session->fileImport);
            unset($_SESSION['fileImport']);
            echo js::alert($this->lang->excel->noData);
            die(js::locate($this->createLink('demand','browse', 'demandpoolID=' . $poolID)));
        }

        /* 判断导入的数据是否大于系统预设最大导入数，如果大于则对数据进行拆分处理。*/
        $allCount = count($demandData);
        $allPager = 1;
        if($allCount > $this->config->file->maxImport)
        {
            if(empty($maxImport))
            {
                $this->view->allCount  = $allCount;
                $this->view->maxImport = $maxImport;
                $this->view->productID = $productID;
                $this->view->branch    = $branch;
                $this->view->type      = $type;
                die($this->display());
            }

            $allPager  = ceil($allCount / $maxImport);
            $demandData = array_slice($demandData, ($pagerID - 1) * $maxImport, $maxImport, true);
        }
        if(empty($demandData)) die(js::locate($this->createLink('demand','browse')));

        /* Judge whether the editedStories is too large and set session. */
        /* 判断要处理的需求意向是否太大，并设置session。*/
        $countInputVars  = count($demandData) * 11;
        $showSuhosinInfo = common::judgeSuhosinSetting($countInputVars);
        if($showSuhosinInfo) $this->view->suhosinInfo = extension_loaded('suhosin') ? sprintf($this->lang->suhosinInfo, $countInputVars) : sprintf($this->lang->maxVarsInfo, $countInputVars);

        $this->app->loadLang('story');
        /* 将要导入的数据及其相关变量，传递到页面进行展示。*/
        $this->view->title      = $this->lang->demand->common . $this->lang->colon . $this->lang->demand->showImport;
        $this->view->poolID     = $poolID;
        $this->view->position[] = $this->lang->demand->showImport;
        $this->view->demandData = $demandData;
        $this->view->allCount   = $allCount;
        $this->view->allPager   = $allPager;
        $this->view->pagerID    = $pagerID;
        $this->view->isEndPage  = $pagerID >= $allPager;
        $this->view->maxImport  = $maxImport;
        $this->view->dataInsert = $insert;
        $this->view->users      = $this->loadModel('user')->getPairs('noclosed');
        $this->view->depts      = $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        $this->view->projects   = ['' => ''] + $this->demand->getBusinessProject();
        unset($this->view->depts[0]);

        $this->display();
    }
}
