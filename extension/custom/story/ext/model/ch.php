<?php
public function changeActualonlinedate()
{
    $releases = $this->dao->select('id,stories,date')->from(TABLE_RELEASE)->where('status')->eq('normal')->andWhere('deleted')->eq('0')->fetchAll();

    foreach($releases as $release)
    {
        $linkStoryIdList   = explode(',', trim($release->stories, ','));
        $stories           = $this->dao->select('id,actualonlinedate')->from('zt_story')->where('id')->in($linkStoryIdList)->fetchPairs('id');

        $updateStoryIdList = array();
        foreach($stories as $storyID => $actualonlinedate)
        {
            if($actualonlinedate == '0000-00-00' || $actualonlinedate < $release->date) $updateStoryIdList[] = $storyID;
        }

        $this->dao->update('zt_story')->set('actualonlinedate')->eq($release->date)->where('id')->in($updateStoryIdList)->exec();
    }
}
/**
 * Review a story.
 *
 * @param  int    $storyID
 * @access public
 * @return bool
 */
public function review($storyID)
{
    if(strpos($this->config->story->review->requiredFields, 'comment') !== false and !$this->post->comment)
    {
        dao::$errors[] = sprintf($this->lang->error->notempty, $this->lang->comment);
        return false;
    }

    if($this->post->result == false)
    {
        dao::$errors[] = $this->lang->story->mustChooseResult;
        return false;
    }

    $oldStory = $this->dao->findById($storyID)->from(TABLE_STORY)->fetch();
    $now      = helper::now();
    $date     = helper::today();
    $story    = fixer::input('post')
        ->setDefault('lastEditedBy', $this->app->user->account)
        ->setDefault('lastEditedDate', $now)
        ->setDefault('status', $oldStory->status)
        ->setDefault('reviewedDate', $date)
        ->stripTags($this->config->story->editor->review['id'], $this->config->allowedTags)
        ->setIF(!$this->post->assignedTo, 'assignedTo', '')
        ->setIF(!empty($_POST['assignedTo']), 'assignedDate', $now)
        ->removeIF($this->post->result != 'reject', 'closedReason, duplicateStory, childStories')
        ->removeIF($this->post->result == 'reject' and $this->post->closedReason != 'duplicate', 'duplicateStory')
        ->removeIF($this->post->result == 'reject' and $this->post->closedReason != 'subdivided', 'childStories')
        ->add('reviewedBy', $oldStory->reviewedBy . ',' . $this->app->user->account)
        ->add('id', $storyID)
        ->remove('result,comment')
        ->get();

    $story->reviewedBy = implode(',', array_unique(explode(',', $story->reviewedBy)));
    $story = $this->loadModel('file')->processImgURL($story, $this->config->story->editor->review['id'], $this->post->uid);

    /* Fix bug #671. */
    $this->lang->story->closedReason = $this->lang->story->rejectedReason;

    $this->dao->update(TABLE_STORYREVIEW)
        ->set('result')->eq($this->post->result)
        ->set('reviewDate')->eq($now)
        ->where('story')->eq($storyID)
        ->andWhere('version')->eq($oldStory->version)
        ->andWhere('reviewer')->eq($this->app->user->account)
        ->exec();

    /* Sync twins. */
    if(!empty($oldStory->twins))
    {
        foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
        {
            $this->dao->update(TABLE_STORYREVIEW)
                ->set('result')->eq($this->post->result)
                ->set('reviewDate')->eq($now)
                ->where('story')->eq($twinID)
                ->andWhere('version')->eq($oldStory->version)
                ->andWhere('reviewer')->eq($this->app->user->account)
                ->exec();
        }
    }

    $story = $this->updateStoryByReview($storyID, $oldStory, $story);

    $skipFields      = 'finalResult';
    $isSuperReviewer = strpos(',' . trim(zget($this->config->story, 'superReviewers', ''), ',') . ',', ',' . $this->app->user->account . ',');
    if($isSuperReviewer === false)
    {
        $reviewers = $this->getReviewerPairs($storyID, $oldStory->version);
        if(count($reviewers) > 1) $skipFields .= ',closedReason';
    }

    $this->dao->update(TABLE_STORY)->data($story, $skipFields)
        ->autoCheck()
        ->batchCheck($this->config->story->review->requiredFields, 'notempty')
        ->checkIF($this->post->result == 'reject', 'closedReason', 'notempty')
        ->checkIF($this->post->result == 'reject' and $this->post->closedReason == 'duplicate',  'duplicateStory', 'notempty')
        ->checkFlow()
        ->where('id')->eq($storyID)
        ->exec();
    if(dao::isError()) return false;

    if($this->post->result != 'reject') $this->setStage($storyID);

    if(isset($story->closedReason) and $isSuperReviewer === false) unset($story->closedReason);
    $changes = common::createChanges($oldStory, $story);
    if($changes)
    {
        $actionID = $this->recordReviewAction($story, $this->post->result, $this->post->closedReason);
        $this->action->logHistory($actionID, $changes);

        $linkStoryIdList = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->eq($storyID)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');
        $linkStoryList   = $this->dao->select('*')->from('zt_story')->where('id')->in($linkStoryIdList)->fetchAll();
        foreach($linkStoryList as $linkStory)
        {
            $linkStory->finalResult = $story->finalResult;
            $actionID = $this->recordReviewAction($linkStory, $this->post->result, $this->post->closedReason);
            $this->action->logHistory($actionID, $changes);
        }
    }

    if(!empty($oldStory->twins)) $this->syncTwins($oldStory->id, $oldStory->twins, $changes, 'Reviewed');

    return true;
}

/**
 * Change history data.
 */
public function changeHistoryData()
{
    $storyIdList       = $this->dao->select('story')->from('zt_task')->where('status')->in(array('doing', 'done', 'pause', 'closed'))->andWhere('story')->ne(0)->fetchPairs('story');
    $activeStoryIdList = $this->dao->select('id')->from('zt_story')->where('id')->in($storyIdList)->andWhere('status')->in(array('active', 'closed'))->fetchPairs('id');
    $requirementIdList = $this->dao->select('id,AID')->from(TABLE_RELATION)->where('BID')->in($activeStoryIdList)->andWhere('BType')->eq('story')->fetchPairs('id', 'AID');
    $this->dao->update('zt_story')->set('status')->eq('devInProgress')->where('id')->in($requirementIdList)->andWhere('status')->eq('active')->exec();

    $requirementIdList = $this->dao->select('id')->from('zt_story')->where('business')->ne('')->andWhere('status')->in(array('active', 'devInProgress', 'closed', 'beOnline'))->fetchPairs('id');
    $storyIdList       = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->in($requirementIdList)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');

    $this->changeRequirementStatusByStoryStage($storyIdList);
}

public function changeRequirementStatusByStoryStage($storyIdList)
{
    $storyList = $this->dao->select('id,stage,type')->from('zt_story')
        ->where('id')->in($storyIdList)
        ->fetchAll();
    if(empty($storyList)) return false;
    $updateRequirementIdList = array();
    foreach($storyList as $story)
    {
        if($story->type == 'requirement')
        {
            $linkRequirementIds        = array($story->id);
            $updateRequirementIdList[] = $story->id;
        }
        else
        {
            $linkRequirementIds = $this->dao->select('id,AID')->from(TABLE_RELATION)->where('BID')->eq($story->id)->andWhere('BType')->eq('story')->fetchPairs('id', 'AID');
            $linkRequirementIds = $this->dao->select('id')->from('zt_story')->where('id')->in($linkRequirementIds)->andWhere('deleted')->eq(0)->fetchPairs();
        }
        if(empty($linkRequirementIds)) continue;
        foreach($linkRequirementIds as $requirementID)
        {
            $linkStoryIdList  = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->eq($requirementID)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');

            if(empty($linkStoryIdList)) continue;

            $linkStoryList = $this->dao->select('id,stage')->from('zt_story')
                ->where('id')->in($linkStoryIdList)
                ->andWhere('status')->in(array('active', 'closed', 'reviewing', 'changing', 'beOnline'))
                ->andWhere('deleted')->eq(0)
                ->fetchAll();

            $isBeOnline = true;
            foreach($linkStoryList as $linkStory)
            {
                if(!in_array($linkStory->stage, array('verified', 'released', 'closed'))) $isBeOnline = false;
            }
            if($isBeOnline) $updateRequirementIdList[] = $requirementID;
        }
    }
    if(empty($updateRequirementIdList)) return false;
    $updateRequirementIdList = $this->dao->select('id')->from('zt_story')->where('id')->in($updateRequirementIdList)->andWhere('status')->in(array('active', 'devInProgress', 'closed', 'beOnline'))->fetchPairs('id');
    if(empty($updateRequirementIdList)) return false;
    $updateRequirementOldStatusList = $this->dao->select('id,status')->from('zt_story')->where('id')->in($updateRequirementIdList)->andWhere('status')->in(array('active', 'devInProgress'))->andWhere('deleted')->eq(0)->fetchPairs('id');
    $this->dao->update('zt_story')->set('status')->eq('beOnline')->where('id')->in($updateRequirementIdList)->andWhere('status')->in(array('active', 'devInProgress'))->andWhere('deleted')->eq(0)->exec();
    foreach($updateRequirementOldStatusList as $key => $updateRequirementOldStatus)
    {
        $actionID = $this->loadModel('action')->create('story', $key, 'changebeonline');
        $result['changes']   = array();
        $result['changes'][] = ['field' => 'status', 'old' => $updateRequirementOldStatus, 'new' => 'beOnline'];
        $this->loadModel('action')->logHistory($actionID, $result['changes']);
    }

    $businessIdList          = $this->dao->select('business')->from('zt_story')->where('id')->in($updateRequirementIdList)->fetchPairs('business');
    $prdPassedBusinessIdList =  $this->dao->select('id')->from('zt_flow_business')->where('id')->in($businessIdList)->andWhere('status')->eq('PRDPassed')->fetchPairs('id');

    if(empty($prdPassedBusinessIdList)) return false;
    $businessRequirements = $this->dao->select('business, id, status')->from('zt_story')->where('business')->in($prdPassedBusinessIdList)->andWhere('deleted')->eq(0)->fetchGroup('business', 'id');

    $beOnlineBusinessIdList = array();
    foreach($businessRequirements as $businessID => $requirements)
    {
        $isBeOnline = true;
        foreach($requirements as $requirement)
        {
            if(!in_array($requirement->status, array('beOnline', 'closed'))) $isBeOnline = false;
        }
        if($isBeOnline) $beOnlineBusinessIdList[] = $businessID;
    }
    if(empty($beOnlineBusinessIdList)) return false;

    $this->dao->update('zt_flow_business')->set('status')->eq('beOnline')->set('realGoLiveDate')->eq(helper::now())->where('id')->in($beOnlineBusinessIdList)->exec();
    foreach($beOnlineBusinessIdList as $beOnlineBusinessID)
    {
        $this->loadModel('flow')->mergeVersionByObjectType($beOnlineBusinessID, 'business');
        $actionID = $this->loadModel('action')->create('business', $beOnlineBusinessID, 'changebeonline');
        $result['changes']   = array();
        $result['changes'][] = ['field' => 'status', 'old' => 'PRDPassed', 'new' => 'beOnline'];
        $this->loadModel('action')->logHistory($actionID, $result['changes']);
    }

    $this->loadModel('projectapproval')->changeStatusClosure($beOnlineBusinessIdList);
}

/**
 * Task estimate is exceed
 * @param int   $storyID
 * @param int   $estimate
 * @return bool
 */
public function taskEstimateIsExceed($storyID, $estimate)
{
    $taskEstimateSum = $this->dao->select('sum(estimate) as estimateSum')->from('zt_task')->where('story')->eq($storyID)->andWhere('deleted')->eq(0)->andWhere('parent')->ne('-1')->fetch('estimateSum');

    if((int)$taskEstimateSum > ($estimate * 8)) return false;

    return true;
}

/**
 * Get stories by a sql.
 *
 * @param  int    $productID
 * @param  string $sql
 * @param  string $orderBy
 * @param  object $pager
 * @param  string $type requirement|story
 * @access public
 * @return array
 */
public function getBySQL($productID, $sql, $orderBy, $pager = null, $type = 'story', $projectID = 0, $projectapprovalID = 0)
{
    /* Get plans. */
    $plans = $this->dao->select('id,title')->from(TABLE_PRODUCTPLAN)
        ->where('deleted')->eq('0')
        ->beginIF($productID != 'all' and $productID != '')->andWhere('product')->eq((int)$productID)->fi()
        ->fetchPairs();

    $review = $this->getRevertStoryIDList($productID);
    $sql = str_replace(array('`product`', '`version`', '`branch`'), array('t1.`product`', 't1.`version`', 't1.`branch`'), $sql);
    if(strpos($sql, 'result') !== false)
    {
        if(strpos($sql, 'revert') !== false)
        {
            $sql  = str_replace("AND `result` = 'revert'", '', $sql);
            $sql .= " AND t1.`id` " . helper::dbIN($review);
        }
        else
        {
            $sql = str_replace(array('`result`'), array('t3.`result`'), $sql);
        }
    }

    $pattern            = '/AND `relatedRequirement` = \'(\d+)\'/';
    $relatedRequirement = '';
    $relations          = array();
    if (preg_match($pattern, $sql, $matches))
    {
        $relatedRequirement = $matches[1];
        $sql = preg_replace($pattern, '', $sql);
    }
    if($relatedRequirement)
    {
        $relations = $this->dao->select('id,BID')->from(TABLE_RELATION)->alias('t1')
            ->where('AType')->eq('requirement')
            ->andWhere('BType')->eq('story')
            ->andWhere('relation')->eq('subdivideinto')
            ->andWhere('AID')->eq($relatedRequirement)
            ->fetchPairs();
    }

    $tmpStories = $this->dao->select("DISTINCT t1.*, IF(t1.`pri` = 0, {$this->config->maxPriValue}, t1.`pri`) as priOrder")->from(TABLE_STORY)->alias('t1')
        ->leftJoin(TABLE_PROJECTSTORY)->alias('t2')->on('t1.id=t2.story')
        ->beginIF(strpos($sql, 'result') !== false)->leftJoin(TABLE_STORYREVIEW)->alias('t3')->on('t1.id = t3.story and t1.version = t3.version')->fi()
        ->where($sql)
        ->beginIF($productID != 'all' and $productID != '')->andWhere('t1.`product`')->eq((int)$productID)->fi()
        ->beginIF($projectID)->andWhere('t2.`project`')->eq((int)$projectID)->fi()
        ->beginIF($projectapprovalID)->andWhere('t1.`status`')->eq('draft')->fi()
        ->beginIF($relations)->andWhere('t1.id')->in($relations)->fi()
        ->andWhere('t1.deleted')->eq(0)
        ->andWhere("FIND_IN_SET('{$this->config->vision}', t1.vision)")
        ->andWhere('t1.type')->eq($type)
        ->orderBy($orderBy)
        ->page($pager, 't1.id')
        ->fetchAll('id');

    if(!$tmpStories) return array();

    /* Process plans. */
    $stories = array();
    foreach($tmpStories as $story)
    {
        $story->planTitle = '';
        $storyPlans = explode(',', trim($story->plan, ','));
        foreach($storyPlans as $planID) $story->planTitle .= zget($plans, $planID, '') . ' ';
        $stories[$story->id] = $story;
    }

    return $stories;
}


/**
 * Get stories to link.
 *
 * @param  int     $storyID
 * @param  string  $type linkStories|linkRelateSR|linkRelateUR
 * @param  string  $browseType
 * @param  int     $queryID
 * @param  string  $storyType
 * @param  object  $pager
 * @param  string  $excludeStories
 * @access public
 * @return array
 */
public function getStories2Link($storyID, $type = 'linkStories', $browseType = 'bySearch', $queryID = 0, $storyType = 'story', $pager = null, $excludeStories = '')
{
    $story         = $this->getById($storyID);
    $tmpStoryType  = $storyType == 'story' ? 'requirement' : 'story';
    $stories2Link  = array();
    $projectID     = $this->dao->select('project')->from('zt_projectstory')->where('story')->eq($storyID)->orderBy('project_desc')->fetch('project');
    $project       = $this->loadModel('project')->getById($projectID);
    if($type == 'linkRelateSR' or $type == 'linkRelateUR')
    {
        $tmpStoryType   = $story->type;
        $linkStoryField = $story->type == 'story' ? 'linkStories' : 'linkRequirements';
        $storyIDList    = $story->id . ',' . $excludeStories . ',' . $story->{$linkStoryField};
    }
    else
    {
        $linkedStories = $this->getRelation($storyID, $story->type);
        $linkedStories = empty($linkedStories) ? array() : $linkedStories;
        $storyIDList   = array_keys($linkedStories);
    }

    if($browseType == 'bySearch')
    {

        if($project && $project->instance)
        {
            $stories2Link = $this->getBySearch('', $story->branch, $queryID, 'id_desc', '', $tmpStoryType, $storyIDList, '', $pager, $projectID, $project->instance);
        }
        else
        {
            $stories2Link = $this->getBySearch('', $story->branch, $queryID, 'id_desc', '', $tmpStoryType, $storyIDList, '', $pager, $projectID);
        }
    }
    elseif($type != 'linkRelateSR' and $type != 'linkRelateUR')
    {
        $status = $storyType == 'story' ? 'active' : 'all';
        if($project && $project->instance) $status = 'draft';
        $stories2Link = $this->getProductStories(0, $story->branch, 0, $status, $tmpStoryType, $orderBy = 'id_desc', true, $storyIDList, $pager, 0, $projectID);
    }

    if($type != 'linkRelateSR' and $type != 'linkRelateUR')
    {
        foreach($stories2Link as $id => $story)
        {
            if($storyType == 'story' and $story->status != 'active' and !($project and $project->instance)) unset($stories2Link[$id]);
        }
    }

    return $stories2Link;
}

