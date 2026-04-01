<?php
helper::importControl('block');
class myblock extends block
{
        /**
     * Print assign to me block.
     *
     * @access public
     * @return void
     */
    public function printAssignToMeBlock($longBlock = true)
    {
        $hasIssue   = helper::hasFeature('issue');
        $hasRisk    = helper::hasFeature('risk');
        $hasMeeting = helper::hasFeature('meeting');

        $hasViewPriv = array();
        if(common::hasPriv('todo',  'view'))                                     $hasViewPriv['todo']  = true;
        if(common::hasPriv('task',  'view'))                                     $hasViewPriv['task']  = true;
        if(common::hasPriv('bug',   'view') and $this->config->vision != 'lite') $hasViewPriv['bug']   = true;
        if(common::hasPriv('story', 'view') and $this->config->vision != 'lite') $hasViewPriv['story'] = true;
        if($this->config->URAndSR and common::hasPriv('story', 'view') and $this->config->vision != 'lite') $hasViewPriv['requirement'] = true;
        if(common::hasPriv('risk',  'view') and ($this->config->edition == 'max' or $this->config->edition == 'ipd') and $this->config->vision != 'lite' && $hasRisk) $hasViewPriv['risk']        = true;
        if(common::hasPriv('issue', 'view') and ($this->config->edition == 'max' or $this->config->edition == 'ipd') and $this->config->vision != 'lite' && $hasIssue) $hasViewPriv['issue']       = true;
        if(common::hasPriv('meeting', 'view') and ($this->config->edition == 'max' or $this->config->edition == 'ipd') and $this->config->vision != 'lite' && $hasMeeting) $hasViewPriv['meeting']     = true;
        if(common::hasPriv('feedback', 'view') and $this->config->edition != 'open') $hasViewPriv['feedback'] = true;
        if(common::hasPriv('ticket', 'view') and $this->config->edition != 'open')   $hasViewPriv['ticket']   = true;

        $params          = $this->get->param;
        $params          = json_decode(base64_decode($params));
        $count           = array();
        $objectList      = array('todo' => 'todos', 'task' => 'tasks', 'bug' => 'bugs', 'story' => 'stories', 'requirement' => 'requirements');
        $objectCountList = array('todo' => 'todoCount', 'task' => 'taskCount', 'bug' => 'bugCount', 'story' => 'storyCount', 'requirement' => 'requirementCount');
        if($this->config->edition == 'max' or $this->config->edition == 'ipd')
        {
            if($hasRisk)
            {
                $objectList      += array('risk' => 'risks');
                $objectCountList += array('risk' => 'riskCount');
            }

            if($hasIssue)
            {
                $objectList      += array('issue' => 'issues');
                $objectCountList += array('issue' => 'issueCount');
            }

            $objectList      += array('feedback' => 'feedbacks', 'ticket' => 'tickets');
            $objectCountList += array('feedback' => 'feedbackCount', 'ticket' => 'ticketCount');
        }

        if($this->config->edition == 'biz')
        {
            $objectList      += array('feedback' => 'feedbacks', 'ticket' => 'tickets');
            $objectCountList += array('feedback' => 'feedbackCount', 'ticket' => 'ticketCount');
        }

        $tasks = $this->loadModel('task')->getUserSuspendedTasks($this->app->user->account);
        foreach($objectCountList as $objectType => $objectCount)
        {
            if(!isset($hasViewPriv[$objectType])) continue;

            $table      = $objectType == 'requirement' ? TABLE_STORY : $this->config->objectTables[$objectType];
            $orderBy    = $objectType == 'todo' ? "`date` desc" : 'id_desc';
            $limitCount = isset($params->{$objectCount}) ? $params->{$objectCount} : 0;
            $objects    = $this->dao->select('t1.*')->from($table)->alias('t1')
                ->beginIF($objectType == 'story' or $objectType == 'requirement')->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product=t2.id')->fi()
                ->beginIF($objectType == 'bug')->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product=t2.id')->fi()
                ->beginIF($objectType == 'task')->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.execution=t2.id')->fi()
                ->beginIF($objectType == 'issue' or $objectType == 'risk')->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project=t2.id')->fi()
                ->beginIF($objectType == 'ticket')->leftJoin(TABLE_USER)->alias('t2')->on('t1.openedBy = t2.account')->fi()
                ->where('t1.deleted')->eq(0)
                ->andWhere('t1.assignedTo')->eq($this->app->user->account)->fi()
                ->beginIF($objectType == 'story')->andWhere('t1.type')->eq('story')->andWhere('t2.deleted')->eq('0')->fi()
                ->beginIF($objectType == 'requirement')->andWhere('t1.type')->eq('requirement')->andWhere('t2.deleted')->eq('0')->fi()
                ->beginIF($objectType == 'bug')->andWhere('t2.deleted')->eq('0')->fi()
                ->beginIF($objectType == 'story' or $objectType == 'requirement')->andWhere('t2.deleted')->eq('0')->fi()
                ->beginIF($objectType == 'todo')->andWhere('t1.cycle')->eq(0)->andWhere('t1.status')->eq('wait')->andWhere('t1.vision')->eq($this->config->vision)->fi()
                ->beginIF($objectType != 'todo')->andWhere('t1.status')->ne('closed')->fi()
                ->beginIF($objectType == 'feedback')->andWhere('t1.status')->in('wait, noreview')->fi()
                ->beginIF($objectType == 'issue' or $objectType == 'risk')->andWhere('t2.deleted')->eq(0)->fi()
                ->beginIF($objectType == 'ticket')->andWhere('t1.status')->in('wait,doing,done')->fi()
                ->orderBy($orderBy)
                ->beginIF($limitCount)->limit($limitCount)->fi()
                ->fetchAll();

            if($objectType == 'todo')
            {
                $this->app->loadClass('date');
                $this->app->loadLang('todo');
                foreach($objects as $key => $todo)
                {
                    if($todo->status == 'done' and $todo->finishedBy == $this->app->user->account)
                    {
                        unset($objects[$key]);
                        continue;
                    }
                    if($todo->type == 'task' and isset($tasks[$todo->idvalue]))
                    {
                        unset($objects[$key]);
                        continue;
                    }

                    $todo->begin = date::formatTime($todo->begin);
                    $todo->end   = date::formatTime($todo->end);
                }
            }

            if($objectType == 'task')
            {
                $this->app->loadLang('task');
                $this->app->loadLang('execution');

                $objects = $this->loadModel('task')->getUserTasks($this->app->user->account, 'assignedTo');

                foreach($objects as $k => $task)
                {
                    if(in_array($task->status, array('closed', 'cancel'))) unset($objects[$k]);
                }
                if($limitCount > 0) $objects = array_slice($objects, 0, $limitCount);
            }

            if($objectType == 'bug')   $this->app->loadLang('bug');
            if($objectType == 'risk')  $this->app->loadLang('risk');
            if($objectType == 'issue') $this->app->loadLang('issue');

            if($objectType == 'feedback' or $objectType == 'ticket')
            {
                $this->app->loadLang('feedback');
                $this->app->loadLang('ticket');
                $this->view->users    = $this->loadModel('user')->getPairs('all,noletter');
                $this->view->products = $this->dao->select('id, name')->from(TABLE_PRODUCT)->where('deleted')->eq('0')->fetchPairs('id', 'name');
            }

            $count[$objectType] = count($objects);
            $this->view->{$objectList[$objectType]} = $objects;
        }

        if(isset($hasViewPriv['meeting']))
        {
            $this->app->loadLang('meeting');
            $today        = helper::today();
            $now          = date('H:i:s', strtotime(helper::now()));
            $meetingCount = isset($params->meetingCount) ? isset($params->meetingCount) : 0;

            $meetings = $this->dao->select('*')->from(TABLE_MEETING)->alias('t1')
                ->leftjoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
                ->where('t1.deleted')->eq('0')
                ->andWhere('t2.deleted')->eq('0')
                ->andWhere('(t1.date')->gt($today)
                ->orWhere('(t1.begin')->gt($now)
                ->andWhere('t1.date')->eq($today)
                ->markRight(2)
                ->andwhere('(t1.host')->eq($this->app->user->account)
                ->orWhere('t1.participant')->in($this->app->user->account)
                ->markRight(1)
                ->orderBy('t1.id_desc')
                ->beginIF($meetingCount)->limit($meetingCount)->fi()
                ->fetchAll();

            $count['meeting'] = count($meetings);
            $objectList += array('meeting' => 'meetings');
            $this->view->meetings = $meetings;
            $this->view->depts    = $this->loadModel('dept')->getOptionMenu();
            $this->view->users    = $this->loadModel('user')->getPairs('all,noletter');
        }

        $limitCount = !empty($params->reviewCount) ? $params->reviewCount : 20;
        $this->app->loadClass('pager', $static = true);
        $pager = new pager(0, $limitCount, 1);
        $reviews = $this->loadModel('my')->getReviewingList('all', 'time_desc', $pager);
        if($reviews)
        {
            $todoPriv = $hasViewPriv['todo'];
            unset($hasViewPriv['todo']);
            $hasViewPriv = array_merge(array('todo' => $todoPriv, 'review' => true), $hasViewPriv);

            $objectList += array('review' => 'reviews');

            $count['review']       = count($reviews);
            
            if($this->config->edition == 'max' or $this->config->edition == 'ipd')
            {
                $this->app->loadLang('approval');
                $this->loadModel('flow');
                $this->view->flows = $this->dao->select('module,name')->from(TABLE_WORKFLOW)->where('buildin')->eq(0)->fetchPairs('module', 'name');

                foreach($reviews as $review)
                {
                    if($review->type == 'review' || $review->type == 'attend') continue;
                    if(strpos(",{$config->my->oaObjectType},", ",$review->type,") !== false) continue;
                    if(!in_array($module, array('demand', 'story', 'testcase', 'feedback')))
                    {
                        $review->action = 'approvalreview' . $this->flow->checkApprovalReviewMethod($reviewAction, $review);
                    }
                }
            }
            $this->view->reviews   = $reviews;
        }

        foreach($objectList as $objectType => $object)
        {
            if($objectType == 'todo') continue;
            if(empty($this->view->$object)) $hasViewPriv[$objectType] = false;
        }
        $this->view->selfCall    = $this->selfCall;
        $this->view->hasViewPriv = $hasViewPriv;
        $this->view->count       = $count;
        $this->view->longBlock   = $longBlock;
        $this->display();
    }
}