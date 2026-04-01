<?php
class demandModel extends model
{
    /**
     * Get demand menu for business.
     *
     * @param  bool   $selectAll
     * @access public
     * @return array
     */
    public function getBusinessProject($selectAll = false)
    {
        return $this->dao->select('id, name')->from('zt_flow_projectapproval')
            ->beginIF(!$selectAll)
            ->where('deleted')->eq('0')
            ->andWhere('status')->in('approvedProject,design,devTest,closure')
            ->andWhere('businessPM')->eq($this->app->user->account)
            ->fi()
            ->orderBy('id_desc')
            ->fetchPairs('id', 'name');
    }

    /**
     * Get demand menu for business.
     *
     * @access public
     * @return void
     */
    public function getDemandMenuForBusiness()
    {
        return $this->dao->select("zt_demand.id,CONCAT(zt_demandpool.name, '-', zt_demand.name) as name")
            ->from('zt_demand')
            ->leftJoin('zt_demandpool')->on('zt_demand.pool=zt_demandpool.id')
            ->where('zt_demand.deleted')->eq('0')
            ->andWhere('zt_demand.status')->eq('active')
            ->andWhere('zt_demand.stage')->eq('0')
            ->fetchPairs('id', 'name');
    }

    /**
     * Get demands by Ids.
     *
     * @param  array  $demandIdList
     * @param  string $status
     * @param  string $stage
     * @access public
     * @return void
     */
    public function getListByIds($demandIdList, $status = '', $stage = '')
    {
        $demands = $this->dao->select('*')->from(TABLE_DEMAND)
            ->where('deleted')->eq('0')
            ->andWhere('id')->in($demandIdList)
            ->beginIF($status !== '')->andWhere('status')->eq($status)->fi()
            ->beginIF($stage !== '')->andWhere('stage')->eq($stage)->fi()
            ->fetchAll('id');

            return $demands;
    }

    /**
     * Get integration data.
     *
     * @param  array  $demands
     * @access public
     * @return void
     */
    public function getIntegrationData($demands)
    {
        $valueLog = array();
        foreach($demands as $demand)
        {
            foreach($demand as $key => $value)
            {
                if(!isset($valueLog[$key])) $valueLog[$key] = '';

                if(stripos($this->config->demand->integration->syncedFields, $key) !== false)
                {
                    $valueLog[$key] .= $value . '<br />';
                }
                else
                {
                    if(!$valueLog[$key]) $valueLog[$key] = $value;
                    if($valueLog[$key] != $value) $valueLog[$key] = '';
                }
            }
        }
        $valueLog['level'] = $valueLog['pri'];

        $keys = array('background', 'overview', 'desc', 'businessDesc', 'businessObjective');

        foreach($keys as $key)
        {
            if(!isset($valueLog[$key])) continue;

            $valueLog[$key] = $this->loadModel('file')->setImgSize($valueLog[$key]);
        }

        return $valueLog;
    }

    /**
     * Get demand list.
     * @param  string  $browseType
     * @param  string  $orderBy
     * @param  object  $pager
     * @access public
     * @return void
     */
    public function getList($poolID = 0, $browseType, $queryID, $moduleID, $orderBy, $pager = null, $extra = '', $type = 'browse', $parent = '')
    {
        /* 获取搜索条件的查询SQL。*/
        $demandQuery = '';
        $isAllPool   = false;
        if($browseType == 'bysearch')
        {
            $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';
            if($query)
            {
                $this->session->set('demandQuery', $query->sql);
                $this->session->set('demandForm', $query->form);
            }
            if($this->session->demandQuery == false) $this->session->set('demandQuery', ' 1 = 1');
            $demandQuery = $this->session->demandQuery;
            if($type == 'track')
            {
                if($this->session->demandTrackQuery == false) $this->session->set('demandTrackQuery', ' 1 = 1');
                $demandQuery = $this->session->demandTrackQuery;
            }

            $allPool = "`pool` = 'all'";
            if(strpos($demandQuery, $allPool))
            {
                $isAllPool = true;

                $account = $this->app->user->account;
                $isAdmin = $this->app->user->admin ? 1 : 0;

                /* 创建SQL查询数据。*/
                $demandpoolIdList = $this->dao->select('id')->from(TABLE_DEMANDPOOL)
                    ->where('deleted')->eq('0')
                    ->andWhere('acl', true)->eq('open')
                    ->orWhere('(acl')->eq('private')
                    ->andWhere('createdBy', true)->eq($account)
                    ->orWhere('owner')->eq($account)
                    ->orWhere($isAdmin)
                    ->orWhere("CONCAT(',', participant, ',')")->like("%,$account,%")
                    ->markRight(1)
                    ->markRight(1)
                    ->markRight(1)
                    ->fetchPairs();

                $demandQuery = str_replace($allPool, '`pool` IN (' . join(',', $demandpoolIdList) . ')', $demandQuery);
            }
        }

        $modules = array();
        if($moduleID) $modules = $this->loadModel('tree')->getAllChildID($moduleID);

        /* 创建SQL查询数据。*/
        $demands = $this->dao->select('*')->from(TABLE_DEMAND)->leftJoin('(select id as businessID, demand as demandID, name as businessName from zt_flow_business)')->alias('tb')
            ->on("FIND_IN_SET(id, tb.demandID) > 0")
            ->where('deleted')->eq('0')
            ->beginIF(!$isAllPool)->andWhere('pool')->eq($poolID)->fi()
            ->beginIF($browseType != 'all' and $browseType != 'bysearch' and $browseType != 'openedbyme' and $browseType != 'assigntome' and $browseType != 'bymodule' and $browseType != 'notintegrated' and $browseType != 'integrated')->andWhere('status')->eq($browseType)->fi()
            ->beginIF($browseType == 'assigntome')->andWhere('assignedTo')->eq($this->app->user->account)->fi()
            ->beginIF($browseType == 'openedbyme')->andWhere('createdBy')->eq($this->app->user->account)->fi()
            ->beginIF($browseType == 'notintegrated')->andWhere('stage')->eq('0')->fi()
            ->beginIF($browseType == 'integrated')->andWhere('stage')->eq('1')->fi()
            ->beginIF($browseType == 'bysearch')->andWhere($demandQuery)->fi()
            ->beginIF($browseType == 'bymodule')->andWhere('module')->in($modules)->fi()
            ->beginIF(strpos($extra, 'nodeleted') !== false)->andWhere('status')->ne('deleted')->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');

        /* 保存查询条件并查询子需求条目。*/
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'demand', $browseType != 'bysearch');