/**
 * Format stories
 *
 * @param  array    $stories
 * @param  string   $type
 * @param  int      $limit
 * @access public
 * @return void
 */
public function formatStories($stories, $type = 'full', $limit = 0)
{
    /* Format these stories. */
    $storyPairs = array(0 => '');
    $i = 0;
    foreach($stories as $story)
    {
        if($type == 'short')
        {
            $property = '[p' . (!empty($this->lang->story->priList[$story->pri]) ? $this->lang->story->priList[$story->pri] : 0) . ', ' . $story->estimate . "{$this->lang->story->day}]";
        }
        elseif($type == 'full')
        {
            $property = '(' . $this->lang->story->pri . ':' . (!empty($this->lang->story->priList[$story->pri]) ? $this->lang->story->priList[$story->pri] : 0) . ',' . $this->lang->story->estimateAB . ':' . $story->estimate . $this->lang->story->day . ')';
        }
        else
        {
            $property = '';
        }
        $storyPairs[$story->id] = $story->id . ':' . $story->title . ' ' . $property;
    }
    return $storyPairs;
}

/**
 * Sync update link story status
 *
 * @param int    $storyId
 * @param string $status
 */
public function syncUpdateLinkStoryStatus($storyID)
{
    $story           = $this->getById($storyID);
    $linkStoryIdList = $this->dao->select('id,BID')->from(TABLE_RELATION)
        ->where('AID')->eq($storyID)
        ->andWhere('AType')->eq('requirement')
        ->fetchPairs('id', 'BID');

    if($linkStoryIdList) $this->dao->update(TABLE_STORY)->set('status')->eq($story->status)->where('id')->in($linkStoryIdList)->exec();
}
/**
 * Set story status by review rules.
 *
 * @param  array  $reviewerList
 * @access public
 * @return string
 */
public function getReviewResult($reviewerList)
{
    $results      = '';
    $passCount    = 0;
    $rejectCount  = 0;
    $revertCount  = 0;
    $clarifyCount = 0;
    $reviewRule   = $this->config->story->reviewRules;
    foreach($reviewerList as $reviewer => $result)
    {
        $passCount    = $result == 'pass'    ? $passCount    + 1 : $passCount;
        $rejectCount  = $result == 'reject'  ? $rejectCount  + 1 : $rejectCount;
        $revertCount  = $result == 'revert'  ? $revertCount  + 1 : $revertCount;
        $clarifyCount = $result == 'clarify' ? $clarifyCount + 1 : $clarifyCount;

        $results .= $result . ',';
    }

    $finalResult = '';
    if($passCount == count($reviewerList)) $finalResult = 'pass';

    if(empty($finalResult))
    {
        if($clarifyCount >= floor(count($reviewerList) / 2) + 1) return 'clarify';
        if($revertCount  >= floor(count($reviewerList) / 2) + 1) return 'revert';
        if($rejectCount  >= floor(count($reviewerList) / 2) + 1) return 'reject';

        if(strpos($results, 'clarify') !== false) return 'clarify';
        if(strpos($results, 'revert')  !== false) return 'revert';
        if(strpos($results, 'reject')  !== false) return 'reject';
    }

    return $finalResult;
}
/**
 * Set story status by reeview result.
 *
 * @param  int    $story
 * @param  int    $oldStory
 * @param  int    $result
 * @param  string $reason
 * @access public
 * @return array
 */
public function setStatusByReviewResult($story, $oldStory, $result, $reason = 'cancel')
{
    if($result == 'pass')
    {
        $story->status = $oldStory->status == 'PRDReviewing' ? 'PRDReviewed' : 'active';
    }

    if($result == 'clarify')
    {
        /* When the review result of the changed story is clarify, the status should be changing. */
        $isChanged = $oldStory->changedBy ? true : false;
        $story->status = $isChanged ? 'changing' : 'draft';
    }

    if($result == 'revert')
    {
        $story->status  = 'active';
        $story->version = $oldStory->version - 1;
        $story->title   = $this->dao->select('title')->from(TABLE_STORYSPEC)->where('story')->eq($story->id)->andWHere('version')->eq($oldStory->version - 1)->fetch('title');

        /* Delete versions that is after this version. */
        $this->dao->delete()->from(TABLE_STORYSPEC)->where('story')->eq($story->id)->andWHere('version')->in($oldStory->version)->exec();
        $this->dao->delete()->from(TABLE_STORYREVIEW)->where('story')->eq($story->id)->andWhere('version')->in($oldStory->version)->exec();

        /* Sync twins. */
        if(!empty($oldStory->twins))
        {
            foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
            {
                $this->dao->delete()->from(TABLE_STORYSPEC)->where('story')->eq($twinID)->andWHere('version')->in($oldStory->version)->exec();
                $this->dao->delete()->from(TABLE_STORYREVIEW)->where('story')->eq($twinID)->andWhere('version')->in($oldStory->version)->exec();
            }
        }
    }

    if($result == 'reject')
    {
        $now    = helper::now();
        $reason = (!empty($story->closedReason)) ? $story->closedReason : $reason;

        $story->status       = 'closed';
        $story->closedBy     = $this->app->user->account;
        $story->closedDate   = $now;
        $story->assignedTo   = 'closed';
        $story->assignedDate = $now;
        $story->stage        = $reason == 'done' ? 'released' : 'closed';
        $story->closedReason = $reason;
    }

    $story->finalResult = $result;

    /* If in ipd mode, set requirement status = 'launched'. */
    if($this->config->systemMode == 'PLM' and $oldStory->type == 'requirement' and $story->status == 'active' and (strpos($oldStory->vision, 'rnd') !== false)) $story->status = 'launched';
    if($story->status == 'launched')
    {
        $project = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($oldStory->id)->orderBy('project')->fetch();
        if($project) $story->status = 'developing';
    }

    return $story;
}

/**
 * Get affected things.
 *
 * @param  object  $story
 * @access public
 * @return object
 */
public function getAffectedScope($story)
{
    if($story->type == 'story') $storyIdList = $story->id;
    if($story->type == 'requirement')
    {
        $stories = $this->getStoryRelationByIds($story->id, 'requirement');
        if(isset($stories[$story->id]))
        {
            $storyIdList = $stories[$story->id];
        }
        else
        {
            $storyIdList = '';
        }
    }
    /* Remove closed executions. */
    if($story->executions)
    {
        foreach($story->executions as $executionID => $execution) if($execution->status == 'done') unset($story->executions[$executionID]);
    }

    /* Get team members. */
    if($story->executions)
    {
        $story->teams = $this->dao->select('account, root')
            ->from(TABLE_TEAM)
            ->where('root')->in(array_keys($story->executions))
            ->andWhere('type')->eq('project')
            ->fetchGroup('root');
    }

    if(!$story->twins && empty($storyIdList))
    {
        $story->bugs  = array();
        $story->cases = array();
        if($story->type == 'requirement')
        {
            $story->affectedStory = array();
            $story->affectedTask = array();
        }

        if($this->config->vision == 'or') $story->stories = array();
        return $story;
    }

    /* Get affected bugs. */
    $story->bugs = $this->dao->select('*')->from(TABLE_BUG)
        ->where('status')->ne('closed')
        ->beginIF($story->twins)->andWhere('story')->in(ltrim($story->twins, ',') . $storyIdList)->fi()
        ->beginIF(!$story->twins)->andWhere('story')->in($storyIdList)->fi()
        ->andWhere('deleted')->eq(0)
        ->orderBy('id desc')->fetchAll();

    /* Get affected cases. */
    $story->cases = $this->dao->select('*')->from(TABLE_CASE)
        ->where('deleted')->eq(0)
        ->beginIF($story->twins)->andWhere('story')->in(ltrim($story->twins, ',') . $storyIdList)->fi()
        ->beginIF(!$story->twins)->andWhere('story')->in($storyIdList)->fi()
        ->fetchAll();

    if($story->type == 'requirement')
    {
        $linkStoryIdList = $this->dao->select('id,BID')->from(TABLE_RELATION)
            ->where('AID')->eq($story->id)
            ->andWhere('AType')->eq('requirement')
            ->fetchPairs('id', 'BID');

        $story->affectedStory = $this->dao->select('*')->from(TABLE_STORY)
            ->where('deleted')->eq(0)
            ->andWhere('id')->in($linkStoryIdList)
            ->fetchAll();

        $story->affectedTask = $this->dao->select('t1.*, t2.id AS storyID, t2.title AS storyTitle, t2.product, t2.branch, t2.version AS latestStoryVersion, t2.status AS storyStatus, t3.realname AS assignedToRealName, IF(t1.`pri` = 0, 999, t1.`pri`) as priOrder')->from(TABLE_TASK)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->leftJoin(TABLE_USER)->alias('t3')->on('t1.assignedTo = t3.account')
            ->where('t1.deleted')->eq(0)
            ->andWhere('story')->in($storyIdList)
            ->fetchAll();
    }


    if($this->config->vision == 'or') $story->stories = $this->dao->select('t1.*, t2.spec, t2.verify, t3.name as productTitle, t3.deleted as productDeleted')
        ->from(TABLE_STORY)->alias('t1')
        ->leftJoin(TABLE_STORYSPEC)->alias('t2')->on('t1.id=t2.story')
        ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t1.product=t3.id')
        ->where('t1.version=t2.version')
        ->andWhere('t1.deleted')->eq(0)
        ->beginIF($story->twins)->andWhere('t1.id')->in(ltrim($story->twins, ',') . $storyIdList)->fi()
        ->beginIF(!$story->twins)->andWhere('t1.id')->in($storyIdList)->fi()
        ->fetchAll('id');

    return $story;
}

/**
 * Update a story.
 *
 * @param  int    $storyID
 * @access public
 * @return array  the changes of the story.
 */
