<?php
/**
 * The control file of chteam currentModule of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     chteam
 * @version     $Id: control.php 5107 2013-07-12 01:46:12Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class chteam extends control
{
    /**
     * Browse chteam.
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
        $this->loadModel('datatable');

        $queryID = (int)$param;
        $this->chteam->buildSearchForm($queryID, $this->createLink('chteam', 'browse', "&browseType=bysearch&queryID=myQueryID"));

        $this->app->loadClass('pager', $static = true);
        $pager   = new pager($recTotal, $recPerPage, $pageID);

        $chteams = $this->chteam->getList($browseType, $queryID, $orderBy, $pager);

        $this->view->title      = $this->lang->chteam->common;
        $this->view->param      = $param;
        $this->view->chteams    = $chteams;
        $this->view->users      = $this->loadModel('user')->getPairs('noletter');
        $this->view->pager      = $pager;
        $this->view->browseType = $browseType;

        $this->display();
    }

    /**
     * Create a chteam.
     * @access public
     * @return void
     */
    public function create()
    {
        if($_POST)
        {
            $chteamID = $this->chteam->create();
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            $this->loadModel('action')->create('chteam', $chteamID, 'opened');

            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => $this->createLink('chteam', 'browse')));
        }

        $this->view->title = $this->lang->chteam->create;
        $this->view->users = $this->loadModel('user')->getPairs('noclosed|nodeleted');

        $this->display();
    }

    /**
     * Edit a chteam.
     *
     * @param  int    $chteamID
     * @param  string $from
     * @access public
     * @return void
     */
    public function edit($chteamID = 0, $from = '')
    {
        $chteam = $this->chteam->getByID($chteamID);

        if($_POST)
        {
            $this->chteam->update($chteamID);
            if(dao::isError()) return print(js::error(dao::getError()));

            $actionID = $this->loadModel('action')->create('chteam', $chteamID, 'edited');
            $this->executeHooks($chteamID);

            return print(js::reload('parent.parent'));
        }

        $this->view->chteam = $chteam;
        $this->view->users  = $this->loadModel('user')->getPairs('noclosed|nodeleted');

        $this->display();
    }

    /**
     * Delete a chteam.
     *
     * @param  int    $chteamID
     * @param  string $confirm yes|no
     * @access public
     * @return void
     */
    public function delete($chteamID, $confirm = 'no')
    {
        if($confirm == 'no') return print(js::confirm($this->lang->chteam->confirmDelete, inlink('delete', "chteamID=$chteamID&confirm=yes")));

        $this->chteam->unbind($chteamID);

        $this->chteam->delete(TABLE_CHTEAM, $chteamID);

        return print(js::reload('parent'));
    }

    /**
     * Ajax get project drop menu.
     *
     * @param  int     $projectID
     * @param  string  $module
     * @param  string  $method
     * @access public
     * @return void
     */
    public function ajaxGetDropMenu($teamID, $module, $method)
    {
        $this->view->link   = $this->chteam->getLink($module, $method, $teamID);
        $this->view->teamID = $teamID;
        $this->view->teams  = $this->chteam->getList('myInvolved');

        $this->display();
    }
}
