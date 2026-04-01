<?php
public function migrationProject()
{
    $projects = $this->dao->select('*')->from('zt_projectapproval')->fetchAll('projectNumber');

    $programs = $this->dao->select('id, name')->from(TABLE_PROGRAM)
         ->where('type')->eq('program')
         ->andWhere('deleted')->eq(0)
         ->fetchPairs('name', 'id');

    $users = $this->dao->select('realname, account')->from(TABLE_USER)->where('deleted')->eq(0)->fetchPairs('realname', 'account');

    $this->loadModel('action');
    $this->loadModel('flow');
    $this->loadModel('workflowfield');
    $this->loadModel('workflowaction');

    $priField = $this->workflowfield->getByField('projectapproval', 'pri');
    $priList  = $this->workflowaction->getRealOptions($priField);

    $businessLineField = $this->workflowfield->getByField('projectapproval', 'businessLine');
    $businessLineList  = $this->workflowaction->getRealOptions($businessLineField);

    $statusField = $this->workflowfield->getByField('projectapproval', 'status');
    $statusList  = $this->workflowaction->getRealOptions($statusField);

    $projectRoleField = $this->workflowfield->getByField('projectmembers', 'projectRole');
    $projectRoleList  = $this->workflowaction->getRealOptions($projectRoleField);

    $userDept = $this->dao->select('account, dept')->from(TABLE_USER)->where('deleted')->eq(0)->fetchPairs('account', 'dept');
    $depts    = $this->loadModel('dept')->getPairs('dept');

    $costTypeField = $this->workflowfield->getByField('projectcost', 'costType');
    $costTypeList  = $this->workflowaction->getRealOptions($costTypeField);

    $costUnitList = $this->loadModel('flow')->ajaxGetProjectCost();

    $gradeDept = $this->loadModel('dept')->getOptionMenuByGrade('', 3);

    $productPlans[]['products'] = $this->dao->select('id')->from(TABLE_PRODUCT)->where('name')->eq($this->lang->defaultProductTitle)->fetch('id');
    $now = helper::now();

    $this->dao->begin();
    foreach($projects as $project)
    {
        $oldProject = $this->dao->select('id')->from('zt_flow_projectapproval')->where('projectNumber')->eq($project->projectNumber)->andWhere('deleted')->eq(0)->limit(1)->fetch();

        if($oldProject) continue;

        $projectapproval = new stdClass();

        $relevantDept = '';
        if(!empty($project->relevantDept))
        {
            foreach(explode(';', $project->relevantDept) as $dept)
            {
                if(!empty(array_search($dept, $gradeDept))) $relevantDept .= ',' . array_search($dept, $gradeDept);
            }
        }

        $projectapproval->name                = $project->name;
        $projectapproval->program             = isset($project->program) ? zget($programs, $project->program) : '';
        $projectapproval->projectNumber       = $project->projectNumber;
        $projectapproval->begin               = !empty($project->begin) ? $project->begin : $now;
        $projectapproval->end                 = !empty($project->end) ? $project->end : $now;
        $projectapproval->days                = $project->days;
        $projectapproval->desc                = rtrim(htmlSpecialString(str_replace('<br>', "\n", strip_tags($project->desc, '<br>'))));
        $projectapproval->businessPM          = isset($project->businessPM) ? zget($users, $project->businessPM) : $project->businessPM;
        $projectapproval->pri                 = !empty($project->pri) ? array_search($project->pri, $priList) : $project->pri;
        $projectapproval->businessLine        = !empty($project->businessLine) ? array_search($project->businessLine, $businessLineList) : $project->businessLine;
        $projectapproval->responsibleDept     = !empty($project->responsibleDept) ? array_search($project->responsibleDept, $gradeDept) : $project->responsibleDept;
        $projectapproval->relevantDept        = trim($relevantDept, ',');
        $projectapproval->businessUnit        = 'default';
        $projectapproval->projectApprovalDate = !empty($project->projectApprovalDate) ? $project->projectApprovalDate : $now;
        $projectapproval->totalCost           = $project->totalCost;
        $projectapproval->status              = !empty($project->status) ? array_search($project->status, $statusList) : $project->status;
        $projectapproval->createdBy           = isset($project->createdBy) ? zget($users, $project->createdBy) : $project->createdBy;
        $projectapproval->createdDate         = isset($project->createdDate) ? $project->createdDate : $project->createdDate;
        $projectapproval->model               = 'agileplus';
        $projectapproval->hasproduct          = 1;
        $projectapproval->acl                 = 'private';
        $projectapproval->auth                = 'extend';
        $projectapproval->productPlan         = json_encode($productPlans);

        $this->dao->insert('zt_flow_projectapproval')->data($projectapproval)->exec();

        $projectApprovalID = $this->dao->lastInsertID();

        $this->migrationProjectMember($projectApprovalID, $projectapproval->projectNumber, $users, $projectRoleList);
        $this->migrationProjectCost($projectApprovalID, $projectapproval->projectNumber, $costTypeList, $gradeDept, $costUnitList);
        $this->migrationProjectValue($projectApprovalID);

        $this->flow->createVersionByObjectType($projectApprovalID, 'projectapproval');

        $this->createProject($projectApprovalID);

        $this->action->create('projectapproval', $projectApprovalID, 'Opened', '', '', $projectapproval->createdBy);
    }

    $this->dao->commit();
}

