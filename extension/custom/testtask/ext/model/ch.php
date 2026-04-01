<?php
/**
 * Get test tasks of a execution.
 *
 * @param  int    $executionID
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getExecutionTasks($executionID, $objectType = 'execution', $orderBy = 'id_desc', $pager = null)
{
    return $this->dao->select('t1.*, t2.name AS buildName')
        ->from(TABLE_TESTTASK)->alias('t1')
        ->leftJoin(TABLE_BUILD)->alias('t2')->on('t1.build = t2.id')
        ->where('t1.deleted')->eq(0)
        ->beginIF($objectType == 'execution')->andWhere('t1.execution')->in($executionID)->fi()
        ->beginIF($objectType == 'project')->andWhere('t1.project')->in($executionID)->fi()
        ->andWhere('t1.auto')->ne('unit')
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');
}

/**
 * Create a test task.
 *
 * @param  int   $projectID
 * @access public
 * @return void
 */
public function create($projectID = 0)
{
    if($this->app->tab == 'chteam')
    {
        if(empty(trim($this->post->product)))
        {
            dao::$errors[] = sprintf($this->lang->notempty, $this->lang->testtask->product);
            return false;
        }
    }
    if($this->post->execution)
    {
        $execution = $this->loadModel('execution')->getByID($this->post->execution);
        $projectID = $execution->project;
    }

    if($this->post->build && empty($projectID))
    {
        $build     = $this->loadModel('build')->getById($this->post->build);
        $projectID = $build->project;
    }

    $task = fixer::input('post')
        ->setDefault('build', '')
        ->setDefault('project', $projectID)
        ->setDefault('createdBy', $this->app->user->account)
        ->setDefault('createdDate', helper::now())
        ->setDefault('members', '')
        ->stripTags($this->config->testtask->editor->create['id'], $this->config->allowedTags)
        ->join('mailto', ',')
        ->join('type', ',')
        ->join('members', ',')
        ->remove('files,labels,uid,contactListMenu')
        ->get();
    $task->members = trim($task->members, ',');

    $task = $this->loadModel('file')->processImgURL($task, $this->config->testtask->editor->create['id'], $this->post->uid);
    $this->dao->insert(TABLE_TESTTASK)->data($task)
        ->autoCheck($skipFields = 'begin,end')
        ->batchcheck($this->config->testtask->create->requiredFields, 'notempty')
        ->checkIF($task->begin != '', 'begin', 'date')
        ->checkIF($task->end != '', 'end', 'date')
        ->checkIF($task->end != '', 'end', 'ge', $task->begin)
        ->checkFlow()
        ->exec();

    if(!dao::isError())
    {
        $taskID = $this->dao->lastInsertID();
        $this->file->updateObjectID($this->post->uid, $taskID, 'testtask');
        $this->file->saveUpload('testtask', $taskID);
        return $taskID;
    }
}

/**
 * Get unassociated test cases for the team's test sheet
 *
 * @param  int    $projectID
 * @param  int    $productID
 * @param  int    $taskID
 * @param  object $pager
 * @access public
 * @return array
 */
public function getChLinkableCase($projectID, $productID, $taskID, $pager = null)
{
    $linkedCases = $this->dao->select('`case`')->from(TABLE_TESTRUN)->where('task')->eq($taskID)->fetchPairs('case');

    return $this->dao->select('distinct t1.*, t2.*,t5.id as projectID, t1.product as productID')->from(TABLE_PROJECTCASE)->alias('t1')
        ->leftJoin(TABLE_CASE)->alias('t2')->on('t1.case=t2.id')
        ->leftJoin(TABLE_MODULE)->alias('t3')->on('t2.module=t3.id')
        ->leftJoin(TABLE_EXECUTION)->alias('t4')->on('t2.execution=t4.id')
        ->leftJoin(TABLE_PROJECT)->alias('t5')->on('t4.project=t5.id')
        ->where('t1.project')->in($projectID)
        ->beginIf(!empty($linkedCases))->andWhere('t2.id')->notin($linkedCases)->fi()
        ->beginIF(!empty($productID))->andWhere('t1.product')->eq($productID)->fi()
        ->andWhere('t2.deleted')->eq('0')
        ->orderBy('id_desc')
        ->page($pager)
        ->fetchAll('id');
}