public function update($storyID)
{
    $now      = helper::now();
    $oldStory = $this->getById($storyID);

    if(!empty($_POST['lastEditedDate']) and $oldStory->lastEditedDate != $this->post->lastEditedDate)
    {
        dao::$errors[] = $this->lang->error->editedByOther;
        return false;
    }

    $storyPlan = array();
    if(!empty($_POST['plan'])) $storyPlan = is_array($_POST['plan']) ? array_filter($_POST['plan']) : array($_POST['plan']);
    if(count($storyPlan) > 1)
    {
        $oldStoryPlan  = !empty($oldStory->planTitle) ? array_keys($oldStory->planTitle) : array();
        $oldPlanDiff   = array_diff($storyPlan, $oldStoryPlan);
        $storyPlanDiff = array_diff($oldStoryPlan, $storyPlan);
        if(!empty($oldPlanDiff) or !empty($storyPlanDiff))
        {
            dao::$errors[] = $this->lang->story->notice->changePlan;
            return false;
        }
    }

    if($this->config->vision != 'or')
    {
        /* Unchanged product when editing requirements on site. */
        $hasProduct = $this->dao->select('t2.hasProduct')->from(TABLE_PROJECTPRODUCT)->alias('t1')
            ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
            ->where('t1.product')->eq($oldStory->product)
            ->andWhere('t2.deleted')->eq(0)
            ->fetch('hasProduct');
        $_POST['product'] = (!empty($hasProduct) && !$hasProduct) ? $oldStory->product : $this->post->product;
    }

    $story = fixer::input('post')
        ->cleanInt('product,module,pri,duplicateStory')
        ->cleanFloat('estimate')
        ->setDefault('assignedDate', $oldStory->assignedDate)
        ->setDefault('lastEditedBy', $this->app->user->account)
        ->setDefault('reviewedBy', $oldStory->reviewedBy)
        ->setDefault('mailto', '')
        ->setDefault('deleteFiles', array())
        ->add('id', $storyID)
        ->add('lastEditedDate', $now)
        ->setDefault('plan,notifyEmail', '')
        ->setDefault('product', $oldStory->product)
        ->setDefault('branch', $oldStory->branch)
        ->setIF(!$this->post->linkStories, 'linkStories', '')
        ->setIF($this->post->assignedTo   != $oldStory->assignedTo, 'assignedDate', $now)
        ->setIF($this->post->closedBy     != false and $oldStory->closedDate == '', 'closedDate', $now)
        ->setIF($this->post->closedReason != false and $oldStory->closedDate == '', 'closedDate', $now)
        ->setIF($this->post->closedBy     != false or  $this->post->closedReason != false, 'status', 'closed')
        ->setIF($this->post->closedReason != false and $this->post->closedBy     == false, 'closedBy', $this->app->user->account)
        ->setIF($this->post->stage == 'released', 'releasedDate', $now)
        ->setIF(!in_array($this->post->source, $this->config->story->feedbackSource), 'feedbackBy', '')
        ->setIF(!in_array($this->post->source, $this->config->story->feedbackSource), 'notifyEmail', '')
        ->setIF(!empty($_POST['plan'][0]) and $oldStory->stage == 'wait', 'stage', 'planned')
        ->setIF(!isset($_POST['title']), 'title', $oldStory->title)
        ->setIF(!isset($_POST['spec']), 'spec', $oldStory->spec)
        ->setIF(!isset($_POST['verify']), 'verify', $oldStory->verify)
        ->stripTags($this->config->story->editor->edit['id'], $this->config->allowedTags)
        ->join('mailto', ',')
        ->join('linkStories', ',')
        ->join('linkRequirements', ',')
        ->join('childStories', ',')
        ->remove('files,labels,comment,contactListMenu,reviewer,needNotReview')
        ->get();
    if($story->business && $oldStory->business != $story->business)
    {
        list($result, $message) = $this->checkBusinessResidueEstimate($story->business);
        if(!$result)
        {
            dao::$errors[] = $this->lang->story->businessError;
            return false;
        }
    }

    /* Relieve twins when change product. */
    if(!empty($oldStory->twins) and $story->product != $oldStory->product)
    {
        $this->dbh->exec("UPDATE " . TABLE_STORY . " SET twins = REPLACE(twins, ',$storyID,', ',') WHERE `product` = $oldStory->product");
        $this->dao->update(TABLE_STORY)->set('twins')->eq('')->where('id')->eq($storyID)->orWhere('twins')->eq(',')->exec();
        $oldStory->twins = '';
    }

    if($oldStory->type == 'story' and !isset($story->linkStories)) $story->linkStories = '';
    if($oldStory->type == 'requirement' and !isset($story->linkRequirements)) $story->linkRequirements = '';
    if($oldStory->status == 'changing' and $story->status == 'draft') $story->status = 'changing';

    if(isset($story->plan) and is_array($story->plan)) $story->plan = trim(join(',', $story->plan), ',');
    if(isset($_POST['branch']) and $_POST['branch'] == 0) $story->branch = 0;

    if(isset($story->stage) and $oldStory->stage != $story->stage) $story->stagedBy = (strpos('tested|verified|released|closed', $story->stage) !== false) ? $this->app->user->account : '';
    $story = $this->loadModel('file')->processImgURL($story, $this->config->story->editor->edit['id'], $this->post->uid);

    if(isset($_POST['reviewer']) or isset($_POST['needNotReview']))
    {
        $_POST['reviewer'] = isset($_POST['needNotReview']) ? array() : array_filter($_POST['reviewer']);
        $oldReviewer       = $this->getReviewerPairs($storyID, $oldStory->version);

        /* Update story reviewer. */
        $this->dao->delete()->from(TABLE_STORYREVIEW)
            ->where('story')->eq($storyID)
            ->andWhere('version')->eq($oldStory->version)
            ->beginIF($oldStory->status == 'reviewing')->andWhere('reviewer')->notin(implode(',', $_POST['reviewer']))
            ->exec();

        /* Sync twins. */
        if(!empty($oldStory->twins))
        {
            foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
            {
                $this->dao->delete()->from(TABLE_STORYREVIEW)
                    ->where('story')->eq($twinID)
                    ->andWhere('version')->eq($oldStory->version)
                    ->beginIF($oldStory->status == 'reviewing')->andWhere('reviewer')->notin(implode(',', $_POST['reviewer']))
                    ->exec();
            }
        }

        foreach($_POST['reviewer'] as $reviewer)
        {
            if($oldStory->status == 'reviewing' and in_array($reviewer, array_keys($oldReviewer))) continue;

            $reviewData = new stdclass();
            $reviewData->story    = $storyID;
            $reviewData->version  = $oldStory->version;
            $reviewData->reviewer = $reviewer;
            $this->dao->insert(TABLE_STORYREVIEW)->data($reviewData)->exec();

            /* Sync twins. */
            if(!empty($oldStory->twins))
            {
                foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
                {
                    $reviewData->story = $twinID;
                    $this->dao->insert(TABLE_STORYREVIEW)->data($reviewData)->exec();
                }
            }
        }

        if($oldStory->status == 'reviewing') $story = $this->updateStoryByReview($storyID, $oldStory, $story);
        if(strpos('draft,changing', $oldStory->status) != false) $story->reviewedBy = '';

        $oldStory->reviewers = implode(',', array_keys($oldReviewer));
        $story->reviewers    = implode(',', array_keys($this->getReviewerPairs($storyID, $oldStory->version)));
    }

    $this->dao->update(TABLE_STORY)
        ->data($story, 'reviewers,spec,verify,finalResult,deleteFiles')
        ->autoCheck()
        ->batchCheck($this->config->story->edit->requiredFields, 'notempty')
        ->checkIF(isset($story->closedBy), 'closedReason', 'notempty')
        ->checkIF(isset($story->closedReason) and $story->closedReason == 'done', 'stage', 'notempty')
        ->checkIF(isset($story->closedReason) and $story->closedReason == 'duplicate',  'duplicateStory', 'notempty')
        ->checkIF($story->notifyEmail, 'notifyEmail', 'email')
        ->checkFlow()
        ->where('id')->eq((int)$storyID)->exec();
    if(dao::isError()) return false;

    if(!dao::isError())
    {
        $this->file->updateObjectID($this->post->uid, $storyID, 'story');
        $addedFiles = $this->file->saveUpload($oldStory->type, $storyID, $oldStory->version);

        if($story->spec != $oldStory->spec or $story->verify != $oldStory->verify or $story->title != $oldStory->title or !empty($story->deleteFiles) or !empty($addedFiles))
        {
            $addedFiles = empty($addedFiles) ? '' : join(',', array_keys($addedFiles)) . ',';
            $storyFiles = $oldStory->files = join(',', array_keys($oldStory->files));
            foreach($story->deleteFiles as $fileID) $storyFiles = str_replace(",$fileID,", ',', ",$storyFiles,");

            $data = new stdclass();
            $data->title  = $story->title;
            $data->spec   = $story->spec;
            $data->verify = $story->verify;
            $data->files  = $story->files = $addedFiles . trim($storyFiles, ',');
            $this->dao->update(TABLE_STORYSPEC)->data($data)->where('story')->eq((int)$storyID)->andWhere('version')->eq($oldStory->version)->exec();

            /* Sync twins. */
            if(!empty($oldStory->twins))
            {
                foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
                {
                    $this->dao->update(TABLE_STORYSPEC)->data($data)
                        ->where('story')->eq((int)$twinID)
                        ->andWhere('version')->eq($oldStory->version)
                        ->exec();
                }
            }
        }

        if($story->product != $oldStory->product)
        {
            $this->updateStoryProduct($storyID, $story->product);
            if($oldStory->parent == '-1')
            {
                $childStories = $this->dao->select('id')->from(TABLE_STORY)->where('parent')->eq($storyID)->andWhere('deleted')->eq(0)->fetchPairs('id');
                foreach($childStories as $childStoryID) $this->updateStoryProduct($childStoryID, $story->product);
            }
        }

        $this->loadModel('action');

        if($story->plan != $oldStory->plan)
        {
            if(!empty($oldStory->plan)) $this->action->create('productplan', $oldStory->plan, 'unlinkstory', '', $storyID);
            if(!empty($story->plan)) $this->action->create('productplan', $story->plan, 'linkstory', '', $storyID);
        }

        $changed = (isset($story->parent) && $story->parent != $oldStory->parent);
        if($oldStory->parent > 0)
        {
            $oldParentStory = $this->dao->select('*')->from(TABLE_STORY)->where('id')->eq($oldStory->parent)->fetch();
            $this->updateParentStatus($storyID, $oldStory->parent, !$changed);

            if($changed)
            {
                $oldChildren = $this->dao->select('id')->from(TABLE_STORY)->where('parent')->eq($oldStory->parent)->andWhere('deleted')->eq(0)->fetchPairs('id', 'id');
                if(empty($oldChildren)) $this->dao->update(TABLE_STORY)->set('parent')->eq(0)->where('id')->eq($oldStory->parent)->exec();
                $this->dao->update(TABLE_STORY)->set('childStories')->eq(join(',', $oldChildren))->set('lastEditedBy')->eq($this->app->user->account)->set('lastEditedDate')->eq(helper::now())->where('id')->eq($oldStory->parent)->exec();
                $this->action->create('story', $storyID, 'unlinkParentStory', '', $oldStory->parent, '', false);

                $actionID = $this->action->create('story', $oldStory->parent, 'unLinkChildrenStory', '', $storyID, '', false);

                $newParentStory = $this->dao->select('*')->from(TABLE_STORY)->where('id')->eq($oldStory->parent)->fetch();
                $changes = common::createChanges($oldParentStory, $newParentStory);
                if(!empty($changes)) $this->action->logHistory($actionID, $changes);
            }
        }

        if(isset($story->parent) && $story->parent > 0)
        {
            $parentStory = $this->dao->select('*')->from(TABLE_STORY)->where('id')->eq($story->parent)->fetch();
            $this->dao->update(TABLE_STORY)->set('parent')->eq(-1)->where('id')->eq($story->parent)->exec();
            $this->updateParentStatus($storyID, $story->parent, !$changed);

            if($changed)
            {
                $children = $this->dao->select('id')->from(TABLE_STORY)->where('parent')->eq($story->parent)->andWhere('deleted')->eq(0)->fetchPairs('id', 'id');
                $this->dao->update(TABLE_STORY)
                    ->set('parent')->eq('-1')
                    ->set('childStories')->eq(join(',', $children))
                    ->set('lastEditedBy')->eq($this->app->user->account)
                    ->set('lastEditedDate')->eq(helper::now())
                    ->where('id')->eq($story->parent)
                    ->exec();

                $this->action->create('story', $storyID, 'linkParentStory', '', $story->parent, '', false);
                $actionID = $this->action->create('story', $story->parent, 'linkChildStory', '', $storyID, '', false);

                $newParentStory = $this->dao->select('*')->from(TABLE_STORY)->where('id')->eq($story->parent)->fetch();
                $changes = common::createChanges($parentStory, $newParentStory);
                if(!empty($changes)) $this->action->logHistory($actionID, $changes);
            }
        }

        if(isset($story->closedReason) and $story->closedReason == 'done') $this->loadModel('score')->create('story', 'close');

        /* Set new stage and update story sort of plan when story plan has changed. */
        if($oldStory->plan != $story->plan)
        {
            $this->updateStoryOrderOfPlan($storyID, $story->plan, $oldStory->plan); // Insert a new story sort in this plan.

            if(empty($oldStory->plan) or empty($story->plan)) $this->setStage($storyID); // Set new stage for this story.
        }

        if(isset($story->stage) and $oldStory->stage != $story->stage)
        {
            $executionIdList = $this->dao->select('t1.project')->from(TABLE_PROJECTSTORY)->alias('t1')
                ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
                ->where('t1.story')->eq($storyID)
                ->andWhere('t2.deleted')->eq(0)
                ->andWhere('t2.type')->in('sprint,stage,kanban')
                ->fetchPairs();

            $this->loadModel('kanban');
            foreach($executionIdList as $executionID) $this->kanban->updateLane($executionID, 'story', $storyID);
        }

        unset($oldStory->parent);
        unset($story->parent);
        if($this->config->edition != 'open' && $oldStory->feedback) $this->loadModel('feedback')->updateStatus('story', $oldStory->feedback, $story->status, $oldStory->status);

        $linkStoryField = $oldStory->type == 'story' ? 'linkStories' : 'linkRequirements';
        $linkStories    = explode(',', $story->{$linkStoryField});
        $oldLinkStories = explode(',', $oldStory->{$linkStoryField});
        $addStories     = array_diff($linkStories, $oldLinkStories);
        $removeStories  = array_diff($oldLinkStories, $linkStories);
        $changeStories  = array_merge($addStories, $removeStories);
        $changeStories  = $this->dao->select("id,$linkStoryField")->from(TABLE_STORY)->where('id')->in(array_filter($changeStories))->fetchPairs();
        foreach($changeStories as $changeStoryID => $changeStory)
        {
            if(in_array($changeStoryID, $addStories))
            {
                $stories = empty($changeStory) ? $storyID : $changeStory . ',' . $storyID;
                $this->dao->update(TABLE_STORY)->set($linkStoryField)->eq($stories)->where('id')->eq((int)$changeStoryID)->exec();
            }

            if(in_array($changeStoryID, $removeStories))
            {
                $linkStories = str_replace(",$storyID,", ',', ",$changeStory,");
                $this->dao->update(TABLE_STORY)->set($linkStoryField)->eq(trim($linkStories, ','))->where('id')->eq((int)$changeStoryID)->exec();
            }
        }

        $changes = common::createChanges($oldStory, $story);
        if($this->post->uid != '' and isset($_SESSION['album']['used'][$this->post->uid])) $files = $this->file->getPairs($_SESSION['album']['used'][$this->post->uid]);

        if($this->post->comment != '' or !empty($changes))
        {
            $action   = !empty($changes) ? 'Edited' : 'Commented';
            $actionID = $this->action->create('story', $storyID, $action, $this->post->comment);
            $this->action->logHistory($actionID, $changes);

            if(isset($story->finalResult)) $this->recordReviewAction($story);
        }

        if(!empty($oldStory->twins)) $this->syncTwins($oldStory->id, $oldStory->twins, $changes, 'Edited');

        return true;
    }
}

/**
 * Build for datatable rows.
 *
 * @param  array    $stories
 * @param  array    $cols
 * @param  array    $options
 * @param  object   $execution
 * @param  string   $storyType
 * @param  int      $parentID
 * @access public
 * @return array
 */
public function generateRow($stories, $cols, $options, $execution, $storyType, $parentID = 0)
{
    $users         = zget($options, 'users',         array());
    $branches      = zget($options, 'branchOption',  array());
    $branchOptions = zget($options, 'branchOptions', array());
    $modulePairs   = zget($options, 'modulePairs',   array());
    $storyStages   = zget($options, 'storyStages',   array());
    $products      = zget($options, 'products',      array());
    $isShowBranch  = zget($options, 'isShowBranch',  '');

    $userFields  = array('openedBy', 'closedBy', 'lastEditedBy', 'feedbackBy');
    $dateFields  = array('assignedDate', 'openedDate', 'closedDate', 'lastEditedDate', 'reviewedDate', 'activatedDate');
    $executionID = empty($execution) ? $this->session->execution : $execution->id;
    $showBranch  = isset($this->config->product->browse->showBranch) ? $this->config->product->browse->showBranch : 1;
    $canView     = common::hasPriv($storyType, 'view', null, "storyType=$storyType");
    $tab         = $this->app->tab;
    $rows        = array();
    $roadmaps    = $this->config->edition == 'ipd' ? $this->loadModel('roadmap')->getPairs() : array();

    if($this->config->edition != 'open')
    {
        $this->loadModel('flow');
        $extendFields = $this->loadModel('workflowfield')->getList('story');
    }

    $storyIdList = array_keys($stories);

    $taskConsumedList = array();
    if($storyType == 'story')
    {
        $relatedRequirementIdList = array_column($stories, 'relatedRequirement', 'relatedRequirement');
        $relatedRequirements      = $this->dao->select('id,title')->from(TABLE_STORY)->where('id')->in($relatedRequirementIdList)->fetchPairs();
        $taskConsumedList = $this->dao->select('story,SUM(consumed) AS taskConsumed')
            ->from(TABLE_TASK)
            ->where('story')->in($storyIdList)
            ->andWhere('parent')->le('0')
            ->andWhere('deleted')->eq(0)
            ->groupBy('story')
            ->fetchPairs();
    }

    $storyTasks = $this->loadModel('task')->getStoryTaskCounts($storyIdList);
    $storyBugs  = $this->loadModel('bug')->getStoryBugCounts($storyIdList);
    $storyCases = $this->loadModel('testcase')->getStoryCaseCounts($storyIdList);

    if($this->config->vision == 'or') $this->app->loadLang('demand');
    foreach($stories as $story)
    {
        $data     = new stdclass();
        $menuType = 'browse';
        if(($tab == 'execution' || ($tab == 'project' and !$this->session->multiple)) && $storyType == 'story') $menuType = 'execution';

        if(!empty($branchOptions)) $branches = zget($branchOptions, $story->product, array());
        $data->id           = $story->id;
        $data->estimateNum  = $story->estimate;
        $data->caseCountNum = zget($storyCases, $story->id, 0);
        $data->actions      = '<div class="c-actions">' . $this->buildOperateMenu($story, $menuType, $execution, $storyType) . '</div>';
        foreach($cols as $col)
        {
            if($col->name == 'assignedTo')   $data->assignedTo   = $this->printAssignedHtml($story, $users, false);
            if($col->name == 'order')        $data->order        = "<i class='icon-move'>";
            if($col->name == 'pri')          $data->pri          = "<span class='" . ($story->pri ? "label-pri label-pri-" . $story->pri : '') . "' title='" . zget($this->lang->story->priList, $story->pri, $story->pri) . "'>" . zget($this->lang->story->priList, $story->pri, $story->pri) . "</span>";
            if($col->name == 'plan')         $data->plan         = isset($story->planTitle) ? $story->planTitle : '';
            if($col->name == 'product')      $data->product      = "<span title='" . zget($products, $story->product, '') . "'>" . zget($products, $story->product, '') . '</span>';
            if($col->name == 'branch')       $data->branch       = zget($branches, $story->branch, '');
            if($col->name == 'module')       $data->module       = zget($modulePairs, $story->module, '');
            if($col->name == 'source')       $data->source       = zget($this->lang->story->sourceList, $story->source);
            if($col->name == 'sourceNote')   $data->sourceNote   = $story->sourceNote;
            if($col->name == 'keywords')     $data->keywords     = $story->keywords;
            if($col->name == 'version')      $data->version      = $story->version;
            if($col->name == 'feedbackBy')   $data->feedbackBy   = $story->feedbackBy;
            if($col->name == 'notifyEmail')  $data->notifyEmail  = $story->notifyEmail;
            if($col->name == 'closedReason') $data->closedReason = zget($this->lang->story->reasonList, $story->closedReason, '');
            if($col->name == 'category')     $data->category     = isset($this->lang->story->categoryList[$story->category]) ? zget($this->lang->story->categoryList, $story->category) : zget($this->lang->story->ipdCategoryList, $story->category);
            if($col->name == 'duration')     $data->duration     = zget($this->lang->demand->durationList, $story->duration);
            if($col->name == 'BSA')          $data->BSA          = zget($this->lang->demand->bsaList, $story->BSA);
            if($col->name == 'taskCount')    $data->taskCount    = $storyTasks[$story->id] > 0 ? html::a(helper::createLink('story', 'tasks', "storyID=$story->id"), $storyTasks[$story->id], '', 'class="iframe" data-toggle="modal"') : '0';
            if($col->name == 'bugCount')     $data->bugCount     = $storyBugs[$story->id]  > 0 ? html::a(helper::createLink('story', 'bugs', "storyID=$story->id"),  $storyBugs[$story->id],  '', 'class="iframe" data-toggle="modal"') : '0';
            if($col->name == 'caseCount')    $data->caseCount    = $storyCases[$story->id] > 0 ? html::a(helper::createLink('story', 'cases', "storyID=$story->id"),  $storyCases[$story->id], '', 'class="iframe" data-toggle="modal"') : '0';
            if($col->name == 'estimate')     $data->estimate     = (float)$story->estimate . $this->lang->story->day;
            if($col->name == 'roadmap')      $data->roadmap      = "<span title='" . zget($roadmaps, $story->roadmap, '') . "'>" . zget($roadmaps, $story->roadmap, '') . '</span>';
            if($col->name == 'reviewedBy')
            {
                $reviewers = array_unique(array_filter(explode(',', $story->reviewedBy)));
                $reviewers = array_map(function($reviewer) use($users){return zget($users, $reviewer);}, $reviewers);
                $data->reviewedBy = join(' ', $reviewers);
            }
            if($col->name == 'reviewer')
            {
                $reviewers = array_unique(array_filter($story->reviewer));
                $reviewers = array_map(function($reviewer) use($users){return zget($users, $reviewer);}, $reviewers);
                $story->reviewer = join(' ', $reviewers);
                $data->reviewer  = "<span title='{$story->reviewer}'>" . $story->reviewer . '</span>';
            }
            if($col->name == 'stage')
            {
                $maxStage = $story->stage;
                if(isset($storyStages[$story->id]))
                {
                    $stageList   = join(',', array_keys($this->lang->story->stageList));
                    $maxStagePos = strpos($stageList, $maxStage);
                    foreach($storyStages[$story->id] as $storyBranch => $storyStage)
                    {
                        if(strpos($stageList, $storyStage->stage) !== false and strpos($stageList, $storyStage->stage) > $maxStagePos)
                        {
                            $maxStage    = $storyStage->stage;
                            $maxStagePos = strpos($stageList, $storyStage->stage);
                        }
                    }
                }
                $data->stage = zget($this->lang->story->stageList, $maxStage);
            }
            if($col->name == 'status')
            {
                $data->status = "<span class='status-{$story->status}'>" . $this->processStatus('story', $story) . '</span>';
                if($story->URChanged) $data->status = "<span class='status-story status-changed'>{$this->lang->story->URChanged}</span>";
            }
            if($col->name == 'title')
            {
                $storyTitle = '';
                $storyLink  = helper::createLink('story', 'view', "storyID=$story->id&version=0&param=&storyType=$story->type") . "#app=$tab";
                if($tab == 'project')
                {
                    $showBranch = isset($this->config->projectstory->story->showBranch) ? $this->config->projectstory->story->showBranch : 1;
                    $storyLink  = helper::createLink('story', 'view', "storyID=$story->id&version=0&param={$this->session->execution}&storyType=$story->type");
                    if($this->session->multiple)
                    {
                        $storyLink = helper::createLink('projectstory', 'view', "storyID=$story->id&project={$this->session->project}");
                        $canView   = common::hasPriv('projectstory', 'view');
                    }
                }
                elseif($tab == 'execution')
                {
                    $storyLink  = helper::createLink('execution', 'storyView', "storyID=$story->id&execution={$this->session->execution}");
                    $canView    = common::hasPriv('execution', 'storyView');
                    $showBranch = 0;
                    if($isShowBranch) $showBranch = isset($this->config->execution->story->showBranch) ? $this->config->execution->story->showBranch : 1;
                }

                if($storyType == 'requirement' and $story->type == 'story') $storyTitle .= '<span class="label label-badge label-light">SR</span> ';
                if($story->parent > 0 and isset($story->parentName)) $storyTitle .= "{$story->parentName} / ";
                if(isset($branches[$story->branch]) and $showBranch and $this->config->vision != 'lite') $storyTitle .= "<span class='label label-outline label-badge' title={$branches[$story->branch]}>{$branches[$story->branch]}</span> ";
                if($story->module and isset($modulePairs[$story->module])) $storyTitle .= "<span class='label label-gray label-badge'>{$modulePairs[$story->module]}</span> ";
                if($story->parent > 0 and !($storyType == 'requirement' and $story->type == 'story')) $storyTitle .= '<span class="label label-badge label-light" title="' . $this->lang->story->children . '">' . $this->lang->story->childrenAB . '</span> ';

                $storyColor  = $story->color ? "style='color: {$story->color}'" : '';
                $storyTitle .= $canView ? html::a($storyLink, $story->title, '', "title='$story->title' $storyColor data-app='$tab'") : "<span $storyColor>{$story->title}</span>";
                $data->title = $storyTitle;
            }
            if($col->name == 'mailto')
            {
                $mailto = array_map(function($account) use($users){$account = trim($account); return zget($users, $account);}, explode(',', $story->mailto));
                $data->mailto = implode(' ', $mailto);
            }
            if($col->name == 'URS' || $col->name == 'SRS')
            {
                $link    = helper::createLink('story', 'relation', "storyID=$story->id&storyType=$story->type");
                $storySR = $this->getStoryRelationCounts($story->id, $story->type);
                if($col->name == 'SRS' && $story->type == 'story')
                {
                    $data->{$col->name} = 0;
                }
                else
                {
                    $data->{$col->name} = $storySR > 0 ? html::a($link, $storySR, '', 'class="iframe" data-toggle="modal"') : 0;
                }
            }
            if($col->name == 'business')
            {
                $businessName = $this->dao->select('name')->from('zt_flow_business')->where('id')->eq($story->business)->fetch('name');

                $data->{$col->name} = $businessName ? html::a(helper::createLink('business', 'view', 'dataID='.$story->business), $businessName, '', "title='$businessName' style='color: $story->color' data-app='$tab'") : '';
            }
            if($col->name == 'residueEstimate')
            {
                $requirementEstimate = (float)$story->estimate;
                $haveEstimateID      = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->eq($story->id)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');
                $haveEstimate        = $this->dao->select('estimate')->from(TABLE_STORY)->where('id')->in($haveEstimateID)->andWhere('deleted')->eq(0)->fetchAll();
                $haveEstimate        = array_reduce($haveEstimate, function($carry, $item){return bcadd($carry, $item->estimate, 2);}, '0.0');

                $data->{$col->name} = $story->type == 'requirement' ? bcsub($requirementEstimate, $haveEstimate, 2) . $this->lang->story->day : '';
            }
            if(in_array($col->name, $userFields)) $data->{$col->name} = zget($users, $story->{$col->name});
            if(in_array($col->name, $dateFields)) $data->{$col->name} = helper::isZeroDate($story->{$col->name}) ? '' : substr($story->{$col->name}, 5, 11);
            if($this->config->edition != 'open')
            {
                if(isset($extendFields[$col->name]) && !$extendFields[$col->name]->buildin)
                {
                    $data->{$col->name} = $this->flow->printFlowCell('story', $story, $col->name, true);
                }
            }
            if($col->name == 'relatedRequirement' && $storyType == 'story')
            {
                $relatedRequirementTitle = isset($relatedRequirements[$story->relatedRequirement]) ? $relatedRequirements[$story->relatedRequirement] : '';

                $data->{$col->name} = ($relatedRequirementTitle && common::hasPriv('story', 'view')) ? html::a(helper::createLink('story', 'view', 'dataID=' . $story->relatedRequirement), $relatedRequirementTitle, '', "title='$relatedRequirementTitle' target='_blank'") : $relatedRequirementTitle;
            }
            if($col->name == 'actualConsumed' && $storyType == 'story')
            {
                $data->{$col->name} = isset($taskConsumedList[$story->id]) ? round($taskConsumedList[$story->id]/8, 2) : 0;
            }
        }

        $data->isParent = false;
        $data->parent   = $story->parent;
        if($data->parent == -1)
        {
            $data->isParent = true;
            $data->parent   = 0;
        }

        if($parentID && $data->isParent)
        {
            $data->isParent = false;
            $data->parent   = $parentID;
        }

        $rows[] = $data;
        if(!empty($story->children)) $rows = array_merge($rows, $this->generateRow($story->children, $cols, $options, $execution, $storyType, $story->id));
    }
    return $rows;
}

