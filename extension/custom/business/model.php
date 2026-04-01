<?php
/**
 * The model file of business module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     business
 * @version     $Id: model.php 5118 2013-07-12 07:41:41Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class businessModel extends model
{
    public function changeStatusProjecting()
    {
        $businessList = $this->dao->select('*')->from('zt_flow_business')->where('status')->eq('approvedProject')->fetchAll('id');
        foreach($businessList as $business)
        {
            $this->loadModel('flow');
            if(!$business->project)
            {
                $this->dao->update('zt_flow_business')->set('status')->eq('projecting')->where('id')->eq($business->id)->exec();

                $this->flow->mergeVersionByObjectType($business->id, 'business');
            }
            else
            {
                $projectapproval = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($business->project)->fetch();
                if(in_array($projectapproval->status, array('draft', 'reviewing', 'toBeEvaluated', 'pendingReview', 'underReview')))
                {
                    $this->dao->update('zt_flow_business')->set('status')->eq('projecting')->where('id')->eq($business->id)->exec();

                    $this->flow->mergeVersionByObjectType($business->id, 'business');
                }
            }
        }
    }

    public function changeStatusPortionPRD()
    {
        $requirementIdList = $this->dao->select('business')->from('zt_story')->where('business')->ne('')->andWhere('status')->in('active,devInProgress,closed,beOnline')->fetchPairs('business');
        $this->dao->update('zt_flow_business')->set('status')->eq('portionPRD')->where('id')->in($requirementIdList)->andWhere('status')->eq('approvedProject')->exec();

        $projectIdList         = $this->dao->select('id')->from('zt_project')->where('instance')->ne('')->fetchPairs('id');
        $projectIdList         = $this->dao->select('project')->from('zt_projectstory')->where('project')->in($projectIdList)->fetchPairs('project');
        $projectapprovalIdList = $this->dao->select('instance')->from('zt_project')->where('id')->in($projectIdList)->fetchPairs('instance');
        $this->dao->update('zt_flow_projectapproval')->set('status')->eq('design')->where('id')->in($projectapprovalIdList)->andWhere('status')->eq('approvedProject')->exec();

        $projectbusinessGroup = $this->dao->select('t1.parent, t1.business, t2.status')
            ->from('zt_flow_projectbusiness')->alias('t1')
            ->leftJoin('zt_flow_business')->alias('t2')->on('t1.business=t2.id')
            ->where('t1.deleted')->eq(0)
            ->fetchGroup('parent');
        $devTestIdList = array();
        $closureIdList = array();
        foreach($projectbusinessGroup as $businessList)
        {
            $isAllBeOnline        = true;
            $isPRDPassed          = false;
            $tempProjectappovalId = 0;
            foreach($businessList as $business)
            {
                $tempProjectappovalId = $business->parent;
                if(in_array($business->status, array('beOnline', 'closed', 'PRDPassed'))) $isPRDPassed = true;
                if(!in_array($business->status, array('beOnline', 'closed'))) $isAllBeOnline = false;
            }
            if($isPRDPassed == true && $isAllBeOnline == false) $devTestIdList[] = $tempProjectappovalId;
            if($isAllBeOnline == true) $closureIdList[] = $tempProjectappovalId;
        }

        $this->dao->update('zt_flow_projectapproval')->set('status')->eq('devTest')->where('id')->in($devTestIdList)->andWhere('status')->in(array('approvedProject', 'design'))->exec();
        $this->dao->update('zt_flow_projectapproval')->set('status')->eq('closure')->where('id')->in($closureIdList)->andWhere('status')->in(array('approvedProject', 'design', 'devTest'))->exec();
    }

    public function changeVersion($type = 'business')
    {
        if($type == 'business')
        {
            $businessList = $this->dao->select('id')->from('zt_flow_business')->where('status')->in('approvedProject,portionPRD,PRDPassed,beOnline')->andWhere('deleted')->eq(0)->andWhere('project')->ne('')->fetchPairs('id');

            $this->dao->begin();

            $this->loadModel('flow');
            foreach($businessList as $businessID) $this->flow->mergeVersionByObjectType($businessID, 'business');

            $this->dao->commit();
        }

        if($type == 'projectapproval')
        {
            //$projectapprovals = $this->dao->select('id')->from('zt_flow_projectapproval')->where('deleted')->eq(0)->andWhere('status')->in('approvedProject,design,devTest,closure')->fetchPairs('id');
            $projectapprovals = $this->dao->select('id')->from('zt_flow_projectapproval')->where('id')->eq('726')->fetchPairs('id');

            $this->dao->begin();

            $this->loadModel('flow');
            foreach($projectapprovals as $projectapprovalID) $this->flow->mergeVersionByObjectType($projectapprovalID, 'projectapproval');

            $this->dao->commit();
        }
    }

    /**
     * Change status of business.
     *
     * @access public
     * @return mixed
     */
    public function changeBusinessStatus()
    {
        $migrationBusiness = $this->dao->select('REQid')->from('zt_business')->fetchPairs('REQid');

        $beOnlineMigrationList = $this->dao->select('id')->from('zt_flow_business')
            ->where('status')->eq('beOnline')
            ->andWhere('deleted')->eq(0)
            ->andWhere('REQid')->in($migrationBusiness)
            ->fetchPairs('id');

        $businessList = $this->dao->select('id, status')->from('zt_flow_business')
            ->where('status')->in('approvedProject,portionPRD,PRDPassed,beOnline')
            ->andWhere('deleted')->eq(0)
            ->andWhere('project')->ne('')
            ->andWhere('id')->notin($beOnlineMigrationList)
            ->fetchPairs('id', 'status');

        $storyList = $this->dao->select('id, status, business')->from('zt_story')->where('deleted')->eq(0)->andWhere('business')->in(array_keys($businessList))->fetchGroup('business');

        $this->dao->begin();

        foreach($businessList as $businessID => $businessStatus)
        {
            $storyByBusiness = isset($storyList[$businessID]) ? $storyList[$businessID] : '';

            //有关联的激活及之后状态的史诗，则变更为prd部分通过。
            if($businessStatus == 'approvedProject')
            {
                if($storyByBusiness)
                {
                    $draftStoryCount = $this->getStoryStatusCount($storyByBusiness, 'draft');
                    if($draftStoryCount != count($storyByBusiness))
                    {
                        $this->updateBusinessStatus($businessID, $businessStatus, 'portionPRD');
                        continue;
                    }
                }

                continue;
            }

            if($businessStatus != 'approvedProject')
            {
                //没有关联的史诗,需要把状态更新为approvedProject(已立项);
                if(!$storyByBusiness)
                {
                    $this->updateBusinessStatus($businessID, $businessStatus, 'approvedProject');
                    continue;
                }

                //有关联的史诗，并且关联的史诗均未激活,需要把状态更新为approvedProject(已立项);
                $draftStoryCount = $this->getStoryStatusCount($storyByBusiness, 'draft');
                if($draftStoryCount == count($storyByBusiness))
                {
                    $this->updateBusinessStatus($businessID, $businessStatus, 'approvedProject');
                    continue;
                }
            }

            //有关联的史诗 且 史诗状态存在未激活，则变更为prd部分通过。
            if($businessStatus != 'portionPRD' && $storyByBusiness)
            {
                $draftStoryCount = $this->getStoryStatusCount($storyByBusiness, 'draft');
                if($draftStoryCount > 0 && $draftStoryCount != count($storyByBusiness))
                {
                    $this->updateBusinessStatus($businessID, $businessStatus, 'portionPRD');
                    continue;
                }
            }

            //有关联的史诗 且 史诗均为“已上线/已验收”则变更为已上线。
            if($businessStatus == 'PRDPassed' && $storyByBusiness)
            {
                $beOnlineStoryCount = $this->getStoryStatusCount($storyByBusiness, 'beOnline,closed');
                if($beOnlineStoryCount == count($storyByBusiness))
                {
                    $this->updateBusinessStatus($businessID, $businessStatus, 'beOnline');
                    continue;
                }
            }

            //有关联的史诗 且 史诗均激活 且 不完全为“已上线/已验收”状态时，则变更为prd 已通过。
            if($businessStatus == 'beOnline' && $storyByBusiness)
            {
                $PRDPassedStoryCount = $this->getStoryStatusCount($storyByBusiness, 'beOnline,closed');
                if($PRDPassedStoryCount != count($storyByBusiness))
                {
                    $this->updateBusinessStatus($businessID, $businessStatus, 'PRDPassed');
                    continue;
                }
            }
        }

        $this->dao->commit();
    }

    /**
     * Get story status count.
     *
     * @param  array  $stories
     * @param  array  $status
     * @access public
     * @return int
     */
    public function getStoryStatusCount($stories, $status = '')
    {
        $statusList = explode(',', $status);

        $storyIdList = [];
        foreach($stories as $story)
        {
            if(in_array($story->status, $statusList)) $storyIdList[] = $story->id;
        }

        return count($storyIdList);
    }

    /**
     * Update business status.
     *
     * @param  int    $businessID
     * @param  string $oldStatus
     * @param  string $status
     * @access public
     * @return mixed
     */
    public function updateBusinessStatus($businessID, $oldStatus, $status)
    {
        a($businessID);
        a($oldStatus);
        a($status);
        a('---------------------------------');
        a('---------------------------------');
        //$this->dao->update('zt_flow_business')->set('status')->eq($status)->where('id')->eq($businessID)->exec();

        //$actionID = $this->loadModel('action')->create('business', $businessID, 'changebusinessstatus');

        //$result['changes'][] = ['field' => 'status', 'old' => $oldStatus, 'new' => $status];
        //$this->loadModel('action')->logHistory($actionID, $result['changes']);

        //$this->loadModel('flow')->mergeVersionByObjectType($businessID, 'business');
    }

    public function cleaningApprovedProject()
    {
        //查历史记录，将现在是已立项，但原本是 已取消/已验收 的业务需求进行状态恢复 。
        $businessIdList = $this->dao->select('id')->from('zt_flow_business')->where('status')->eq('approvedProject')->andWhere('deleted')->eq(0)->fetchPairs('id');

        $historyList = $this->dao->select('action')->from(TABLE_HISTORY)->where('field')->eq('isCancel')->andWhere('new')->eq('Y')->fetchPairs('action', 'action');
        $actionList  = $this->dao->select('id,objectID,action,date')->from('zt_action')
            ->where('objectType')->eq('business')
            ->andWhere('objectID')->in($businessIdList)
            ->andWhere('id')->in($historyList)
            ->fetchGroup('objectID');

        $cancelBusinessIDList = [];
        foreach($actionList as $businessID => $actions)
        {
            $isCancel = false;
            foreach($actions as $action)
            {
                $projectchangeStatus = $this->dao->select('action')->from('zt_action')
                    ->where('objectType')->eq('business')
                    ->andWhere('objectID')->in($businessIdList)
                    ->andWhere('action')->in('passprojectchange,cancelprojectchange')
                    ->andWhere('date')->gt($action->date)
                    ->orderBy('date asc')
                    ->fetch('action');

                if($projectchangeStatus == 'passprojectchange') $isCancel = true;
            }

            if($isCancel)
            {
                $this->updateBusinessStatus($businessID, 'approvedProject', 'cancelled');
                $cancelBusinessIDList[$businessID] = $businessID;
            }
        }

        $changBusinessIDList = $this->dao->select('objectID,action')->from('zt_action')
            ->where('objectType')->eq('business')
            ->andWhere('objectID')->in($businessIdList)
            ->andWhere('action')->in('businesscancel,close')
            ->andWhere('objectID')->notin($cancelBusinessIDList)
            ->orderBy('action asc')
            ->fetchPairs('objectID', 'action');

        if($changBusinessIDList)
        {
            foreach($changBusinessIDList as $businessID => $status)
            {
                if($status == 'businesscancel') $action = 'cancelled';
                if($status == 'close')          $action = 'closed';

                $this->updateBusinessStatus($businessID, 'approvedProject', $action);
            }
        }
    }

    public function cleaningMigrationBusiness()
    {
        $this->loadModel('workflowfield');
        $this->loadModel('workflowaction');

        $statusLineField = $this->workflowfield->getByField('business', 'status');
        $statusLineList  = $this->workflowaction->getRealOptions($statusLineField);

        $migrationBusiness = $this->dao->select('REQid,status')->from('zt_business')->fetchPairs('REQid', 'status');
        $businessIdList = $this->dao->select('id,REQid,status')->from('zt_flow_business')->where('REQid')->in(array_keys($migrationBusiness))->andWhere('deleted')->eq(0)->fetchAll('REQid');

        foreach($migrationBusiness as $REQid => $status)
        {
            if(isset($businessIdList[$REQid]) && $status != $businessIdList[$REQid])
            {
                $oldStatus = $businessIdList[$REQid]->status;

                if(!$statusLineList[$businessIdList[$REQid]->status]) continue;

                if($status != $statusLineList[$businessIdList[$REQid]->status])
                {
                    $businessID = $businessIdList[$REQid]->id;

                    //将历史迁移数据中，筛选”已取消 "的业务需求，根据 REQ id 在禅道做匹配 ，将状态刷新为已取消。
                    if($status == $statusLineList['cancelled'])
                    {
                        $this->updateBusinessStatus($businessID, $oldStatus, 'cancelled');
                        continue;
                    }

                    //将历史迁移数据中，筛选”已上线 "的业务需求，根据 REQ id 在禅道做匹配 ，将当前非取消和验收状态的需求，将状态刷新为已上线 。
                    if($status == $statusLineList['beOnline'] && (!in_array($oldStatus, array('cancelled', 'closed'))))
                    {
                        $this->updateBusinessStatus($businessID, $oldStatus, 'beOnline');
                        continue;
                    }

                    //将历史迁移数据中，筛选”已验收 "的业务需求，根据 REQ id 在禅道做匹配 ，将当前非取消状态的需求，将状态刷新为已验收 。
                    if($status == $statusLineList['closed'] && $oldStatus != 'cancelled')
                    {
                        $this->updateBusinessStatus($businessID, $oldStatus, 'closed');
                        continue;
                    }
                }
            }
        }
    }

    public function changeStoryStatusToClosed()
    {
        $closedStories = $this->dao->select('storyID')->from('zt_closedstory')->fetchPairs('storyID');

        $activeStoryIDList = $this->dao->select('objectID')->from('zt_action')
            ->where('objectType')->eq('story')
            ->andWhere('objectID')->in($closedStories)
            ->andWhere('action')->eq('activated')
            ->andWhere('actor')->eq('admin')
            ->andWhere('date')->between('2025-02-10 10:00:00', '2025-02-12 14:00:00')
            ->fetchPairs('objectID');

        $oldStories = $this->dao->select('*')->from(TABLE_STORY)
            ->where('id')->in($activeStoryIDList)
            ->andWhere('deleted')->eq(0)
            ->andWhere('status')->ne('closed')
            ->fetchAll('id');

        $now = helper::now();
        foreach($oldStories as $storyID => $oldStory)
        {
            if($oldStory->parent == -1) continue;

            $story = new stdclass();
            $story->lastEditedBy   = $this->app->user->account;
            $story->lastEditedDate = $now;
            $story->closedBy       = $this->app->user->account;
            $story->closedDate     = $now;
            $story->assignedDate   = $now;
            $story->status         = 'closed';

            $story->closedReason   = 'finish';
            $story->duplicateStory = $oldStory->duplicateStory;
            $story->childStories   = $oldStory->childStories;

            if($story->closedReason != 'done') $story->plan  = '';

            $stories[$storyID] = $story;
            unset($story);
        }

        if($stories)
        {
            $this->loadModel('story');
            $this->loadModel('action');

            $this->dao->begin();
            foreach($stories as $storyID => $story)
            {
                if(!$story->closedReason) continue;

                $oldStory = $oldStories[$storyID];

                $this->dao->update(TABLE_STORY)->data($story)->where('id')->eq($storyID)->exec();

                /* Update parent story status. */
                if($oldStory->parent > 0) $this->story->updateParentStatus($storyID, $oldStory->parent);

                $changes  = common::createChanges($oldStory, $story);
                $actionID = $this->action->create('story', $storyID, 'Closed', '', 'finish');

                if($changes) $this->action->logHistory($actionID, $changes);

                if($this->config->edition != 'open' && $oldStory->feedback && !isset($feedbacks[$oldStory->feedback]))
                {
                    $feedbacks[$oldStory->feedback] = $oldStory->feedback;
                    $this->loadModel('feedback')->updateStatus('story', $oldStory->feedback, $story->status, $oldStory->status);
                }

                $this->loadModel('score')->create('story', 'close', $storyID);
            }

            $this->dao->commit();
        }
    }

    public function changeStatusToBeOnline()
    {
        $REQidList      = $this->dao->select('REQid')->from('zt_business')->fetchPairs('REQid');
        $businessIdList = $this->dao->select('id,status')->from('zt_flow_business')
            ->where('REQid')->notin($REQidList)
            ->andWhere('deleted')->eq(0)
            ->andWhere('status')->eq('approvedProject')
            ->fetchPairs('id', 'status');

        foreach($businessIdList as $businessID => $status)
        {
            $this->updateBusinessStatus($businessID, $status, 'beOnline');
            continue;
        }
    }

    public function updateErrorVersion()
    {
        //查询版本不一致的业务需求,根据上面的sql，生成查询语句
        $businesses = $this->dao->select('fb.id, fb.name, fb.version AS fbversion, latest_version.version AS latversion')
            ->from('zt_flow_business fb')
            ->leftJoin('(SELECT objectID, MAX(version) AS version, objectType FROM zt_objectversion where objectType=business GROUP BY objectID) AS latest_version')
            ->on('fb.id = latest_version.objectID')
            ->where('fb.version != latest_version.version')
            ->andWhere('latest_version.objectType')->eq('business')
            ->fetchAll('id');

        //将版本号不一致的业务需求，将版本号更新为最新版本号。
        foreach($businesses as $businessID => $business)
        {
            $this->dao->update('zt_flow_business')->set('version')->eq($business->latversion)->where('id')->eq($businessID)->exec();
        }
    }

    public function updateBusinessDate()
    {
        $updateBusinesses = $this->dao->select('*')->from('zt_businessdate')->fetchAll('businessID');

        $businesses = $this->dao->select('id,REQid,PRDconfirmDate,goLiveConfirmDate,closeDate')->from('zt_flow_business')->where('REQid')->in(array_keys($updateBusinesses))->fetchAll('REQid');

        $this->loadModel('flow');
        $this->loadModel('action');
        foreach($businesses as $REQid => $business)
        {
            $data = [];

            $updateBusiness = $updateBusinesses[$REQid];

            if((!helper::isZeroDate($updateBusiness->PRDconfirmDate)) && (helper::isZeroDate($business->PRDconfirmDate)) && ($updateBusiness->PRDconfirmDate != $business->PRDconfirmDate)) $data['PRDconfirmDate'] = $updateBusiness->PRDconfirmDate;
            if((!helper::isZeroDate($updateBusiness->goLiveConfirmDate)) && (helper::isZeroDate($business->goLiveConfirmDate)) && ($updateBusiness->goLiveConfirmDate != $business->goLiveConfirmDate)) $data['goLiveConfirmDate'] = $updateBusiness->goLiveConfirmDate;
            if((!helper::isZeroDate($updateBusiness->closeDate)) && (helper::isZeroDate($business->closeDate)) && ($updateBusiness->closeDate != $business->closeDate)) $data['closeDate'] = $updateBusiness->closeDate;

            if(!empty($data))
            {
                $this->dao->update('zt_flow_business')->data($data)->where('id')->eq($business->id)->exec();
                $actionID = $this->action->create('business', $business->id, 'changebusinessdate');

                $changes  = common::createChanges($business, $data);
                $this->action->logHistory($actionID, $changes);

                $this->flow->mergeVersionByObjectType($business->id, 'business');
            }
        }
    }

    public function changeDemand()
    {
        $demands = $this->dao->select('id,demand')->from('zt_flow_business')->where('demand')->ne('')->fetchPairs('id');

         $demandList = array();
         foreach($demands as $demandStr)
         {
             $demands = array();
             $demands = explode(',', $demandStr);
             foreach($demands as $demand)
             {
                 if($demand) $demandList[$demand] = $demand;
             }
         }

         $demandStageID = $this->dao->select('id')->from(TABLE_DEMAND)->where('id')->in($demandList)->andWhere('stage')->eq(0)->fetchPairs('id');

         $this->dao->update(TABLE_DEMAND)->set('stage')->eq(1)->where('id')->in($demandStageID)->exec();
    }

    public function updatePRDconfirmDate()
    {
        $businesses = $this->dao->select('id,goLiveDate,PRDconfirmDate,goLiveConfirmDate')->from('zt_flow_business')
            ->where('deleted')->eq('0')
            ->andWhere('status')->in('PRDPassed,beOnline,closed')
            ->andWhere('PRDconfirmDate')->in(NULL)
            ->orderBy('id desc')
            ->fetchAll('id');

        $requirements = $this->dao->select('business, MAX(actualonlinedate) AS actualonlinedate')->from(TABLE_STORY)
            ->where('deleted')->eq('0')
            ->andWhere('business')->in(array_keys($businesses))
            ->andWhere('type')->eq('requirement')
            ->groupBy('business')
            ->fetchPairs('business');

        $this->loadModel('flow');
        foreach($businesses as $business)
        {
            if(helper::isZeroDate($business->goLiveDate)) continue;

            $this->dao->update('zt_flow_business')->set('PRDconfirmDate')->eq($business->goLiveDate)->where('id')->eq($business->id)->exec();

            $goLiveConfirmDate = (isset($requirements[$business->id]) && !helper::isZeroDate($requirements[$business->id])) ? $requirements[$business->id] : '';

            if($business->goLiveConfirmDate != $goLiveConfirmDate) $this->dao->update('zt_flow_business')->set('goLiveConfirmDate')->eq($goLiveConfirmDate)->where('id')->eq($business->id)->exec();

            $this->flow->mergeVersionByObjectType($business->id, 'business');
        }
    }
}
