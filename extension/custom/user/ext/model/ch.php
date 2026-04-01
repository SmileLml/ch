<?php
public function syncAllUsers()
{
    set_time_limit(0);
    ini_set('memory_limit', '7168M');
    $this->loadModel('apiRequest');

    $result = $this->apiRequest->getUsers('2000-01-01 00:00:00', helper::now());

    if($result['state'] != 'success') return $result;

    $result = $this->saveUsersData($result);

    // if totalCount > 60000 for page get
    if($result['totalCount'] && $result['tableName'])
    {
        $pageSize      = 60000;
        $totalPage     = ceil($result['totalCount'] / $pageSize);
        $currentPageNo = 1;

        while($currentPageNo < $totalPage)
        {
            $result = $this->apiRequest->getUsers('', '', $result['tableName'], $currentPageNo, $pageSize);
            $this->saveUsersData($result);
            $currentPageNo ++ ;
        }
    }

    return $result;
}

public function saveUsersData($result)
{
    $users = $result['result'];

    /* The data of user list is too huge, so we chunk it then import them to avoid the OOM. */
    if(!empty($users) and is_array($users))
    {
        $filePath = $this->app->getWWWRoot() . "data/allUsers" . date('Ymd') . '.info';
        if(is_file($filePath)) unlink($filePath);

        foreach($users as $user) file_put_contents($filePath, json_encode($user) . "\n", FILE_APPEND);
        if(is_file($filePath)) $result = $this->insertUserIntoDB($filePath);
        return $result;
    }
}

public function syncAllDepts($begin = '', $end = '')
{
    set_time_limit(0);
    ini_set('memory_limit', '7168M');
    $this->loadModel('apiRequest');

    if(empty($begin)) $begin = '2000-01-01 00:00:00';
    if(empty($end)) $end = date('Y-m-d') . ' 23:59:59';

    $result = $this->apiRequest->getDepts($begin, $end);

    if($result['state'] != 'success') return $result;

    $requestAgain = false;
    $pageNo       = 2;
    do{
        $depts = $result['result'];

        if(!empty($depts) and is_array($depts)) $this->syncDeptsToZentao($depts);

        if(!empty($result['tableName']))
        {
            $result = $this->apiRequest->getDepts('', '', $result['tableName'], $pageNo);
            if($result['state'] != 'success')
            {
                $requestAgain = false;
                $this->saveDeptApiDataLog(json_encode($result['result'], JSON_UNESCAPED_UNICODE));
                return $result;
            }

            if($pageNo * 60000 < $result['totalCount'])
            {
                $pageNo += 1;
                $requestAgain = true;
            }
            else
            {
                $requestAgain = false;
            }
        }
    } while($requestAgain);

    $this->saveDeptApiDataLog(json_encode($result['result'], JSON_UNESCAPED_UNICODE));
    return $result;
}

public function saveDeptApiDataLog($message)
{
    $logFile = $this->app->logRoot . 'allDept' . date('Ymd') . '.log.php';
    if(!is_file($logFile)) file_put_contents($logFile, "<?php\n die();\n?>\n");

    file_put_contents($logFile, $message . "\n", FILE_APPEND);
}

public function syncDeptsToZentao($deptList)
{
    if(empty($deptList)) return;

    $this->syncDeleteDepts($deptList);

    $this->loadModel('dept');
    $zentaoDeptList     = $this->dept->getAllDepartment();
    $zentaoDeptCodeList = array_keys($zentaoDeptList);

    for($class = -1; $class <= 3; $class++)
    {
        $classDeptList = $this->getClassDeptartmentList($deptList, $class);
        if(empty($classDeptList)) continue;

        foreach($classDeptList as $dept)
        {
            if(in_array($dept->departmentCode, $zentaoDeptCodeList))
            {
                $this->updateDept($dept, $zentaoDeptList);
            }
            else
            {
                $this->createDept($dept, $zentaoDeptCodeList, $zentaoDeptList);
            }
        }
    }

    $deptIDListForDelete = [];
    foreach($deptList as $dept)
    {
        if(isset($dept->isValid) && $dept->isValid != 0)
        {
            $tmpDept = $zentaoDeptList[$dept->departmentCode] ?? null;
            if(empty($tmpDept)) continue;

            $deptIDListForDelete[] = $tmpDept->id;
        }
    }

    if(!empty($deptIDListForDelete)) $this->dao->delete()->from(TABLE_DEPT)->where('id')->in($deptIDListForDelete)->exec();
}