/**
 * Check business  residue estiamte.
 * @param  int    storyID
 * @access public
 * @return array
 */
public function checkRequirementResidueEstimate($storyID, $storyType = 'requirement', $actionType = 'create', $estimate = 0, $linkStoryID = 0)
{
    $requirementEstimate = $this->dao->select('estimate')->from(TABLE_STORY)->where('id')->eq($storyID)->fetch('estimate');
    $allEstimate         = 0;
    $haveEstimateID      = $this->dao->select('id,BID')->from(TABLE_RELATION)
        ->where('AID')->eq($storyID)
        ->andWhere('AType')->eq('requirement')
        ->beginIF($storyType == 'story')->andWhere('BID')->ne($linkStoryID)->fi()
        ->fetchPairs('id', 'BID');
    $haveEstimate        = $this->dao->select('estimate')->from(TABLE_STORY)->where('id')->in($haveEstimateID)->andWhere('deleted')->eq(0)->fetchAll();
    $haveEstimate        = array_reduce($haveEstimate, function($carry, $item){return bcadd($carry, $item->estimate, 2);}, '0.0');
    if(is_array($_POST['title']))
    {
        foreach($_POST['title'] as $i => $title)
        {
            if(empty($title)) continue;
            $allEstimate = bcadd($allEstimate, $_POST['estimate'][$i], 2);
        }
    }
    else
    {
        if($storyType == 'requirement')
        {
            $storyEstimate = $this->dao->select('estimate')->from(TABLE_STORY)->where('id')->in($_POST['stories'])->fetchAll();
            $allEstimate   = array_reduce($storyEstimate, function($carry, $item){return bcadd($carry, $item->estimate, 2);}, '0.0');
        }
        else
        {
            $allEstimate = $estimate;
        }
    }

    $residueEstimate = bcsub($requirementEstimate, $haveEstimate, 2);
    if(bcsub($residueEstimate, $allEstimate, 2) < 0) return array(false, $this->lang->story->requirementError);

    return array(true, '');
}

/**
 * Check business  residue estiamte for story.
 * @param  int    storyID
 * @access public
 * @return array
 */
public function checkRequirementResidueEstimateForStory($storyID, $estimate = 0)
{
    $linkRequirementIds = $this->dao->select('id,AID')->from(TABLE_RELATION)->where('BID')->eq($storyID)->andWhere('BType')->eq('story')->fetchPairs('id', 'AID');
    foreach($linkRequirementIds as $requirementID)
    {
        list($result, $message) = $this->checkRequirementResidueEstimate($requirementID, 'story', 'esit', $estimate, $storyID);
        if(!$result) return array($result, $message);
    }

    return array(true, '');
}

/**
 * Check business  residue estiamte.
 * @param  int    businessID
 * @param  array  requirementID
 * @param  string actionType
 * @param  string actionType
 * @access public
 * @return array
 */
public function checkBusinessResidueEstimate($businessID, $requirementID = 0, $actionType = 'create', $estimate = 0)
{
    if(empty($businessID)) return array(true, '');
    $developmentBudget = $this->dao->select('developmentBudget')->from('zt_flow_business')->where('id')->eq($businessID)->fetch('developmentBudget');
    $allEstimate       = 0;
    $haveEstimate      = $this->dao->select('estimate')->from(TABLE_STORY)->where('business')->eq($businessID)->andWhere('type')->eq('requirement')->beginIF($actionType == 'edit')->andWhere('id')->ne($requirementID)->fi()->andWhere('deleted')->eq(0)->fetchAll();
    $haveEstimate      = array_reduce($haveEstimate, function($carry, $item){return bcadd($carry, $item->estimate, 2);}, '0.0');;

    if($actionType == 'create' && is_array($_POST['title']))
    {
        foreach($_POST['title'] as $i => $title)
        {
            if(empty($title)) continue;
            $allEstimate = bcadd($allEstimate, $_POST['estimate'][$i], '2');
        }
    }
    else
    {
        $allEstimate = $actionType == 'create' ? $_POST['estimate'] : $estimate;
    }

    $residueEstimate = bcsub($developmentBudget, $haveEstimate, '2');

    if(bcsub($residueEstimate, $allEstimate, 2) < 0) return array(false, $this->lang->story->businessError);

    return array(true, '');
}

/**
 * Batch create stories.
 *
 * @access public
 * @param  int    $productID
 * @param  int    $branch
 * @param  string $type
 * @param  int    $businessID
 * @param  int    $executionID
 *
 * @return type requirement|story
 */
public function batchCreate($productID = 0, $branch = 0, $type = 'story', $businessID = 0, $executionID = 0)
{
    $forceReview = $this->checkForceReview();

    $this->loadModel('action');
    $branch    = (int)$branch;
    $productID = (int)$productID;
    $now       = helper::now();
    $mails     = array();
    $stories   = fixer::input('post')->setDefault('type', 'story')->get();

    $saveDraft = false;
    if(isset($stories->status))
    {
        if($stories->status == 'draft') $saveDraft = true;
        unset($stories->status);
    }

    $result  = $this->loadModel('common')->removeDuplicate('story', $stories, "product={$productID}");
    $stories = $result['data'];

    $module = 0;
    $plan   = '';
    $pri    = 0;
    $source = '';

    foreach($stories->title as $i => $title)
    {
        if(empty($title) and $this->common->checkValidRow('story', $stories, $i))
        {
            dao::$errors["title$i"][] = sprintf($this->lang->error->notempty, $this->lang->story->title);
        }

        $module = $stories->module[$i] == 'ditto' ? $module : $stories->module[$i];
        $plan   = isset($stories->plan[$i]) ? ($stories->plan[$i] == 'ditto' ? $plan : $stories->plan[$i]) : '';
        $pri    = $stories->pri[$i]    == 'ditto' ? $pri    : $stories->pri[$i];
        $source = (empty($stories->source[$i]) || $stories->source[$i] == 'ditto') ? $source : $stories->source[$i];
        $stories->module[$i] = (int)$module;
        $stories->plan[$i]   = $plan;
        $stories->pri[$i]    = (int)$pri;
        $stories->source[$i] = $source;
        if(empty($stories->category[$i]))   $stories->category[$i] = '';
        if(empty($stories->sourceNote[$i])) $stories->sourceNote[$i] = '';
        if(empty($stories->verify[$i]))     $stories->verify[$i] = '';
    }

    if(isset($stories->uploadImage)) $this->loadModel('file');

    $extendFields = $this->getFlowExtendFields();
    $data         = array();
    foreach($stories->title as $i => $title)
    {
        if(empty($title)) continue;

        $story = new stdclass();
        $story->type        = $type;
        $story->branch      = isset($stories->branch[$i]) ? $stories->branch[$i] : 0;
        $story->module      = $stories->module[$i];
        $story->plan        = $stories->plan[$i];
        $story->color       = $stories->color[$i];
        $story->title       = $stories->title[$i];
        $story->source      = $stories->source[$i];
        $story->category    = $stories->category[$i];
        $story->pri         = $stories->pri[$i];
        $story->estimate    = $stories->estimate[$i] ? $stories->estimate[$i] : 0;
        $story->spec        = $stories->spec[$i];
        $story->verify      = $stories->verify[$i];
        $story->status      = 'draft';
        $story->stage       = ($this->app->tab == 'project' or $this->app->tab == 'execution') ? 'projected' : 'wait';
        $story->keywords    = $stories->keywords[$i];
        $story->sourceNote  = $stories->sourceNote[$i];
        $story->product     = $productID;
        $story->openedBy    = $this->app->user->account;
        $story->vision      = $this->config->vision;
        $story->openedDate  = $now;
        $story->version     = 1;
        $story->business    = $businessID;

        foreach($extendFields as $extendField)
        {
            $story->{$extendField->field} = $this->post->{$extendField->field}[$i];
            if(is_array($story->{$extendField->field})) $story->{$extendField->field} = join(',', $story->{$extendField->field});

            $story->{$extendField->field} = htmlSpecialString($story->{$extendField->field});
        }

        foreach(explode(',', $this->config->story->create->requiredFields) as $field)
        {
            $field = trim($field);
            if(empty($field)) continue;
            if($type == 'requirement' and $field == 'plan') continue;

            if(!isset($story->$field)) continue;
            if(!empty($story->$field)) continue;
            if($field == 'estimate' and $story->estimate and strlen(trim($story->estimate)) != 0) continue;

            dao::$errors["{$field}$i"][] = sprintf($this->lang->error->notempty, $this->lang->story->$field);
        }
        $data[$i] = $story;
    }

    $activeStatus = '';
    if($executionID)
    {
        $project = $this->loadModel('project')->getById($executionID);
        if(!$project->instance) $activeStatus = 'active';
    }

    $link2Plans = array();
    foreach($data as $i => $story)
    {
        /* If in ipd mode, set requirement status = 'launched'. */
        if($this->config->systemMode == 'PLM' and $type == 'requirement' and $story->status == 'active' and $this->config->vision == 'rnd') $story->status = 'launched';
        if($story->status == 'launched' and $this->app->tab != 'product') $story->status = 'developing';
        if($activeStatus) $story->status = $activeStatus;

        $this->dao->insert(TABLE_STORY)->data($story, 'spec,verify')->autoCheck()->checkFlow()->exec();
        if(!dao::isError())
        {
            $storyID = $this->dao->lastInsertID();
            $this->setStage($storyID);

            /* Update product plan stories order. */
            if($story->plan)
            {
                $this->updateStoryOrderOfPlan($storyID, $story->plan);
                $link2Plans[$story->plan] = empty($link2Plans[$story->plan]) ? $storyID : "{$link2Plans[$story->plan]},$storyID";
            }

            $specData = new stdclass();
            $specData->story   = $storyID;
            $specData->version = 1;
            $specData->title   = $stories->title[$i];
            $specData->spec    = '';
            $specData->verify  = '';
            if(!empty($stories->spec[$i]))  $specData->spec   = nl2br($stories->spec[$i]);
            if(!empty($stories->verify[$i]))$specData->verify = nl2br($stories->verify[$i]);

            if(!empty($stories->uploadImage[$i]) and $stories->uploadImage[$i] !== 'undefined')
            {
                $fileName = $stories->uploadImage[$i];
                $file     = $this->session->storyImagesFile[$fileName];

                $realPath = $file['realpath'];
                unset($file['realpath']);

                if(!is_dir($this->file->savePath)) mkdir($this->file->savePath, 0777, true);
                if($realPath and rename($realPath, $this->file->savePath . $this->file->getSaveName($file['pathname'])))
                {
                    $file['addedBy']    = $this->app->user->account;
                    $file['addedDate']  = $now;
                    $file['objectType'] = 'story';
                    $file['objectID']   = $storyID;
                    if(in_array($file['extension'], $this->config->file->imageExtensions))
                    {
                        $file['extra'] = 'editor';
                        $this->dao->insert(TABLE_FILE)->data($file)->exec();

                        $fileID = $this->dao->lastInsertID();
                        $specData->spec .= '<img src="{' . $fileID . '.' . $file['extension'] . '}" alt="" />';
                    }
                    else
                    {
                        $this->dao->insert(TABLE_FILE)->data($file)->exec();
                    }
                }
            }

            $this->dao->insert(TABLE_STORYSPEC)->data($specData)->exec();

            $this->executeHooks($storyID);

            $actionID = $this->action->create('story', $storyID, 'Opened', '');
            if(!dao::isError()) $this->loadModel('score')->create('story', 'create',$storyID);
            $mails[$i] = new stdclass();
            $mails[$i]->storyID  = $storyID;
            $mails[$i]->actionID = $actionID;
        }

    }

    if(!dao::isError())
    {
        /* Remove upload image file and session. */
        if(!empty($stories->uploadImage) and $this->session->storyImagesFile)
        {
            $classFile = $this->app->loadClass('zfile');
            $file = current($_SESSION['storyImagesFile']);
            $realPath = dirname($file['realpath']);
            if(is_dir($realPath)) $classFile->removeDir($realPath);
            unset($_SESSION['storyImagesFile']);
        }

        $this->loadModel('score')->create('ajax', 'batchCreate');
        foreach($link2Plans as $planID => $stories) $this->action->create('productplan', $planID, 'linkstory', '', $stories);
    }
    return $mails;
}

/**
 * Get stories pairs of a execution.
 *
 * @param  int           $executionID
 * @param  int           $productID
 * @param  int           $branch
 * @param  array|string  $moduleIdList
 * @param  string        $type full|short
 * @param  string        $status all|unclosed|review
 * @param  string        $storyType story|requirement
 * @access public
 * @return array
 */
