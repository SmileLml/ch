<?php
class yearplandemandModel extends model
{
    public function getByID($yearplandemandID)
    {
        $yearplandemand = $this->dao->findByID($yearplandemandID)->from(TABLE_YEARPLANDEMAND)->fetch();
        if(!$yearplandemand) return array();

        $yearplandemand->files = $this->loadModel('file')->getByObject('yearplandemand', $yearplandemandID);

        $yearplandemand = $this->loadModel('file')->replaceImgURL($yearplandemand, $this->config->yearplandemand->editor->view['id']);

        return $yearplandemand;
    }

    public function getByIdList($idList)
    {
        $yearplandemands = $this->dao->select('*')->from(TABLE_YEARPLANDEMAND)->where('id')->in($idList)->fetchAll();

        if(!$yearplandemands) return array();

        foreach($yearplandemands as &$yearplandemand)
        {
            $yearplandemand->files = $this->loadModel('file')->getByObject('yearplandemand', $yearplandemand->id);

            $yearplandemand = $this->loadModel('file')->replaceImgURL($yearplandemand, $this->config->yearplandemand->editor->view['id']);
        }

        return $yearplandemands;
    }

    public function getPairsByIdList($idList)
    {
        return $this->dao->select('id,name')->from(TABLE_YEARPLANDEMAND)
            ->where('id')->in($idList)
            ->fetchPairs('id', 'name');
    }

    public function getMergeObject()
    {
        $yearplandemands = $this->loadModel('transfer')->getQueryDatas('yearplandemand');

        $mergeIdList = '';
        foreach($yearplandemands as $yearplandemand)
        {
            $mergeIdList .= $yearplandemand->mergeSources . ',';
            $mergeIdList .= $yearplandemand->mergeTo . ',';
        }
        $mergeIdList = explode(',', $mergeIdList);
        $mergeIdList = array_unique($mergeIdList);

        return $this->getPairsByIdList($mergeIdList);
    }

    /**
     * Get yearplandemand list.
     * @param  string  $browseType
     * @param  string  $orderBy
     * @param  object  $pager
     * @access public
     * @return void
     */
    public function getList($yearplanID = 0, $browseType, $queryID, $orderBy, $pager = null, $extra = '', $type = 'browse')
    {
        /* 获取搜索条件的查询SQL。*/
        $yearplandemandQuery = '';
        if($browseType == 'bysearch')
        {
            $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';
            if($query)
            {
                $this->session->set('yearplandemandQuery', $query->sql);
                $this->session->set('yearplandemandForm', $query->form);
            }
            if($this->session->yearplandemandQuery == false) $this->session->set('yearplandemandQuery', ' 1 = 1');
            $yearplandemandQuery = $this->session->yearplandemandQuery;
        }

        $noStatus = array('all', 'bysearch', 'bydept');

        if($browseType == 'bydept')
        {
            $currentDeptID = $this->app->user->dept;
            $currentDept = $this->loadModel('dept')->getByID($currentDeptID);
            if($currentDept->grade > 3)
            {
                $path          = $currentDept->path;
                $deptIdList    = explode(',', trim($path, ','));
                $currentDeptID = $deptIdList[2];
            }
        }

        /* 创建SQL查询数据。*/
        $yearplandemands = $this->dao->select('*')->from(TABLE_YEARPLANDEMAND)
            ->where('deleted')->eq('0')
            ->andWhere('parent')->eq($yearplanID)
            ->beginIF(!in_array($browseType, $noStatus))->andWhere('status')->eq($browseType)->fi()
            ->beginIF($browseType == 'bydept')->andWhere('initDept')->eq($currentDeptID)->fi()
            ->beginIF($browseType == 'bysearch')->andWhere($yearplandemandQuery)->fi()
            ->beginIF(strpos($extra, 'nodeleted') !== false)->andWhere('status')->ne('deleted')->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');

        /* 保存查询条件并查询子需求条目。*/
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'yearplandemand', $browseType != 'bysearch');