public function migrationProjectMember($projectApprovalID, $projectNumber, $users, $projectRoleList)
{
    $projectMembers = $this->dao->select('*')->from('zt_projectmember')->where('projectNumber')->eq($projectNumber)->fetchAll();

    foreach($projectMembers as $member)
    {
        $projectMember = new stdClass();

        $projectMember->parent      = $projectApprovalID;
        $projectMember->account     = !empty($member->account) ? zget($users, $member->account, '') : '';
        $projectMember->projectRole = !empty($member->projectRole) ? array_search($member->projectRole, $projectRoleList) : '';
        $projectMember->description = !empty($projectMember->projectRole) ? $this->config->{$projectMember->projectRole} : $member->description;

        $this->dao->insert('zt_flow_projectmembers')->data($projectMember)->exec();
    }
}

public function migrationProjectCost($projectapprovalID, $projectNumber, $costTypeList, $gradeDept, $costUnitList)
{
    $projectCosts = $this->dao->select('*')->from('zt_projectcost')->where('projectNumber')->eq($projectNumber)->fetchAll();

    foreach($projectCosts as $cost)
    {
        $projectCost = new stdClass();

        $projectCost->parent      = $projectapprovalID;
        $projectCost->costType    = !empty($cost->costType) ? array_search($cost->costType, $costTypeList) : $cost->costType;
        $projectCost->costBudget  = $cost->costBudget;
        $projectCost->costUnit    = isset($costUnitList[$projectCost->costType]) ? $costUnitList[$projectCost->costType]->costUnit : '';
        $projectCost->costDept    = !empty($cost->costDept) ? array_search($cost->costDept, $gradeDept) : $cost->costDept;
        $projectCost->costDesc    = isset($costUnitList[$projectCost->costType]) ? $costUnitList[$projectCost->costType]->costDesc : '';
        $projectCost->descComment = $cost->descComment;

        $this->dao->insert('zt_flow_projectcost')->data($projectCost)->exec();
    }
}

public function migrationProjectValue($projectApprovalID)
{
    $projectValue = new stdClass();

    $projectValue->parent         = $projectApprovalID;
    $projectValue->valueType      = 'security';
    $projectValue->valueName      = '默认值';
    $projectValue->valueDesc      = '默认数据';
    $projectValue->isQuantifiable = 'N';
    $projectValue->dataSources    = '默认值';
    $projectValue->formula        = 'A=B+C';
    $projectValue->reachedDate    = helper::now();

    $this->dao->insert('zt_flow_projectvalue')->data($projectValue)->exec();
}

/**
 * Create project by project approval.
 *
 * @param  int    $dataID
 * @access public
 * @return mixed
 */