public function syncDeleteDepts($deptList)
{
    if(empty($deptList)) return false;

    $deptCodeList = [];
    foreach($deptList as $dept)
    {
        if($dept->isValid == 1) $deptCodeList[] = $dept->departmentCode;
    }

    if(!empty($deptCodeList)) $this->dao->delete()->from(TABLE_DEPT)->where('departmentCode')->in($deptCodeList)->exec();
    return true;
}

public function updateDept($dept, $zentaoDeptList)
{
    if(isset($dept->isValid) and $dept->isValid != '0')
    {
        $this->dao->delete()->from(TABLE_DEPT)->where('id')->eq($dept->departmentCode)->exec();
        return true;
    }

    $zentaoDept = $zentaoDeptList[$dept->departmentCode] ?? null;
    if(empty($zentaoDept)) return true;
    $zentaoParentDept = $zentaoDeptList[$dept->parentDepartmentCode] ?? null;

    $deptObj = new stdclass();
    $deptObj->name                 = isset($dept->departmentName)  ? $dept->departmentName        : 'not set';
    $deptObj->grade                = isset($dept->departmentClass) ? ($dept->departmentClass + 2) : 1;
    $deptObj->parentDepartmentCode = isset($dept->parentDepartmentCode) ? ($dept->parentDepartmentCode) : '';

    if(!empty($zentaoParentDept))
    {
        $deptObj->parent = $zentaoParentDept->id ?? 0;
        $deptObj->path   = $zentaoParentDept->path . "{$zentaoDept->id},";
    }
    else
    {
        $deptObj->path = ',' . $zentaoDept->id . ',';
    }

    $this->dao->update(TABLE_DEPT)->data($deptObj)->autoCheck()->check('name', 'notempty')->where('id')->eq($zentaoDept->id)->exec();

    $department = $this->getByID($zentaoDept->id);
    $zentaoDeptList[$zentaoDept->departmentCode] = $department;

    /* Add log. */
    file_put_contents($this->app->getTmpRoot() . 'updateDept' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n"
        . "data: " . json_encode($deptObj) . "\n"
        . 'daoError: ' . json_encode(dao::getError()) . "\n"
        , FILE_APPEND);

    if(dao::isError()) return false;

    return true;
}

public function createDept($dept, &$zentaoDeptCodeList, &$zentaoDeptList)
{
    if(isset($dept->isValid) and $dept->isValid != '0') return true;
    $deptObj = new stdclass();
    $deptObj->id                   = isset($dept->departmentCode)  ? $dept->departmentCode        : '';
    $deptObj->departmentCode       = isset($dept->departmentCode)  ? $dept->departmentCode        : '';
    $deptObj->name                 = isset($dept->departmentName)  ? $dept->departmentName        : 'not set';
    $deptObj->grade                = isset($dept->departmentClass) ? ($dept->departmentClass + 2) : 1;
    $deptObj->parentDepartmentCode = isset($dept->parentDepartmentCode) ? ($dept->parentDepartmentCode) : '';

    if(!empty($deptObj->parentDepartmentCode))
    {
        $parent = $zentaoDeptList[$deptObj->parentDepartmentCode] ?? null;
        $deptObj->parent = $parent->id ?? 0;
        $parentPath = $parent->path;
    }
    else
    {
        $parentPath = ',';
    }

    $this->dao->insert(TABLE_DEPT)->data($deptObj)->exec();

    $deptID       = $this->dao->lastInsertID();
    $childPath    = $parentPath . "$deptID,";
    $this->dao->update(TABLE_DEPT)->set('path')->eq($childPath)->where('id')->eq($deptID)->exec();

    $department = $this->getByID($deptID);
    $zentaoDeptList[$deptObj->departmentCode] = $department;
    $zentaoDeptCodeList[]                     = $department->departmentCode;

    /* Add log. */
    file_put_contents($this->app->getTmpRoot() . 'insertDept' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n"
        . "data: " . json_encode($deptObj) . "\n"
        . 'daoError: ' . json_encode(dao::getError()) . "\n"
        , FILE_APPEND);

    if(dao::isError()) return false;

    return true;
}