public function getExecutionStoryPairs($executionID = 0, $productID = 0, $branch = 'all', $moduleIdList = 0, $type = 'full', $status = 'all', $storyType = 'story')
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getExecutionStoryPairs();

    $stories = $this->dao->select('t2.id, t2.title, t2.module, t2.pri, t2.estimate, t3.name AS product')
        ->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t1.product = t3.id')
        ->where('t1.project')->in($executionID)
        ->andWhere('t2.deleted')->eq(0)
        ->andWhere('t2.type')->eq($storyType)
        ->beginIF($productID)->andWhere('t2.product')->eq((int)$productID)->fi()
        ->beginIF($branch !== 'all')->andWhere('t2.branch')->in("0,$branch")->fi()
        ->beginIF($moduleIdList)->andWhere('t2.module')->in($moduleIdList)->fi()
        ->beginIF($status == 'unclosed')->andWhere('t2.status')->ne('closed')->fi()
        ->beginIF($status == 'review')->andWhere('t2.status')->in('draft,changing')->fi()
        ->beginIF($status == 'active')->andWhere('t2.status')->eq('active')->fi()
        ->orderBy('t1.`order` desc, t1.`story` desc')
        ->fetchAll('id');

    return empty($stories) ? array() : $this->formatStories($stories, $type);
}

/**
 * Get stories list of a execution.
 *
 * @param  int    $executionID
 * @param  int    $productID
 * @param  int    $branch
 * @param  string $orderBy
 * @param  string $type
 * @param  int    $param
 * @param  string $storyType
 * @param  string $excludeStories
 * @param  string $excludeStatus
 * @param  object $pager
 * @access public
 * @return array
 */
public function getExecutionStories($executionID = 0, $productID = 0, $branch = 0, $orderBy = 't1.`order`_desc', $type = 'byModule', $param = 0, $storyType = 'story', $excludeStories = '', $excludeStatus = '', $pager = null)
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getExecutionStories();

    if(!$executionID) return array();
    $executions = $this->dao->select('*')->from(TABLE_PROJECT)->where('id')->in($executionID)->fetchAll('id');
    $hasProject = false;
    $hasExecution = false;
    foreach($executions as $execution)
    {
        if($execution->type == 'project') $hasProject   = true;
        if($execution->type != 'project') $hasExecution = true;
    }

    $orderBy = str_replace('branch_', 't2.branch_', $orderBy);
    $orderBy = str_replace('version_', 't2.version_', $orderBy);
    $type    = strtolower($type);

    $products = $this->loadModel('product')->getProducts($executionID);
    if($type == 'bysearch')
    {
        $queryID = (int)$param;

        if($this->session->executionStoryQuery == false) $this->session->set('executionStoryQuery', ' 1 = 1');
        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                if($this->app->rawModule == 'projectstory')
                {
                    $this->session->set('projectstoryQuery', $query->sql);
                    $this->session->set('projectstoryForm', $query->form);
                }
                elseif($this->app->rawModule == 'chproject')
                {
                    $this->session->set('chprojectStoryQuery', $query->sql);
                    $this->session->set('chprojectStoryForm', $query->form);
                }
                else
                {
                    $this->session->set('executionStoryQuery', $query->sql);
                    $this->session->set('executionStoryForm', $query->form);
                }
            }
        }

        if(in_array($this->app->rawModule, array('projectstory', 'chproject')))
        {
            $searchQuery = $this->session->{$this->app->rawModule . 'Query'};
            if($this->app->rawModule == 'chproject') $searchQuery = $this->session->chprojectStoryQuery;
            $this->session->executionStoryQuery = $searchQuery;
        }

        $allProduct = "`product` = 'all'";
        $storyQuery = $this->session->executionStoryQuery;
        if(strpos($this->session->executionStoryQuery, $allProduct) !== false)
        {
            $storyQuery = str_replace($allProduct, '1', $this->session->executionStoryQuery);
        }
        $storyQuery = preg_replace('/`(\w+)`/', 't2.`$1`', $storyQuery);

        if($this->app->rawModule != 'projectstory' and $products) $productID = key($products);
        $review = $this->getRevertStoryIDList($productID);

        if(strpos($storyQuery, 'result') !== false)
        {
            if(strpos($storyQuery, 'revert') !== false)
            {
                $storyQuery  = str_replace("AND t2.`result` = 'revert'", '', $storyQuery);
                $storyQuery .= " AND t2.`id` " . helper::dbIN($review);
            }
            else
            {
                $storyQuery = str_replace(array('t2.`result`'), array('t4.`result`'), $storyQuery);
            }
        }

        $pattern            = '/AND t2\.`relatedRequirement` = \'(\d+)\'/';
        $relatedRequirement = '';
        $relations          = array();
        if (preg_match($pattern, $storyQuery, $matches))
        {
            $relatedRequirement = $matches[1];
            $storyQuery = preg_replace($pattern, '', $storyQuery);
        }
        if($relatedRequirement)
        {
            $relations = $this->dao->select('id,BID')->from(TABLE_RELATION)->alias('t1')
                ->where('AType')->eq('requirement')
                ->andWhere('BType')->eq('story')
                ->andWhere('relation')->eq('subdivideinto')
                ->andWhere('AID')->eq($relatedRequirement)
                ->fetchPairs();
        }

        $stories = $this->dao->select("distinct t1.*, t2.*, IF(t2.`pri` = 0, {$this->config->maxPriValue}, t2.`pri`) as priOrder, t3.type as productType, t2.version as version")->from(TABLE_PROJECTSTORY)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t2.product = t3.id')
            ->beginIF(strpos($storyQuery, 'result') !== false)->leftJoin(TABLE_STORYREVIEW)->alias('t4')->on('t2.id = t4.story and t2.version = t4.version')->fi()
            ->where('t2.type')->eq($storyType)
            ->andWhere("($storyQuery)")
            ->andWhere('t1.project')->in($executionID)
            ->andWhere('t2.deleted')->eq(0)
            ->andWhere('t3.deleted')->eq(0)
            ->beginIF($excludeStories)->andWhere('t2.id')->notIN($excludeStories)->fi()
            ->beginIF($relations)->andWhere('t2.id')->in($relations)->fi()
            ->orderBy($orderBy)
            ->page($pager, 't2.id')
            ->fetchAll('id');
    }
    else
    {
        $productParam = ($type == 'byproduct' and $param) ? $param : $this->cookie->storyProductParam;
        $branchParam  = ($type == 'bybranch'  and $param !== '') ? $param : $this->cookie->storyBranchParam;
        $moduleParam  = ($type == 'bymodule'  and $param !== '') ? $param : $this->cookie->storyModuleParam;

        $modules = array();
        if(!empty($moduleParam) or strpos('allstory,unclosed,bymodule', $type) !== false)
        {
            $modules = $this->dao->select('id')->from(TABLE_MODULE)->where('path')->like("%,$moduleParam,%")->andWhere('type')->eq('story')->andWhere('deleted')->eq(0)->fetchPairs();
        }

        if(strpos($branchParam, ',') !== false) list($productParam, $branchParam) = explode(',', $branchParam);

        $unclosedStatus = $this->lang->story->statusList;
        unset($unclosedStatus['closed']);

        /* Get story id list of linked executions. */
        $storyIdList = array();
        if($type == 'linkedexecution' or $type == 'unlinkedexecution')
        {
            $executions  = $this->loadModel('execution')->getPairs($executionID);
            $storyIdList = $this->dao->select('story')->from(TABLE_PROJECTSTORY)->where('project')->in(array_keys($executions))->fetchPairs();
        }

        $type = (strpos('bymodule|byproduct', $type) !== false and $this->session->storyBrowseType) ? $this->session->storyBrowseType : $type;

        $stories = $this->dao->select("distinct t1.*, t2.*, IF(t2.`pri` = 0, {$this->config->maxPriValue}, t2.`pri`) as priOrder, t3.type as productType, t2.version as version")->from(TABLE_PROJECTSTORY)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t2.product = t3.id')
            ->where('t1.project')->in($executionID)
            ->andWhere('t2.type')->eq($storyType)
            ->beginIF($excludeStories)->andWhere('t2.id')->notIN($excludeStories)->fi()
            ->beginIF($hasProject)
            ->beginIF(!empty($productID))->andWhere('t1.product')->eq($productID)->fi()
            ->beginIF($type == 'bybranch' and $branchParam !== '')->andWhere('t2.branch')->in("0,$branchParam")->fi()
            ->beginIF($type == 'linkedexecution')->andWhere('t2.id')->in($storyIdList)->fi()
            ->beginIF($type == 'unlinkedexecution')->andWhere('t2.id')->notIn($storyIdList)->fi()
            ->fi()
            ->beginIF($hasExecution)
            ->beginIF(!empty($productParam))->andWhere('t1.product')->eq($productParam)->fi()
            ->beginIF($this->session->executionStoryBrowseType and strpos('changing|', $this->session->executionStoryBrowseType) !== false)->andWhere('t2.status')->in(array_keys($unclosedStatus))->fi()
            ->fi()
            ->beginIF(strpos('draft|reviewing|changing|closed', $type) !== false)->andWhere('t2.status')->eq($type)->fi()
            ->beginIF($type == 'unclosed')->andWhere('t2.status')->in(array_keys($unclosedStatus))->fi()
            ->beginIF($excludeStatus)->andWhere('t2.status')->notIN($excludeStatus)->fi()
            ->beginIF($this->session->storyBrowseType and strpos('changing|', $this->session->storyBrowseType) !== false)->andWhere('t2.status')->in(array_keys($unclosedStatus))->fi()
            ->beginIF($modules)->andWhere('t2.module')->in($modules)->fi()
            ->andWhere('t2.deleted')->eq(0)
            ->andWhere('t3.deleted')->eq(0)
            ->orderBy($orderBy)
            ->page($pager, 't2.id')
            ->fetchAll('id');
    }

    $query = $this->dao->get();

    /* Get the stories of main branch. */
    $branchStoryList = $this->dao->select('t1.*,t2.branch as productBranch')->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_PROJECTPRODUCT)->alias('t2')->on('t1.project = t2.project')
        ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t1.product = t3.id')
        ->where('t1.story')->in(array_keys($stories))
        ->andWhere('t1.branch')->eq(BRANCH_MAIN)
        ->andWhere('t3.type')->ne('normal')
        ->fetchAll();

    $branches       = array();
    $stageOrderList = 'wait,planned,projected,developing,developed,testing,tested,verified,released,closed';

    foreach($branchStoryList as $story) $branches[$story->productBranch][$story->story] = $story->story;

    /* Set up story stage. */
    // foreach($branches as $branchID => $storyIdList)
    // {
    //     $stages = $this->dao->select('*')->from(TABLE_STORYSTAGE)->where('story')->in($storyIdList)->andWhere('branch')->eq($branchID)->fetchPairs('story', 'stage');

    //     /* Take the earlier stage. */
    //     foreach($stages as $storyID => $stage) if(strpos($stageOrderList, $stories[$storyID]->stage) > strpos($stageOrderList, $stage)) $stories[$storyID]->stage = $stage;
    // }

    $this->dao->sqlobj->sql = $query;
    return $this->mergePlanTitle($productID, $stories, $branch, $storyType);
}

/**
 * Get project pairs by story id.
 *
 * @param  int    $storyID
 * @param  string $fields
 * @param  string $type
 * @access public
 * @return array
 */
public function getProjectPairsByID($storyID, $fields, $type = 'all')
{
    return $this->dao->select($fields)->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->where('t1.story')->eq($storyID)
        ->andWhere('t2.deleted')->eq('0')
        ->beginIF($type != 'all')->andWhere('t2.type')->in($type)->fi()
        ->fetchPairs();
}

/**
 * Get stories list of a product.
 *
 * @param  int          $productID
 * @param  int          $branch
 * @param  array|string $moduleIdList
 * @param  string       $status
 * @param  string       $type    requirement|story
 * @param  string       $orderBy
 * @param  array|string $excludeStories
 * @param  object       $pager
 * @param  bool         $hasParent
 * @param  int          $objectID
 *
 * @access public
 * @return array
 */
public function getProductStories($productID = 0, $branch = 0, $moduleIdList = 0, $status = 'all', $type = 'story', $orderBy = 'id_desc', $hasParent = true, $excludeStories = '', $pager = null, $objectID = 0, $projectID = 0, $isNotLink = false)
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getStories();

    if($isNotLink)
    {
        $tempObject  = $this->dao->select('*')->from('zt_project')->where('id')->eq($objectID)->fetch();
        $isExecution = $tempObject->type == 'project' ? false : true;
    }

    $stories        = array();
    $branchProducts = array();
    $normalProducts = array();
    $productList    = $this->dao->select('*')->from(TABLE_PRODUCT)->where('id')->in($productID)->fetchAll('id');
    foreach($productList as $product)
    {
        if($product->type != 'normal')
        {
            $branchProducts[$product->id] = $product->id;
            continue;
        }

        $normalProducts[$product->id] = $product->id;
    }

    $productQuery = '(';
    if(!empty($normalProducts)) $productQuery .= '`product` ' . helper::dbIN(array_keys($normalProducts));
    if(!empty($branchProducts))
    {
        if(!empty($normalProducts)) $productQuery .= " OR ";
        $productQuery .= "(`product` " . helper::dbIN(array_keys($branchProducts));

        if($branch !== 'all')
        {
            if(is_array($branch)) $branch = join(',', $branch);
            $productQuery .= " AND `branch` " . helper::dbIN($branch);
        }
        $productQuery .= ')';
    }
    if(empty($normalProducts) and empty($branchProducts)) $productQuery .= '1 = 1';
    $productQuery .= ') ';
    $productQuery = str_replace(['`product`', '`branch`'], ['t1.product', 't1.branch'], $productQuery);
    $storyIdList = [];

    $stories = $this->dao->select("*, IF(`pri` = 0, {$this->config->maxPriValue}, `pri`) as priOrder, t1.product")->from(TABLE_STORY)->alias('t1')
        ->beginIF($projectID or $isNotLink)->leftJoin(TABLE_PROJECTSTORY)->alias('t2')->on('t1.id=t2.story')->fi()
        ->where('1=1')
        ->beginIF($productID)->andWhere('t1.product')->in($productID)->fi()
        ->andWhere($productQuery)
        ->beginIF(!$hasParent)->andWhere("parent")->ge(0)->fi()
        ->beginIF($projectID)->andWhere("project")->eq($projectID)->fi()
        ->beginIF(!empty($moduleIdList))->andWhere('module')->in($moduleIdList)->fi()
        ->beginIF(!empty($excludeStories))->andWhere('id')->notIN($excludeStories)->fi()
        ->beginIF($status and $status != 'all')->andWhere('status')->in($status)->fi()
        ->beginIF($isNotLink and !$isExecution)->andWhere('t2.story IS NULL')->fi()
        ->beginIF($isNotLink and $isExecution)->andWhere("(t2.story IS NULL or t2.project = {$tempObject->project})")->fi()
        ->andWhere("FIND_IN_SET('{$this->config->vision}', t1.vision)")
        ->andWhere('type')->eq($type)
        ->andWhere('deleted')->eq(0)
        ->beginIF($storyIdList)->andWhere('id')->in($storyIdList)->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');

    return $this->mergePlanTitle($productID, $stories, $branch, $type);
}

/**
 * Get stories through search.
 *
 * @access public
 * @param  int         $productID
 * @param  int|string  $branch
 * @param  int         $queryID
 * @param  string      $orderBy
 * @param  string      $executionID
 * @param  string      $type requirement|story
 * @param  string      $excludeStories
 * @param  string      $excludeStatus
 * @param  object      $pager
 * @access public
 * @return array
 */
public function getBySearch($productID, $branch = '', $queryID = 0, $orderBy = '', $executionID = '', $type = 'story', $excludeStories = '', $excludeStatus = '', $pager = null, $projectID = 0, $projectapprovalID = 0, $isNotLink = false)
{
    if($isNotLink)
    {
        $tempObject  = $this->dao->select('*')->from('zt_project')->where('id')->eq($executionID)->fetch();
        $isExecution = $tempObject->type == 'project' ? false : true;
    }

    $this->loadModel('product');
    $executionID = empty($executionID) ? 0 : $executionID;
    $products    = empty($executionID) ? $this->product->getList($programID = 0, $status = 'all', $limit = 0, $line = 0, $shadow = 'all') : $this->product->getProducts($executionID);

    $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';

    /* Get the sql and form status from the query. */
    if($query)
    {
        $this->session->set('storyQuery', $query->sql);
        $this->session->set('storyForm', $query->form);
    }
    if($this->session->storyQuery == false) $this->session->set('storyQuery', ' 1 = 1');

    $allProduct     = "`product` = 'all'";
    $storyQuery     = $this->session->storyQuery;
    $queryProductID = $productID;
    if(strpos($storyQuery, $allProduct) !== false)
    {
        $storyQuery     = str_replace($allProduct, '1', $storyQuery);
        $queryProductID = 'all';
    }

    $storyQuery = $storyQuery . ' AND `product` ' . helper::dbIN(array_keys($products));

    if($excludeStories) $storyQuery = $storyQuery . ' AND `id` NOT ' . helper::dbIN($excludeStories);
    if($excludeStatus)  $storyQuery = $storyQuery . ' AND `status` NOT ' . helper::dbIN($excludeStatus);
    if($this->app->moduleName == 'productplan') $storyQuery .= " AND `status` NOT IN ('closed') AND `parent` >= 0 ";
    if(in_array($this->app->moduleName, array('build', 'projectrelease', 'release'))) $storyQuery .= "AND `parent` >= 0 ";
    $allBranch = "`branch` = 'all'";
    if(!empty($executionID))
    {
        $normalProducts = array();
        $branchProducts = array();
        foreach($products as $product)
        {
            if($product->type != 'normal')
            {
                $branchProducts[$product->id] = $product;
                continue;
            }

            $normalProducts[$product->id] = $product;
        }

        $storyQuery .= ' AND (';
        if(!empty($normalProducts)) $storyQuery .= '`product` ' . helper::dbIN(array_keys($normalProducts));
        if(!empty($branchProducts))
        {
            $branches = array(BRANCH_MAIN => BRANCH_MAIN);
            if($branch === '')
            {
                foreach($branchProducts as $product)
                {
                    foreach($product->branches as $branchID) $branches[$branchID] = $branchID;
                }
            }
            else
            {
                $branches[$branch] = $branch;
            }

            $branches    = join(',', $branches);
            if(!empty($normalProducts)) $storyQuery .= " OR ";
            $storyQuery .= "(`product` " . helper::dbIN(array_keys($branchProducts)) . " AND `branch` " . helper::dbIN($branches) . ")";
        }
        if(empty($normalProducts) and empty($branchProducts)) $storyQuery .= '1 = 1';
        $storyQuery .= ') ';

        if($this->app->moduleName == 'release' or $this->app->moduleName == 'build')
        {
            $storyQuery .= " AND `status` NOT IN ('draft')"; // Fix bug #990.
        }
        else
        {
            $storyQuery .= " AND `status` NOT IN ('draft', 'reviewing', 'changing', 'closed')";
        }

        if($this->app->rawModule == 'build' and $this->app->rawMethod == 'linkstory') $storyQuery .= " AND `parent` != '-1'";
    }
    elseif(strpos($storyQuery, $allBranch) !== false)
    {
        $storyQuery = str_replace($allBranch, '1', $storyQuery);
    }
    elseif($branch !== 'all' and $branch !== '' and strpos($storyQuery, '`branch` =') === false and $queryProductID != 'all')
    {
        if($branch and strpos($storyQuery, '`branch` =') === false) $storyQuery .= " AND `branch` " . helper::dbIN($branch);
    }

    if($isNotLink) $storyQuery .= $isExecution ? " and (t2.story IS NULL or t2.project = {$tempObject->project})" : 'and t2.story IS NULL';

    $storyQuery = preg_replace("/`plan` +LIKE +'%([0-9]+)%'/i", "CONCAT(',', `plan`, ',') LIKE '%,$1,%'", $storyQuery);
    return $this->getBySQL($queryProductID, $storyQuery, $orderBy, $pager, $type, $projectID, $projectapprovalID);
}

