<?php
public function batchClone()
{
    $this->loadModel('testcase');
    $this->loadModel('action');

    $now   = helper::now();
    $cases = fixer::input('post')->get();

    $fromCaseIdList = $this->post->caseIDList;
    $fromCases = $this->dao->select('*')->from('zt_case')->where('id')->in($fromCaseIdList)->fetchPairs('id');

    foreach($cases->title as $i => $title)
    {
        if(!empty($cases->title[$i]) and empty($cases->type[$i])) return print(js::alert(sprintf($this->lang->error->notempty, $this->lang->testcase->type)));
    }

    $forceNotReview = $this->testcase->forceNotReview();
    foreach($cases->title as $i => $title)
    {
        if(empty($title)) continue;
        if(empty($cases->lib[$i])) continue;
        if(empty($cases->type[$i])) continue;
        $currentFromCaseID = $cases->caseIDList[$i];
        $currentFromCase   = $this->testcase->getByID($currentFromCaseID);

        $data[$i] = new stdclass();
        $data[$i]->lib          = $cases->lib[$i];
        $data[$i]->module       = $cases->modules[$i];
        $data[$i]->type         = $cases->type[$i];
        $data[$i]->pri          = $cases->pri[$i];
        $data[$i]->stage        = empty($cases->stage[$i]) ? '' : implode(',', $cases->stage[$i]);
        $data[$i]->color        = $cases->color[$i];
        $data[$i]->title        = $cases->title[$i];
        $data[$i]->precondition = $cases->precondition[$i];
        $data[$i]->keywords     = $cases->keywords[$i];
        $data[$i]->openedBy     = $this->app->user->account;
        $data[$i]->openedDate   = $now;
        $data[$i]->status       = $forceNotReview ? 'normal' : 'wait';
        $data[$i]->version      = 1;
        $data[$i]->project      = 0;

        if($this->lang->navGroup->caselib != 'qa' and $this->session->project) $data[$i]->project = $this->session->project;
        $files = $currentFromCase->files;
        $steps = $currentFromCase->steps;

        $this->dao->insert(TABLE_CASE)->data($data[$i])
            ->autoCheck()
            ->batchCheck($this->config->testcase->create->requiredFields, 'notempty')
            ->exec();
        if(dao::isError())
        {
            return helper::end(js::error(dao::getError()));
        }

        $caseID   = $this->dao->lastInsertID();

        foreach($steps as $stepID => $step)
        {
            unset($step->id);
            $step->version = 1;
            $step->case    = $caseID;
            $this->dao->insert(TABLE_CASESTEP)->data($step)->autoCheck()->exec();
        }

        foreach($files as $fileID => $file)
        {
            $fileName = pathinfo($file->pathname, PATHINFO_FILENAME);
            $datePath = substr($file->pathname, 0, 6);
            $realPath = $this->app->getAppRoot() . "www/data/upload/{$this->app->company->id}/" . "{$datePath}/" . $fileName;

            $rand        = rand();
            $newFileName = $fileName . 'copy' . $rand;
            $newFilePath = $this->app->getAppRoot() . "www/data/upload/{$this->app->company->id}/" . "{$datePath}/" .  $newFileName;
            copy($realPath, $newFilePath);

            $newFileName = $file->pathname;
            $newFileName = str_replace('.', "copy$rand.", $newFileName);

            unset($file->id, $file->realPath, $file->webPath);
            $file->objectID = $caseID;
            $file->pathname = $newFileName;
            $this->dao->insert(TABLE_FILE)->data($file)->exec();
            $newFiles .= $this->dao->lastInsertId() . ',';
        }
        $actionID = $this->action->create('case', $caseID, 'Opened');
    }
}

/**
 * Get libraries.
 *
 * @access public
 * @return array
 */
public function getLibraries()
{
    return $this->dao->select("id,name")->from(TABLE_TESTSUITE)
        ->where('product')->eq(0)
        ->andWhere('deleted')->eq(0)
        ->andWhere('type')->eq('library')
        ->beginIF(!$this->app->user->admin)->andWhere('(acl')->eq('open')->orWhere('whitelist')->like("%{$this->app->user->account}%")->markRight(1)->fi()
        ->orderBy('order_desc, id_desc')
        ->fetchPairs('id', 'name');
}

/**
 * Update a caselib.
 *
 * @param  int   $libID
 * @access public
 * @return bool|array
 */