public function createProject($projectapprovalID)
{
    $project = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($projectapprovalID)->fetch();

    $projectName = $project->projectNumber . '-' . $project->name;

    $oldProjectID = $this->dao->select('id')->from(TABLE_PROJECT)->where('name')->eq($projectName)->andWhere('deleted')->eq(0)->fetch('id');

    if($oldProjectID)
    {
        $productID = $this->dao->select('id')->from(TABLE_PRODUCT)->where('name')->eq($this->lang->defaultProductTitle)->fetch('id');

        $oldProjectProduct = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($oldProjectID)->andWhere('product')->eq($productID)->fetch('product');

        if(!$oldProjectProduct)
        {
            $_POST['otherProducts'][0] = $productID;
            $this->loadModel('project')->updateProducts($oldProjectID);
        }

        $this->dao->update(TABLE_PROJECT)->set('instance')->eq($projectapprovalID)->set('projectType')->eq('2')->where('id')->eq($oldProjectID)->exec();

        $this->dao->update('zt_flow_projectmembers')->set('project')->eq($oldProjectID)->where('parent')->eq($projectapprovalID)->exec();
        $this->dao->update('zt_flow_projectcost')->set('project')->eq($oldProjectID)->where('parent')->eq($projectapprovalID)->exec();
        $this->dao->update('zt_flow_projectvalue')->set('project')->eq($oldProjectID)->where('parent')->eq($projectapprovalID)->exec();

        $this->loadModel('flow')->mergeVersionByObjectType($projectapprovalID, 'projectapproval');
    }
    else
    {
        $status = 'wait';
        if(in_array($project->status, array('design', 'devTest', 'closure'))) $status = 'doing';
        if(in_array($project->status, array('finished', 'cancelled')))        $status = 'closed';

        $project->name        = $projectName;
        $project->instance    = $projectapprovalID;
        $project->status      = $status;
        $project->openedBy    = $project->createdBy;
        $project->openedDate  = helper::now();
        $project->parent      = $project->program;
        $project->level       = $project->pri;
        $project->budget      = $project->future     == '1' ? 0 : $project->budget;
        $project->multiple    = $project->multiple   == '1' ? $project->multiple : 0;
        $project->hasProduct  = $project->hasProduct == '1' ? $project->hasProduct : 0;
        $project->acl         = 'private';
        $project->auth        = 'extend';
        $project->projectType = '2';

        $productPlans      = json_decode($project->productPlan, true);
        $projectColumns    = $this->dao->query("SHOW COLUMNS FROM zt_project")->fetchAll(PDO::FETCH_COLUMN);
        $preProjectColumns = $this->dao->query("SHOW COLUMNS FROM zt_flow_projectapproval")->fetchAll(PDO::FETCH_COLUMN);
        $diffColumns       = array_diff($preProjectColumns, $projectColumns);
        $skip              = 'id,' . implode(',', $diffColumns);

        $this->dao->insert(TABLE_PROJECT)->data($project, $skip)->exec();

        /* Add the creater to the team. */
        if(!dao::isError())
        {
            $projectID = $this->dao->lastInsertID();

            $this->loadModel('action')->create('project', $projectID, 'opened', '', '', $project->openedBy);

            /* Set team of project. */
            $members = isset($_POST['teamMembers']) ? $_POST['teamMembers'] : array();

            $projectMembers = $this->dao->select('account')->from('zt_flow_projectmembers')->where('parent')->eq($projectapprovalID)->andWhere('deleted')->eq('0')->fetchPairs();

            array_push($members, $project->PM, $project->openedBy);
            $members = array_unique($members + $projectMembers);
            $roles   = $this->loadModel('user')->getUserRoles(array_values($members));

            $this->loadModel('execution');

            $teamMembers = array();
            foreach($members as $account)
            {
                if(empty($account)) continue;

                $member = new stdClass();
                $member->root    = $projectID;
                $member->type    = 'project';
                $member->account = $account;
                $member->role    = zget($roles, $account, '');
                $member->join    = helper::now();
                $member->days    = zget($project, 'days', 0);
                $member->hours   = $this->config->execution->defaultWorkhours;
                $this->dao->insert(TABLE_TEAM)->data($member)->exec();
                $teamMembers[$account] = $member;
            }
            $this->execution->addProjectMembers($projectID, $teamMembers);

            /* Create doc lib. */
            $this->app->loadLang('doc');

            $lib = new stdclass();
            $lib->project   = $projectID;
            $lib->name      = $this->lang->doclib->main['project'];
            $lib->type      = 'project';
            $lib->main      = '1';
            $lib->acl       = 'default';
            $lib->users     = '';
            $lib->vision    = zget($project, 'vision', 'rnd');
            $lib->addedBy   = $this->app->user->account;
            $lib->addedDate = helper::now();
            $this->dao->insert(TABLE_DOCLIB)->data($lib)->exec();

            foreach($productPlans as $index => $productPlan)
            {
                $_POST['products'][$index] = $productPlan['products'];
            }

            $this->loadModel('project')->updateProducts($projectID);

            /* Save order. */
            $this->dao->update(TABLE_PROJECT)->set('`order`')->eq($projectID * 5)->where('id')->eq($projectID)->exec();
            $this->loadModel('program')->setTreePath($projectID);

            /* Add project admin. */
            $groupPriv = $this->dao->select('t1.*')->from(TABLE_USERGROUP)->alias('t1')
                ->leftJoin(TABLE_GROUP)->alias('t2')->on('t1.`group` = t2.id')
                ->where('t1.account')->eq($project->openedBy)
                ->andWhere('t2.role')->eq('projectAdmin')
                ->fetch();

            if(!empty($groupPriv))
            {
                $newProject = $groupPriv->project . ",$projectID";
                $this->dao->update(TABLE_USERGROUP)->set('project')->eq($newProject)->where('account')->eq($groupPriv->account)->andWhere('`group`')->eq($groupPriv->group)->exec();
            }
            else
            {
                $projectAdminID = $this->dao->select('id')->from(TABLE_GROUP)->where('role')->eq('projectAdmin')->fetch('id');

                $groupPriv = new stdclass();
                $groupPriv->account = $project->openedBy;
                $groupPriv->group   = $projectAdminID;
                $groupPriv->project = $projectID;
                $this->dao->replace(TABLE_USERGROUP)->data($groupPriv)->exec();
            }

            if($project->acl != 'open') $this->loadModel('user')->updateUserView($projectID, 'project');

            if(empty($project->multiple) && !in_array($project->model, array('waterfall', 'waterfallplus'))) $this->loadModel('execution')->createDefaultSprint($projectID);

            $this->dao->update('zt_flow_projectmembers')->set('project')->eq($projectID)->where('parent')->eq($projectapprovalID)->exec();
            $this->dao->update('zt_flow_projectcost')->set('project')->eq($projectID)->where('parent')->eq($projectapprovalID)->exec();
            $this->dao->update('zt_flow_projectvalue')->set('project')->eq($projectID)->where('parent')->eq($projectapprovalID)->exec();

            $this->loadModel('flow')->mergeVersionByObjectType($projectapprovalID, 'projectapproval');
        }
    }
}

