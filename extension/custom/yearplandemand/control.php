<?php
class yearplandemand extends control
{
    public function __construct($module = '', $method = '')
    {
        parent::__construct($module, $method);
        $this->loadModel('yearplan');
        $this->loadModel('tree');
    }

    public function browse($yearplanID = 0,$browseType = '', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('yearplandemand');
        $this->loadModel('datatable');
        $this->loadModel('dept');
        $yearplanID = $this->yearplan->setMenu($yearplanID);

        $chteamdept         = $this->dept->getByName($this->lang->yearplandemand->chteamdept);
        $infoManger         = $this->dept->getByName($this->lang->yearplandemand->infoManger, $chteamdept->id);
        $infoTechnical      = $this->dept->getByName($this->lang->yearplandemand->infoTechnical, $chteamdept->id);
        $infoMangerChild    = $this->dept->getAllChildId($infoManger->id);
        $infoTechnicalChild = $this->dept->getAllChildId($infoTechnical->id);
        
        $infoAllChild = array_merge($infoMangerChild, $infoTechnicalChild);
        if($browseType == '') $browseType = !in_array($this->app->user->dept, $infoAllChild) ? 'bydept' : 'all';       

        $browseType = strtolower($browseType);

        $queryID = ($browseType == 'bysearch') ? (int)$param : 0;
        $actionURL = $this->createLink('yearplandemand', 'browse', "yearplanID=$yearplanID&browseType=bySearch&param=myQueryID");
        $this->yearplandemand->buildSearchForm($queryID, $actionURL, $yearplanID);

        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        $this->view->title           = $this->lang->yearplandemand->browse;
        $this->view->yearplandemands = $this->yearplandemand->getList($yearplanID, $browseType, $queryID, $orderBy, $pager);
        $this->view->orderBy         = $orderBy;
        $this->view->pager           = $pager;
        $this->view->browseType      = $browseType;
        $this->view->yearplanID      = $yearplanID;
        $this->view->users           = $this->loadModel('user')->getPairs('noletter');
        $this->display();
    }

    public function getFixedPosition()
    {
        $this->loadModel('flow');
        $extendFieldArr = array('problems', 'projectCost', 'class', 'businessLine');
        $extendFields   = $this->dao->select('*')->from('zt_workflowfield')->where('module')->eq('yearplandemand')->andWhere('field')->in($extendFieldArr)->fetchAll('field');
        $extendFields   = $this->loadModel('workflowaction')->processFields($extendFields, true, '');
        $requiredFields = ',';
        foreach($extendFields as  $extendField)
        {
            if(strpos($extendField->rules, '1') !== false) $requiredFields .= $extendField->field . ',';
        }

        $this->view->extendFields = $extendFields;
        return $requiredFields;
    }

    /**
     * Create a yearplandemand.
     *
     * @access public
     * @return void
     */
    public function create($yearplanID = 0)
    {
        
        $yearplanID = $this->yearplan->setMenu($yearplanID);

        $requiredFields = $this->getFixedPosition();

        if($_POST)
        {
            $isValid = $this->validateMilestone();
            if(!$isValid) return $this->send(array('result' => 'fail', 'message' => $this->lang->yearplandemand->milestoneRequired));

            $yearplandemandID = $this->yearplandemand->create($yearplanID, $requiredFields);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }

            $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'created', $this->post->comment);
            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('browse', "yearplanID=$yearplanID");