        $projects         = $this->loadModel('project')->getPairsByPM();
        $projectApprovals = $this->getProjectApprovalPairs();
        $allProjects      = $projects + $projectApprovals;
        /* foreach($demands as $demand) $demand->projectName = $allProjects[max($demand->project,$demand->projectApproval)]; */
        $businessProject  = $this->getBusinessProject();
        foreach($demands as $demand) if($demand->project) $demand->projectName = $businessProject[$demand->project];

        return $demands;
    }

    public function getPairs($poolID = 0, $orderBy = 'id_desc')
    {
        $demands = $this->dao->select('id,name')->from(TABLE_DEMAND)
            ->where('status')->ne('deleted')
            ->andWhere('pool')->eq($poolID)
            ->orderBy($orderBy)
            ->fetchPairs();

        return $demands;
    }

    /**
     * Project: chengfangjinke
     * Method: getByID
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:50
     * Desc: This is the code comment. This method is called getByID.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param $demandID
     * @return mixed
     */
    public function getByID($demandID)
    {
        /* 查询需求意向的子需求条目、需求意向信息和需求意向相关附件然后返回数据。*/
        $demand = $this->dao->findByID($demandID)->from(TABLE_DEMAND)->fetch();
        if(!$demand) return array();

        $demand->files = $this->loadModel('file')->getByObject('demand', $demandID);

        $demand = $this->loadModel('file')->replaceImgURL($demand, 'background,overview,desc,businessDesc,businessObjective');

        $this->loadModel('story');

        $demand->URS = $this->dao->select('*')->from(TABLE_STORY)
            ->where('fromDemand')->eq($demandID)
            ->andWhere('type')->eq('requirement')
            ->fetchAll();

        $demand->SRS = $this->dao->select('*')->from(TABLE_STORY)
            ->where('fromDemand')->eq($demandID)
            ->andWhere('type')->eq('story')
            ->fetchAll();

        $demand->business = $this->dao->select('*')->from('zt_flow_business')
            ->where("CONCAT (',', demand, ',')")->like("%," . $demandID . ",%")
            ->andWhere('deleted')->eq('0')
            ->orderBy('id desc')
            ->fetch();

        return $demand;
    }

    /**
     * Get project approval pairs and filter by method.
     *
     * @access public
     * @return void
     */
    public function getProjectApprovalPairs()
    {
        $rawMethod = $this->app->rawMethod;

        return $this->dao->select('id,name')->from('zt_flow_projectapproval')
            ->where('deleted')->eq('0')
            ->beginIF(!empty($rawMethod) && !in_array($rawMethod, array('browse', 'view', 'edit')))->andWhere('`status`')->eq('draft')->fi()
            ->fetchPairs('id', 'name');
    }

    /**
     * Create a demand.
     *
     * @access public
     * @return void
     */
    public function create($poolID = 0)
    {
        /* 获取post数据并处理数据。*/
        $today = helper::today();
        $demand = fixer::input('post')
            ->add('pool', $poolID)
            ->add('createdBy', $this->app->user->account)
            ->add('createdDate', $today)
            ->setIF($this->post->reviewer, 'status', 'wait')
            ->setIF(!$this->post->reviewer, 'status', 'active')
            ->remove('uid,files,labels,contactListMenu')
            ->join('mailto', ',')
            ->join('dept', ',')
            ->stripTags($this->config->demand->editor->create['id'], $this->config->allowedTags)
            ->get();

        if($demand->dept) $demand->dept = ",{$demand->dept},";
        /* 插入数据后，判断是否有误，然后更新code参数，并保存文件。*/
        $demand = $this->loadModel('file')->processImgURL($demand, $this->config->demand->editor->create['id'], $this->post->uid);
        $this->dao->insert(TABLE_DEMAND)->data($demand)
            ->autoCheck()
            ->batchCheck($this->config->demand->create->requiredFields, 'notempty')->exec();

        if(!dao::isError())
        {
            $demandID = $this->dao->lastInsertID();

            $this->loadModel('file')->updateObjectID($this->post->uid, $demandID, 'demand');
            $this->file->saveUpload('demand', $demandID);

            return $demandID;
        }

        return false;
    }

    public function batchCreate($poolID = 0)
    {
        $today        = helper::today();
        $demands = (object)$_POST;
        $this->loadModel('action');

        foreach($demands->name as $i => $name)
        {
            if(!$name) continue;

            $demand = new stdclass();
            $demand->name              = $name;
            $demand->severity          = $demands->severity[$i];
            $demand->desc              = $demands->desc[$i];
            $demand->reviewer          = isset($demands->reviewer[$i]) ? $demands->reviewer[$i] : '';
            $demand->deadline          = $demands->deadline[$i];
            $demand->businessDesc      = $demands->businessDesc[$i];
            $demand->businessObjective = $demands->businessObjective[$i];
            $demand->project           = isset($demands->project[$i]) ? $demands->project[$i] : '';
            $demand->businessUnit      = isset($demands->businessUnit[$i]) ? implode(',', $demands->businessUnit[$i]) : '';
            $demand->pool              = $poolID;
            $demand->status            = $demand->reviewer ? 'wait' : 'active';
            $demand->createdDate       = $today;
            $demand->createdBy         = $this->app->user->account;
            if($demands->dept[$i]) $demand->dept = ',' . implode(',', $demands->dept[$i]) . ',';

            $this->dao->insert(TABLE_DEMAND)->data($demand)->autoCheck()->batchCheck($this->config->demand->create->requiredFields, 'notempty')->exec();

            $demandID = $this->dao->lastInsertID();
            $this->action->create('demand', $demandID, 'created');

            //foreach(explode(',', $this->config->demand->batchCreate->requiredFields) as $field)
            //{
            //    $field = trim($field);
            //    if($field and empty($demand->$field))
            //    {
            //        dao::$errors['message'][] = sprintf($this->lang->error->notempty, $this->lang->demand->$field);
            //        return false;
            //    }
            //}
        }

        return true;
    }

    /**
     * Update a demand.
     *
     * @access int $demandID
     * @access public
     * @return void
     */
    public function update($demandID)
    {
        /* 获取旧的需求意向数据，并处理post请求参数。*/
        $oldDemand = $this->getByID($demandID);
        $demand = fixer::input('post')
            ->join('mailto', ',')
            ->join('dept', ',')
            ->remove('uid,files,labels,comment,contactListMenu')
            ->stripTags($this->config->demand->editor->edit['id'], $this->config->allowedTags)
            ->get();

        if($demand->dept) $demand->dept = ",{$demand->dept},";
        /* 执行SQL，处理相关附件，并获取变动的字段进行返回。*/
        $demand = $this->loadModel('file')->processImgURL($demand, $this->config->demand->editor->edit['id'], $this->post->uid);
        $this->dao->update(TABLE_DEMAND)->data($demand)->autoCheck()
            ->batchCheck($this->config->demand->edit->requiredFields, 'notempty')
            ->where('id')->eq($demandID)
            ->exec();

        $this->loadModel('file')->updateObjectID($this->post->uid, $demandID, 'demand');
        $this->file->saveUpload('demand', $demandID);

        return common::createChanges($oldDemand, $demand);
    }

    /**
     * Assign a demand.
     *
     * @param  int    $demandID
     * @access public
     * @return void
     */
    public function assign($demandID)
    {
        $oldDemand = $this->dao->findById($demandID)->from(TABLE_DEMAND)->fetch();
        $assignedTo     = $this->post->assignedTo;
        if($assignedTo == $oldDemand->assignedTo) return array();

        $demand = new stdclass();
        $demand->assignedTo = $assignedTo;

        $this->dao->update(TABLE_DEMAND)->data($demand)->autoCheck()->where('id')->eq((int)$demandID)->exec();
        if(!dao::isError()) return common::createChanges($oldDemand, $demand);
        return false;
    }

    /**
     * Review a demand.
     *
     * @param  int    $demandID
     * @access public
     * @return void
     */
    public function review($demandID)
    {
        $oldDemand = $this->dao->findById($demandID)->from(TABLE_DEMAND)->fetch();
        $demand = fixer::input('post')
            ->join('mailto', ',')
            ->setIF($this->post->result == 'pass', 'status', 'active')
            ->setIF($this->post->result == 'refuse', 'status', 'refuse')
            ->remove('uid,comment,result,contactListMenu')
            ->get();

        $this->dao->update(TABLE_DEMAND)
            ->data($demand)
            ->autoCheck()
            ->batchCheck($this->config->demand->review->requiredFields, 'notempty')
            ->where('id')->eq((int)$demandID)->exec();
        if(!dao::isError()) return common::createChanges($oldDemand, $demand);
        return false;
    }

    public function close($demandID)
    {
        $oldDemand = $this->dao->findById($demandID)->from(TABLE_DEMAND)->fetch();
        $now       = helper::now();
        $demand    = fixer::input('post')
            ->add('status', 'closed')
            ->add('assignedTo', 'closed')
            ->remove('uid')
            ->get();

        $this->dao->update(TABLE_DEMAND)->data($demand, 'comment')
            ->autoCheck()
            ->where('id')->eq((int)$demandID)
            ->exec();
        if(!dao::isError()) return common::createChanges($oldDemand, $demand);
        return false;
    }

    /**
     * Project: chengfangjinke.
     * Method: suspend
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 13:14
     * Desc: This is the code comment. This method is called suspend.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param $poolID
     * @return array
     */
    public function suspend($demandID)
    {
        $oldDemand = $this->getByID($demandID);
        $demand = fixer::input('post')
            ->remove('comment')
            ->get();

        $this->dao->update(TABLE_DEMAND)->data($demand)
            ->where('id')->eq($demandID)
            ->exec();

        $this->loadModel('consumed')->record('demand', $demandID, 0, $this->app->user->account, $oldDemand->status, 'suspend');

        return common::createChanges($oldDemand, $demand);
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
        $this->config->demand->search['actionURL'] = $actionURL;
        $this->config->demand->search['queryID']   = $queryID;
        $this->config->demand->search['params']['product']['values'] = array('' => '') + $this->loadModel('product')->getPairs();
        $this->config->demand->search['params']['project']['values'] = array('' => '') + $this->getBusinessProject();

        $poolName = $this->dao->select('name')->from(TABLE_DEMANDPOOL)->where('id')->eq($poolID)->fetch('name');
        $this->config->demand->search['params']['pool']['values'] = [$poolID => $poolName, 'all' => $this->lang->all, '' => ''];

        $depts    = $this->loadModel('dept')->getOptionMenu();
        $newDepts = array();
        foreach($depts as $key => $value)
        {
            $newDepts[",{$key},"] = $value;
        }

        $this->config->demand->search['params']['dept']['values']    = $newDepts;

        $this->loadModel('search')->setSearchParams($this->config->demand->search);
    }

    public function printAssignedHtml($demand, $users)
    {
        $this->loadModel('task');
        $btnTextClass   = '';
        $assignedToText = zget($users, $demand->assignedTo);

        if(empty($demand->assignedTo))
        {
            $btnTextClass   = 'text-primary';
            $assignedToText = $this->lang->task->noAssigned;
        }
        if($demand->assignedTo == $this->app->user->account) $btnTextClass = 'text-red';

        $btnClass     = $demand->assignedTo == 'closed' ? ' disabled' : '';
        $btnClass     = "iframe btn btn-icon-left btn-sm {$btnClass}";
        $assignToLink = helper::createLink('demand', 'assignTo', "demandID=$demand->id", '', true);
        $assignToHtml = html::a($assignToLink, "<i class='icon icon-hand-right'></i> <span class='{$btnTextClass}'>{$assignedToText}</span>", '', "class='$btnClass'");

        echo !common::hasPriv('demand', 'assignTo', $demand) ? "<span style='padding-left: 21px' class='{$btnTextClass}'>{$assignedToText}</span>" : $assignToHtml;
    }

    /**
     * Project: chengfangjinke
     * Method: setListValue
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:50
     * Desc: This is the code comment. This method is called setListValue.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     */
    public function setListValue($poolID = 0)
    {
        $this->loadModel('story');
        /* 导出需求意向数据调用该方法设置下拉选项的可选值。*/
        $users    = $this->loadModel('user')->getPairs('noletter|noclosed');
        $projects = $this->getBusinessProject();
        $depts    = $this->loadModel('dept')->getOptionMenuByGrade(0, 3);

        $newUsers    = array();
        $newProjects = array();
        $newDepts    = array();
        foreach($users as $account => $name)
        {
            if(!$account) continue;
            $newUsers[$account] = $name . "(#$account)";
        }

        foreach($projects as $id => $name)
        {
            if(!$id) continue;
            $newProjects[$id] = $name . "(#$id)";
        }

        foreach($depts as $id => $name)
        {
            if(!$id) continue;
            $newDepts[$id] = $name . "(#$id)";
        }

        $priList   = $this->lang->demand->priList;
        $stageList = $this->lang->demand->stageList;


        $this->post->set('assignedToList', array_values($newUsers));
        $this->post->set('reviewerList', array_values($newUsers));
        $this->post->set('priList', join(',', $priList));
        $this->post->set('listStyle',  $this->config->demand->export->listFields);
        $this->post->set('extraNum',   0);
        $this->post->set('projectList', $newProjects);
        $this->post->set('deptList', $newDepts);
        $this->post->set('stageList', join(',', $stageList));
    }

    /**
     * Project: chengfangjinke
     * Method: createFromImport
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:50
     * Desc: This is the code comment. This method is called createFromImport.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     */
    public function createFromImport($poolID = 0)
    {
        /* 加载action、demand和file模块，并获取导入数据。*/
        $this->loadModel('action');
        $this->loadModel('file');
        $now   = helper::today();
        $data  = (object)$_POST;

        /* 加载purifier富文本过滤器。*/
        $this->app->loadClass('purifier', true);
        $purifierConfig = HTMLPurifier_Config::createDefault();
        $purifierConfig->set('Filter.YouTube', 1);
        $purifier = new HTMLPurifier($purifierConfig);

        /* 获取旧的需求意向数据。*/
        if(!empty($_POST['id']))
        {
            $oldDemands = $this->dao->select('*')->from(TABLE_DEMAND)->where('id')->in(($_POST['id']))->fetchAll('id');
        }

        /* 初始化导入数据变量。*/
        $demands = array();
        $line     = 1;
        foreach($data->name as $key => $name)
        {
            /* 定义一个导入数据对象，如果name参数为空，则跳过该行数据。*/
            $demandData = new stdclass();

            /* 将页面获取到的数据赋值给对象。*/
            $demandData->pool              = $poolID;
            $demandData->name              = $name;
            $demandData->pri               = $data->pri[$key];
            $demandData->severity          = $data->severity[$key];
            $demandData->assignedTo        = $data->assignedTo[$key];
            $demandData->reviewer          = $data->reviewer[$key];
            $demandData->deadline          = $data->deadline[$key];
            $demandData->desc              = nl2br($purifier->purify($this->post->desc[$key]));
            $demandData->businessDesc      = $data->businessDesc[$key];
            $demandData->businessObjective = $data->businessObjective[$key];
            $demandData->project           = $data->project[$key];

            if($data->dept[$key]) $demandData->dept = ',' . implode(',', $data->dept[$key]) . ',';

            foreach($data->businessUnit[$key] as $businessUnitKey => $businessUnitValue)
            {
                if($businessUnitValue === '') unset($data->businessUnit[$key][$businessUnitKey]);
            }
            $demandData->businessUnit = ',' . implode(',', $data->businessUnit[$key]);

            /* 判断那些字段是必填的。*/
            if(isset($this->config->demand->create->requiredFields))
            {
                $requiredFields = explode(',', $this->config->demand->create->requiredFields);
                foreach($requiredFields as $requiredField)
                {
                    $requiredField = trim($requiredField);
                    if(empty($demandData->$requiredField)) dao::$errors[] = sprintf($this->lang->demand->noRequire, $line, $this->lang->demand->$requiredField);
                }
            }

            $demands[$key]['demandData'] = $demandData;
            $line++;
        }

        /* 判断是否由必填项，如果有，则提示错误信息。*/
        if(dao::isError()) die(js::error(dao::getError()));

        /* 进行导入数据处理。*/
        foreach($demands as $key => $newDemand)
        {
            /* 判断当前数据是否已存在，不存在的则为$demandID赋值为0。*/
            $demandID   = 0;
            $demandData = $newDemand['demandData'];
            if(!empty($_POST['id'][$key]) and empty($_POST['insert']))
            {
                $demandID = $data->id[$key];
                if(!isset($oldDemands[$demandID])) $demandID = 0;
            }

            /* 如果$demandID有值，则说明需求意向已存在，按照更新的情况来处理。*/
            if($demandID)
            {
                $oldDemand     = $oldDemands[$demandID];
                $demandChanges = common::createChanges($oldDemand, $demandData);

                if($demandChanges)
                {
                    $this->dao->update(TABLE_DEMAND)
                        ->data($demandData)
                        ->autoCheck()
                        ->batchCheck($this->config->demand->create->requiredFields, 'notempty')
                        ->where('id')->eq((int)$demandID)->exec();

                    if(!dao::isError())
                    {
                        if($demandChanges)
                        {
                            $actionID = $this->action->create('demand', $demandID, 'Edited', '');
                            $this->action->logHistory($actionID, $demandChanges);
                        }
                    }
                }
            }
            else
            {
                /* 如果是全新插入的需求意向，处理好数据后，执行SQL进行数据插入。*/
                $demandData->createdBy   = $this->app->user->account;
                $demandData->createdDate = helper::today();
                $demandData->status      = $demandData->reviewer ? 'wait' : 'active';

                $this->dao->insert(TABLE_DEMAND)->data($demandData)->autoCheck()->exec();

                if(!dao::isError())
                {
                    $demandID = $this->dao->lastInsertID();
                    $this->action->create('demand', $demandID, 'created', '');
                }
            }
        }

        /* 判断数据是否处理完毕，处理完毕则删除导入文件，并清除session信息。*/
        if($this->post->isEndPage)
        {
            unlink($this->session->fileImport);
            unset($_SESSION['fileImport']);
        }
    }

    /**
     * Project: chengfangjinke
     * Method: isClickable
     * User: Tony Stark
     * Year: 2021
     * Date: 2021/10/8
     * Time: 14:50
     * Desc: This is the code comment. This method is called isClickable.
     * remarks: The sooner you start to code, the longer the program will take.
     * Product: PhpStorm
     * @param $demand
     * @param $action
     * @return bool
     */
    public static function isClickable($demand, $action)
    {
        global $app;
        /* 对操作转换成小写，根据状态判断当前操作是否允许高亮。*/
        $action = strtolower($action);

        if($action == 'submit') return ($demand->status == 'refuse');
        if($action == 'close')  return ($demand->status != 'closed');
        if($action == 'review') return ($demand->status == 'wait') and ($demand->reviewer == $app->user->account);

        return true;
    }

    public function printCell($col, $demand, $users, $modulePairs, $mode = 'datatable')
    {
        $canView = common::hasPriv('demand', 'view');

        $depts          = $this->loadModel('dept')->getOptionMenu();
        $projects       = ['' => ''] + $this->getBusinessProject();
        $currentProject = $this->dao->select('id,name')->from('zt_flow_projectapproval')->where('id')->eq($demand->project)->fetch();
        if($currentProject) $projects[$currentProject->id] = $currentProject->name;

        $demandLink = helper::createLink('demand', 'view', "demandID=$demand->id");
        $account = $this->app->user->account;
        $id      = $col->id;
        $canBatchAction = (common::hasPriv('demand', 'batchEdit') or common::hasPriv('demand', 'batchReview') or common::hasPriv('demand', 'batchAssign') or common::hasPriv('demand', 'integration'));

        if($col->show)
        {
            $class = "c-$id";
            $title = '';
            switch($id)
            {
                case 'id':
                    $class .= ' cell-id';
                    break;
                case 'status':
                    $class .= ' demand-' . $demand->status;
                    $title  = "title='" . $this->processStatus('demand', $demand) . "'";
                    break;
                case 'name':
                    $class .= ' text-left';
                    $title  = "title='{$demand->name}'";
                    break;
                case 'businessUnit':
                    $title = "title='" . $this->loadModel('flow')->printFlowCell('demand', $demand, $id, true) . "'";
                    break;
                case 'project':
                    $title = "title='" . zget($projects, $demand->project, '') . "'";
                    break;
                case 'dept':
                    $dept     = explode(',', trim($demand->dept, ','));
                    $deptText = '';
                    foreach($dept as $item)
                    {

                        $deptText .= zget($depts, $item) . " &nbsp;";
                    }
                    $title = "title='{$deptText}'";
                    break;
                case 'assignedTo':
                    $class .= ' has-btn text-left';
                    if($demand->assignedTo == $account) $class .= ' red';
                    break;
                case 'reviewer':
                    if($demand->reviewer == $account and $demand->status == 'wait') $class .= ' red';
                    break;
                case 'openedBy':
                    $class .= ' c-user';
                    $title  = "title='" . zget($users, $demand->openedBy) . "'";
                    break;
            }

            echo "<td class='" . $class . "' $title>";
            if($this->config->edition != 'open') $this->loadModel('flow')->printFlowCell('demand', $demand, $id);
            switch($id)
            {
            case 'id':
                if($canBatchAction)
                {
                    echo html::checkbox('demandIDList', array($demand->id => '')) . html::a(helper::createLink('demand', 'view', "demandID=$demand->id"), sprintf('%03d', $demand->id));
                }
                else
                {
                    printf('%03d', $demand->id);
                }
                break;
            case 'pri':
                echo "<span class='label-pri label-pri-" . $demand->pri . "' title='" . zget($this->lang->demand->priList, $demand->pri, $demand->pri) . "'>";
                echo zget($this->lang->demand->priList, $demand->pri, $demand->pri);
                echo "</span>";
                break;
            case 'name':
                echo $canView ? html::a($demandLink, $demand->name) : "{$demand->name}";
                break;
            case 'toTask':
                break;
            case 'status':
                echo "<span class='status-demand status-{$demand->status}'>";
                echo zget($this->lang->demand->statusList, $demand->status);
                echo  '</span>';
                break;
            case 'mailto':
                $mailto = explode(',', $demand->mailto);
                foreach($mailto as $account)
                {
                    $account = trim($account);
                    if(empty($account)) continue;
                    echo zget($users, $account) . " &nbsp;";
                }
                break;
            case 'createdBy':
                echo zget($users, $demand->createdBy);
                break;
            case 'createdDate':
                echo helper::isZeroDate($demand->createdDate) ? '' : substr($demand->createdDate, 0, 10);
                break;
            case 'reviewer':
                echo zget($users, $demand->reviewer);
                break;
            case 'assignedTo':
                $this->printAssignedHtml($demand, $users);
                break;
            case 'deadline':
                echo helper::isZeroDate($demand->deadline) ? '' : substr($demand->deadline, 0, 10);
                break;
            case 'closedBy':
                echo zget($users, $demand->closedBy);
                break;
            case 'closedDate':
                echo helper::isZeroDate($demand->closedDate) ? '' : substr($demand->closedDate, 0, 10);
                break;
            case 'project':
                echo zget($projects, $demand->project, '');
                break;
            case 'dept':
                echo $deptText;
                break;
            case 'stage':
                echo zget($this->lang->demand->stageList, $demand->stage);
                break;
            case 'actions':
                //common::printIcon('demand', 'submit', "demandID=$demand->id", $demand, 'list', 'start', '', 'iframe', true);
                //common::printIcon('demand', 'review', "demandID=$demand->id", $demand, 'list', 'glasses', '', 'iframe', true);

                if($demand->status == 'active' and common::hasPriv('demand', 'tostory'))
                {
                    if($this->config->URAndSR)
                    {
                        echo html::a('#toStory', '<i class="icon-demand-subdivide icon-split"></i>', '', "title='{$this->lang->demand->tostory}' data-toggle='modal' class='btn' data-id='$demand->id' onclick='getDemandID(this)'");
                    }
                    else
                    {
                        echo html::a(helper::createLink('demand', 'tostory', "demandID={$demand->id}&type=story"), '<i class="icon-demand-subdivide icon-split"></i>', '', "title='{$this->lang->demand->tostory}' class='btn'");
                    }
                }
                else
                {
                    echo html::a('#toStory', '<i class="icon-demand-subdivide icon-split"></i>', '', "disabled='disabled' class='btn' data-id='$demand->id' onclick='getDemandID(this)'");
                }

                common::printIcon('demand', 'edit', "demandID=$demand->id", $demand, 'list');
                common::printIcon('demand', 'close', "demandID=$demand->id", $demand, 'list', 'off', '', 'iframe', true);
                common::printIcon('demand', 'delete', "demandID=$demand->id", $demand, 'list', 'trash', 'hiddenwin');
                break;
            }
            echo '</td>';
        }
    }

    public function activate($demandID = 0)
    {
        $oldDemand = $this->getByID($demandID);
        $demand = fixer::input('post')
            ->remove('comment')
            ->get();

        $this->dao->update(TABLE_DEMAND)->data($demand)
            ->where('id')->eq($demandID)
            ->exec();

        $this->loadModel('consumed')->record('demand', $demandID, 0, $this->app->user->account, 'suspend', $demand->status);
        return common::createChanges($oldDemand, $demand);

    }

    /**
     * sendmail
     *
     * @param  int    $demandID
     * @param  int    $actionID
     * @access public
     * @return void
     */
    public function sendmail($demandID, $actionID)
    {
        /* 加载mail模块用于发信通知，获取需求意向和人员信息。*/
        $this->loadModel('mail');
        $demand = $this->getById($demandID);
        $users       = $this->loadModel('user')->getPairs('noletter');

        /* Get action info. */
        /* 当前需求意向的操作记录。*/
        $action          = $this->loadModel('action')->getById($actionID);
        $history         = $this->action->getHistory($actionID);
        $action->history = isset($history[$actionID]) ? $history[$actionID] : array();

        /* Get mail content. */
        /* 获取当前模块路径，然后获取发信模板，为发信模板赋值。*/
        $modulePath = $this->app->getModulePath($appName = '', 'demand');
        $oldcwd     = getcwd();
        $viewFile   = $modulePath . 'view/sendmail.html.php';
        chdir($modulePath . 'view');
        if(file_exists($modulePath . 'ext/view/sendmail.html.php'))
        {
            $viewFile = $modulePath . 'ext/view/sendmail.html.php';
            chdir($modulePath . 'ext/view');
        }
        ob_start();
        include $viewFile;
        foreach(glob($modulePath . 'ext/view/sendmail.*.html.hook.php') as $hookFile) include $hookFile;
        $mailContent = ob_get_contents();
        ob_end_clean();
        chdir($oldcwd);

        /* 获取发信人和抄送人数据。*/
        $sendUsers = $this->getToAndCcList($demand);
        if(!$sendUsers) return;
        list($toList, $ccList) = $sendUsers;
        $subject = $this->getSubject($demand, $action->action);

        /* Send mail. */
        /* 调用mail模块的send方法进行发信。*/
        $this->mail->send($toList, $subject, $mailContent, $ccList);
        if($this->mail->isError()) trigger_error(join("\n", $this->mail->getError()));
    }

    /**
     * Get mail subject.
     *
     * @param  object $demand
     * @param  string $actionType created|edited
     * @access public
     * @return string
     */
    public function getSubject($demand, $actionType)
    {
        /* Set email title. */
        return sprintf($this->lang->demand->mail->$actionType, $this->app->user->realname, $demand->id, $demand->name);
    }

    /**
     * Get toList and ccList.
     *
     * @param  object     $demand
     * @access public
     * @return bool|array
     */
    public function getToAndCcList($demand)
    {
        /* Set toList and ccList. */
        /* 初始化发信人和抄送人变量，获取发信人和抄送人数据。*/
        $toList   = $demand->assignedTo;
        $ccList   = str_replace(' ', '', trim($demand->mailto, ','));
        $ccList  .= ",$demand->createdBy";
        if(empty($toList))
        {
            if(empty($ccList)) return false;
            if(strpos($ccList, ',') === false)
            {
                $toList = $ccList;
                $ccList = '';
            }
            else
            {
                $commaPos = strpos($ccList, ',');
                $toList   = substr($ccList, 0, $commaPos);
                $ccList   = substr($ccList, $commaPos + 1);
            }
        }
        return array($toList, $ccList);
    }

    public static function createDemandLink($type, $module, $poolID)
    {
        return html::a(helper::createLink('demand', 'browse', "poolID=$poolID&type=byModule&param={$module->id}"), $module->name, '', "id='module{$module->id}'");
    }

    public function getTracks($poolID, $pager, $browseType, $queryID)
    {
        $this->loadModel('story');
        $demands = $this->getList($poolID, $browseType, $queryID, $moduleID, 'id_desc', $pager, '', 'track', '1');
        foreach($demands as $demandID => $demand)
        {
            $requirements = $this->dao->select('*')->from(TABLE_STORY)
                ->where('deleted')->eq('0')
                ->andWhere('type')->eq('requirement')
                ->andWhere('fromDemand')->eq($demandID)
                ->fetchAll('id');

            foreach($requirements as $requirementID => $requirement)
            {
                $stories = $this->getRelation($requirement->id, 'requirement');
                foreach($stories as $id => $story)
                {
                    $stories[$id]->title       = $story->title;
                    $stories[$id]->cases       = $this->loadModel('testcase')->getStoryCases($id);
                    $stories[$id]->bugs        = $this->loadModel('bug')->getStoryBugs($id);
                    $stories[$id]->tasks       = $this->loadModel('task')->getStoryTasks($id);
                    $stories[$id]->projects    = $this->story->getStoryProjects($id);
                    $stories[$id]->executions  = $this->story->getStoryProjects($id, 'execution');
                    $stories[$id]->builds      = $this->story->getStoryBuilds($id, $story->product);
                    $stories[$id]->testtasks   = $this->story->getStoryTesttasks(array_keys($stories[$id]->builds), $story->product);
                    $stories[$id]->testreports = $this->story->getStoryTestreports(array_keys($stories[$id]->testtasks), $story->product);
                    $stories[$id]->releases    = $this->story->getStoryReleases($id, $story->product);
                    if($this->config->edition == 'max')
                    {
                        $stories[$id]->designs   = $this->dao->select('id, name')->from(TABLE_DESIGN)
                            ->where('story')->eq($id)
                            ->andWhere('deleted')->eq('0')
                            ->fetchAll('id');
                        $stories[$id]->revisions = $this->dao->select('BID, t2.comment')->from(TABLE_RELATION)->alias('t1')
                            ->leftjoin(TABLE_REPOHISTORY)->alias('t2')->on('t1.BID = t2.id')
                            ->where('t1.AType')->eq('design')
                            ->andWhere('t1.BType')->eq('commit')
                            ->andWhere('t1.AID')->in(array_keys($stories[$id]->designs))
                            ->fetchPairs();
                    }
                }

                $requirements[$requirementID]->stories = $stories;
            }

            $demands[$demandID]->requirements = $requirements;

            $stories = $this->dao->select('*')->from(TABLE_STORY)
                ->where('deleted')->eq('0')
                ->andWhere('type')->eq('story')
                ->andWhere('fromDemand')->eq($demandID)
                ->fetchAll('id');
            foreach($stories as $id => $story)
            {
                $stories[$id]->title       = $story->title;
                $stories[$id]->cases       = $this->loadModel('testcase')->getStoryCases($id);
                $stories[$id]->bugs        = $this->loadModel('bug')->getStoryBugs($id);
                $stories[$id]->tasks       = $this->loadModel('task')->getStoryTasks($id);
                $stories[$id]->projects    = $this->story->getStoryProjects($id);
                $stories[$id]->executions  = $this->story->getStoryProjects($id, 'execution');
                $stories[$id]->builds      = $this->story->getStoryBuilds($id, $story->product);
                $stories[$id]->testtasks   = $this->story->getStoryTesttasks(array_keys($stories[$id]->builds), $story->product);
                $stories[$id]->testreports = $this->story->getStoryTestreports(array_keys($stories[$id]->testtasks), $story->product);
                $stories[$id]->releases    = $this->story->getStoryReleases($id, $story->product);
                if($this->config->edition == 'max')
                {
                    $stories[$id]->designs   = $this->dao->select('id, name')->from(TABLE_DESIGN)
                        ->where('story')->eq($id)
                        ->andWhere('deleted')->eq('0')
                        ->fetchAll('id');
                    $stories[$id]->revisions = $this->dao->select('BID, t2.comment')->from(TABLE_RELATION)->alias('t1')
                        ->leftjoin(TABLE_REPOHISTORY)->alias('t2')->on('t1.BID = t2.id')
                        ->where('t1.AType')->eq('design')
                        ->andWhere('t1.BType')->eq('commit')
                        ->andWhere('t1.AID')->in(array_keys($stories[$id]->designs))
                        ->fetchPairs();
                }
            }

            $demands[$demandID]->stories = $stories;
        }

        return $demands;
    }

    public function getRelation($storyID, $storyType, $fields = array())
    {
        $BType    = $storyType == 'story' ? 'requirement' : 'story';
        $relation = $storyType == 'story' ? 'subdividedfrom' : 'subdivideinto';

        $relations = $this->dao->select('BID')->from(TABLE_RELATION)
            ->where('AType')->eq($storyType)
            ->andWhere('BType')->eq($BType)
            ->andWhere('relation')->eq($relation)
            ->andWhere('AID')->eq($storyID)
            ->fetchPairs();

        if(empty($relations)) return array();

        return $this->dao->select('*')->from(TABLE_STORY)->where('id')->in($relations)->andWhere('deleted')->eq(0)->fetchAll('id');
    }

    /**
     * Add prefix for array.
     *
     * @param  array  $datas
     * @param  string $prefix
     * @access public
     * @return array
     */
    public function addPrefix($datas, $prefix)
    {
        foreach($datas as $key => $data)
        {
            if(empty($data)) continue;

            $keyWithPrefix = $prefix . $key;
            switch($prefix)
            {
                case 'P':
                    $data = "{$this->lang->demand->projectTypeList[2]} - {$data}";
                    break;
                case 'PA':
                    $data = "{$this->lang->demand->projectTypeList[1]} - {$data}";
                    break;
                default:
                    break;
            }

            $datas[$keyWithPrefix] = $data;
            unset($datas[$key]);
        }

        return $datas;
    }

    public function buildTrackSearchForm($poolID, $demandPools, $queryID, $actionURL)
    {
        $this->config->demand->search['module']    = 'demandTrack';
        $this->config->demand->search['actionURL'] = $actionURL;
        $this->config->demand->search['queryID']   = $queryID;

        $this->config->demand->search['params']['dept']['values'] = array('' => '') + $this->loadModel('dept')->getOptionMenu();

        $this->loadModel('search')->setSearchParams($this->config->demand->search);
    }
}