public function migrationBusiness()
{
    $businesses = $this->dao->select('*')->from('zt_business')->fetchAll();

    $projectapprovals = $this->dao->select('id, projectNumber,businessPM')->from('zt_flow_projectapproval')
         ->where('deleted')->eq(0)
         ->fetchAll('projectNumber');

    $users = $this->dao->select('realname, account')->from(TABLE_USER)->where('deleted')->eq(0)->fetchPairs('realname', 'account');

    $this->loadModel('action');
    $this->loadModel('flow');
    $this->loadModel('workflowfield');
    $this->loadModel('workflowaction');

    $severityField = $this->workflowfield->getByField('business', 'severity');
    $severityList  = $this->workflowaction->getRealOptions($severityField);

    $statusLineField = $this->workflowfield->getByField('business', 'status');
    $statusLineList  = $this->workflowaction->getRealOptions($statusLineField);

    $reasonTypeUnitField = $this->workflowfield->getByField('business', 'reasonType');
    $reasonTypeUnitList  = $this->workflowaction->getRealOptions($reasonTypeUnitField);

    $gradeDept = $this->loadModel('dept')->getOptionMenuByGrade('', 3);

    $now = helper::now();

    $this->dao->begin();

    foreach($businesses as $business)
    {
        $oldBusiness = $this->dao->select('id')->from('zt_flow_business')->where('REQid')->eq($business->REQid)->andWhere('deleted')->eq(0)->fetch();

        if($oldBusiness) continue;

        $businessData = new stdClass();

        $businessData->name              = $business->name;
        $businessData->acl               = 'open';
        $businessData->severity          = !empty($business->severity) ? array_search($business->severity, $severityList) : $business->severity;
        $businessData->project           = (!empty($business->projectNumber) && isset($projectapprovals[$business->projectNumber])) ? $projectapprovals[$business->projectNumber]->id : '';
        $businessData->createdDept       = (!empty($business->createdDept) && array_search($business->createdDept, $gradeDept)) ? array_search($business->createdDept, $gradeDept) : '';
        $businessData->deadline          = $business->deadline;
        $businessData->status            = !empty($business->status) ? array_search($business->status, $statusLineList) : $business->status;
        $businessData->reasonType        = (!empty($business->reasonType) && array_search($business->reasonType, $reasonTypeUnitList)) ? array_search($business->reasonType, $reasonTypeUnitList) : '';
        $businessData->businessDesc      = rtrim(htmlSpecialString(str_replace('<br>', "\n", strip_tags($business->businessDesc, '<br>'))));
        $businessData->businessUnit      = 'default';
        $businessData->businessObjective = $business->businessObjective;
        $businessData->createdBy         = (!empty($business->createdBy) && isset($users[$business->createdBy])) ?  $users[$business->createdBy] : '';
        $businessData->createdDate       = $business->createdDate;
        $businessData->developmentBudget = $business->developmentBudget;
        $businessData->outsourcingBudget = $business->outsourcingBudget;
        $businessData->processChange     = $business->processChange;
        $businessData->processName       = $business->processName;
        $businessData->desc              = rtrim(htmlSpecialString(str_replace('<br>', "\n", strip_tags($business->desc, '<br>'))));
        $businessData->REQid             = $business->REQid;
        $businessData->PRDdate           = $business->PRDdate;
        $businessData->acceptanceDate    = $business->acceptanceDate;
        $businessData->goLiveDate        = $business->goLiveDate;
        $businessData->businessPM        = (!empty($business->projectNumber) && isset($projectapprovals[$business->projectNumber])) ? $projectapprovals[$business->projectNumber]->businessPM : '';

        $this->dao->insert('zt_flow_business')->data($businessData)->exec();
        $businessID = $this->dao->lastInsertID();
        $this->flow->createVersionByObjectType($businessID, 'business');

        if($businessData->project)
        {
            $businessData->id = $businessID;
            $this->migrationProjectBusiness($businessData, array_search($businessData->businessPM, $users));
        }

        $this->action->create('business', $businessID, 'Opened', '', '', $businessData->createdBy);
    }

    $this->dao->commit();
}