/**
 * Merge project name.
 *
 * @param  array $stories
 * @param  int   $chproject
 * @access public
 * @return array
 */
public function appendChproject($stories, $chproject = 0)
{
    $linkedExcutionProjects = $chproject ? $this->loadModel('chproject')->getIntancesProjectOptions($chproject, 'executionID', 'projectName') : [];
    $executionIdList        = $chproject ? $this->dao->select('story,project')->from(TABLE_PROJECTSTORY)->where('story')->in(array_column($stories, 'id'))->fetchGroup('story') : [];

    foreach($stories as $story)
    {
        if($chproject)
        {
            $story->linkedProjects = array();

            foreach($executionIdList[$story->id] as $execution)
            {
                if(isset($linkedExcutionProjects[$execution->project])) $story->linkedProjects[$execution->project] = $linkedExcutionProjects[$execution->project];
            }
        }

        $projects = isset($story->linkedProjects) ? array_values($story->linkedProjects) : $this->getProjectPairsByID($story->id, 'id,name', 'project');

        $story->projectName = implode(', ', $projects);
    }

    return $stories;
}

/**
 * Build operate menu.
 *
 * @param  object $story
 * @param  string $type
 * @param  object $execution
 * @param  string $storyType story|requirement
 * @access public
 * @return string
 */
public function buildOperateMenu($story, $type = 'view', $execution = '', $storyType = 'story')
{
    if($this->app->tab == 'chteam' && $type == 'browse') $type = 'execution';

    $params = "storyID=$story->id";

    if($type == 'browse')    $menu = $this->buildBrowseOperateMenu($story, $type, $execution, $storyType, $params);
    if($type == 'view')      $menu = $this->buildViewOperateMenu($story, $type, $execution, $storyType, $params);
    if($type == 'execution') $menu = $this->buildExecutionOperateMenu($story, $execution, $storyType, $params);

    return $menu;
}

/**
 * Build execution operate menu.
 *
 * @param  int    $story
 * @param  int    $execution
 * @param  string $storyType
 * @param  string $params
 * @access private
 * @return string
 */
private function buildExecutionOperateMenu($story, $execution, $storyType = 'story', $params = '')
{
    $menu = '';

    static $taskGroups = array();

    $hasDBPriv    = common::hasDBPriv($execution, 'execution');
    $canBeChanged = common::canModify('execution', $execution);
    if($canBeChanged)
    {
        $executionID = empty($execution) ? $this->session->execution : $execution->id;
        $param       = "executionID=$executionID&story={$story->id}&moduleID={$story->module}";

        $story->reviewer  = isset($story->reviewer)  ? $story->reviewer  : array();
        $story->notReview = isset($story->notReview) ? $story->notReview : array();

        $canSubmitReview    = (strpos('draft,changing,PRDReviewed', $story->status) !== false and common::hasPriv('story', 'submitReview'));
        $canReview          = (strpos('draft,changing,PRDReviewed', $story->status) === false and common::hasPriv('story', 'review'));
        $canRecall          = common::hasPriv('story', 'recall');
        $canCreateTask      = common::hasPriv('task', 'create');
        $canBatchCreateTask = common::hasPriv('task', 'batchCreate');
        $canCreateCase      = ($hasDBPriv and common::hasPriv('testcase', 'create') and $this->app->tab != 'chteam');
        $canEstimate        = common::hasPriv('execution', 'storyEstimate', $execution);
        $canUnlinkStory     = (common::hasPriv('execution', 'unlinkStory', $execution) and ($execution->hasProduct or $execution->multiple));

        if($story->type == 'requirement')
        {
            if(strpos('draft,changing,PRDReviewed', $story->status) !== false)
            {
                if($canSubmitReview) $menu .= common::buildIconButton('story', 'submitReview', "storyID=$story->id&from=story", $story, 'list', 'confirm', '', 'iframe', true, "data-width='50%'");
            }
            else
            {
                if($canReview)
                {
                    $reviewDisabled = in_array($this->app->user->account, $story->notReview) and ($story->status == 'draft' or $story->status == 'changing') ? '' : 'disabled';
                    $story->from = 'execution';
                    $menu .= common::buildIconButton('story', 'review', "story={$story->id}&from=execution", $story, 'list', 'search', '', $reviewDisabled, false, "data-group=execution");
                }
            }

            if($canRecall)
            {
                $recallDisabled = empty($story->reviewedBy) and strpos('draft,changing,PRDReviewed', $story->status) !== false and !empty($story->reviewer) ? '' : 'disabled';
                $title  = $story->status == 'changing' ? $this->lang->story->recallChange : $this->lang->story->recall;
                $menu  .= common::buildIconButton('story', 'recall', "story={$story->id}", $story, 'list', 'undo', 'hiddenwin', $recallDisabled, '', '', $title);
            }
        }
        if(!$execution->hasProduct && $this->app->tab != 'chteam') $menu .= common::buildIconButton('story', 'edit', $params . "&kanbanGroup=default&storyType=$story->type", $story, 'list', '', '', 'showinonlybody');

        $this->lang->task->create = $this->lang->execution->wbs;
        $toTaskDisabled = '';
        if(commonModel::isTutorialMode())
        {
            $wizardParams = helper::safe64Encode($param);
            $menu .=  html::a(helper::createLink('tutorial', 'wizard', "module=task&method=create&params=$wizardParams"), "<i class='icon-plus'></i>",'', "class='btn btn-task-create' title='{$this->lang->execution->wbs}' data-app='{$this->app->tab}'");
        }
        else
        {
            $chProjectID = 0;
            if($this->app->tab == 'chteam' && $this->session->chproject)
            {
                $chProjectID = $this->session->chproject;
                $intances    = $this->loadModel('chproject')->getIntances($chProjectID);
                $executionID = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('project')->in($intances)->andWhere('story')->eq($story->id)->fetch('project');
            }

            $taskParam = "executionID=$executionID&story={$story->id}&moduleID={$story->module}&taskID=0&todoID=0&extra=&bugID=0&chProjectID=$chProjectID";
            if($hasDBPriv and $storyType == 'story') $menu .= common::buildIconButton('task', 'create', $taskParam, '', 'list', 'plus', '', 'btn-task-create ' . $toTaskDisabled);
        }

        $this->lang->task->batchCreate = $this->lang->execution->batchWBS;
        if($hasDBPriv and $storyType == 'story' and $this->app->tab != 'chteam') $menu .= common::buildIconButton('task', 'batchCreate', "executionID=$executionID&story={$story->id}", '', 'list', 'pluses', '', $toTaskDisabled);

        if(($canSubmitReview or $canReview or $canRecall or $canCreateTask or $canBatchCreateTask) and ($canCreateCase or $canEstimate or $canUnlinkStory)) $menu .= "<div class='dividing-line'></div>";
        if($canEstimate and $storyType == 'story') $menu .= common::buildIconButton('execution', 'storyEstimate', "executionID=$executionID&storyID=$story->id", '', 'list', 'estimate', '', 'iframe', true, "data-width='470px'");

        $this->lang->testcase->batchCreate = $this->lang->testcase->create;
        if($canCreateCase and $storyType == 'story') $menu .= common::buildIconButton('testcase', 'create', "productID=$story->product&branch=$story->branch&moduleID=$story->module&form=&param=0&storyID=$story->id", '', 'list', 'sitemap', '', 'iframe', true, "data-app='{$this->app->tab}'");

        if(($canEstimate or $canCreateCase) and $canUnlinkStory) $menu .= "<div class='dividing-line'></div>";

        $executionID = empty($execution) ? 0 : $execution->id;

        /* Adjust code, hide split entry. */
        if(common::hasPriv('story', 'batchCreate') and !$execution->multiple and !$execution->hasProduct and $this->app->tab != 'chteam')
        {
            if(empty($taskGroups[$story->id])) $taskGroups[$story->id] = $this->dao->select('id')->from(TABLE_TASK)->where('story')->eq($story->id)->fetch('id');

            $isClick = $this->isClickable($story, 'batchcreate');
            $title   = $story->type == 'story' ? $this->lang->story->subdivideSR : $this->lang->story->subdivide;
            if(!$isClick and $story->status != 'closed')
            {
                if($story->parent > 0)
                {
                    $title = $this->lang->story->subDivideTip['subStory'];
                }
                else
                {
                    if($story->status != 'active') $title = sprintf($this->lang->story->subDivideTip['notActive'], $story->type == 'story' ? $this->lang->SRCommon : $this->lang->URCommon);
                    if($story->status == 'active' and $story->stage != 'wait') $title = sprintf($this->lang->story->subDivideTip['notWait'], zget($this->lang->story->stageList, $story->stage));
                    if($story->status == 'active' and !empty($taskGroups[$story->id])) $title = sprintf($this->lang->story->subDivideTip['notWait'], $this->lang->story->hasDividedTask);
                }
            }

            $menu .= $this->buildMenu('story', 'batchCreate', "productID=$story->product&branch=$story->branch&module=$story->module&$params&executionID=$executionID&plan=0&storyType=story", $story, 'browse', 'split', '', 'showinonlybody', '', '', $title);
        }

        if(common::hasPriv('story', 'close', "storyType={$story->type}") and !$execution->multiple and !$execution->hasProduct) $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, 'browse', '', '', 'iframe', true);

        if($canUnlinkStory and $this->app->tabe != 'chteam') $menu .= common::buildIconButton('execution', 'unlinkStory', "executionID=$executionID&storyID=$story->id&confirm=no", '', 'list', 'unlink', 'hiddenwin');

        if(common::hasPriv('execution', 'unlinkStory', $execution) && $this->app->tab == 'chteam')
        {
            $menu .= "<div class='btn-group dropdown'>";
            $menu .= "<button type='button' class='btn dropdown-toggle' data-toggle='context-dropdown' title='{$this->lang->story->unlinkStory}' style='border-radius: 4px;'><i class='icon-unlink'></i></button>";
            $menu .= "<ul class='dropdown-menu pull-right text-left' role='menu'>";

            foreach($story->linkedProjects as $linkedExcutionID => $projectName)
            {
                $title = sprintf($this->lang->story->unlinkStoryFrom, $projectName);
                $menu .= '<li>' . html::a(helper::createLink('execution', 'unlinkStory', "executionID=$linkedExcutionID&storyID={$story->id}&confirm=no", '', true), '<i class="icon-unlink"></i> ' . $projectName, '', "class='btn-link' title='$title' target='hiddenwin'") . "</li>";
            }

            $menu .= '</ul></div>';
        }
    }

    return $menu;
}

/**
 * Build browse operate menu.
 *
 * @param  object $story
 * @param  string $type
 * @param  string $execution
 * @param  string $storyType
 * @param  string $params
 * @access private
 * @return string
 */