/**
 * Print rows of cases.
 *
 * @param  array  $cases
 * @param  array  $setting
 * @param  array  $users
 * @param  array  $branchOption
 * @param  array  $modulePairs
 * @param  string $browseType
 * @param  string $mode
 * @param  int    $chprojectID
 * @access public
 * @return int
 */
public function printRow($cases, $setting, $users, $task, $branchOption, $modulePairs, $browseType, $mode, $chprojectID = 0)
{
    foreach($cases as $case)
    {
        $trClass = '';
        $trAttrs = "data-id='{$case->id}' data-auto='" . zget($case, 'auto', '') . "' data-order='{$case->sort}' data-parent='{$case->parent}' data-product='{$case->product}'";
        if($case->isScene)
        {
            $trAttrs .= " data-nested='true'";
            $trClass .= $case->parent == '0' ? ' is-top-level table-nest-child-hide' : ' table-nest-hide';
        }

        if($case->parent)
        {
            if(!$case->isScene) $trClass .= ' is-nest-child';
            $trClass .= ' table-nest-hide';
            $trAttrs .= " data-nest-parent='{$case->parent}' data-nest-path='{$case->path}'";
        }
        elseif(!$case->isScene)
        {
            $trClass .= ' no-nest';
        }
        $trAttrs .= " class='row-case $trClass'";

        $case->id = str_replace(array('case_', 'scene_'), '', $case->id);   // Remove the prefix of case id.

        $isScene = $case->isScene ? 1 : 0;
        echo "<tr data-is-scene='{$isScene}' {$trAttrs}>";
        foreach($setting as $key => $value) $this->printCell($value, $case, $users, $task, $branchOption, $modulePairs, $mode, $chprojectID);
        echo '</tr>';

        if(!empty($case->children) || !empty($case->cases))
        {
            if(!empty($case->children)) $this->printRow($case->children, $setting, $users, $task, $branchOption, $modulePairs, $browseType, $mode, $chprojectID);
            if(!empty($case->cases))    $this->printRow($case->cases,    $setting, $users, $task, $branchOption, $modulePairs, $browseType, $mode, $chprojectID);
        }
    }
}

/**
 * Print cell data.
 *
 * @param mixed $col
 * @param mixed $run
 * @param mixed $users
 * @param mixed $task
 * @param mixed $branches
 * @param mixed $modulePairs
 * @param string $mode
 * @param int   $chprojectID
 * @access public
 * @return void
 */