public function migrationProjectBusiness($business, $businessPMRealName)
{
    $projectID = $this->dao->select('id')->from(TABLE_PROJECT)->where('instance')->eq($business->project)->limit(1)->fetch('id');

    $projectBusiness = new stdClass();

    $projectBusiness->parent            = $business->project;
    $projectBusiness->business          = $business->id;
    $projectBusiness->PRDdate           = $business->PRDdate;
    $projectBusiness->developmentBudget = $business->developmentBudget;
    $projectBusiness->acceptanceDate    = $business->acceptanceDate;
    $projectBusiness->goLiveDate        = $business->goLiveDate;
    $projectBusiness->headBusiness      = $businessPMRealName;
    $projectBusiness->outsourcingBudget = $business->outsourcingBudget;
    $projectBusiness->project           = $projectID;

    $this->dao->insert('zt_flow_projectbusiness')->data($projectBusiness)->exec();

    $this->loadModel('flow')->mergeVersionByObjectType($projectBusiness->parent, 'projectapproval');
}

public function migrationStory()
{
    $stories = $this->dao->select('*')->from('zt_migrationstory')->fetchAll();

    $projects = $this->dao->select('id, name')->from(TABLE_PROJECT)
         ->where('deleted')->eq(0)
         ->fetchAll('name');

    $users = $this->dao->select('realname, account')->from(TABLE_USER)
        ->where('deleted')->eq(0)
        ->fetchPairs('realname', 'account');

    $requirements = $this->dao->select('epicID, id')->from(TABLE_STORY)
        ->where('epicID')->ne('')
        ->andWhere('deleted')->eq(0)
        ->fetchPairs('epicID', 'id');

    $this->loadModel('action');
    $this->loadModel('project');
    $this->loadModel('story');
    $this->loadModel('kanban');

    $categoryList = $this->lang->story->categoryList;
    $priList      = $this->lang->story->priList;
    $statusList   = $this->lang->story->statusList;
    $stageList    = $this->lang->story->stageList;
    $sourceList   = $this->lang->story->sourceList;

    $this->dao->begin();

    foreach($stories as $story)
    {
        $oldStory = $this->dao->select('id')->from('zt_story')->where('redmineid')->eq($story->redmineid)->andWhere('deleted')->eq(0)->andWhere('type')->eq('story')->limit(1)->fetch();

        if($oldStory) continue;

        $storyData = new stdClass();

        $productID = $story->product;

        $storyData->title            = $story->title;
        $storyData->product          = $productID;
        $storyData->stage            = !empty($story->stage) ? array_search($story->stage, $stageList) : $story->stage;
        $storyData->category         = !empty($story->category) ? array_search($story->category, $categoryList) : $story->category;
        $storyData->pri              = !empty($story->pri) ? array_search($story->pri, $priList) : $story->pri;
        $storyData->status           = !empty($story->status) ? array_search($story->status, $statusList) : $story->status;
        $storyData->source           = !empty($story->source) ? array_search($story->source, $sourceList) : $story->source;
        $storyData->sourceNote       = $story->sourceNote;
        $storyData->assignedTo       = (!empty($story->assignedTo) && isset($users[$story->assignedTo])) ?  $users[$story->assignedTo] : '';
        $storyData->assign           = $storyData->assignedTo;
        $storyData->estimate         = !empty($story->estimate) ? $story->estimate : '';
        $storyData->openedBy         = (!empty($story->openedBy) && isset($users[$story->openedBy])) ?  $users[$story->openedBy] : $story->openedBy;
        $storyData->openedDate       = $story->openedDate;
        $storyData->planonlinedate   = $story->planonlinedate;
        $storyData->actualonlinedate = $story->actualonlinedate;
        $storyData->spec             = isset($story->spec) ? $story->spec : '';
        $storyData->type             = 'story';
        $storyData->epicID           = $story->epicID;
        $storyData->redmineid        = $story->redmineid;
        $storyData->CQTESTTIME       = $story->CQTESTTIME;
        $storyData->CQTESTPM         = $story->CQTESTPM;

        $this->dao->insert(TABLE_STORY)->data($storyData, 'spec')->exec();

        $storyID = $this->dao->lastInsertID();

        $this->action->create('story', $storyID, 'Opened', '', '', $storyData->openedBy);

        $data          = new stdclass();
        $data->story   = $storyID;
        $data->version = 1;
        $data->title   = $storyData->title;
        $data->spec    = $storyData->spec;
        $data->verify  = '';
        $data->files   = '';

        $this->dao->insert(TABLE_STORYSPEC)->data($data)->exec();

        $projectID = isset($projects[$story->projectName]) ? $projects[$story->projectName]->id : 0;
        if($projectID)
        {
            $this->story->linkStory($projectID, $productID, $storyID);

            $this->kanban->updateLane($projectID, 'story');

            $oldProjectProduct = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($projectID)->andWhere('product')->eq($productID)->fetch('product');

            if(!$oldProjectProduct)
            {
                $_POST['otherProducts'][0] = $productID;
                $this->project->updateProducts($projectID);
            }
        }

        if(isset($requirements[$story->epicID]) && !empty($requirements[$story->epicID]))
        {
            $URID = $requirements[$story->epicID];
            $requirement = $this->story->getByID($URID);
            $data = new stdclass();
            $data->product  = $story->product;
            $data->AType    = 'requirement';
            $data->relation = 'subdivideinto';
            $data->BType    = 'story';
            $data->AID      = $URID;
            $data->BID      = $storyID;
            $data->AVersion = $requirement->version;
            $data->BVersion = 1;
            $data->extra    = 1;

            $this->dao->insert(TABLE_RELATION)->data($data)->exec();

            $data->AType    = 'story';
            $data->relation = 'subdividedfrom';
            $data->BType    = 'requirement';
            $data->AID      = $storyID;
            $data->BID      = $URID;
            $data->AVersion = 1;
            $data->BVersion = $requirement->version;

            $this->dao->insert(TABLE_RELATION)->data($data)->exec();
        }
    }

    $this->dao->commit();
}

