<?php

/**
 * Save xmind config.
 *
 * @access public
 * @return array
 */
function saveXmindConfig()
{
    $configList = array();

    $module = $this->post->module;
    if(isset($module) && !empty($module))
    {
        if(!$this->checkConfigValue($module)) return array('result' => 'fail', 'message' => '模块特征字符串只能是1-10个字母');
        $configList[] = array('key'=>'module','value'=>$module);
    }

    $scene = $this->post->scene;
    if(isset($scene) && !empty($scene))
    {
        if(!$this->checkConfigValue($scene)) return array('result' => 'fail', 'message' => '场景特征字符串只能是1-10个字母');
        $configList[] = array('key'=>'scene','value'=>$scene);
    }

    $case = $this->post->case;
    if(isset($case) && !empty($case))
    {
        if(!$this->checkConfigValue($case)) return array('result' => 'fail', 'message' => '测试用例特征字符串只能是1-10个字母');
        $configList[] = array('key'=>'case','value'=>$case);
    }

    $pri = $this->post->pri;
    if(isset($pri) && !empty($pri))
    {
        if(!$this->checkConfigValue($pri)) return array('result' => 'fail', 'message' => '优先级特征字符串只能是1-10个字母');
        $configList[] = array('key'=>'pri','value'=>$pri);
    }

    $group = $this->post->group;
    if(isset($group) && !empty($group))
    {
        if(!$this->checkConfigValue($group)) return array('result' => 'fail', 'message' => '步骤分组特征字符串只能是1-10个字母');
        $configList[] = array('key'=>'group','value'=>$group);
    }

    $precondition = $this->post->precondition;
    if(isset($precondition) && !empty($precondition))
    {
        if(!$this->checkConfigValue($precondition)) return array('result' => 'fail', 'message' => '步骤分组特征字符串只能是1-10个字母');
        $configList[] = array('key'=>'precondition','value'=>$precondition);
    }

    $map = array();
    $map[strtolower($module)]       = true;
    $map[strtolower($scene)]        = true;
    $map[strtolower($case)]         = true;
    $map[strtolower($pri)]          = true;
    $map[strtolower($group)]        = true;
    $map[strtolower($precondition)] = true;

    if(count($map) < 6) return array('result' => 'fail', 'message' => '特征字符串不能重复');

    $this->dao->begin();

    $this->dao->delete()->from(TABLE_CONFIG)
        ->where('owner')->eq($this->app->user->account)
        ->andWhere('module')->eq('testcase')
        ->andWhere('section')->eq('xmind')
        ->exec();

    foreach($configList as $one)
    {
        $config = new stdclass();

        $config->module  = 'testcase';
        $config->section = 'xmind';
        $config->key     = $one['key'];
        $config->value   = $one['value'];
        $config->owner   = $this->app->user->account;

        $this->dao->insert(TABLE_CONFIG)->data($config)->autoCheck()->exec();

        if($this->dao->isError())
        {
            $this->dao->rollBack();
            return array('result' => 'fail', 'message' => $this->dao->getError(true));
        }
    }

    $this->dao->commit();

    return array("result" => "success", "message" => 1);
}


/**
 * Save test case.
 *
 * @param  array $testcaseData
 * @param  array $sceneIds
 * @access public
 * @return array
 */