        return $yearplandemands;
    }

    /**
     * Create a yearplandemand.
     *
     * @access public
     * @return void
     */
    public function create($yearplanID = 0, $requiredFields = '')
    {
        $today = helper::today();

        $yearplandemand = fixer::input('post')
            ->add('parent', $yearplanID)
            ->add('createdBy', $this->app->user->account)
            ->add('createdDate', $today)
            ->add('status', 'draft')
            ->add('oldStatus', 'draft')
            ->remove('files,labels,yearplanmilestone')
            ->join('dept', ',')
            ->join('businessManager', ',')
            ->stripTags($this->config->yearplandemand->editor->create['id'], $this->config->allowedTags)
            ->get();

        if($yearplandemand->dept) $yearplandemand->dept = ",{$yearplandemand->dept},";

        $yearplandemand = $this->loadModel('file')->processImgURL($yearplandemand, $this->config->yearplandemand->editor->create['id'], $this->post->uid);

        $this->dao->insert(TABLE_YEARPLANDEMAND)->data($yearplandemand)
            ->autoCheck()
            ->batchCheck($this->config->yearplandemand->create->requiredFields . $requiredFields, 'notempty')
            ->checkFlow()
            ->exec();

        if(!dao::isError())
        {
            $yearplandemandID = $this->dao->lastInsertID();

            $this->loadModel('file')->updateObjectID($this->post->uid, $yearplandemandID, 'yearplandemand');
            $this->loadModel('file')->saveUpload('yearplandemand', $yearplandemandID);

            $tempYearplanmilestones = fixer::input('post')->get('yearplanmilestone');
            foreach($tempYearplanmilestones['batch'] as $key => $batch)
            {
                $tempchild = array();
                if(!empty($tempYearplanmilestones['batch'][$key]) || !empty($tempYearplanmilestones['name'][$key]) || !empty($tempYearplanmilestones['planConfirmDate'][$key]) || !empty($tempYearplanmilestones['goliveDate'][$key]))
                {
                    $tempchild['parent']          = $yearplandemandID;
                    $tempchild['batch']           = $tempYearplanmilestones['batch'][$key];
                    $tempchild['name']            = $tempYearplanmilestones['name'][$key];
                    $tempchild['planConfirmDate'] = $tempYearplanmilestones['planConfirmDate'][$key];
                    $tempchild['goliveDate']      = $tempYearplanmilestones['goliveDate'][$key];

                    $this->dao->insert('zt_yearplanmilestone')->data($tempchild)->exec();
                }
            }

            if(isset($yearplandemand->mergeSources))
            {
                $mergeSources = explode(',', $yearplandemand->mergeSources);
                $mergeData = array();
                $mergeData['status']    = 'merged';
                $mergeData['oldStatus'] = 'merged';
                $mergeData['mergeTo']   = $yearplandemandID;
                $this->dao->update(TABLE_YEARPLANDEMAND)->data($mergeData)->where('id')->in($mergeSources)->exec();
            }

            return $yearplandemandID;
        }

        return false;
    }

    public function update($yearplandemandID, $requiredFields)
    {
        $oldYearplandemand = $this->getByID($yearplandemandID);

        $yearplandemand = fixer::input('post')
            ->remove('files,labels,yearplanmilestone')
            ->join('dept', ',')
            ->join('businessManager', ',')
            ->stripTags($this->config->yearplandemand->editor->edit['id'], $this->config->allowedTags)
            ->get();

        $yearplandemand->dept = (isset($yearplandemand->dept) && $yearplandemand->dept) ? ",{$yearplandemand->dept}," : '';

        $yearplandemand = $this->loadModel('file')->processImgURL($yearplandemand, $this->config->yearplandemand->editor->edit['id'], $this->post->uid);

        $this->dao->update(TABLE_YEARPLANDEMAND)->data($yearplandemand)
            ->autoCheck()
            ->batchCheck($this->config->yearplandemand->create->requiredFields . $requiredFields, 'notempty')
            ->checkFlow()
            ->where('id')->eq($yearplandemandID)
            ->exec();

        $this->loadModel('file')->updateObjectID($this->post->uid, $yearplandemandID, 'yearplandemand');
        $this->loadModel('file')->saveUpload('yearplandemand', $yearplandemandID);

        $tempYearplanmilestones = fixer::input('post')->get('yearplanmilestone');
        foreach($tempYearplanmilestones['id'] as $key => $id)
        {
            $tempchild['parent']          = $yearplandemandID;
            $tempchild['batch']           = $tempYearplanmilestones['batch'][$key];
            $tempchild['name']            = $tempYearplanmilestones['name'][$key];
            $tempchild['planConfirmDate'] = $tempYearplanmilestones['planConfirmDate'][$key];
            $tempchild['goliveDate']      = $tempYearplanmilestones['goliveDate'][$key];

            if(!empty($tempYearplanmilestones['batch'][$key]) || !empty($tempYearplanmilestones['name'][$key]) || !empty($tempYearplanmilestones['planConfirmDate'][$key]) || !empty($tempYearplanmilestones['goliveDate'][$key]))
            {
                if(!empty($id))
                {
                    $this->dao->update('zt_yearplanmilestone')->data($tempchild)->where('id')->eq($id)->limit(1)->exec();
                    continue;
                }
                $this->dao->insert('zt_yearplanmilestone')->data($tempchild)->exec();
                continue;
            }
            if(!empty($id))
            {
                $this->dao->delete()->from('zt_yearplanmilestone')->where('id')->eq($id)->exec();
            }
        }

        return common::createChanges($oldYearplandemand, $yearplandemand);
    }

    public function confirm($yearplandemandID)
    {
        $oldYearplandemand = $this->getByID($yearplandemandID);

        $yearplandemand = fixer::input('post')
            ->remove('files,labels')
            ->setDefault('confirmResult', 'pass')
            ->setDefault('status', 'confirmed')
            ->setDefault('oldStatus', 'confirmed')
            ->stripTags($this->config->yearplandemand->editor->confirm['id'], $this->config->allowedTags)
            ->get();

        $yearplandemand = $this->loadModel('file')->processImgURL($yearplandemand, $this->config->yearplandemand->editor->edit['id'], $this->post->uid);

        $this->dao->update(TABLE_YEARPLANDEMAND)->data($yearplandemand)
            ->autoCheck()
            ->batchCheck($this->config->yearplandemand->confirm->requiredFields, 'notempty')
            ->where('id')->eq($yearplandemandID)
            ->exec();

        $this->loadModel('file')->updateObjectID($this->post->uid, $yearplandemandID, 'yearplandemand');
        $this->loadModel('file')->saveUpload('yearplandemand', $yearplandemandID);

        return common::createChanges($oldYearplandemand, $yearplandemand);
    }

    /**
     * Build search form.
     *
     * @param  int    $queryID
     * @param  string $actionURL
     * @param  int    $poolID
     * @access public
     * @return void
     */
    public function buildSearchForm($queryID, $actionURL, $poolID = '')
    {
        $this->config->yearplandemand->search['actionURL'] = $actionURL;
        $this->config->yearplandemand->search['queryID']   = $queryID;

        $depts             = array('' => '') + $this->loadModel('dept')->getOptionMenuByGrade(0, 3);
        $businessArchitect = array('' => '') + $this->loadModel('user')->getUsersByUserGroupName($this->lang->yearplandemand->businessArchitect);
        $newDepts          = array();
        foreach($depts as $key => $value)
        {
            $newDepts[",{$key},"] = $value;
        }

        $this->config->yearplandemand->search['params']['dept']['values']              = $newDepts;
        $this->config->yearplandemand->search['params']['initDept']['values']          = $depts;
        $this->config->yearplandemand->search['params']['businessArchitect']['values'] = $businessArchitect;

        $this->loadModel('search')->setSearchParams($this->config->yearplandemand->search);
    }

    function printCell($col, $yearplandemand, $users)
    {
        $canView = common::hasPriv('yearplandemand', 'view');
        $depts   = $this->loadModel('dept')->getOptionMenuByGrade(0, 3);

        $yearplandemandLink = helper::createLink('yearplandemand', 'view', "yearplandemandID=$yearplandemand->id");
        $account = $this->app->user->account;
        $id      = $col->id;
        $canBatchAction = common::hasPriv('yearplandemand', 'integration');

        if($col->show)
        {
            $class = "c-$id";
            $title = '';
            switch($id)
            {
                case 'id':
                    $class .= ' cell-id';
                    break;
                case 'name':
                    $class .= ' text-left';
                    $title  = "title='{$yearplandemand->name}'";
                    break;
                case 'level':
                    $class .= ' text-left';
                    $title  = "title='" . zget($this->lang->yearplandemand->levelList, $yearplandemand->level) . "'";
                    break;
                case 'category':
                    $class .= ' text-left';
                    $title  = "title='" . zget($this->lang->yearplandemand->categoryList, $yearplandemand->category) . "'";
                    break;
                case 'initDept':
                    $class .= ' text-left';
                    $title  = "title='" . zget($depts, $yearplandemand->initDept) . "'";
                    break;
                case 'dept':
                    $dept     = explode(',', trim($yearplandemand->dept, ','));
                    $deptText = '';
                    foreach($dept as $item) $deptText .= zget($depts, $item) . " &nbsp;";

                    $title = "title='{$deptText}'";
                    break;
                case 'itPM':
                    $class .= ' c-user';
                    $title  = "title='" . zget($users, $yearplandemand->itPM) . "'";
                    break;
                case 'businessArchitect':
                    $class .= ' c-user';
                    $title  = "title='" . zget($users, $yearplandemand->businessArchitect) . "'";
                    break;
                case 'businessManager':
                    $class   .= ' c-user';
                    $user     = explode(',', trim($yearplandemand->businessManager, ','));
                    $userText = '';
                    foreach($user as $item) $userText .= zget($users, $item) . " &nbsp;";

                    $title = "title='{$userText}'";
                    break;
                case 'createdBy':
                    $class .= ' c-user';
                    $title  = "title='" . zget($users, $yearplandemand->createdBy) . "'";
                    break;
            }

            echo "<td class='" . $class . "' $title>";
            $this->loadModel('flow')->printFlowCell('yearplandemand', $yearplandemand, $id);

            switch($id)
            {
            case 'id':
                if($canBatchAction)
                {
                    echo html::checkbox('yearplandemandIDList', array($yearplandemand->id => '')) . html::a($yearplandemandLink, sprintf('%03d', $yearplandemand->id));
                }
                else
                {
                    printf('%03d', $yearplandemand->id);
                }
                break;
            case 'name':
                echo $canView ? html::a($yearplandemandLink, $yearplandemand->name) : "{$yearplandemand->name}";
                break;
            case 'level':
                echo zget($this->lang->yearplandemand->levelList, $yearplandemand->level);
                break;
            case 'category':
                echo zget($this->lang->yearplandemand->categoryList, $yearplandemand->category);
                break;
            case 'initDept':
                echo zget($depts, $yearplandemand->initDept, '');
                break;
            case 'dept':
                echo $deptText;
                break;
            case 'approvalDate':
                echo helper::isZeroDate($yearplandemand->approvalDate) ? '' : substr($yearplandemand->approvalDate, 0, 10);
                break;
            case 'planConfirmDate':
                echo helper::isZeroDate($yearplandemand->planConfirmDate) ? '' : substr($yearplandemand->planConfirmDate, 0, 10);
                break;
            case 'goliveDate':
                echo helper::isZeroDate($yearplandemand->goliveDate) ? '' : substr($yearplandemand->goliveDate, 0, 10);
                break;
            case 'itPlanInto':
                echo $yearplandemand->itPlanInto;
                break;
            case 'itPM':
                echo zget($users, $yearplandemand->itPM);
                break;
            case 'businessArchitect':
                echo zget($users, $yearplandemand->businessArchitect);
                break;
            case 'businessManager':
                echo $userText;
                break;
            case 'isPurchased':
                echo zget($this->lang->yearplandemand->isPurchasedList,$yearplandemand->isPurchased);
                break;
            case 'status':
                echo zget($this->lang->yearplandemand->statusList,$yearplandemand->status);
                break;
            case 'createdBy':
                echo zget($users, $yearplandemand->createdBy);
                break;
            case 'createdDate':
                echo helper::isZeroDate($yearplandemand->createdDate) ? '' : substr($yearplandemand->createdDate, 0, 10);
                break;
            case 'actions':
                common::printIcon('yearplandemand', 'edit', "yearplandemandID=$yearplandemand->id", $yearplandemand, 'list');
                common::printIcon('yearplandemand', 'submit', "yearplandemandID=$yearplandemand->id&yearplanId=$yearplandemand->parent", $yearplandemand, 'list', 'confirm', 'hiddenwin');
                common::printIcon('yearplandemand', 'confirm', "yearplandemandID=$yearplandemand->id&yearplanId=$yearplandemand->parent", $yearplandemand, 'list', 'ok');
                common::printIcon('yearplandemand', 'cancel', "yearplandemandID=$yearplandemand->id&yearplanId=$yearplandemand->parent", $yearplandemand, 'list', 'cancel ', 'hiddenwin');
                common::printIcon('yearplandemand', 'restore', "yearplandemandID=$yearplandemand->id&yearplanId=$yearplandemand->parent", $yearplandemand, 'list', 'magic ', 'hiddenwin');
                common::printIcon('yearplandemand', 'delete', "yearplandemandID=$yearplandemand->id&yearplanId=$yearplandemand->parent", $yearplandemand, 'list', 'trash', 'hiddenwin');
                common::printIcon('yearplandemand', 'sendback', "yearplandemandID=$yearplandemand->id&yearplanId=$yearplandemand->parent", $yearplandemand, 'list', 'back', 'hiddenwin');
                break;
            }
            echo '</td>';
        }
    }

    public function createFromImport($yearplanID = 0)
    {
        $this->loadModel('action');
        $this->loadModel('file');
        $now   = helper::today();
        $data  = (object)$_POST;

        $yearplandemands = array();
        $line    = 1;

        if($this->config->edition != 'open')
        {
            $extendFields = $this->getFlowExtendFields();
            $notEmptyRule = $this->loadModel('workflowrule')->getByTypeAndRule('system', 'notempty');

            foreach($extendFields as $extendField)
            {
                if(strpos(",$extendField->rules,", ",$notEmptyRule->id,") !== false)
                {
                    $this->config->yearplandemand->create->requiredFields .= ',' . $extendField->field;
                    $this->lang->yearplandemand->{$extendField->field}     = $extendField->name;
                }
            }
        }

        foreach($data->name as $key => $name)
        {
            $yearplandemandData = new stdClass();

            $yearplandemandData->name              = $name;
            $yearplandemandData->level             = $data->level[$key];
            $yearplandemandData->category          = $data->category[$key];
            $yearplandemandData->initDept          = $data->initDept[$key];
            $yearplandemandData->approvalDate      = $data->approvalDate[$key];
            $yearplandemandData->planConfirmDate   = $data->planConfirmDate[$key];
            $yearplandemandData->goliveDate        = $data->goliveDate[$key];
            $yearplandemandData->itPlanInto        = $data->itPlanInto[$key];
            $yearplandemandData->itPM              = $data->itPM[$key];
            $yearplandemandData->businessArchitect = $data->businessArchitect[$key];
            $yearplandemandData->isPurchased       = $data->isPurchased[$key];
            $yearplandemandData->purchasedContents = $data->purchasedContents[$key];
            $yearplandemandData->desc              = $data->desc[$key];

            if($data->dept[$key]) $yearplandemandData->dept = ',' . implode(',', $data->dept[$key]) . ',';
            if($data->businessManager[$key]) $yearplandemandData->businessManager = implode(',', $data->businessManager[$key]);

            foreach($extendFields as $extendField)
            {
                $dataArray = $_POST[$extendField->field];

                $yearplandemandData->{$extendField->field} = $dataArray[$key];
                if(is_array($yearplandemandData->{$extendField->field})) $yearplandemandData->{$extendField->field} = join(',', $yearplandemandData->{$extendField->field});

                $yearplandemandData->{$extendField->field} = htmlSpecialString($yearplandemandData->{$extendField->field});
            }

            if(isset($this->config->yearplandemand->create->requiredFields))
            {
                $requiredFields = explode(',', $this->config->yearplandemand->create->requiredFields);
                foreach($requiredFields as $requiredField)
                {
                    $requiredField = trim($requiredField);
                    if(empty($yearplandemandData->$requiredField)) dao::$errors[] = sprintf($this->lang->yearplandemand->noRequire, $line, $this->lang->yearplandemand->$requiredField);
                }
            }

            $yearplandemands[$key]['yearplandemandData'] = $yearplandemandData;
            $line++;
        }

        if(dao::isError()) die(js::error(dao::getError()));

        foreach($yearplandemands as $key => $newYearplandemand)
        {
            $yearplandemandData = $newYearplandemand['yearplandemandData'];
            $yearplandemandData->createdBy   = $this->app->user->account;
            $yearplandemandData->createdDate = helper::today();
            $yearplandemandData->status      = 'draft';
            $yearplandemandData->oldStatus   = 'draft';
            $yearplandemandData->parent      = $yearplanID;

            $this->dao->insert(TABLE_YEARPLANDEMAND)->data($yearplandemandData)->autoCheck()->exec();

            if(!dao::isError())
            {
                $yearplandemandID = $this->dao->lastInsertID();
                $this->action->create('yearplandemand', $yearplandemandID, 'created', '');
            }
            if(dao::isError()) die(js::error(dao::getError()));
        }

        if($this->post->isEndPage)
        {
            unlink($this->session->fileImport);
            unset($_SESSION['fileImport']);
        }
    }

    public static function isClickable($yearplandemand, $action)
    {
        global $app;
        $action = strtolower($action);
        $instance = new self();

        $architectUsers = $instance->loadModel('user')->getUsersByUserGroupName($instance->lang->yearplandemand->businessArchitect);

        if($action == 'edit') return ($yearplandemand->status == 'draft' || $yearplandemand->status == 'tobeevaluated');
        if($action == 'submit') return $yearplandemand->status == 'draft';
        if($action == 'confirm') return (($yearplandemand->level == '1' || $yearplandemand->level == '2') && $yearplandemand->status == 'tobeevaluated' && ($instance->app->user->admin || isset($architectUsers[$instance->app->user->account])));
        if($action == 'cancel') return ($yearplandemand->status == 'draft' || $yearplandemand->status == 'tobeevaluated');
        if($action == 'restore') return ($yearplandemand->status == 'cancelled');
        if($action == 'delete') return ($yearplandemand->status != 'confirmed');
        if($action == 'sendback') return ($yearplandemand->status == 'confirmed');

        return true;
    }
}
