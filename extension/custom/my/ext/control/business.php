<?php
class myMy extends my
{
    public function business($type = 'assignedTo', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('projectapproval');
        $this->loadModel('business');
        $queryID  = ($type == 'bySearch') ? (int)$param : 0;

        /* Save session. */
        if($type != 'bySearch')            $this->session->set('myBusiness', $type);
        if($this->app->viewType != 'json') $this->session->set('businessList', $this->app->getURI(true));

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        if($this->app->getViewType() == 'mhtml') $recPerPage = 10;
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* append id for secend sort. */
        $sort = common::appendOrder($orderBy);

        /* Get tasks. */
        if($type == 'bySearch')
        {
            $businessList = $this->my->getBusinessList($this->app->user->account, $pager, $sort, $type, $queryID);
        }
        else
        {
            $businessList = $this->my->getBusinessList($this->app->user->account, $pager, $sort);
        }

        $module = 'business';
        $action = 'browse';

        $actionURL = $this->createLink('my', $this->app->rawMethod, "mode=business&browseType=bySearch&queryID=myQueryID");

        $fields = $this->loadModel('workflowaction', 'flow')->getFields($module, $action, true, $businessList);
        $flow   = $this->loadModel('workflow', 'flow')->getByModule($module);
        $action = $this->loadModel('workflowaction', 'flow')->getByModuleAndAction($module, $action);
        
        foreach($fields as $key => $value)
        {
            if(in_array($key, array('id', 'name', 'status', 'realGoLiveDate', 'project', 'createdDept', 'developmentBudget', 'createdBy', 'actions')))
            {
                $fields[$key]->show = 1;
            }
            else
            {
                $fields[$key]->show = 0;
            }
            
            if($key == 'actions')
            {
                unset($fields[$key]);
                $fields['actions'] = $value;
            }
        }
        $fields['actions'] = $fields['actions'];

        $this->loadModel('flow')->setWorkFlowSearchParams($flow, 'Business', $actionURL);

        $this->view->title        = $this->lang->my->common . $this->lang->colon . $this->lang->my->myBusiness;
        $this->view->position[]   = $this->lang->my->myBusiness;
        $this->view->businessList = $businessList;
        $this->view->recTotal     = $recTotal;
        $this->view->recPerPage   = $recPerPage;
        $this->view->pageID       = $pageID;
        $this->view->orderBy      = $orderBy;
        $this->view->type         = $type;
        $this->view->pager        = $pager;
        $this->view->mode         = 'business';
        $this->view->fields       = $fields;
        $this->view->flow         = $flow;

        $this->display();
    }
}