public function update($libID)
{
    $oldLib = $this->dao->select("*")->from(TABLE_TESTSUITE)->where('id')->eq((int)$libID)->fetch();
    $lib    = fixer::input('post')
        ->stripTags($this->config->caselib->editor->edit['id'], $this->config->allowedTags)
        ->setIF($this->post->acl == 'open' || !isset($_POST['whitelist']), 'whitelist', '')
        ->add('id', $libID)
        ->add('lastEditedBy', $this->app->user->account)
        ->add('lastEditedDate', helper::now())
        ->join('whitelist', ',')
        ->remove('uid,contactListMenu')
        ->get();
    $lib = $this->loadModel('file')->processImgURL($lib, $this->config->caselib->editor->edit['id'], $this->post->uid);
    $this->dao->update(TABLE_TESTSUITE)->data($lib)
        ->autoCheck()
        ->batchcheck($this->config->caselib->edit->requiredFields, 'notempty')
        ->checkFlow()
        ->where('id')->eq($libID)
        ->checkFlow()
        ->exec();
    if(!dao::isError())
    {
        $this->file->updateObjectID($this->post->uid, $libID, 'caselib');
        return common::createChanges($oldLib, $lib);
    }
    return false;
}

/**
 * Create lib.
 *
 * @access public
 * @return int
 */
public function create()
{
    $lib = fixer::input('post')
        ->stripTags($this->config->caselib->editor->create['id'], $this->config->allowedTags)
        ->setForce('type', 'library')
        ->setIF($this->lang->navGroup->caselib != 'qa', 'project', (int)$this->session->project)
        ->setIF($this->post->acl == 'open' || !isset($_POST['whitelist']), 'whitelist', '')
        ->add('addedBy', $this->app->user->account)
        ->add('addedDate', helper::now())
        ->join('whitelist', ',')
        ->remove('uid,contactListMenu')
        ->get();

    $lib = $this->loadModel('file')->processImgURL($lib, $this->config->caselib->editor->create['id'], $this->post->uid);

    $this->lang->testsuite->name = $this->lang->caselib->name;
    $this->lang->testsuite->desc = $this->lang->caselib->desc;
    $this->dao->insert(TABLE_TESTSUITE)->data($lib)
        ->batchcheck($this->config->caselib->create->requiredFields, 'notempty')
        ->check('name', 'unique', "deleted = '0'")
        ->checkFlow()
        ->exec();
    if(!dao::isError())
    {
        $libID = $this->dao->lastInsertID();
        $this->file->updateObjectID($this->post->uid, $libID, 'caselib');
        return $libID;
    }
    return false;
}

/**
 * Create module without cases.
 *
 * @param  int    $libID
 * @access public
 * @return mixed
 */
public function createModuleWithoutCases($libID)
{
    $subjectID = $this->dao->select('AL_ITEM_ID')->from('zt_all_lists')->where('AL_DESCRIPTION')->eq('Subject')->fetch('AL_ITEM_ID');
    $allLists  = $this->dao->select('*')->from('zt_all_lists')->where('AL_FATHER_ID')->eq($subjectID)->fetchAll('AL_ITEM_ID');

    $module = $this->dao->select('id')->from(TABLE_MODULE)->where('from')->eq(key($allLists))->andWhere('type')->eq('caselib')->andWhere('root')->eq($libID)->fetch('id');

    if($module) return true;

    if($allLists)
    {
        foreach($allLists as $itemID => $list)
        {
            $subject = new stdClass();

            $subject->root   = $libID;
            $subject->type   = 'caselib';
            $subject->parent = 0;
            $subject->name   = $list->AL_DESCRIPTION;
            $subject->branch = 0;
            $subject->short  = '';
            $subject->order  = '10';
            $subject->grade  = '1';
            $subject->from   = $itemID;

            $this->dao->insert(TABLE_MODULE)->data($subject)->exec();
            $moduleID = $this->dao->lastInsertID();

            $path = ",$moduleID,";
            $this->dao->update(TABLE_MODULE)->set('`path`')->eq($path)->where('id')->eq($moduleID)->exec();

            $this->createModuleWithoutCase($libID, $moduleID, $list->AL_ITEM_ID, 1, 10, $path);
        }
    }
}

/**
 * Create without case.
 *
 * @param  int    $libID
 * @param  string $type
 * @access public
 * @return mixed
 */