public function getClassDeptartmentList($deptList, $class = 0)
{
    $data = [];
    $classList = [-1, 0, 1, 2, 3];
    if(!in_array($class, $classList)) return $data;

    foreach($deptList as $dept)
    {
        if($dept->isValid != '0') continue;
        if($dept->departmentClass != $class) continue;
        $data[] = $dept;
    }

    return $data;
}

public function insertDeptIntoDB($dept, $deptObj)
{
    if(isset($dept->isValid) and $dept->isValid == '1') return true;
    $deptObj->departmentCode       = isset($dept->departmentCode)  ? $dept->departmentCode        : '';
    $deptObj->name                 = isset($dept->departmentName)  ? $dept->departmentName        : 'not set';
    $deptObj->grade                = isset($dept->departmentClass) ? ($dept->departmentClass + 2) : 0;
    $deptObj->parentDepartMentCode = isset($dept->parentDepartmentCode) ? ($dept->parentDepartmentCode) : '';
    $deptObj->manager              = isset($dept->managePositionCode) ? $dept->managePositionCode : '';

    $deptObj->path = '';
    for($i = 1; $i <= $deptObj->grade; $i++)
    {
        switch ($i)
        {
            case 1:
                $deptObj->path .= isset($dept->groupDepartmentCode)  ? "{$dept->groupDepartmentCode},"  : ',';
                break;
            case 2:
                $deptObj->path .= isset($dept->class0DepartmentCode) ? "{$dept->class0DepartmentCode}," : ',';
                break;
            case 3:
                $deptObj->path .= isset($dept->class1DepartmentCode) ? "{$dept->class1DepartmentCode}," : ',';
                break;
            case 4:
                $deptObj->path .= isset($dept->class2DepartmentCode) ? "{$dept->class2DepartmentCode}," : ',';
                break;
            case 5:
                $deptObj->path .= isset($dept->class3DepartmentCode) ? "{$dept->class3DepartmentCode}," : ',';
                break;
        }
    }

    $this->dao->replace(TABLE_DEPT)->data($deptObj)->exec();

    /* Add log. */
    file_put_contents($this->app->getTmpRoot() . 'insertDept' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n"
        . "data: " . json_encode($deptObj) . "\n"
        . 'daoError: ' . json_encode(dao::getError()) . "\n"
        , FILE_APPEND);

    if(dao::isError()) return false;

    return true;
}

public function insertUserIntoDB($filePath)
{
    $handle = fopen($filePath, "r");
    $result = array();
    if(!$handle)
    {
        $result['state']   = 'failed';
        $result['message'] = 'error';
	return $result;
    }


    $batchSize = 50;
    $lineCount = 0;
    $batchData = array();
    $isSuccess = true;

    while(!feof($handle))
    {
	    $line = fgets($handle);
	    if($line === false) break;
	    $jsonData = json_decode($line);
	    if($jsonData !== null) $batchData[] = $jsonData;

	    $lineCount ++;

	    if(($lineCount % $batchSize === 0) || feof($handle))
	    {
		    $isSuccess = $this->processBatchData($batchData, $lineCount);
                    if(!$isSuccess)
                    {
                        $result['message'] .= " $lineCount";
                        break;
                    }

		    $batchData = [];
	    }
    }

    fclose($handle);

    if($isSuccess)
    {
        $result['state']     = 'success';
        $result['message']   = 'success';
        $result['lineCount'] = $lineCount;
    }

    return $result;
}