public function migrationRequirement()
{
    $requirements = $this->dao->select('*')->from('zt_migrationrequirement')->fetchAll();

    $projects = $this->dao->select('id, name')->from(TABLE_PROJECT)
         ->where('deleted')->eq(0)
         ->fetchAll('name');

    $users = $this->dao->select('realname, account')->from(TABLE_USER)
        ->where('deleted')->eq(0)
        ->fetchPairs('realname', 'account');

    $businesses = $this->dao->select('REQid, id')->from('zt_flow_business')
        ->where('deleted')->eq(0)
        ->andWhere('REQid')->ne('')
        ->fetchPairs('REQid', 'id');

    $this->loadModel('action');
    $this->loadModel('project');
    $this->loadModel('story');
    $this->loadModel('kanban');

    $categoryList = $this->lang->story->categoryList;
    $priList      = $this->lang->story->priList;
    $statusList   = $this->lang->story->statusList;
    $sourceList   = $this->lang->story->sourceList;

    $now = helper::now();

    $this->dao->begin();

    foreach($requirements as $requirement)
    {
        $oldRequirement = $this->dao->select('id')->from('zt_story')->where('epicID')->eq($requirement->epicID)->andWhere('deleted')->eq(0)->andWhere('type')->eq('requirement')->limit(1)->fetch();

        if($oldRequirement) continue;

        $productID = $requirement->product;

        $requirementData = new stdClass();

        $requirementData->title            = $requirement->title;
        $requirementData->product          = $productID;
        $requirementData->category         = !empty($requirement->category) ? array_search($requirement->category, $categoryList) : $requirement->category;
        $requirementData->pri              = !empty($requirement->pri) ? array_search($requirement->pri, $priList) : $requirement->pri;
        $requirementData->status           = !empty($requirement->status) ? array_search($requirement->status, $statusList) : $requirement->status;
        $requirementData->source           = (!empty($requirement->source) && array_search($requirement->source, $sourceList)) ? array_search($requirement->source, $sourceList) : '';
        $requirementData->sourceNote       = $requirement->sourceNote;
        $requirementData->assignedTo       = (!empty($requirement->assignedTo) && isset($users[$requirement->assignedTo])) ?  $users[$requirement->assignedTo] : '';
        $requirementData->estimate         = !empty($requirement->estimate) ? $requirement->estimate : '';
        $requirementData->openedBy         = (!empty($requirement->openedBy) && isset($users[$requirement->openedBy])) ?  $users[$requirement->openedBy] : '';
        $requirementData->openedDate       = $requirement->openedDate;
        $requirementData->planonlinedate   = $requirement->planonlinedate;
        $requirementData->actualonlinedate = $requirement->actualonlinedate;
        $requirementData->epicID           = $requirement->epicID;
        $requirementData->REQid            = $requirement->REQid;
        $requirementData->spec             = isset($requirement->spec) ? $requirement->spec : '';
        $requirementData->business         = isset($businesses[$requirement->REQid]) ? $businesses[$requirement->REQid] : 0;
        $requirementData->type             = 'requirement';

        $this->dao->insert(TABLE_STORY)->data($requirementData, 'spec')->exec();

        $storyID = $this->dao->lastInsertID();

        $data          = new stdclass();
        $data->story   = $storyID;
        $data->version = 1;
        $data->title   = $requirementData->title;
        $data->spec    = $requirementData->spec;
        $data->verify  = '';
        $data->files   = '';

        $this->dao->insert(TABLE_STORYSPEC)->data($data)->exec();

        $projectID = isset($projects[$requirement->projectName]) ? $projects[$requirement->projectName]->id : 0;
        if($projectID)
        {
            $this->story->linkStory($projectID, $productID, $storyID);

            $this->kanban->updateLane($projectID, 'story');

            $oldProjectProduct = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($projectID)->andWhere('product')->eq($productID)->fetch('product');

            if(!$oldProjectProduct)
            {
                $_POST['otherProducts'][0] = $productID;
                $this->project->updateProducts($projectID);
            }
        }

        $this->action->create('story', $storyID, 'Opened', '', '', $requirementData->openedBy);
    }

    $this->dao->commit();
}