public function createWithoutCase($libID, $type)
{
    $tests = $this->dao->select('*')->from('zt_chunhangtest')->fetchAll();

    $priList = ['5-Urgent' => '1', '4-Very High' => 2, '3-High' => 3, '2-Medium' => 4, '1-Low' => 5];

    foreach($tests as $test)
    {
        $testcase = $this->dao->select('id')->from(TABLE_CASE)->where('tstestID')->eq($test->TS_TEST_ID)->andWhere('lib')->eq($libID)->limit(1)->fetch('id');

        if($testcase) continue;

        $case = new stdClass();

        $case->lib          = $libID;
        $case->module       = 0;
        $case->type         = $type;
        $case->title        = $test->TS_NAME;
        $case->pri          = zget($priList, $test->TS_USER_02, 3);
        $case->precondition = rtrim(htmlSpecialString(str_replace('<br>', "\n", strip_tags($test->TS_DESCRIPTION, '<br>'))));
        $case->status       = 'normal';
        $case->version      = '1';
        $case->fromBug      = 0;
        $case->openedBy     = 'qualitycenter';
        $case->openedDate   = $test->TS_CREATION_DATE;;
        $case->story        = 0;
        $case->tstestID     = $test->TS_TEST_ID;
        $case->module       = $this->dao->select('id')->from(TABLE_MODULE)->where('from')->eq($test->TS_SUBJECT)->andWhere('type')->eq('caselib')->andWhere('root')->eq($libID)->limit(1)->fetch('id');

        $this->dao->insert(TABLE_CASE)->data($case)->exec();
        $caseID = $this->dao->lastInsertID();

        $this->loadModel('action')->create('case', $caseID, 'create', '', '', 'qualitycenter');

        $this->dao->update(TABLE_CASE)->set('sort')->eq($caseID)->where('id')->eq($caseID)->exec();

        $this->loadModel('score')->create('testcase', 'create', $caseID);
    }
}

/**
 * Create without case.
 *
 * @param  int    $libID
 * @param  string $type
 * @access public
 * @return mixed
 */
public function createWithoutCaseStep($libID)
{
    $testcases = $this->dao->select('id, tstestID, callCaseID')->from(TABLE_CASE)->where('lib')->eq($libID)->fetchAll('id');

    foreach($testcases as $caseID => $testcase)
    {
        $steps = $this->dao->select('*')->from('zt_chunhangstep')
            ->where('DS_TEST_ID')->eq($testcase->tstestID)
            ->orderBy('DSSTEPORDER')
            ->fetchAll();

        if($steps)
        {
            $callCaseIdList = '';

            foreach($steps as $step)
            {
                $desc = rtrim(htmlSpecialString(str_replace('<br>', "\n", strip_tags($step->DS_DESCRIPTION, '<br>'))));
                if($step->DS_LINK_TEST > 0)
                {
                    $callCaseID = $this->dao->select('id')->from(TABLE_CASE)->where('tstestID')->eq($step->DS_LINK_TEST)->andWhere('lib')->eq($libID)->limit(1)->fetch('id');
                    $desc = '调用' . $callCaseID;

                    $callCaseIdList .= $callCaseID . ',';
                }

                $stepData          = new stdClass();
                $stepData->type    = 'step';
                $stepData->parent  = 0;
                $stepData->case    = $caseID;
                $stepData->version = 1;
                $stepData->desc    = $desc;
                $stepData->expect  = rtrim(htmlSpecialString(str_replace('<br>', "\n", strip_tags($step->DS_EXPECTED, '<br>'))));

                $this->dao->insert(TABLE_CASESTEP)->data($stepData)->exec();
            }

            if($callCaseIdList)
            {
                $callCaseIdList = explode(',', trim($callCaseIdList, ','));
                $this->dao->update(TABLE_CASE)->set('callCaseID')->eq(implode(',', array_unique(array_filter($callCaseIdList))))->where('id')->eq($caseID)->exec();
            }
        }
    }
}

public function createModuleWithoutCase($libID, $parentID, $alItemID, $grade, $order, $path)
{
    $allLists = $this->dao->select('*')->from('zt_all_lists')->where('AL_FATHER_ID')->eq($alItemID)->fetchAll('AL_ITEM_ID');

    if($allLists)
    {
        foreach($allLists as $itemID => $list)
        {
            $subject = new stdClass();

            $subject->root   = $libID;
            $subject->type   = 'caselib';
            $subject->parent = $parentID;
            $subject->name   = $list->AL_DESCRIPTION;
            $subject->branch = 0;
            $subject->short  = '';
            $subject->order  = $order + 10;
            $subject->grade  = $grade + 1;
            $subject->from   = $itemID;

            $this->dao->insert(TABLE_MODULE)->data($subject)->exec();

            $moduleID   = $this->dao->lastInsertID();
            $modulePath = $path . "$moduleID,";

            $this->dao->update(TABLE_MODULE)->set('`path`')->eq($modulePath)->where('id')->eq($moduleID)->exec();

            $this->createModuleWithoutCase($libID, $moduleID, $list->AL_ITEM_ID, $subject->grade, $subject->order, $modulePath);
        }
    }
}

/**
 * Check priv.
 *
 * @param  int    $libID
 * @access public
 * @return bool
 */
public function checkPriv($libID)
{
    if($this->app->user->admin) return true;

    $lib = $this->dao->select('*')->from(TABLE_TESTSUITE)->where('id')->eq((int)$libID)->fetch();
    if(!$lib) return false;

    if($lib->acl == 'private')
    {
        $whitelist = explode(',', $lib->whitelist);

        if(!in_array($this->app->user->account, $whitelist)) return false;
    }

    return true;
}