public function processBatchData($batchData, $lineCount)
{
    foreach($batchData as $row)
    {
        $user = new stdclass();

        $user->account  = isset($row->accountNum)         ? $row->accountNum         : '';
        $user->realname = isset($row->psnName)            ? $row->psnName            : '';
        $user->birthday = isset($row->birthDate)          ? $row->birthDate          : '';
        $user->join     = isset($row->onBoardDate)        ? $row->onBoardDate        : '';
        $user->dept     = isset($row->tierDepartmentCode) ? $row->tierDepartmentCode : '';
        $user->zipcode  = isset($row->postCode)           ? $row->postCode           : '';
        $user->phone    = isset($row->homePhone)          ? $row->homePhone          : '';
        $user->mobile   = isset($row->officePhone)        ? $row->officePhone        : '';
        $user->email    = isset($row->mailBox)            ? $row->mailBox            : '';
        $user->dept     = 0;
        $user->isITDept = 'Y';

        if(isset($row->accountStatus))  $user->locked  = ($row->accountStatus == 'A')  ? ''       : '2099-01-01 23:59:59';
        if(isset($row->userType))       $user->type    = ($row->userType == '0')       ? 'inside' : 'outside';
        if(isset($row->employeeGender)) $user->gender  = ($row->employeeGender == '0') ? 'm'      : 'f';
        if(isset($row->isValid))        $user->deleted = ($row->isValid == '0')        ? '0'      : '1';

        $trash = (!$this->checkITDept($row) && !$this->checkThreeModernizationUser($row));
        if($trash)
        {
            $user->deleted  = '1';
            $user->isITDept = 'N';
        }

        if(isset($row->tierDepartmentCode))
        {
            $dept = $this->dao->select('*')->from(TABLE_DEPT)->where('id')->eq($row->tierDepartmentCode)->fetch();
            if($dept) $user->dept = $dept->id;
        }

        // if(!in_array($user->dept, $this->config->user->allowSyncDeptList)) $user->deleted = '1';

        $userdata = $this->dao->select('*')->from(TABLE_USER)->where('account')->eq($user->account)->fetch();
        if($userdata)
        {
            unset($user->deleted);
            unset($user->type);
            unset($user->realname);

            if($row->isValid == '1' || $row->isIncumbent == '0') $user->deleted = '1';
            // update data
            $this->dao->update(TABLE_USER)->data($user)
                ->autoCheck()
                ->check('account', 'account')
                ->checkIF($this->post->email != '', 'email', 'email')
                ->where('id')->eq($userdata->id)
                ->exec();

            $groupInfo = $this->dao->select('1')->from(TABLE_USERGROUP)->where('account')->eq($user->account)->fetch();
            if(empty($groupInfo))
            {
                $groupData = new stdClass();
                $groupData->account = $user->account;
                $groupData->group   = 22;
                $groupData->project = $savedUserID;

                $this->dao->insert(TABLE_USERGROUP)->data($groupData)->exec();
            }

            $savedUserID = $userdata->id;
        }
        else
        {
            if($row->isValid == '0' || $row->isIncumbent == '1')
            {
                $user->password = md5('123456');
                $this->dao->insert(TABLE_USER)->data($user)
                    ->autoCheck()
                    ->batchCheck($this->config->user->create->requiredFields, 'notempty')
                    ->check('account', 'account')
                    ->checkIF($this->post->email != '', 'email', 'email')
                    ->exec();

                $savedUserID = $this->dao->lastInsertID();

                $this->dao->delete()->from(TABLE_USERGROUP)->where('account')->eq($user->account)->exec();

                $groupData = new stdClass();
                $groupData->account = $user->account;
                $groupData->group   = 22;
                $groupData->project = $savedUserID;
                $this->dao->insert(TABLE_USERGROUP)->data($groupData)->exec();

            }
        }

        /* Add delete log for recover. */
        if($trash)
        {
            $hasAction = $this->dao->select('*')->from(TABLE_ACTION)
                ->where('objectType')->eq('user')
                ->andWhere('objectID')->eq($savedUserID)
                ->andWhere('action')->eq('deleted')
                ->fetch();
            if(!$hasAction)
            {
                $action             = new stdclass();
                $action->objectType = "user";
                $action->objectID   = $savedUserID;
                $action->actor      = "admin";
                $action->action     = "deleted";
                $action->date       = helper::now();
                $action->extra      = "1";

                if(!defined('IN_UPGRADE')) $action->vision = $this->config->vision;

                $this->dao->insert(TABLE_ACTION)->data($action)->autoCheck()->exec();
            }
        }

        /* Add log. */
        file_put_contents($this->app->getTmpRoot() . 'insertUser' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n"
            . "endLine: " . $lineCount . "\n"
            . "data: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n"
            . 'daoError: ' . json_encode(dao::getError()) . "\n"
            , FILE_APPEND);

//        if(dao::isError()) return false;
    }

    return true;
}

/**
 * Check if the user is in the IT department.
 *
 * @param  object  $row
 * @access public
 * @return boolean
 */
public function checkITDept($row)
{
    if(in_array($row->tierDepartmentSixLevelCode, $this->config->user->itDeptList)) return true;
    return false;
}

/**
 * Check if the user is in the three modernization user list.
 *
 * @param  object  $row
 * @access public
 * @return boolean
 */
public function checkThreeModernizationUser($row)
{
    if(in_array($row->accountNum, $this->config->user->threeModernizationUserList)) return true;

    return false;
}