public function changeProduct($type)
{
    $projects = $this->dao->select('id, name')->from(TABLE_PROJECT)
         ->where('deleted')->eq(0)
         ->fetchPairs('name', 'id');

    $defaultProduct = $this->dao->select('id')->from(TABLE_PRODUCT)->where('name')->eq($this->lang->defaultProductTitle)->fetch('id');

    $storyList = $this->dao->select('id, title')->from(TABLE_STORY)
         ->where('deleted')->eq(0)
         ->andWhere('type')->eq($type)
         ->andWhere('product')->eq($defaultProduct)
         ->fetchPairs('title', 'id');

    if($type == 'story')       $table = 'zt_storyproduct';
    if($type == 'requirement') $table = 'zt_requirementproduct';

    $stories = $this->dao->select('*')->from($table)->fetchAll();

    $this->dao->begin();
    foreach($stories as $story)
    {
        $projectID = isset($projects[$story->project]) ? $projects[$story->project] : 0;
        $productID = $story->productID;
        $storyID   = $storyList[$story->name];

        if($projectID)
        {
            $oldProjectProduct = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($projectID)->andWhere('product')->eq($productID)->fetch('product');

            if(!$oldProjectProduct)
            {
                $_POST['otherProducts'][0] = $productID;
                $this->loadModel('project')->updateProducts($projectID);
            }
        }

        if($storyID)
        {
            $this->dao->update(TABLE_STORY)->set('product')->eq($productID)->where('id')->eq($storyID)->exec();
            $this->dao->update(TABLE_PROJECTSTORY)->set('product')->eq($productID)->where('story')->eq($storyID)->exec();
        }
    }

    $this->dao->commit();
}

