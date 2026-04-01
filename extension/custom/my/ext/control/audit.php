<?php
class myMy extends my
{
    public function audit($browseType = 'all', $param = 0, $orderBy = 'time_desc', $recTotal = 0, $recPerPage = 15, $pageID = 1)
    {
        $this->app->loadClass('pager', true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        $typeList = array();
        if($this->app->rawMethod == 'contribute')
        {
            $reviewList = $this->my->getReviewedList($browseType, $orderBy, $pager);
        }
        else
        {
            $typeList = $this->my->getReviewingTypeList();
            if(!isset($typeList->$browseType)) $browseType = 'all';

            $this->lang->my->featureBar['audit'] = (array)$typeList;
            $reviewList = $this->my->getReviewingList($browseType, $orderBy, $pager);
        }

        $this->view->flows = array();
        if($this->config->edition == 'max' or $this->config->edition == 'ipd')
        {
            $this->app->loadLang('approval');
            $this->loadModel('flow');
            $this->view->flows = $this->dao->select('module,name')->from(TABLE_WORKFLOW)->where('buildin')->eq(0)->fetchPairs('module', 'name');

            $reviewActions = $this->dao->select('*')->from(TABLE_WORKFLOWACTION)
                ->where('role')->eq('approval')
                ->beginIF($browseType != 'all')->andWhere('module')->eq($browseType)->fi()
                ->andWhere('action')->like("approvalreview%")
                ->andWhere('status')->eq('enable')
                ->fetchAll();

            foreach($reviewList as $review)
            {
                if($review->type == 'review' || $review->type == 'attend') continue;
                if(strpos(",{$config->my->oaObjectType},", ",$review->type,") !== false) continue;
                if(!in_array($module, array('demand', 'story', 'testcase', 'feedback')))
                {
                    if($review->type == 'business') $business = $this->dao->select('*')->from('zt_flow_business')->where('id')->eq($review->id)->fetch();
                    if($review->type == 'business' && $business->status == 'PRDReviewing')
                    {
                        $review->action = 'prdreview';
                    }
                    else
                    {
                        $review->action = 'approvalreview' . $this->flow->checkApprovalReviewMethod($reviewAction, $review);
                    }
                }
            }
        }

        $this->view->title       = $this->lang->review->common;
        $this->view->users       = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->reviewList  = $reviewList;
        $this->view->recTotal    = $recTotal;
        $this->view->recPerPage  = $recPerPage;
        $this->view->pageID      = $pageID;
        $this->view->browseType  = $browseType;
        $this->view->orderBy     = $orderBy;
        $this->view->pager       = $pager;
        $this->view->mode        = 'audit';
        $this->display();
    }
}
