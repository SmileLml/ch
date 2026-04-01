<?php
$this->loadModel('projectapproval');
$fields['reviewResult']->options['noReview'] = '不评审';

$project           = $this->dao->select('*')->from('zt_project')->where('instance')->eq($dataID)->fetch();
$storyIdList       = $this->dao->select('story')->from('zt_projectstory')->where('project')->eq($project->id)->fetchPairs('story');
$practicalBegin    = $this->dao->select('openedDate')->from('zt_story')->where('id')->in($storyIdList)->andWhere('type')->eq('requirement')->orderBy('id_asc')->fetch('openedDate');
$businessIdList    = $this->dao->select('parent, business')->from('zt_flow_projectbusiness')->where('parent')->eq($dataID)->andWhere('deleted')->eq(0)->fetchPairs('parent');
$practicalEnd      = $this->dao->select('closeDate')->from('zt_flow_business')->where('id')->in($businessIdList)->orderBy('closeDate_desc')->fetch('closeDate');
$allBugNum         = $this->dao->select('count(1) as number')->from('zt_bug')->where('project')->eq($project->id)->andWhere('deleted')->eq(0)->fetch('number');
$unsolvedBugNum    = $this->dao->select('count(1) as number')->from('zt_bug')->where('project')->eq($project->id)->andWhere('deleted')->eq(0)->andWhere('status')->eq('active')->fetch('number');
$onlineBugNum      = $this->dao->select('count(1) as number')->from('zt_bug')->where('project')->eq($project->id)->andWhere('deleted')->eq(0)->andWhere('defectedinversion')->eq('4')->fetch('number');
$sumEstimate       = $this->dao->select('sum(estimate) as sumEstimate')->from('zt_story')->where('id')->in($storyIdList)->andWhere('type')->eq('requirement')->andWhere('deleted')->eq(0)->fetch('sumEstimate');
$onlineBugprogress = (float)$sumEstimate == 0 ? '0%' : number_format((int)$onlineBugNum/(float)$sumEstimate*100, 2) . '%';

$projectUnitPrice = $this->loadModel('setting')->getItem("owner=system&module=common&section=&key=projectUnitPrice");

if($practicalEnd == '0000-00-00 00:00:00')
{
    $progressDeviation = '';
    $practicalEnd      = '';
}
else
{
    $deviationBegin    = $data->end > $practicalEnd ? $practicalEnd : $data->end;
    $deviationEnd      = $data->end > $practicalEnd ? $data->end : $practicalEnd;
    $workDays          = $this->loadModel('holiday')->getActualWorkingDays($deviationBegin, $deviationEnd);
    $progressDeviation = $data->end > $practicalEnd ? '+' . (string)count($workDays) : '-' . (string)count($workDays);
}


$allChangeNum = 0;
$changeTypeNum = array();
foreach($fields['changeType']->options as $changeTypeKey => $changeType)
{
    if(!empty($changeType)) $changeTypeNum[$changeTypeKey] = 0;
}
$tempActions = $this->loadModel('action')->getList($flow->module, $data->id);
foreach($tempActions as $tempAction)
{
    if($tempAction->action == 'approvalreview3')
    {
        $realAction = false;
        foreach($tempAction->history as $historyItem)
        {
            if($historyItem->field == 'changeType')
            {
                $allChangeNum += 1;
                $realAction = true;
            }
        }

        if($realAction)
        {
            foreach($tempAction->history as $historyItem)
            {
                if($historyItem->field == 'changeType')
                {
                    $changeTypes = explode(',', $historyItem->new);
                    foreach($changeTypes as $changeType)
                    {
                        $changeTypeNum[$changeType] += 1;
                    }
                }
            }
        }

    }
}

$data->finishReviewDate     = '';
$data->finishReviewLocation = '';
$data->finishParticipant    = array();
$data->finishAbsentee       = array();
$data->finishRecorder       = array();
$data->remark         = '';

$this->view->data              = $data;
$this->view->project           = $project;
$this->view->practicalBegin    = $practicalBegin;
$this->view->practicalEnd      = $practicalEnd;
$this->view->allBugNum         = $allBugNum;
$this->view->unsolvedBugNum    = $unsolvedBugNum;
$this->view->onlineBugNum      = $onlineBugNum;
$this->view->sumEstimate       = $sumEstimate;
$this->view->progressDeviation = $progressDeviation;
$this->view->onlineBugprogress = $onlineBugprogress;
$this->view->allChangeNum      = $allChangeNum;
$this->view->changeTypeNum     = $changeTypeNum;