public function correspondenceStory()
{
    $stories = $this->dao->select('storyID, requirementID')->from('zt_storyrequirement')->fetchPairs('storyID', 'requirementID');

    $this->loadModel('story');

    $this->dao->begin();
    foreach($stories as $storyID => $requirementID)
    {
        $story = $this->story->getByID($storyID);

        if($story)
        {
            $relation      = $this->dao->select('AID')->from(TABLE_RELATION)->where('AID')->eq($requirementID)->andWhere('BID')->eq($storyID)->fetch('AID');
            $storyRelation = $this->dao->select('AID')->from(TABLE_RELATION)->where('BID')->eq($storyID)->fetch();
            if(!$relation && !$storyRelation)
            {
                $requirement = $this->story->getByID($requirementID);

                $data = new stdclass();
                $data->product  = $story->product;
                $data->AType    = 'requirement';
                $data->relation = 'subdivideinto';
                $data->BType    = 'story';
                $data->AID      = $requirementID;
                $data->BID      = $storyID;
                $data->AVersion = $requirement->version;
                $data->BVersion = 1;
                $data->extra    = 1;

                $this->dao->insert(TABLE_RELATION)->data($data)->exec();

                $data->AType    = 'story';
                $data->relation = 'subdividedfrom';
                $data->BType    = 'requirement';
                $data->AID      = $storyID;
                $data->BID      = $requirementID;
                $data->AVersion = 1;
                $data->BVersion = $requirement->version;

                $this->dao->insert(TABLE_RELATION)->data($data)->exec();
            }

            if($story->status == 'draft') $this->dao->update(TABLE_STORY)->set('status')->eq('active')->where('id')->eq($storyID)->exec();
        }
    }

    $this->dao->commit();
}

public function changeBusinessPM()
{
    $businesses = $this->dao->select('id, createdBy')->from('zt_flow_business')
        ->where('deleted')->eq(0)
        ->andWhere('businessPM')->eq('')
        ->andWhere('status')->eq('activate')
        ->fetchPairs('id', 'createdBy');

    $this->dao->begin();

    foreach($businesses as $businessID => $createdBy)
    {
        $this->dao->update('zt_flow_business')->set('businessPM')->eq($createdBy)->where('id')->eq($businessID)->exec();
        $this->loadModel('flow')->mergeVersionByObjectType($businessID, 'business');
    }

    $this->dao->commit();
}

public function changeStoryBusiness()
{
    $stories = $this->dao->select('storyID, businessID')->from('zt_storybusiness')->fetchPairs('storyID', 'businessID');

    $this->dao->begin();

    foreach($stories as $storyID => $businessID) $this->dao->update(TABLE_STORY)->set('business')->eq($businessID)->where('id')->eq($storyID)->exec();

    $this->dao->commit();
}