public function saveTestcase($testcaseData, $sceneIds)
{
    $tmpPId = $testcaseData['tmpPId'];
    $scene  = 0;

    if(isset($sceneIds[$tmpPId]))
    {
        $pScene = $sceneIds[$tmpPId];
        $scene  = $pScene['id'];
    }

    $id           = isset($testcaseData['id']) ? $testcaseData['id'] : -1;
    $module       = $testcaseData['module'];
    $product      = $testcaseData['product'];
    $branch       = $testcaseData['branch'];
    $title        = $testcaseData['name'];
    $pri          = $testcaseData['pri'];
    $precondition = $testcaseData['precondition'];
    $now          = helper::now();
    $testcaseID   = -1;
    $version      = 1;

    if(!isset($testcaseData['id']))
    {
        $testcase               = new stdclass();
        $testcase->scene        = $scene;
        $testcase->module       = $module;
        $testcase->product      = $product;
        $testcase->branch       = $branch;
        $testcase->title        = $title;
        $testcase->pri          = $pri;
        $testcase->precondition = $precondition;
        $testcase->type         = 'feature';
        $testcase->status       = 'normal';
        $testcase->version      = $version;
        $testcase->openedBy     = $this->app->user->account;
        $testcase->openedDate   = $now;

        $this->dao->insert(TABLE_CASE)->data($testcase)->autoCheck()->exec();

        $testcaseID = $this->dao->lastInsertID();
        $this->dao->update(TABLE_CASE)->set('sort')->eq($testcaseID)->where('id')->eq($testcaseID)->exec();
    }
    else
    {
        $oldCase = $this->dao->select('version,id')->from(TABLE_CASE)->where('id')->eq((int)$id)->fetch();

        if(isset($oldCase->id))
        {
            if(!isset($oldCase->version)) return array('result' => 'fail', 'message' => 'not exist testcase');

            $version  = $oldCase->version + 1;

            $testcase                 = new stdclass();
            $testcase->id             = $id;
            $testcase->scene          = $scene;
            $testcase->module         = $module;
            $testcase->product        = $product;
            $testcase->branch         = $branch;
            $testcase->title          = $title;
            $testcase->pri            = $pri;
            $testcase->precondition   = $precondition;
            $testcase->version        = $version;
            $testcase->lastEditedBy   = $this->app->user->account;
            $testcase->lastEditedDate = $now;

            $testcaseID = $id;
            $this->dao->update(TABLE_CASE)->data($testcase)->where('id')->eq((int)$id)->exec();
        }
        else
        {
            $testcase               = new stdclass();
            $testcase->scene        = $scene;
            $testcase->module       = $module;
            $testcase->product      = $product;
            $testcase->branch       = $branch;
            $testcase->title        = $title;
            $testcase->pri          = $pri;
            $testcase->precondition = $precondition;
            $testcase->type         = 'feature';
            $testcase->status       = 'normal';
            $testcase->version      = $version;
            $testcase->openedBy     = $this->app->user->account;
            $testcase->openedDate   = $now;

            $this->dao->insert(TABLE_CASE)->data($testcase)->autoCheck()->exec();

            $testcaseID = $this->dao->lastInsertID();
            $this->dao->update(TABLE_CASE)->set('sort')->eq($testcaseID)->where('id')->eq($testcaseID)->exec();
        }
    }

    if(dao::isError()) return array('result' => 'fail', 'message' => $this->dao->getError(true));

    $stepList = isset($testcaseData['stepList']) ? $testcaseData['stepList'] : array();
    if(isset($stepList))
    {
        foreach($stepList as $step)
        {
            $tmpPId = $step['tmpPId'];
            $pObj   = isset($sceneIds[$tmpPId]) ? $sceneIds[$tmpPId] : array();

            $parent = 0;
            if(isset($sceneIds[$tmpPId])) $parent = $pObj['id'];

            $case   = $testcaseID;
            $type   = $step['type'];
            $desc   = $step['desc'];
            $expect = isset($step['expect']) ? $step['expect'] : '';

            $casestep          = new stdclass();
            $casestep->case    = $case;
            $casestep->version = $version;
            $casestep->type    = $type;
            $casestep->parent  = $parent;
            $casestep->desc    = $desc;
            $casestep->expect  = $expect;

            $this->dao->insert(TABLE_CASESTEP)->data($casestep)->autoCheck()->exec();
            $casestepID = $this->dao->lastInsertID();

            if(dao::isError()) return array('result' => 'fail', 'message' => $this->dao->getError(true));

            $sceneIds[$step['tmpId']] = array('id' => $casestepID, 'tmpPId' => $tmpPId);
        }
    }

    return array('result' => 'success', 'message' => 1, 'testcaseID' => $testcaseID);
}