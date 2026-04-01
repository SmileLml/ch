<?php
class myMy extends my
{
    public function projectapproval($type = 'assignedTo', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $queryID  = ($type == 'bySearch') ? (int)$param : 0;

        /* Save session. */
        if($type != 'bySearch')            $this->session->set('myProjectapproval', $type);
        if($this->app->viewType != 'json') $this->session->set('projectapprovalList', $this->app->getURI(true));

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'mhtml') $recPerPage = 10;
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* append id for secend sort. */
        $sort = common::appendOrder($orderBy);

        /* Get tasks. */
        if($type == 'bySearch')
        {
            $projectapprovalList = $this->my->getProjectapprovalList($this->app->user->account, $pager, $sort, $type, $queryID);
        }
        else
        {
            $projectapprovalList = $this->my->getProjectapprovalList($this->app->user->account, $pager, $sort);
        }

        $module = 'projectapproval';
        $action = 'browse';

        $actionURL = $this->createLink('my', $this->app->rawMethod, "mode=projectapproval&browseType=bySearch&queryID=myQueryID");

        $fields = $this->loadModel('workflowaction', 'flow')->getFields($module, $action, true, $projectapprovalList);
        $flow   = $this->loadModel('workflow', 'flow')->getByModule($module);
        $action = $this->loadModel('workflowaction', 'flow')->getByModuleAndAction($module, $action);

        $this->loadModel('flow')->setWorkFlowSearchParams($flow, 'Projectapproval', $actionURL);

        $this->view->title               = $this->lang->my->common . $this->lang->colon . $this->lang->my->myProjectapproval;
        $this->view->position[]          = $this->lang->my->myProjectapproval;
        $this->view->projectapprovalList = $projectapprovalList;
        $this->view->recTotal            = $recTotal;
        $this->view->recPerPage          = $recPerPage;
        $this->view->pageID              = $pageID;
        $this->view->orderBy             = $orderBy;
        $this->view->type                = $type;
        $this->view->pager               = $pager;
        $this->view->mode                = 'projectapproval';
        $this->view->fields              = $fields;
        $this->view->flow                = $flow;

        $this->display();
    }
}