public function printCell($col, $run, $users, $task, $branches, $modulePairs, $mode = 'datatable', $chprojectID = 0)
{
    $isScene        = $run->isScene;
    $canBatchEdit   = common::hasPriv('testcase', 'batchEdit');
    $canBatchUnlink = common::hasPriv('testtask', 'batchUnlinkCases');
    $canBatchAssign = common::hasPriv('testtask', 'batchAssign');
    $canBatchRun    = common::hasPriv('testtask', 'batchRun');

    $canBatchAction = ($canBatchEdit or $canBatchUnlink or $canBatchAssign or $canBatchRun);

    $canView     = common::hasPriv('testcase', 'view');
    $caseLink    = helper::createLink('testcase', 'view', "caseID=$run->id&version=$run->version&from=testtask&taskID=$task->id");
    $account     = $this->app->user->account;
    $id          = $col->id;

    $run->caseVersion = isset($run->caseVersion) ? $run->caseVersion : 1;
    $run->assignedTo  = isset($run->assignedTo) ? $run->assignedTo : '';
    $caseChanged = !$run->isScene && $run->version < $run->caseVersion;
    $fromCaseID  = $run->fromCaseID;

    if($col->show)
    {
        $class = "c-$id ";
        $title = '';
        if($id == 'status') $class .= "{$run->status} status-testcase status-{$run->caseStatus}";
        if($id == 'title')
        {
            $class .= ' text-left';
            $title  = "title='{$run->title}'";
        }
        if($id == 'id')     $class .= ' cell-id';
        if($id == 'lastRunResult') $class .= "result-testcase $run->lastRunResult";
        if($id == 'assignedTo' && $run->assignedTo == $account) $class .= ' red';
        if($id == 'actions') $class .= 'c-actions';

        if($id == 'title')
        {
            if($isScene)
            {
                echo "<td class='c-name table-nest-title text-left sort-handler has-prefix has-suffix' {$title}><span class='table-nest-icon icon '></span>";
            }
            else
            {
                $icon = $run->auto == 'auto' ? 'icon-ztf' : 'icon-test';
                echo "<td class='c-name table-nest-title text-left sort-handler has-prefix has-suffix' {$title}><span class='table-nest-icon icon {$icon}'></span>";
            }
        }
        else
        {
            echo "<td class='" . $class . "'" . ($id=='title' ? "title='{$run->title}'":'') . ">";
        }

        if($this->config->edition != 'open') $this->loadModel('flow')->printFlowCell('testcase', $run, $id);
        switch ($id)
        {
        case 'id':
            $showID = sprintf('%03d', $run->id);
            if($canBatchAction)
            {
                if(!$isScene)
                {
                    echo html::checkbox('caseIDList', array($run->id => ''), '') . html::a(helper::createLink('testcase', 'view', "caseID=$run->id"), $showID, '', "data-app='{$this->app->tab}'");
                }
                else
                {
                    echo html::checkbox('sceneIDList', array($run->id => ''), '');
                }
            }
            else
            {
                echo $showID;
            }
            break;
        case 'pri':
            echo "<span class='label-pri label-pri-" . $run->pri . "' title='" . zget($this->lang->testcase->priList, $run->pri, $run->pri) . "'>";
            echo zget($this->lang->testcase->priList, $run->pri, $run->pri);
            echo "</span>";
            break;
        case 'title':
            if(!empty($branches)) echo "<span class='label label-badge label-outline'>{$branches[$run->branch]}</span> ";
            if($modulePairs and $run->module and isset($modulePairs[$run->module])) echo "<span class='label label-gray label-badge'>{$modulePairs[$run->module]}</span> ";
            if($canView and !$isScene)
            {
                if($fromCaseID)
                {
                    echo html::a($caseLink, $run->title, null, "style='color: $run->color'") . html::a(helper::createLink('testcase', 'view', "caseID=$fromCaseID"), "[<i class='icon icon-share' title='{$this->lang->testcase->fromCase}'></i>#$fromCaseID]");
                }
                else
                {
                    echo html::a($caseLink, $run->title, null, "style='color: $run->color'");
                }
            }
            else
            {
                echo "<span style='color: $run->color'>$run->title</span>";
            }
            break;
        case 'branch':
            echo $branches[$run->branch];
            break;
        case 'type':
            echo $this->lang->testcase->typeList[$run->type];
            break;
        case 'stage':
            foreach(explode(',', trim($run->stage, ',')) as $stage) echo $this->lang->testcase->stageList[$stage] . '<br />';
            break;
        case 'status':
            if($run->caseStatus != 'wait' and $caseChanged)
            {
                echo "<span title='{$this->lang->testcase->changed}' class='warning'>{$this->lang->testcase->changed}</span>";
            }
            else
            {
                $case = new stdClass();
                $case->status = $run->caseStatus;

                $status = $this->processStatus('testcase', $case);
                if($run->status == $status) $status = $this->processStatus('testtask', $run);
                echo $status;
            }
            break;
        case 'precondition':
            echo $run->precondition;
            break;
        case 'keywords':
            echo $run->keywords;
            break;
        case 'version':
            echo $run->version;
            break;
        case 'openedBy':
            echo zget($users, $run->openedBy);
            break;
        case 'openedDate':
            echo substr($run->openedDate, 5, 11);
            break;
        case 'reviewedBy':
            echo zget($users, $run->reviewedBy);
            break;
        case 'reviewedDate':
            echo substr($run->reviewedDate, 5, 11);
            break;
        case 'lastEditedBy':
            echo zget($users, $run->lastEditedBy);
            break;
        case 'lastEditedDate':
            echo substr($run->lastEditedDate, 5, 11);
            break;
        case 'lastRunner':
            echo zget($users, $run->lastRunner);
            break;
        case 'lastRunDate':
            echo helper::isZeroDate($run->lastRunDate) ? '' : substr($run->lastRunDate, 5, 11);
            break;
        case 'lastRunResult':
            $lastRunResultText = $run->lastRunResult ? zget($this->lang->testcase->resultList, $run->lastRunResult, $run->lastRunResult) : $this->lang->testcase->unexecuted;
            echo $lastRunResultText;
            break;
        case 'story':
            if($run->story and $run->storyTitle) echo html::a(helper::createLink('story', 'view', "storyID=$run->story"), $run->storyTitle);
            break;
        case 'assignedTo':
            $btnTextClass = '';
            if($run->assignedTo == $this->app->user->account) $btnTextClass = 'assigned-current';
            if(!empty($run->assignedTo) and $run->assignedTo != $this->app->user->account) $btnTextClass = 'assigned-other';
            echo "<span class='$btnTextClass'>" . zget($users, $run->assignedTo) . '</span>';
            break;
        case 'bugs':
            echo (common::hasPriv('testcase', 'bugs') and $run->bugs) ? html::a(helper::createLink('testcase', 'bugs', "runID={$run->run}&caseID={$run->case}"), $run->bugs, '', "class='iframe'") : $run->bugs;
            break;
        case 'results':
            $params = "runID={$run->run}&caseID={$run->case}";
            if($this->app->tab == 'chteam') $params .= "&version=0&status=done&chprojectID={$chprojectID}";
            echo (common::hasPriv('testtask', 'results') and $run->results) ? html::a(helper::createLink('testtask', 'results', $params), $run->results, '', "class='iframe'") : $run->results;
            break;
        case 'stepNumber':
            echo $run->stepNumber;
            break;
        case 'actions':
            if($isScene) break;
            if(!empty($run->confirmeObject))
            {
                $method = $run->confirmeObject['type'] == 'confirmedretract' ? 'confirmDemandRetract' : 'confirmDemandUnlink';
                common::printIcon('testcase', $method, "objectID=$run->case&object=case&extra={$run->confirmeObject['id']}", $run, 'list', 'search', '', 'iframe', true);
                break;
            }

            if($run->caseStatus != 'wait' and $caseChanged)
            {
                common::printIcon('testcase', 'confirmChange', "id=$run->case&taskID=$run->task&from=list", $run, 'list', 'search', 'hiddenwin');
                break;
            }

            if($this->app->tab == 'chteam')
            {
                common::printIcon('testcase', 'createBug', "product=$run->product&branch=$run->branch&extra=executionID=$task->execution,buildID=$task->build,caseID=$run->case,version=$run->version,runID=$run->run,testtask=$task->id,storyID=$run->story,projectID=$run->project&chprojectID=$chprojectID", $run, 'list', 'bug', '', 'iframe', '', "data-width='90%'");

                common::printIcon('testtask', 'runCase', "id=$run->run&caseID=$run->case&version=0&confirm=&chprojectID=$chprojectID", $run, 'list', 'play', '', 'runCase iframe', false, "data-width='95%'");
                common::printIcon('testtask', 'results', "id=$run->run&caseID=$run->case&version=0&status=done&chprojectID=$chprojectID", $run, 'list', '', '', 'iframe', '', "data-width='90%'");
            }
            else
            {
                common::printIcon('testcase', 'createBug', "product=$run->product&branch=$run->branch&extra=executionID=$task->execution,buildID=$task->build,caseID=$run->case,version=$run->version,runID=$run->run,testtask=$task->id,storyID=$run->story", $run, 'list', 'bug', '', 'iframe', '', "data-width='90%'");

                common::printIcon('testtask', 'runCase', "id=$run->run&caseID=$run->case", $run, 'list', 'play', '', 'runCase iframe', false, "data-width='95%'");
                common::printIcon('testtask', 'results', "id=$run->run", $run, 'list', '', '', 'iframe', '', "data-width='90%'");
            }

            if(common::hasPriv('testtask', 'unlinkCase', $run))
            {
                $unlinkURL = helper::createLink('testtask', 'unlinkCase', "caseID=$run->run&confirm=yes");
                echo html::a("javascript:void(0)", '<i class="icon-unlink"></i>', '', "title='{$this->lang->testtask->unlinkCase}' class='btn' onclick='ajaxDelete(\"$unlinkURL\", \"casesForm\", confirmUnlink)'");
            }

            break;
        }
        echo '</td>';
    }
}