private function buildBrowseOperateMenu($story, $type = 'view', $execution = '', $storyType = 'story', $params = '')
{
    static $taskGroups = array();

    if(!common::canBeChanged('story', $story)) return $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, 'list', '', '', 'iframe', true);

    $storyReviewer = isset($story->reviewer) ? $story->reviewer : array();
    if($story->URChanged) return $this->buildMenu('story', 'processStoryChange', $params, $story, $type, 'ok', '', 'iframe', true, '', $this->lang->confirm);

    $isClick = $this->isClickable($story, 'change');
    $title   = $isClick ? '' : $this->lang->story->changeTip;
    $menu    = $this->buildMenu('story', 'change', $params . "&from=&storyType=$story->type", $story, $type, 'alter', '', 'showinonlybody', false, '', $title);

    if($story->status == 'draft' && $story->type == 'requirement')
    {
        $menu .= $this->buildMenu('story', 'submitReview', "storyID=$story->id&storyType=$story->type&type=PRD", $story, $type, 'confirm', '', 'iframe', true, "data-width='50%'");
    }
    elseif($story->status == 'PRDReviewed' && $story->type == 'requirement')
    {
        $menu .= $this->buildMenu('story', 'submitReview', "storyID=$story->id&storyType=$story->type&type=business", $story, $type, 'confirm', '', 'iframe', true, "data-width='50%'");
    }
    else
    {
        $isClick = $this->isClickable($story, 'review');
        $title   = $this->lang->story->review;
        if(!$isClick and $story->status != 'closed')
        {
            if($story->status == 'active')
            {
                $title = $this->lang->story->reviewTip['active'];
            }
            elseif($storyReviewer and in_array($this->app->user->account, $storyReviewer))
            {
                $title = $this->lang->story->reviewTip['reviewed'];
            }
            elseif($storyReviewer and !in_array($this->app->user->account, $storyReviewer))
            {
                $title = $this->lang->story->reviewTip['notReviewer'];
            }
        }
        $menu .= $this->buildMenu('story', 'review', $params . "&from=&storyType=$story->type", $story, $type, 'search', '', 'showinonlybody', false, '', $title);
    }

    $isClick = $this->isClickable($story, 'recall');
    $title   = $story->status == 'changing' ? $this->lang->story->recallChange : $this->lang->story->recall;
    $title   = $isClick ? $title : $this->lang->story->recallTip['actived'];

    $recallType == '';
    if($story->status == 'PRDReviewing') $recallType = 'PRD';
    if($story->status == 'confirming')   $recallType = 'business';

    if($story->type == 'requirement') $menu .= $this->buildMenu('story', 'recall', $params . "&from=list&confirm=no&storyType=$story->type&type=$recallType", $story, $type, 'undo', 'hiddenwin', 'showinonlybody', false, '', $title);

    $menu .= $this->buildMenu('story', 'edit', $params . "&kanbanGroup=default&storyType=$story->type", $story, $type, '', '', 'showinonlybody');

    $vars            = "storyType={$story->type}";
    $canChange       = common::hasPriv('story', 'change', '', $vars);
    $canRecall       = common::hasPriv('story', 'recall', '', $vars);
    $canSubmitReview = (strpos('draft,changing,PRDReviewed', $story->status) !== false and common::hasPriv('story', 'submitReview', '', $vars));
    $canReview       = (strpos('draft,changing,PRDReviewed', $story->status) === false and common::hasPriv('story', 'review', '', $vars));
    $canEdit         = common::hasPriv('story', 'edit', '', $vars);
    $canBatchCreate  = ($this->app->tab == 'product' and (common::hasPriv('story', 'batchCreate', '', 'storyType=story')));
    $canCreateCase   = ($story->type == 'story' and common::hasPriv('testcase', 'create'));
    $canClose        = common::hasPriv('story', 'close', '', $vars);
    $canUnlinkStory  = ($this->app->tab == 'project' and common::hasPriv('projectstory', 'unlinkStory'));

    if(in_array($this->app->tab, array('product', 'project')))
    {
        if(($canChange or $canRecall or $canSubmitReview or $canReview or $canEdit) and ($canCreateCase or $canBatchCreate or $canClose or $canUnlinkStory))
        {
            $menu .= "<div class='dividing-line'></div>";
        }
    }

    if($this->app->tab == 'product' and $storyType == 'requirement')
    {
        if($story->status != 'closed')
        {
            $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe', true);
        }
        else
        {
            $menu .= $this->buildMenu('story', 'activate', $params . "&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);
        }

        if($canClose and ($canBatchCreate or $canCreateCase)) $menu .= "<div class='dividing-line'></div>";
    }

    if($story->type != 'requirement' and $this->config->vision != 'lite') $menu .= $this->buildMenu('testcase', 'create', "productID=$story->product&branch=$story->branch&module=0&from=&param=0&$params", $story, $type, 'sitemap', '', 'iframe showinonlybody', true, "data-app='{$this->app->tab}'");

    $shadow = $this->dao->findByID($story->product)->from(TABLE_PRODUCT)->fetch('shadow');
    if($this->app->rawModule != 'projectstory' OR $this->config->vision == 'lite' OR $shadow OR $story->type == 'requirement')
    {
        if($shadow and empty($taskGroups[$story->id])) $taskGroups[$story->id] = $this->dao->select('id')->from(TABLE_TASK)->where('story')->eq($story->id)->fetch('id');

        $isClick = $this->isClickable($story, 'batchcreate');
        $title   = $story->type == 'story' ? $this->lang->story->subdivideSR : $this->lang->story->subdivide;
        $parent  = $story->parent;
        if($storyType == 'requirement' && $story->type == 'story') $story->parent = 0;
        if(!$isClick and $story->status != 'closed')
        {
            if($story->parent > 0)
            {
                $title = $this->lang->story->subDivideTip['subStory'];
            }
            elseif(!empty($story->twins))
            {
                $title = $this->lang->story->subDivideTip['twinsSplit'];
            }
            else
            {
                if($story->status != 'active') $title = sprintf($this->lang->story->subDivideTip['notActive'], $story->type == 'story' ? $this->lang->SRCommon : $this->lang->URCommon);
                if($story->status == 'active' and $story->stage != 'wait') $title = sprintf($this->lang->story->subDivideTip['notWait'], zget($this->lang->story->stageList, $story->stage));
                if($story->status == 'active' and !empty($taskGroups[$story->id])) $title = sprintf($this->lang->story->subDivideTip['notWait'], $this->lang->story->hasDividedTask);
            }
        }

        $executionID = empty($execution) ? 0 : $execution->id;
        if($this->config->vision != 'or') $menu .= $this->buildMenu('story', 'batchCreate', "productID=$story->product&branch=$story->branch&module=$story->module&$params&executionID=$executionID&plan=0&storyType=$storyType", $story, $type, 'split', '', 'showinonlybody', '', '', $title);
        $story->parent = $parent;
    }

    if(($this->app->rawModule == 'projectstory' or ($this->app->tab != 'product' and $storyType == 'requirement')) and $this->config->vision != 'lite')
    {
        if($canClose) $menu .= "<div class='dividing-line'></div>";

        $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe', true);
        if(!empty($execution) and $execution->hasProduct and !($storyType == 'requirement' and $story->type == 'story') and ($story->type == 'story' and $this->app->rawModule == 'projectstory'))
        {
            $moduleName = $execution->multiple ? 'projectstory' : 'execution';
            $objectID   = $execution->multiple ? $this->session->project : $execution->id;
            $menu .= $this->buildMenu($moduleName, 'unlinkStory', "projectID={$objectID}&$params", $story, $type, 'unlink', 'hiddenwin', 'showinonlybody');
        }
    }

    if($this->app->tab == 'product' and $storyType == 'story')
    {
        if(($canBatchCreate or $canCreateCase) and $canClose) $menu .= "<div class='dividing-line'></div>";

        $menu .= $this->buildMenu('story', 'close', $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe', true);
    }

    return $menu;
}

/**
 * Build view operate menu.
 *
 * @param  object $story
 * @param  string $type
 * @param  object $execution
 * @param  string $storyType
 * @param  string $params
 * @access private
 * @return string
 */
private function buildViewOperateMenu($story, $type = 'view', $execution = '', $storyType = 'story', $params = '')
{
    static $taskGroups = array();

    $menu = $this->buildMenu('story', 'change', $params . "&from=&storyType=$story->type", $story, $type, 'alter', '', 'showinonlybody');

    if($story->status == 'reviewing' || $story->status == 'PRDReviewing')
    {
        $submitReviewType = $story->status == 'draft' ? 'PRD' : 'business';
        $menu .= $this->buildMenu('story', 'submitReview', $params . "&storyType=$story->type&type=$submitReviewType", $story, $type, 'confirm', '', 'showinonlybody iframe', true, "data-width='50%'");
    }

    $title = $story->status == 'changing' ? $this->lang->story->recallChange : $this->lang->story->recall;

    $recallType == '';
    if($story->status == 'PRDReviewing') $recallType = 'PRD';
    if($story->status == 'confirming')   $recallType = 'business';

    $menu .= $this->buildMenu('story', 'recall', $params . "&from=view&confirm=no&storyType=$story->type&type=$recallType", $story, $type, 'undo', 'hiddenwin', 'showinonlybody', false, '', $title);

    $menu .= $this->buildMenu('story', 'review', $params . "&from={$this->app->tab}&storyType=$story->type", $story, $type, 'search', '', 'showinonlybody');

    $executionID = empty($execution) ? 0 : $execution->id;
    if(!isonlybody())
    {
        $subdivideTitle = $story->type == 'story' ? $this->lang->story->subdivideSR : $this->lang->story->subdivide;
        if($this->config->vision != 'or') $menu .= $this->buildMenu('story', 'batchCreate', "productID=$story->product&branch=$story->branch&moduleID=$story->module&$params&executionID=$executionID&plan=0&storyType=story", $story, $type, 'split', '', 'divideStory', true, "data-toggle='modal' data-type='iframe' data-width='95%'", $subdivideTitle);

    }

    $menu .= $this->buildMenu('story', 'assignTo', $params . "&kanbanGroup=default&from=&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);
    $menu .= $this->buildMenu('story', 'close',    $params . "&from=&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);
    $menu .= $this->buildMenu('story', 'activate', $params . "&storyType=$story->type", $story, $type, '', '', 'iframe showinonlybody', true);


    /* Print testcate actions. */
    if($story->parent >= 0 and $story->type != 'requirement' and (common::hasPriv('testcase', 'create', $story) or common::hasPriv('testcase', 'batchCreate', $story)) and $this->app->tab != 'chteam')
    {
        $this->app->loadLang('testcase');
        $menu .= "<div class='btn-group dropup'>";
        $menu .= "<button type='button' class='btn dropdown-toggle' data-toggle='dropdown'><i class='icon icon-sitemap'></i> " . $this->lang->testcase->common . " <span class='caret'></span></button>";
        $menu .= "<ul class='dropdown-menu' id='createCaseActionMenu'>";

        $misc = "data-toggle='modal' data-type='iframe' data-width='95%'";
        if(isonlybody()) $misc = '';

        if(common::hasPriv('testcase', 'create', $story))
        {
            $link  = helper::createLink('testcase', 'create', "productID=$story->product&branch=$story->branch&moduleID=0&from=&param=0&$params", '', true);
            $menu .= "<li>" . html::a($link, $this->lang->testcase->create, '', $misc) . "</li>";
        }

        if(common::hasPriv('testcase', 'batchCreate'))
        {
            $link  = helper::createLink('testcase', 'batchCreate', "productID=$story->product&branch=$story->branch&moduleID=0&$params", '', true);
            $menu .= "<li>" . html::a($link, $this->lang->testcase->batchCreate, '', $misc) . "</li>";
        }

        $menu .= "</ul></div>";
    }

    if($story->parent >= 0 and $story->type != 'requirement' && common::hasPriv('testcase', 'create', $story) && $this->app->tab == 'chteam')
    {
        $intances    = $this->loadModel('chproject')->getIntances($this->session->chproject);
        $executionID = (empty($execution) && in_array(key($story->executions), $intances)) ? key($story->executions) : $execution->id;
        $extras      = "executionID=$executionID";

        $menu .= $this->buildMenu('testcase', 'create', "productID=$story->product&branch=$story->branch&moduleID=0&from=&param=0&$params&extras=$extras&chproject={$this->session->chproject}", $story, $type, '', '', 'iframe showinonlybody', true);
    }

    $moreActions      = '';
    $disabledFeatures = ",{$this->config->disabledFeatures},";
    if($story->type != 'requirement' and ($this->config->edition == 'max' or $this->config->edition == 'ipd') and $this->app->tab == 'project' and common::hasPriv('story', 'importToLib') and strpos($disabledFeatures, ',assetlibStorylib,') === false and strpos($disabledFeatures, ',assetlib,') === false)
    {
        $moreActions .= '<li>' . html::a('#importToLib', "<i class='icon icon-assets'></i> " . $this->lang->story->importToLib, '', 'class="btn" data-toggle="modal"') . '</li>';
    }

    if(($this->app->tab == 'execution' or (!empty($execution) and $execution->multiple === '0')) and $story->status == 'active' and $story->type == 'story') $moreActions .= '<li>' . $this->buildMenu('task', 'create', "execution={$this->session->execution}&{$params}&moduleID=$story->module", $story, $type, 'plus', '', 'showinonlybody') . '</li>';

    if($moreActions)
    {
        $menu .= "<div class='btn-group dropup'>";
        $menu .= "<button type='button' class='btn dropdown-toggle' data-toggle='dropdown'>" . $this->lang->more . "<span class='caret'></span></button>";
        $menu .= "<ul class='dropdown-menu' id='moreActions'>";
        $menu .= $moreActions;
        $menu .='</ul></div>';
    }

    $menu .= "<div class='divider'></div>";
    $menu .= $this->buildFlowMenu('story', $story, $type, 'direct');
    $menu .= "<div class='divider'></div>";

    $menu .= $this->buildMenu('story', 'edit', $params . "&kanbanGroup=default&storyType=$story->type", $story, $type);

    $executionIdList = $this->getProjectPairsByID($story->id, 'id,id', 'sprint');
    $executionID     = current($executionIdList);

    $menu .= $this->buildMenu('story', 'create', "productID=$story->product&branch=$story->branch&moduleID=$story->module&{$params}&executionID=$executionID&bugID=0&planID=0&todoID=0&extra=&storyType=$story->type&chproject={$this->session->chproject}", $story, $type, 'copy', '', '', '', "data-width='1050'");
    $menu .= $this->buildMenu('story', 'delete', $params . "&confirm=no&from=&storyType=$story->type", $story, 'button', 'trash', 'hiddenwin', 'showinonlybody');

    return $menu;
}

/**
 * Submit review.
 *
 * @param  int    $storyID
 * @param  string $type
 * @access public
 * @return array|bool
 */
public function submitReview($storyID, $type = 'PRD')
{
    if(isset($_POST['reviewer'])) $_POST['reviewer'] = array_filter($_POST['reviewer']);
    if(!$this->post->needNotReview and empty($_POST['reviewer']))
    {
        dao::$errors[] = $this->lang->story->errorEmptyReviewedBy;
        return false;
    }

    $oldStory     = $this->dao->findById($storyID)->from(TABLE_STORY)->fetch();
    $reviewerList = $this->getReviewerPairs($oldStory->id, $oldStory->version);
    $oldStory->reviewer = implode(',', array_keys($reviewerList));

    $story = fixer::input('post')
        ->setDefault('status', 'active')
        ->setDefault('reviewer', '')
        ->setDefault('reviewedBy', '')
        ->setDefault('submitedBy', $this->app->user->account)
        ->remove('needNotReview')
        ->join('reviewer', ',')
        ->get();

    $this->dao->delete()->from(TABLE_STORYREVIEW)->where('story')->eq($storyID)->andWhere('version')->eq($oldStory->version)->exec();

    /* Sync twins. */
    if(!empty($oldStory->twins))
    {
        foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
        {
            $this->dao->delete()->from(TABLE_STORYREVIEW)->where('story')->eq($twinID)->andWhere('version')->eq($oldStory->version)->exec();
        }
    }

    if(isset($_POST['reviewer']))
    {
        foreach($this->post->reviewer as $reviewer)
        {
            if(empty($reviewer)) continue;

            $reviewData = new stdclass();
            $reviewData->story    = $storyID;
            $reviewData->version  = $oldStory->version;
            $reviewData->reviewer = $reviewer;
            $this->dao->insert(TABLE_STORYREVIEW)->data($reviewData)->exec();

            /* Sync twins. */
            if(!empty($oldStory->twins))
            {
                foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
                {
                    $reviewData->story = $twinID;
                    $this->dao->insert(TABLE_STORYREVIEW)->data($reviewData)->exec();
                }
            }
        }

        $story->status = 'reviewing';

        if($type == 'PRD')      $story->status = 'PRDReviewing';
        if($type == 'business') $story->status = 'confirming';
    }

    /* If in ipd mode, set requirement status = 'launched'. */
    if($this->config->systemMode == 'PLM' and $oldStory->type == 'requirement' and $story->status == 'active' and $this->config->vision == 'rnd') $story->status = 'launched';
    if($story->status == 'launched' and $this->app->tab != 'product') $story->status = 'developing';

    $this->dao->update(TABLE_STORY)->data($story, 'reviewer')->where('id')->eq($storyID)->exec();

    $changes = common::createChanges($oldStory, $story);
    if(!empty($oldStory->twins)) $this->syncTwins($storyID, $oldStory->twins, $changes, 'submitReview');
    if(!dao::isError()) return $changes;

    return false;
}

/**
 * Recall the story review.
 *
 * @param  int    $storyID
 * @param  string $type
 * @access public
 * @return void
 */
public function recallReview($storyID, $type = '')
{
    $oldStory  = $this->getById($storyID);
    $isChanged = $oldStory->changedBy ? true : false;

    $story = clone $oldStory;
    $story->status = $isChanged ? 'changing' : 'draft';

    if($type == 'PRD')      $story->status = 'draft';
    if($type == 'business') $story->status = 'PRDReviewed';

    $this->dao->update(TABLE_STORY)->set('status')->eq($story->status)->where('id')->eq($storyID)->exec();

    $this->dao->delete()->from(TABLE_STORYREVIEW)->where('story')->eq($storyID)->andWhere('version')->eq($oldStory->version)->exec();

    /* Sync twins. */
    if(!empty($oldStory->twins))
    {
        foreach(explode(',', trim($oldStory->twins, ',')) as $twinID)
        {
            $this->dao->delete()->from(TABLE_STORYREVIEW)->where('story')->eq($twinID)->andWhere('version')->eq($oldStory->version)->exec();
        }
    }

    $changes = common::createChanges($oldStory, $story);
    if(!empty($oldStory->twins)) $this->syncTwins($storyID, $oldStory->twins, $changes, 'recalled');
}

/**
 * Adjust the action clickable.
 *
 * @param  object $story
 * @param  string $action
 * @access public
 * @return void
 */
public static function isClickable($story, $action)
{
    global $app, $config;
    $action = strtolower($action);

    $isNotCloseProject = true;

    $projectIdList = $app->dbQuery('SELECT project FROM zt_projectstory WHERE story = ' . $story->id . ' order by project desc')->fetchAll();

    foreach($projectIdList as $projectID)
    {

        $projectapprovalID = $app->dbQuery('SELECT instance FROM zt_project WHERE id = ' . $projectID->project)->fetch();

        if(empty($projectapprovalID->instance)) continue;
        $projectapproval   = $app->dbQuery('SELECT `status` FROM zt_flow_projectapproval WHERE id = ' . $projectapprovalID->instance)->fetch();
        if($projectapproval->status == 'cancelled' || $projectapproval->status == 'finished') $isNotCloseProject = false;
    }
    if($story->type == 'requirement')
    {
        $projectID = empty($projectIdList) ? 0 : $projectIdList[0]->project;
        $project = $app->dbQuery('SELECT instance FROM zt_project WHERE id = ' . $projectID)->fetch();
    }

    if($action == 'recall')     return strpos('reviewing,changing,PRDReviewing,confirming', $story->status) !== false;
    if($action == 'close')      return $story->status != 'closed';
    if($action == 'activate')   return $story->status == 'closed';
    if($action == 'assignto')   return $story->status != 'closed';
    if($action == 'batchcreate' and $story->parent > 0) return false;
    if($action == 'batchcreate' and !empty($story->twins)) return false;
    if($story->type == 'requirement' && $project)
    {
        if($action == 'batchcreate' && $project->instance) return $story->status == 'draft';
        if($action == 'batchcreate' && !$project->instance && $story->status == 'active') return true;
    }
    if($action == 'submitreview' and !$story->business) return false;
    if($action == 'submitreview' and strpos('draft,changing,PRDReviewed', $story->status) === false) return false;

    static $shadowProducts = array();
    static $taskGroups     = array();
    static $hasShadow      = true;
    if($hasShadow and empty($shadowProducts[$story->product]))
    {
        $stmt = $app->dbQuery('SELECT id FROM ' . TABLE_PRODUCT . " WHERE shadow = 1")->fetchAll();
        if(empty($stmt)) $hasShadow = false;
        foreach($stmt as $row) $shadowProducts[$row->id] = $row->id;
    }

    if($hasShadow and empty($taskGroups[$story->id])) $taskGroups[$story->id] = $app->dbQuery('SELECT id FROM ' . TABLE_TASK . " WHERE story = $story->id")->fetch();

    if($story->parent < 0 and strpos($config->story->list->actionsOpratedParentStory, ",$action,") === false) return false;

    if($action == 'batchcreate')
    {
        if($config->vision == 'lite' and ($story->status == 'active' and in_array($story->stage, array('wait', 'projected')))) return true;

        if($story->status != 'active' or !empty($story->plan)) return false;
        if(isset($shadowProducts[$story->product]) && (!empty($taskGroups[$story->id]) or $story->stage != 'projected')) return false;
        if(!isset($shadowProducts[$story->product]) && $story->stage != 'wait') return false;
    }

    $story->reviewer  = isset($story->reviewer)  ? $story->reviewer  : array();
    $story->notReview = isset($story->notReview) ? $story->notReview : array();
    $isSuperReviewer = strpos(',' . trim(zget($config->story, 'superReviewers', ''), ',') . ',', ',' . $app->user->account . ',');

    if($action == 'change') return (($isSuperReviewer !== false or count($story->reviewer) == 0 or count($story->notReview) == 0) and $story->status == 'active' and $isNotCloseProject);
    if($action == 'review') return (($isSuperReviewer !== false or in_array($app->user->account, $story->notReview)) and ($story->status == 'reviewing' or $story->status == 'PRDReviewing' or $story->status == 'confirming'));
    if($action == 'edit') return $isNotCloseProject;
    if($action == 'batchedit') return $isNotCloseProject;

    return true;
}

/**
 * Build for datatable columns.
 *
 * @param  string $orderBy
 * @param  string $storyType
 * @param  bool   $hasChildren
 * @access public
 * @return array
 */
public function generateCol($orderBy = '', $storyType = 'story', $hasChildren = false)
{
    $setting   = $this->loadModel('datatable')->getSetting('product');
    $fieldList = $this->config->story->datatable->fieldList;
    foreach($fieldList as $field => $items)
    {
        if(isset($items['title'])) continue;

        $title    = $field == 'id' ? 'ID' : zget($this->lang->story, $field, zget($this->lang, $field, $field));
        $fieldList[$field]['title'] = $title;
    }

    if(empty($setting))
    {
        $setting = $this->config->story->datatable->defaultField;
        $order   = 1;
        foreach($setting as $key => $value)
        {
            $set = new stdclass();
            $set->id    = $value;
            $set->order = $order ++;
            $set->show  = true;
            $setting[$key] = $set;
        }
    }

    if($storyType == 'requirement')
    {
        $settingArray = array();
        foreach($setting as $k => $v)
        {
            if($v->id == 'relatedRequirement' || $v->id == 'actualConsumed')
            {
                unset($setting[$k]);
                continue;
            }

            $settingArray[] = (array)$v;
        }

        $seetingID = array_column($settingArray, 'id');
        foreach ($this->config->story->datatable->defaultFieldRequirement as $k => $v) {
            if(in_array($v, $seetingID)) continue;
            $setValue = $this->config->story->datatable->fieldList[$v];
            $setValue['id']    = $v;
            $setValue['show']  = true;
            $setValue['order'] = 3;
            $settingArray[]    = $setValue;
        }

        foreach ($settingArray as $k => $v) {
            $setting[$k] = (object)$v;
        }
    }

    $viewType    = $this->app->getViewType();
    $shownFields = array();
    foreach($setting as $key => $set)
    {
        if($storyType == 'requirement' and in_array($set->id, array('plan', 'stage', 'taskCount', 'bugCount', 'caseCount'))) $set->show = false;
        if(($this->config->edition != 'ipd' || ($this->config->edition == 'ipd' && $storyType == 'story')) && in_array($set->id, array('roadmap'))) $set->show = false;
        if($viewType == 'xhtml' and !in_array($set->id, array('title', 'id', 'pri', 'status'))) $set->show = false;
        if(empty($set->show)) continue;

        $sortType = '';
        if(!strpos($orderBy, ',') && strpos($orderBy, $set->id) !== false)
        {
            $sort = str_replace("{$set->id}_", '', $orderBy);
            $sortType = $sort == 'asc' ? 'up' : 'down';
        }

        $set->name  = $set->id;
        $set->title = $fieldList[$set->id]['title'];

        if($storyType != 'requirement' && in_array($set->name, $this->config->story->datatable->defaultFieldRequirement))
        {
            continue;
        }

        if(isset($fieldList[$set->id]['checkbox']))     $set->checkbox     = $fieldList[$set->id]['checkbox'];
        if(isset($fieldList[$set->id]['nestedToggle'])) $set->nestedToggle = $fieldList[$set->id]['nestedToggle'];
        if(isset($fieldList[$set->id]['fixed']))        $set->fixed        = $fieldList[$set->id]['fixed'];
        if(isset($fieldList[$set->id]['type']))         $set->type         = $fieldList[$set->id]['type'];
        if(isset($fieldList[$set->id]['sortType']))     $set->sortType     = $fieldList[$set->id]['sortType'];
        if(isset($fieldList[$set->id]['flex']))         $set->flex         = $fieldList[$set->id]['flex'];
        if(isset($fieldList[$set->id]['minWidth']))     $set->minWidth     = $fieldList[$set->id]['minWidth'];
        if(isset($fieldList[$set->id]['maxWidth']))     $set->maxWidth     = $fieldList[$set->id]['maxWidth'];
        if(isset($fieldList[$set->id]['pri']))          $set->pri          = $fieldList[$set->id]['pri'];
        if(isset($fieldList[$set->id]['map']))          $set->map          = $fieldList[$set->id]['map'];

        if($sortType) $set->sortType = $sortType;

        if(isset($set->fixed) && $set->fixed == 'no') unset($set->fixed);
        if(isset($set->width)) $set->width = str_replace('px', '', $set->width);
        unset($set->id);
        $shownFields[$set->name] = $set;
    }

    if(!$hasChildren) $shownFields['title']->nestedToggle = false;
    usort($shownFields, array('datatableModel', 'sortCols'));

    return array_values($shownFields);
}

/**
 * Update business date.
 *
 * @param  int    $projectapprovalID
 * @param  array  $projectIdList
 * @param  int    $businessID
 * @param  date   $maxGoLiveDate
 * @param  object $business
 * @access public
 * @return mixed
 */
public function updateBusinessDate($projectapprovalID, $projectIdList, $businessID, $maxGoLiveDate, $business)
{
    $businessData = new stdClass();
    $changes = [];
    if(helper::isZeroDate($business->goLiveDate))
    {
        $businessData->goLiveDate = date('Y-m-d', strtotime($maxGoLiveDate));
        $changes[] = ['field' => 'goLiveDate', 'old' => '', 'new' => $businessData->goLiveDate];
    }

    if(helper::isZeroDate($business->acceptanceDate))
    {
        $acceptanceDate = new DateTime($maxGoLiveDate);
        $acceptanceDate->add(new DateInterval('P3M'));
        $acceptanceDate = $acceptanceDate->format('Y-m-d');
        $businessData->acceptanceDate = $acceptanceDate;

        $changes[] = ['field' => 'acceptanceDate', 'old' => '', 'new' => $businessData->acceptanceDate];
    }

    $this->dao->update('zt_flow_projectbusiness')->data($businessData)->where('project')->in($projectIdList)->andWhere('business')->eq($businessID)->andWhere('deleted')->eq(0)->exec();
    $this->dao->update('zt_flow_business')->data($businessData)->where('id')->eq($businessID)->limit(1)->exec();

    if($changes)
    {
        $actionID = $this->loadModel('action')->create('business', $businessID, 'syncdatebystory');

        $this->loadModel('action')->logHistory($actionID, $result['changes']);
    }

    $this->loadModel('flow');
    $this->flow->mergeVersionByObjectType($projectapprovalID, 'projectapproval');
    $this->flow->mergeVersionByObjectType($businessID, 'business');
}

/**
 * Update business status.
 *
 * @param  int    $businessID
 * @access public
 * @return void
 */
public function updateBusinessStatusToPortionPRD($businessID)
{
    $business = $this->dao->select('id, status')->from('zt_flow_business')->where('id')->eq($businessID)->fetch();

    if($business->status != 'approvedProject') return false;

    $storyList = $this->dao->select('id, status')->from('zt_story')->where('deleted')->eq(0)->andWhere('business')->eq($businessID)->fetchAll('id');

    if($storyList)
    {
        $draftStoryCount = $this->loadModel('business')->getStoryStatusCount($storyList, 'draft,PRDReviewing,confirming');
        if($draftStoryCount != count($storyList))
        {
            $this->dao->update('zt_flow_business')->set('status')->eq('portionPRD')->where('id')->eq($businessID)->exec();

            $actionID = $this->loadModel('action')->create('business', $businessID, 'changebusinessstatus');

            $result['changes'][] = ['field' => 'status', 'old' => 'approvedProject', 'new' => 'portionPRD'];
            $this->loadModel('action')->logHistory($actionID, $result['changes']);

            $this->loadModel('flow')->mergeVersionByObjectType($businessID, 'business');
        }
    }
}

/**
 * Get export storys .
 *
 * @param  int    $executionID
 * @param  string $orderBy
 * @param  string $storyType
 * @access public
 * @return void
 */
public function getExportStories($executionID, $orderBy = 'id_desc', $storyType = 'story')
{
    $this->loadModel('file');
    $this->loadModel('branch');

    $this->replaceURLang($storyType);
    $storyLang   = $this->lang->story;
    $storyConfig = $this->config->story;
    if($storyType == 'requirement')
    {
        $this->lang->story->linkStories = str_replace($this->lang->URCommon, $this->lang->SRCommon, $this->lang->story->linkStories);
        $this->lang->story->childStories = str_replace($this->lang->URCommon, $this->lang->SRCommon, $this->lang->story->childStories);
    }

    /* Create field lists. */
    $fields = $this->post->exportFields ? $this->post->exportFields : explode(',', $storyConfig->list->exportFields);
    foreach($fields as $key => $fieldName)
    {
        $fieldName = trim($fieldName);
        $fields[$fieldName] = isset($storyLang->$fieldName) ? $storyLang->$fieldName : $fieldName;
        unset($fields[$key]);
    }

    /* Get stories. */
    $stories        = array();
    $selectedIDList = $this->post->checkedItem ? $this->post->checkedItem : '0';
    if($this->session->storyOnlyCondition)
    {
        if($this->post->exportType == 'selected')
        {
            $stories = $this->dao->select('id,title,linkStories,childStories,parent,mailto,reviewedBy')->from(TABLE_STORY)->where('id')->in($selectedIDList)->orderBy($orderBy)->fetchAll('id');
        }
        else
        {
            $stories = $this->dao->select('id,title,linkStories,childStories,parent,mailto,reviewedBy')->from(TABLE_STORY)->where($this->session->storyQueryCondition)->orderBy($orderBy)->fetchAll('id');
        }
    }
    else
    {
        $field = $executionID ? 't2.id' : 't1.id';
        if($this->post->exportType == 'selected')
        {
            $stmt  = $this->app->dbQuery("SELECT * FROM " . TABLE_STORY . "WHERE `id` IN({$selectedIDList})" . " ORDER BY " . strtr($orderBy, '_', ' '));
        }
        else
        {
            $stmt  = $this->app->dbQuery($this->session->storyQueryCondition . " ORDER BY " . strtr($orderBy, '_', ' '));
        }
        while($row = $stmt->fetch()) $stories[$row->id] = $row;
    }

    if(empty($stories)) return $stories;

    $storyIdList = array_keys($stories);
    $children    = array();
    foreach($stories as $story)
    {
        if($story->parent > 0 and isset($stories[$story->parent]))
        {
            $children[$story->parent][$story->id] = $story;
            unset($stories[$story->id]);
        }
    }

    if(!empty($children))
    {
        $reorderStories = array();
        foreach($stories as $story)
        {
            $reorderStories[$story->id] = $story;
            if(isset($children[$story->id]))
            {
                foreach($children[$story->id] as $childrenID => $childrenStory)
                {
                    $reorderStories[$childrenID] = $childrenStory;
                }
            }
            unset($stories[$story->id]);
        }
        $stories = $reorderStories;
    }

    /* Get users, products and relations. */
    $users           = $this->loadModel('user')->getPairs('noletter');
    $products        = $this->loadModel('product')->getPairs('nocode');
    $relatedStoryIds = array();

    foreach($stories as $story) $relatedStoryIds[$story->id] = $story->id;

    $storyTasks = $this->loadModel('task')->getStoryTaskCounts($relatedStoryIds);
    $storyBugs  = $this->loadModel('bug')->getStoryBugCounts($relatedStoryIds);
    $storyCases = $this->loadModel('testcase')->getStoryCaseCounts($relatedStoryIds);

    /* Get related objects title or names. */
    $relatedSpecs   = $this->dao->select('*')->from(TABLE_STORYSPEC)->where('`story`')->in($storyIdList)->orderBy('version desc')->fetchGroup('story');
    $relatedStories = $this->dao->select('*')->from(TABLE_STORY)->where('`id`')->in($relatedStoryIds)->fetchPairs('id', 'title');

    $fileIdList = array();
    foreach($relatedSpecs as $storyID => $relatedSpec)
    {
        if(!empty($relatedSpec[0]->files)) $fileIdList[] = $relatedSpec[0]->files;
    }
    $fileIdList   = array_unique($fileIdList);
    $relatedFiles = $this->dao->select('id, objectID, pathname, title')->from(TABLE_FILE)->where('objectType')->eq('story')->andWhere('objectID')->in($storyIdList)->andWhere('extra')->ne('editor')->fetchGroup('objectID');
    $filesInfo    = $this->dao->select('id, objectID, pathname, title')->from(TABLE_FILE)->where('id')->in($fileIdList)->andWhere('extra')->ne('editor')->fetchAll('id');

    $taskConsumedList = array();
    $relations        = array();
    if($storyType == 'story')
    {
        $storyIdList = array_keys($stories);
        $relations = $this->dao->select('BID,AID')->from(TABLE_RELATION)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.AID=t2.id')
            ->where('t1.AType')->eq('requirement')
            ->andWhere('t1.BType')->eq('story')
            ->andWhere('t1.relation')->eq('subdivideinto')
            ->andWhere('t1.BID')->in($storyIdList)
            ->andWhere('t2.deleted')->eq(0)
            ->fetchPairs();

        $relatedRequirements = $this->dao->select('id,title')->from(TABLE_STORY)->where('id')->in($relations)->fetchPairs();
        $taskConsumedList = $this->dao->select('story,SUM(consumed) AS taskConsumed')
            ->from(TABLE_TASK)
            ->where('story')->in($storyIdList)
            ->andWhere('parent')->le('0')
            ->andWhere('deleted')->eq(0)
            ->groupBy('story')
            ->fetchPairs();
    }

    foreach($stories as $story)
    {
        if($storyType == 'story')
        {
            $relatedRequirement = isset($relations[$story->id]) ? $relations[$story->id] : 0;
            if($relatedRequirement) $story->relatedRequirement = isset($relatedRequirements[$relatedRequirement]) ? $relatedRequirements[$relatedRequirement] : '';
            $story->actualConsumed = isset($taskConsumedList[$story->id]) ? round($taskConsumedList[$story->id]/8, 2) : 0;
        }

        $story->spec   = '';
        $story->verify = '';
        if(isset($relatedSpecs[$story->id]))
        {
            $storySpec     = $relatedSpecs[$story->id][0];
            $story->title  = $storySpec->title;
            $story->spec   = $storySpec->spec;
            $story->verify = $storySpec->verify;

            if(!empty($storySpec->files) and empty($relatedFiles[$story->id]) and !empty($filesInfo[$storySpec->files]))
            {
                $relatedFiles[$story->id][0] = $filesInfo[$storySpec->files];
            }
        }

        if($this->post->fileType == 'csv')
        {
            $story->spec = htmlspecialchars_decode($story->spec);
            $story->spec = str_replace("<br />", "\n", $story->spec);
            $story->spec = str_replace('"', '""', $story->spec);
            $story->spec = str_replace('&nbsp;', ' ', $story->spec);

            $story->verify = htmlspecialchars_decode($story->verify);
            $story->verify = str_replace("<br />", "\n", $story->verify);
            $story->verify = str_replace('"', '""', $story->verify);
            $story->verify = str_replace('&nbsp;', ' ', $story->verify);
        }
        /* fill some field with useful value. */

        if(isset($storyTasks[$story->id])) $story->taskCountAB = $storyTasks[$story->id];
        if(isset($storyBugs[$story->id]))  $story->bugCountAB  = $storyBugs[$story->id];
        if(isset($storyCases[$story->id])) $story->caseCountAB = $storyCases[$story->id];

        if($story->linkStories)
        {
            $tmpLinkStories    = array();
            $linkStoriesIdList = explode(',', $story->linkStories);
            foreach($linkStoriesIdList as $linkStoryID)
            {
                $linkStoryID = trim($linkStoryID);
                $tmpLinkStories[] = zget($relatedStories, $linkStoryID);
            }
            $story->linkStories = join("; \n", $tmpLinkStories);
        }

        if($story->childStories)
        {
            $tmpChildStories = array();
            $childStoriesIdList = explode(',', $story->childStories);
            foreach($childStoriesIdList as $childStoryID)
            {
                if(empty($childStoryID)) continue;

                $childStoryID = trim($childStoryID);
                $tmpChildStories[] = zget($relatedStories, $childStoryID);
            }
            $story->childStories = join("; \n", $tmpChildStories);
        }

        /* Set related files. */
        $story->files = '';
        if(isset($relatedFiles[$story->id]))
        {
            foreach($relatedFiles[$story->id] as $file)
            {
                $fileURL = common::getSysURL() . helper::createLink('file', 'download', "fileID=$file->id");
                $story->files .= html::a($fileURL, $file->title, '_blank') . '<br />';
            }
        }

        $story->mailto = trim(trim($story->mailto), ',');
        $mailtos = explode(',', $story->mailto);
        $story->mailto = '';
        foreach($mailtos as $mailto)
        {
            $mailto = trim($mailto);
            if(isset($users[$mailto])) $story->mailto .= $users[$mailto] . ',';
        }
        $story->mailto = rtrim($story->mailto, ',');

        $story->reviewedBy = trim(trim($story->reviewedBy), ',');
        $reviewedBys = explode(',', $story->reviewedBy);
        $story->reviewedBy = '';
        foreach($reviewedBys as $reviewedBy)
        {
            $reviewedBy = trim($reviewedBy);
            if(isset($users[$reviewedBy])) $story->reviewedBy .= $users[$reviewedBy] . ',';
        }
        $story->reviewedBy = rtrim($story->reviewedBy, ',');

        /* Set child story title. */
        if($story->parent > 0 && strpos($story->title, htmlentities('>', ENT_COMPAT | ENT_HTML401, 'UTF-8')) !== 0) $story->title = '>' . $story->title;
    }

    return $stories;
}
