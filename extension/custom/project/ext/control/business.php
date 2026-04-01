<?php
helper::importControl('project');
class myProject extends project
{
    /**
     * Browse record list of a flow.
     *
     * @param  string $mode         browse | bysearch
     * @param  int    $label
     * @param  string $category
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function business($projectID, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->project->setMenu($projectID);
        $module = 'business';
        $action = 'browse';

        $this->loadModel('flow');

        $this->session->set('businessViewBackUrl', rtrim(helper::createLink('project', 'business', 'projectID=' . $projectID), '='));

        $this->app->loadClass('pager', $static = true);

        $pager      = new pager($recTotal, $recPerPage, $pageID);
        $flow       = $this->loadModel('workflow', 'flow')->getByModule($module);
        $dataList   = $this->project->getBusinessList($projectID, $orderBy, $pager);
        $dataList   = $this->project->processBusiness($dataList);
        $fields     = $this->loadModel('workflowaction', 'flow')->getFields($module, $action, true, $dataList);
        $browseLink = $this->createLink('project', 'business', "projectID=$projectID&orderBy=$orderBy&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID");

        if(isset($fields['project'])) $fields['project']->options = $this->dao->select('id, name')->from('zt_flow_projectapproval')->fetchPairs('id', 'name');

        $newFields = array();
        foreach($fields as $key => $value)
        {
            if(in_array($key, array('dept', 'project'))) $value->show = 0;

            if($key == 'developmentBudget') $value->show = 1;
            $newFields[$key] = $value;
            if($key == 'name')
            {
                $newFields['requirement'] = clone $fields['name'];
                $newFields['requirement']->field = 'requirement';
                $newFields['requirement']->name  = $this->lang->project->requirement;

                $newFields['estimate'] = clone $fields['name'];
                $newFields['estimate']->field = 'estimate';
                $newFields['estimate']->name  = $this->lang->project->estimate;

                $newFields['developmentBudget'] = clone $fields['developmentBudget'];
            }
        }

        $this->view->title     = $this->lang->common->business;
        $this->view->dataList  = $dataList;
        $this->view->summary   = $this->flow->getSummary($dataList, $fields);
        $this->view->action    = $action;
        $this->view->orderBy   = $orderBy;
        $this->view->pager     = $pager;
        $this->view->fields    = $newFields;
        $this->view->flow      = $flow;
        $this->view->projectID = $projectID;

        $this->display();
    }
}