            return $this->send($response);
        }

        $this->view->title             = $this->lang->yearplandemand->create;
        $this->view->users             = $this->loadModel('user')->getPairs('noclosed');
        $this->view->businessArchitect = array('' => '') + $this->loadModel('user')->getUsersByUserGroupName($this->lang->yearplandemand->businessArchitect);
        $this->view->depts             = array('' => '') + $this->loadModel('dept')->getOptionMenuByGrade(0, 3);

        $this->display();
    }

    /**
     * Edit a yearplandemand.
     *
     * @access public
     * @return void
     */
    public function edit($yearplandemandID)
    {
        $yearplanDemand = $this->loadModel('yearplandemand')->getByID($yearplandemandID);
        $this->yearplan->setmenu($yearplanDemand->parent);

        $requiredFields = $this->getFixedPosition();

        if($_POST)
        {
            $isValid = $this->validateMilestone();
            if(!$isValid) return $this->send(array('result' => 'fail', 'message' => $this->lang->yearplandemand->milestoneRequired));

            if($yearplanDemand->status == 'tobeevaluated')
            {
                $validResult = $this->processAndValidData();
                if($validResult['result'] == 'fail') return $this->send($validResult);
            }

            $changes = $this->yearplandemand->update($yearplandemandID, $requiredFields);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }

            if($changes)
            {
                $actionID = $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'edited', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('view', "yearplandemandID=$yearplandemandID");

            return $this->send($response);
        }

        $this->view->title              = $this->lang->yearplandemand->edit;
        $this->view->yearplanDemand     = $yearplanDemand;
        $this->view->users              = $this->loadModel('user')->getPairs('noclosed');
        $this->view->businessArchitect  = array('' => '') + $this->loadModel('user')->getUsersByUserGroupName($this->lang->yearplandemand->businessArchitect);
        $this->view->depts              = array('' => '') + $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        $this->view->yearplanmilestones = $this->loadModel('yearplanmilestone')->getByParent($yearplandemandID);

        $this->display();
    }

    function processAndValidData()
    {
        $yearplanDemand = fixer::input('post')
            ->remove('files,labels,yearplanmilestone')
            ->join('dept', ',')
            ->join('businessManager', ',')
            ->stripTags($this->config->yearplandemand->editor->edit['id'], $this->config->allowedTags)
            ->get();

        $tempMilestones = fixer::input('post')->get('yearplanmilestone');
        $milestones     = array();
        foreach($tempMilestones['name'] as $key => $name)
        {
            if(empty($name)) continue;
            $tempchild = new stdclass();
            $tempchild->batch           = $tempMilestones['batch'][$key];
            $tempchild->name            = $tempMilestones['name'][$key];
            $tempchild->planConfirmDate = $tempMilestones['planConfirmDate'][$key];
            $tempchild->goliveDate      = $tempMilestones['goliveDate'][$key];

            $milestones[] = $tempchild;
        }

        return $this->validSubmit($yearplanDemand, $milestones);
    }

    /**
     * View a yearplandemand.
     *
     * @param  int    $yearplandemandID
     * @access public
     * @return void
     */
    public function view($yearplandemandID)
    {
        $yearplanDemand = $this->loadModel('yearplandemand')->getByID($yearplandemandID);
        $this->yearplan->setmenu($yearplanDemand->parent);

        $idList = explode(',', $yearplanDemand->mergeSources);
        $idList[] = $yearplanDemand->mergeTo;

        $this->view->title              = $this->lang->yearplandemand->view;
        $this->view->yearplanDemand     = $yearplanDemand;
        $this->view->actions            = $this->loadModel('action')->getList('yearplandemand', $yearplandemandID);
        $this->view->yearplanDemands    = $this->yearplandemand->getPairsByIdList($idList);
        $this->view->users              = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->businessArchitect  = array('' => '') + $this->loadModel('user')->getUsersByUserGroupName($this->lang->yearplandemand->businessArchitect);
        $this->view->depts              = array('' => '') + $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        $this->view->yearplanmilestones = $this->loadModel('yearplanmilestone')->getByParent($yearplandemandID);

        $this->display();
    }

    public function integration($yearplanID)
    {
        $idList = $this->post->yearplandemandIDList;
        unset($_POST['yearplandemandIDList']);
        $this->yearplan->setmenu($yearplanID);
        $requiredFields = $this->getFixedPosition();
        
        if($_POST)
        {
            $isValid = $this->validateMilestone();
            if(!$isValid) return $this->send(array('result' => 'fail', 'message' => $this->lang->yearplandemand->milestoneRequired));

            $yearplandemandID = $this->yearplandemand->create($yearplanID, $requiredFields);

            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }

            $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'created', $this->post->comment);
            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('browse', "yearplanID=$yearplanID");

            return $this->send($response);
        }

        $yearplandemands = $this->yearplandemand->getByIdList($idList);

        if(count($yearplandemands) < 2) return print(js::alert($this->lang->yearplandemand->integrationCountError) . js::reload('parent'));

        $errorStatusId  = '';
        $tempData = new stdClass();
        $tempData->name            = '';
        $tempData->desc            = '';
        $tempData->dept            = '';
        $tempData->approvalDate    = '';
        $tempData->planConfirmDate = '';
        $tempData->goliveDate      = '';
        $tempData->itPlanInto      = 0;
        $tempData->planBudget      = 0;
        $tempData->itQuotedPrice   = 0;
        $tempData->purchasedBudget = 0;
        
        $yearplanmilestones = array();

        foreach($yearplandemands as $yearplandemand)
        {
            if($yearplandemand->status != 'draft' && $yearplandemand->status != 'tobeevaluated') $errorStatusId .= $yearplandemand->id . '、';
            if(!isset($level)) $level = $yearplandemand->level;
            if(!isset($category)) $category = $yearplandemand->category;
            if(!isset($initDept)) $initDept = $yearplandemand->initDept;
            if(!isset($itPM)) $itPM = $yearplandemand->itPM;
            if(!isset($businessArchitect)) $businessArchitect = $yearplandemand->businessArchitect;
            if(!isset($isPurchased)) $isPurchased = $yearplandemand->isPurchased;
            if(!isset($class)) $class = $yearplandemand->class;
            if(!isset($businessLine)) $businessLine = $yearplandemand->businessLine;

            if($level != $yearplandemand->level) $level = '';
            if($category != $yearplandemand->category) $category = '';
            if($initDept != $yearplandemand->initDept) $initDept = '';
            if($itPM != $yearplandemand->itPM) $itPM = '';
            if($businessArchitect != $yearplandemand->businessArchitect) $businessArchitect = '';
            if($isPurchased != $yearplandemand->isPurchased) $isPurchased = '';
            if($class != $yearplandemand->class) $class = '';
            if($businessLine != $yearplandemand->businessLine) $businessLine = '';

            if($yearplandemand->approvalDate > $tempData->approvalDate) $tempData->approvalDate = $yearplandemand->approvalDate;
            if($yearplandemand->planConfirmDate > $tempData->planConfirmDate) $tempData->planConfirmDate = $yearplandemand->planConfirmDate;
            if($yearplandemand->goliveDate > $tempData->goliveDate) $tempData->goliveDate = $yearplandemand->goliveDate;
            if(!empty($yearplandemand->initDept)) $tempData->dept .= $yearplandemand->initDept . ',';
            if(!empty($yearplandemand->dept)) $tempData->dept .= $yearplandemand->dept;
            if(!empty($yearplandemand->businessManager)) $tempData->businessManager .= $yearplandemand->businessManager . ',';
            if(!empty($yearplandemand->itPlanInto)) $tempData->itPlanInto += $yearplandemand->itPlanInto;
            if(!empty($yearplandemand->planBudget)) $tempData->planBudget += $yearplandemand->planBudget;
            if(!empty($yearplandemand->itQuotedPrice)) $tempData->itQuotedPrice += $yearplandemand->itQuotedPrice;
            if(!empty($yearplandemand->purchasedBudget)) $tempData->purchasedBudget += $yearplandemand->purchasedBudget;
            if(!empty($yearplandemand->desc)) $tempData->desc .= $yearplandemand->desc . '<br/>';
            if(!empty($yearplandemand->remarks)) $tempData->remarks .= $yearplandemand->remarks . '<br/>';
            if(!empty($yearplandemand->purchasedContents)) $tempData->purchasedContents .= $yearplandemand->purchasedContents . "\n";
            if(!empty($yearplandemand->problems)) $tempData->problems .= $yearplandemand->problems . "\n";
            if(!empty($yearplandemand->projectCost)) $tempData->projectCost .= $yearplandemand->projectCost . "\n";
            if(!empty($yearplandemand->domain)) $tempData->domain .= $yearplandemand->domain;

            $tempMilestones = $this->loadModel('yearplanmilestone')->getByParent($yearplandemand->id);
            $yearplanmilestones = array_merge($yearplanmilestones, $tempMilestones);
        }

        if(!empty($errorStatusId)) return print(js::alert(sprintf($this->lang->yearplandemand->integrationStatusError, trim($errorStatusId, '、'))) . js::reload('parent'));

        $tempData->level             = $level;
        $tempData->category          = $category;
        $tempData->initDept          = $initDept;
        $tempData->itPM              = $itPM;
        $tempData->businessArchitect = $businessArchitect;
        $tempData->isPurchased       = $isPurchased;
        $tempData->class             = $class;
        $tempData->businessLine      = $businessLine;

        $this->view->title              = $this->lang->yearplandemand->integration;
        $this->view->yearplanDemand     = $tempData;
        $this->view->users              = $this->loadModel('user')->getPairs('noclosed');
        $this->view->businessArchitect  = array('' => '') + $this->loadModel('user')->getUsersByUserGroupName($this->lang->yearplandemand->businessArchitect);
        $this->view->depts              = array('' => '') + $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        $this->view->yearplanmilestones = $yearplanmilestones;
        $this->view->mergeSources       = implode(',', $idList);

        $this->display();
    }

    public function validateMilestone()
    {
        $tempYearplanmilestones = fixer::input('post')->get('yearplanmilestone');
        foreach($tempYearplanmilestones['batch'] as $key => $batch)
        {
            if(!empty($tempYearplanmilestones['batch'][$key]) || !empty($tempYearplanmilestones['name'][$key]) || !empty($tempYearplanmilestones['planConfirmDate'][$key]) || !empty($tempYearplanmilestones['goliveDate'][$key]))
            {
                if(empty($tempYearplanmilestones['batch'][$key]) || empty($tempYearplanmilestones['name'][$key]) || empty($tempYearplanmilestones['planConfirmDate'][$key]) || empty($tempYearplanmilestones['goliveDate'][$key])) return false;
            }
        }

        return true;
    }

    public function confirm($yearplandemandID, $yearplanID)
    {
        $yearplanID = $this->yearplan->setMenu($yearplanID);

        $yearplanDemand = $this->loadModel('yearplandemand')->getByID($yearplandemandID);
        $milestones     = $this->loadModel('yearplanmilestone')->getByParent($yearplanDemand->id);

        if($_POST)
        {
            $changes = $this->yearplandemand->confirm($yearplandemandID);
            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }

            if($changes)
            {
                $actionID = $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'confirm', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = inlink('browse', "yearplanID=$yearplanID");

            return $this->send($response);
        }

        $this->view->title          = $this->lang->yearplandemand->confirm;
        $this->view->yearplanDemand = $yearplanDemand;
        $this->view->yearplanID     = $yearplanID;

        return $this->display();
    }

    public function delete($yearplandemandID, $yearplanID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            echo js::confirm($this->lang->yearplandemand->confirmDelete, $this->createLink('yearplandemand', 'delete', "yearplandemandID=$yearplandemandID&yearplanId=$yearplanID&confirm=yes"), '');
            exit;
        }
        else
        {
            $this->yearplandemand->delete('zt_yearplandemand', $yearplandemandID);

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            die(js::reload('parent'));
        }
    }

    public function cancel($yearplandemandID, $yearplanID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            return print(js::confirm($this->lang->yearplandemand->confirmCancel, $this->createLink('yearplandemand', 'cancel', "yearplandemandID=$yearplandemandID&yearplanId=$yearplanID&confirm=yes")));
        }
        else
        {
            $this->dao->update(TABLE_YEARPLANDEMAND)->set('status')->eq('cancelled')->where('id')->eq($yearplandemandID)->exec();

            $actionID = $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'cancel', $this->post->comment);

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            die(js::reload('parent'));
        }
    }

    public function restore($yearplandemandID, $yearplanID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            echo js::confirm($this->lang->yearplandemand->confirmRestore, $this->createLink('yearplandemand', 'restore', "yearplandemandID=$yearplandemandID&yearplanId=$yearplanID&confirm=yes"), '');
            exit;
        }
        else
        {
            $yearplanDemand = $this->yearplandemand->getByID($yearplandemandID);

            $this->dao->update(TABLE_YEARPLANDEMAND)->set('status')->eq($yearplanDemand->oldStatus)->where('id')->eq($yearplandemandID)->exec();

            $actionID = $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'restore', $this->post->comment);

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            die(js::reload('parent'));
        }
    }

    public function sendback($yearplandemandID, $yearplanID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            echo js::confirm($this->lang->yearplandemand->confirmSendback, $this->createLink('yearplandemand', 'sendback', "yearplandemandID=$yearplandemandID&yearplanId=$yearplanID&confirm=yes"), '');
            exit;
        }
        else
        {
            $this->dao->update(TABLE_YEARPLANDEMAND)->set('status')->eq('tobeevaluated')->set('oldStatus')->eq('tobeevaluated')->where('id')->eq($yearplandemandID)->exec();

            $actionID = $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'sendback', $this->post->comment);

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            die(js::reload('parent'));
        }
    }

    public function export($yearplanID = 0, $orderBy = 'id_desc', $browseType = 'all')
    {
        if($_POST)
        {
            $this->loadModel('transfer')->export('yearplandemand');
            $this->fetch('file', 'export2' . $_POST['fileType'], $_POST);
        }
        /* 将导出页面所需的相关变量传递到页面。*/
        $this->view->fileName        = $this->lang->yearplandemand->common;
        $this->view->allExportFields = $this->config->yearplandemand->list->exportFields;
        $this->view->customExport    = true;
        $this->display();
    }

    public function exportTemplate($yearplanID = 0)
    {
        if($_POST)
        {
            $this->fetch('transfer', 'exportTemplate', 'model=yearplandemand&params=yearplanID='. $yearplanID);
        }
        
        $this->loadModel('transfer');
        $this->display();
    }

    public function validSubmit($yearplanDemand, $milestones)
    {
        $requiredFields  = array('name', 'level', 'initDept', 'approvalDate', 'planConfirmDate', 'goliveDate', 'itPlanInto', 'desc', 'itPM', 'businessArchitect', 'businessManager', 'problems', 'projectCost', 'class', 'businessLine', 'planBudget', 'itQuotedPrice');

        $extendFields   = $this->dao->select('*')->from('zt_workflowfield')->where('module')->eq('yearplandemand')->fetchAll('field');
        foreach($extendFields as $extendField)
        {
            $this->lang->yearplandemand->{$extendField->field} = $extendField->name;
        }

        if($yearplanDemand->isPurchased == 1)
        {
            $requiredFields[] = 'purchasedContents';
            $requiredFields[] = 'purchasedBudget';
        }

        foreach($requiredFields as $requiredField)
        {
            $isDescEmpty = $requiredField == 'desc' && $yearplanDemand->$requiredField == '<br />';
            if($yearplanDemand->$requiredField === '' || (strpos($requiredField, 'Date') !== false && $yearplanDemand->$requiredField == '0000-00-00 00:00:00') || $isDescEmpty) return array('result' => 'fail', 'message' => array($requiredField => sprintf($this->lang->yearplandemand->requiredError, $this->lang->yearplandemand->$requiredField, $this->lang->yearplandemand->submit)));
        }

        if($yearplanDemand->goliveDate < $yearplanDemand->planConfirmDate) return array('result' => 'fail', 'message' => sprintf($this->lang->yearplandemand->dateError, $this->lang->yearplandemand->planConfirmDate, $this->lang->yearplandemand->goliveDate, $this->lang->yearplandemand->submit));

        if($yearplanDemand->planConfirmDate < $yearplanDemand->approvalDate) return array('result' => 'fail', 'message' => sprintf($this->lang->yearplandemand->dateError, $this->lang->yearplandemand->approvalDate, $this->lang->yearplandemand->planConfirmDate, $this->lang->yearplandemand->submit));

        foreach($milestones as $milestone)
        {
            if($milestone->goliveDate < $milestone->planConfirmDate) return array('result' => 'fail', 'message' => sprintf($this->lang->yearplandemand->milestoneError, $this->lang->yearplandemand->planConfirmDate, $this->lang->yearplandemand->goliveDate, $this->lang->yearplandemand->submit));

            if($milestone->goliveDate < $yearplanDemand->approvalDate || $milestone->goliveDate > $yearplanDemand->goliveDate) return array('result' => 'fail', 'message' => sprintf($this->lang->yearplandemand->milestoneGoLiveDateError, $this->lang->yearplandemand->submit));

            if($milestone->planConfirmDate < $yearplanDemand->approvalDate || $milestone->planConfirmDate > $yearplanDemand->planConfirmDate) return array('result' => 'fail', 'message' => sprintf($this->lang->yearplandemand->milestoneConfirmDateError, $this->lang->yearplandemand->submit));
        }

        return array('result' => 'success');
    }

    public function submit($yearplandemandID, $yearplanID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            return print(js::confirm($this->lang->yearplandemand->confirmSubmit, $this->createLink('yearplandemand', 'submit', "yearplandemandID=$yearplandemandID&yearplanId=$yearplanID&confirm=yes")));
        }
        else
        {
            $yearplanDemand = $this->loadModel('yearplandemand')->getByID($yearplandemandID);
            $milestones     = $this->loadModel('yearplanmilestone')->getByParent($yearplanDemand->id);

            $validResult = $this->validSubmit($yearplanDemand, $milestones);
            if($validResult['result'] == 'fail') return print(js::error($validResult['message']));

            $this->dao->update(TABLE_YEARPLANDEMAND)->set('status')->eq('tobeevaluated')->set('oldStatus')->eq('tobeevaluated')->where('id')->eq($yearplandemandID)->exec();

            $actionID = $this->loadModel('action')->create('yearplandemand', $yearplandemandID, 'submit', $this->post->comment);

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            die(js::reload('parent'));
        }
    }

    public function import($yearplanID = 0)
    {
        if($_FILES)
        {
            /* 如果文件存在，则判断文件类型是否符合要求。*/
            $file = $this->loadModel('file')->getUpload('file');
            $file = $file[0];
            if($file['extension'] != 'xlsx') die(js::alert($this->lang->file->onlySupportXLSX));

            /* 将导入的文件存放于临时目录。*/
            $fileName = $this->file->savePath . $this->file->getSaveName($file['pathname']);
            move_uploaded_file($file['tmpname'], $fileName);

            /* 加载phpexcel库，解析excel文件内容，解析完调用showImport方法进行数据确认。*/
            $phpExcel  = $this->app->loadClass('phpexcel');
            $phpReader = new PHPExcel_Reader_Excel2007();
            if(!$phpReader->canRead($fileName))
            {
                $phpReader = new PHPExcel_Reader_Excel5();
                if(!$phpReader->canRead($fileName))die(js::alert($this->lang->excel->canNotRead));
            }
            $this->session->set('fileImport', $fileName);
            die(js::locate(inlink('showImport', "yearplanID=$yearplanID"), 'parent.parent'));
        }

        $this->display();
    }

    public function showImport($yearplanID = 0, $pagerID = 1, $maxImport = 0, $insert = '')
    {
        $yearplanID = $this->yearplan->setMenu($yearplanID);

        if($this->config->edition != 'open')
        {
            $appendFields = $this->dao->select('t2.*')->from(TABLE_WORKFLOWLAYOUT)->alias('t1')
                ->leftJoin(TABLE_WORKFLOWFIELD)->alias('t2')->on('t1.field=t2.field && t1.module=t2.module')
                ->where('t1.module')->eq('yearplandemand')
                ->andWhere('t1.action')->eq('showImport')
                ->andWhere('t2.buildin')->eq(0)
                ->orderBy('t1.order')
                ->fetchAll();
            foreach($appendFields as $appendField)
            {
                $this->lang->yearplandemand->{$appendField->field} = $appendField->name;
                $this->config->yearplandemand->list->exportFields .= ',' . $appendField->field;
            }
        }

        $file    = $this->session->fileImport;
        $tmpPath = $this->loadModel('file')->getPathOfImportedFile();
        $tmpFile = $tmpPath . DS . md5(basename($file));

        if($_POST)
        {
            $this->yearplandemand->createFromImport($yearplanID);
            if($this->post->isEndPage)
            {
                unlink($tmpFile);
                die(js::locate($this->createLink('yearplandemand','browse', "yearplanID=$yearplanID"), 'parent'));
            }
            else
            {
                die(js::locate(inlink('showImport', "yearplanID=$yearplanID&pagerID=" . ($this->post->pagerID + 1) . "&maxImport=$maxImport&insert=" . zget($_POST, 'insert', '')), 'parent'));
            }
        }

        if(!empty($maxImport) and file_exists($tmpFile))
        {
            $yearplandemandData = unserialize(file_get_contents($tmpFile));
        }
        else
        {
            $pagerID            = 1;
            $yearplandemandLang = $this->lang->yearplandemand;
            $fields             = $this->loadModel('transfer')->getImportFields('yearplandemand');
            $fieldList          = $this->loadModel('transfer')->initFieldList('yearplandemand', array_keys($fields), false);
            $list               = $this->loadModel('transfer')->setListValue('yearplandemand', $fieldList);
            $rows               = $this->file->getRowsFromExcel($file);
            $yearplandemandData = array();
            foreach($rows as $currentRow => $row)
            {
                if($currentRow == count($rows)) continue;
                $yearplandemand = new stdClass();
                foreach($row as $currentColumn => $cellValue)
                {
                    if($currentRow == 1)
                    {
                        $field = array_search($cellValue, $fields);
                        $columnKey[$currentColumn] = $field ? $field : '';
                        continue;
                    }

                    if(empty($columnKey[$currentColumn]))
                    {
                        $currentColumn++;
                        continue;
                    }
                    $field = $columnKey[$currentColumn];
                    $currentColumn++;

                    if(empty($cellValue))
                    {
                        $yearplandemand->$field = '';
                        continue;
                    }
                    if(in_array($field.'List', array_keys($list)))
                    {
                        if(!empty($fieldList[$field]['from']) and in_array($fieldList[$field]['control'], array('select', 'multiple')))
                        {
                            $control = $fieldList[$field]['control'];
                            if($control == 'multiple')
                            {
                                $cellValue = explode("\n", $cellValue);
                                foreach($cellValue as &$value) $value = array_search($value, $fieldList[$field]['values'], true);
                                $yearplandemand->$field = join(',', $cellValue);
                            }
                            else
                            {
                                $yearplandemand->$field = array_search($cellValue, $fieldList[$field]['values']);
                            }
                        }
                        elseif(strrpos($cellValue, '(#') === false)
                        {
                            $yearplandemand->$field = $cellValue;
                            if(!isset($yearplandemandLang->{$field . 'List'}) or !is_array($yearplandemandLang->{$field . 'List'})) continue;

                            $listKey = array_keys($yearplandemandLang->{$field . 'List'});
                            unset($listKey[0]);
                            unset($listKey['']);
                            $fieldKey = array_search($cellValue, $yearplandemandLang->{$field . 'List'});
                            if($fieldKey) $yearplandemand->$field = $fieldKey;
                        }
                        else
                        {
                            $id = trim(substr($cellValue, strrpos($cellValue,'(#') + 2), ')');
                            $yearplandemand->$field = $id;
                        }
                    }
                    elseif($field == 'background' or $field == 'desc')
                    {
                        /* 针对富文本类型字段内容进行处理。*/
                        $yearplandemand->$field = str_replace("\n", "\n", $cellValue);
                    }
                    elseif(in_array($field, array('itPlanInto', 'itQuotedPrice', 'planBudget', 'purchasedBudget')))
                    {
                        $yearplandemand->$field = preg_replace('/\D/', '', $cellValue);
                    }
                    else
                    {
                        $yearplandemand->$field = $cellValue;
                    }
                }

                if(empty($yearplandemand->name)) continue;
                $yearplandemandData[$currentRow] = $yearplandemand;
                unset($yearplandemand);
            }

            file_put_contents($tmpFile, serialize($yearplandemandData));
        }

        if(empty($yearplandemandData))
        {
            unlink($this->session->fileImport);
            unset($_SESSION['fileImport']);
            echo js::alert($this->lang->excel->noData);
            die(js::locate($this->createLink('yearplandemand','browse', 'yearplanID=' . $yearplanID)));
        }

        $allCount = count($yearplandemandData);
        $allPager = 1;
        if($allCount > $this->config->file->maxImport)
        {
            if(empty($maxImport))
            {
                $this->view->allCount  = $allCount;
                $this->view->maxImport = $this->config->file->maxImport;
                die($this->display());
            }

            $allPager           = ceil($allCount / $maxImport);
            $yearplandemandData = array_slice($yearplandemandData, ($pagerID - 1) * $maxImport, $maxImport, true);
        }

        if(empty($yearplandemandData)) die(js::locate($this->createLink('yearplandemand','browse', 'yearplanID=' . $yearplanID)));

        $countInputVars  = count($yearplandemandData) * 11;
        $showSuhosinInfo = common::judgeSuhosinSetting($countInputVars);
        if($showSuhosinInfo) $this->view->suhosinInfo = extension_loaded('suhosin') ? sprintf($this->lang->suhosinInfo, $countInputVars) : sprintf($this->lang->maxVarsInfo, $countInputVars);

        $this->view->title              = $this->lang->yearplandemand->common . $this->lang->colon . $this->lang->yearplandemand->showImport;
        $this->view->users              = $this->loadModel('user')->getPairs('noclosed');
        $this->view->businessArchitect  = array('' => '') + $this->loadModel('user')->getUsersByUserGroupName($this->lang->yearplandemand->businessArchitect);
        $this->view->depts              = array('' => '') + $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        $this->view->yearplandemandData = $yearplandemandData;
        $this->view->yearplanID         = $yearplanID;
        $this->view->allCount           = $allCount;
        $this->view->allPager           = $allPager;
        $this->view->pagerID            = $pagerID;
        $this->view->isEndPage          = $pagerID >= $allPager;

        $this->display();
    }
}