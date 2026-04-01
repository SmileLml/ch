<?php
class demandpool extends control
{
    public function __construct($module = '', $method = '')
    {
        parent::__construct($module, $method);
        $this->loadModel('story');
    }

    /**
     * Browse demandpool list.
     * 
     * @param  string $browseType 
     * @param  int    $param 
     * @param  string $orderBy 
     * @param  int    $recTotal 
     * @param  int    $recPerPage 
     * @param  int    $pageID 
     * @access public
     * @return void
     */
    public function browse($browseType = 'all', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        unset($this->lang->demandpool->menu);

        /* 加载demandpool模块，可以使用该模块的配置，语言项，model方法。*/
        $this->loadModel('demandpool');
        $browseType = strtolower($browseType);

        $this->session->set('demandpoolList', $this->app->getURI(true));

        /* By search. 构建页面搜索表单。*/
        $queryID = ($browseType == 'bysearch') ? (int)$param : 0;
        $actionURL = $this->createLink('demandpool', 'browse', "browseType=bySearch&param=myQueryID");
        $this->demandpool->buildSearchForm($queryID, $actionURL);

        /* Load pager. 加载分页逻辑，获取分页对象。*/
        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        /* 将相关变量传递到页面。*/
        $this->view->title         = $this->lang->demandpool->browse;
        $this->view->demandpools  = $this->demandpool->getList($browseType, $queryID, $orderBy, $pager);
        $this->view->orderBy       = $orderBy;
        $this->view->pager         = $pager;
        $this->view->browseType    = $browseType;
        $this->view->users         = $this->loadModel('user')->getPairs('noletter');
        $this->view->depts         = $this->loadModel('dept')->getOptionMenu();
        $this->display();
    }

    /**
     * Create a demandpool.
     *
     * @access public
     * @return void
     */
    public function create()
    {
        unset($this->lang->demandpool->menu);

        /* 如果是post请求，就会调用model中的create方法，处理业务逻辑。根据model层返回信息，判断是否错误还是创建成功，如果创建成功会将创建操作记录到action表。*/
        if($_POST)
        {
            $poolID = $this->demandpool->create();

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            $this->loadModel('action')->create('demandpool', $poolID, 'created', $this->post->comment);
            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('browse');

            $this->send($response);
        }

        $this->view->title = $this->lang->demandpool->create;
        $this->view->users = $this->loadModel('user')->getPairs('noclosed');
        $this->view->depts = $this->loadModel('dept')->getOptionMenu();
        $this->display();
    }

    /**
     * Edit a demandpool.
     * 
     * @param  int $poolID 
     * @access public
     * @return void
     */
    public function edit($poolID = 0)
    {
        unset($this->lang->demandpool->menu);

        /* 如果是post请求，就会调用model中的update方法，处理业务逻辑。根据model层返回信息，判断是否错误还是编辑成功，如果成功了，会将修改操作记录到action表。*/
        if($_POST)
        {
            $changes = $this->demandpool->update($poolID);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            if($changes)
            {
                $actionID = $this->loadModel('action')->create('demandpool', $poolID, 'edited', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('view', "poolID=$poolID");

            $this->send($response);
        }

        /* 将需求意向相关信息变量传统到页面用于编辑。*/
        $this->view->title  = $this->lang->demandpool->edit;
        $this->view->users  = $this->loadModel('user')->getPairs('noclosed');
        $this->view->demandpool = $this->loadModel('demandpool')->getByID($poolID);
        $this->view->depts  = $this->loadModel('dept')->getOptionMenu();
        $this->display();
    }

    /**
     * View a demandpool. 
     * 
     * @param  int    $poolID 
     * @access public
     * @return void
     */
    public function view($poolID = 0)
    {
        $this->demandpool->setMenu($poolID);

        /* 查询需求意向详情及其相关变量信息，用于详情展示。*/
        $this->view->title       = $this->lang->demandpool->view;
        $this->view->users       = $this->loadModel('user')->getPairs('noletter');
        $this->view->actions     = $this->loadModel('action')->getList('demandpool', $poolID);
        $this->view->demandpool = $this->loadModel('demandpool')->getByID($poolID);
        $this->view->depts       = $this->loadModel('dept')->getOptionMenu();

        $this->display();
    }

    public function close($poolID = 0)
    {
        if($_POST)
        {
            $changes = $this->demandpool->close($poolID);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                $this->send($response);
            }

            if($changes || $this->post->comment != '')
            {
                $actionID = $this->loadModel('action')->create('demandpool', $poolID, 'closed', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = 'parent';

            $this->send($response);
        }

        $this->view->title   = $this->lang->demandpool->close;
        $this->view->demandpool  = $this->demandpool->getByID($poolID);
        $this->view->users   = $this->loadModel('user')->getPairs('nodeleted');
        $this->view->actions = $this->loadModel('action')->getList('demandpool', $poolID);
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
     * @param $poolID
     */
    public function delete($poolID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            echo js::confirm($this->lang->demandpool->confirmDelete, $this->createLink('demandpool', 'delete', "demandpool=$poolID&confirm=yes"), '');
            exit;
        }
        else
        {
            $this->demandpool->delete(TABLE_DEMANDPOOL, $poolID);

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            $locateLink = $this->createLink('demandpool', 'browse');
            die(js::locate($locateLink, 'parent'));
        }
    }

    public function ajaxGetDropMenu($poolID, $module, $method)
    {
        $this->view->poolID  = $poolID;
        $this->view->demandpools   = $this->demandpool->getList();
        $this->view->module    = $module;
        $this->view->method    = $method;

        $this->display();
    }
}
