<?php
public function getFlowIDByObject($rootID = 0, $objectType = '', $data = null, $action = '')
{
    if(!$objectType) return 0;

    $this->app->loadLang('baseline');
    if($this->config->systemMode == 'PLM') $this->lang->baseline->objectList = array_merge($this->lang->baseline->objectList, $this->lang->baseline->ipd->pointList);
    $baselineObjects = $this->lang->baseline->objectList;

    $workflow = $this->loadModel('workflow')->getByModule($objectType);
    if($workflow)
    {
        if($action && strpos($action, 'approvalsubmit') !== false)
        {
            $actionKey = $result = str_replace("approvalsubmit", "", $action);

            $flowID = $this->dao->select('flow')->from(TABLE_APPROVALFLOWOBJECT)
                ->where('root')->eq($rootID)
                ->andWhere('objectType')->eq($objectType)
                ->andWhere('condition')->like('%"actionKey":' . $actionKey . '%')
                ->fetch('flow');

            return $flowID ? $flowID : 0;
        }
        else
        {
            $flows = $this->dao->select('*')->from(TABLE_APPROVALFLOWOBJECT)
                ->where('root')->eq($rootID)
                ->andWhere('objectType')->eq($objectType)
                ->fetchAll('id');

            $this->loadModel('flow');
            foreach($flows as $flow)
            {
                $conditions = json_decode($flow->condition);
                if($this->flow->checkConditions(array($conditions), $data)) return $flow->flow;
            }
        }
    }
    else
    {
        $flowID = $this->dao->select('flow')->from(TABLE_APPROVALFLOWOBJECT)
            ->where('root')->eq($rootID)
            ->andWhere('objectType')->eq($objectType)
            ->fetch('flow');
    }

    /* Baseline object list default simple flow. */
    if(in_array($objectType, array_keys($baselineObjects)) and !$flowID) $flowID = $this->dao->select('id')->from(TABLE_APPROVALFLOW)->where('code')->eq('simple')->fetch('id');

    return $flowID ? $flowID : 0;
}

/**
 * Search nodes to confirm.
 *
 * @param array  $nodes
 * @access public
 * @return array
 */
public function searchNodesToConfirm($nodes)
{
    $upLevel = '';
    /* If I am a moderators, use it firstly. */
    $parent = $this->dao->select('parent')->from(TABLE_DEPT)->where('manager')->eq($this->app->user->account)->andWhere('parent')->ne(0)->orderBy('grade')->fetch('parent');
    if($parent) $upLevel = $this->dao->select('manager')->from(TABLE_DEPT)->where('id')->eq($parent)->fetch('manager');

    /* If I am not a manager, use the manager of my dept. */
    if(!$upLevel) $upLevel = $this->dao->select('manager')->from(TABLE_DEPT)->where('id')->eq($this->app->user->dept)->fetch('manager');

    /* Get users of all roles. */
    $roles = $this->dao->select('id,users')->from(TABLE_APPROVALROLE)->where('deleted')->eq(0)->fetchPairs();
    foreach($roles as $id => $users) $roles[$id] = explode(',', trim($users, ','));

    $results = array();
    foreach($nodes as $node)
    {
        if($node->type == 'branch')
        {
            $exeDefault = true; // Need execute default branch ?
            foreach($node->branches as $branch)
            {
                if(!$this->checkCondition($branch->conditions)) continue;

                $results = array_merge($results, $this->searchNodesToConfirm($branch->nodes));
                if($node->branchType != 'parallel')
                {
                    $exeDefault = false;
                    break;
                }
            }
            if($exeDefault) $results = array_merge($results, $this->searchNodesToConfirm($node->default->nodes));
        }
        else
        {
            $result = array('types' => array());
            if(in_array($node->type, array('start', 'end')))
            {
                $result['id']    = $node->type;
                $result['title'] = $this->lang->approvalflow->nodeTypeList[$node->type];
            }
            else
            {
                $result['id']    = $node->id;
                $result['title'] = isset($node->title) ? $node->title : $this->lang->approvalflow->nodeTypeList[$node->type];
            }

            if(isset($node->reviewers) and !empty($node->reviewers))
            {
                foreach($node->reviewers as $reviewer)
                {
                    if(!isset($reviewer->type)) continue;
                    if($reviewer->type == 'select')    $result['types'][] = 'reviewer';
                    if($reviewer->type == 'appointee') $result['appointees']['reviewer'] = array_values($reviewer->users);
                    if($reviewer->type == 'upLevel')   $result['upLevel']['reviewer'][] = $upLevel ? $upLevel : '';
                    if($reviewer->type == 'role')
                    {
                        if(!isset($result['role']['reviewer'])) $result['role']['reviewer'] = array();
                        foreach($reviewer->roles as $role) $result['role']['reviewer'] = array_merge($result['role']['reviewer'], zget($roles, $role, array()));
                    }

                    if($reviewer->type == 'groupMember')        $result['groupMember']['reviewer']        = $reviewer->groupMembers;
                    if($reviewer->type == 'permissionGrouping') $result['permissionGrouping']['reviewer'] = $reviewer->permissionGroupings;
                }
            }
            if(isset($node->ccs) and !empty($node->ccs))
            {
                foreach($node->ccs as $cc)
                {
                    if(!isset($cc->type)) continue;
                    if($cc->type == 'select')    $result['types'][] = 'ccer';
                    if($cc->type == 'appointee') $result['appointees']['ccer'] = array_values($cc->users);
                    if($cc->type == 'upLevel')   $result['upLevel']['ccer'][] = $upLevel ? $upLevel : '';
                    if($cc->type == 'role')
                    {
                        if(!isset($result['role']['ccer'])) $result['role']['ccer'] = array();
                        foreach($cc->roles as $role) $result['role']['ccer'] = array_merge($result['role']['ccer'], zget($roles, $role, array()));
                    }
                }
            }

            if(count($result['types']) >= 1 or isset($result['appointees']) or isset($result['upLevel']) or isset($result['role']) or isset($result['groupMember']) or isset($result['permissionGrouping'])) $results[] = $result;
        }
    }

    return $results;
}

/**
 * Check condition
 *
 * @param  array  $conditions
 * @access public
 * @return bool
 */
public function checkCondition($conditions)
{
    if(empty($conditions)) return true;

    if(empty($this->submitter))
    {
        /* Depts. */
        $path = '';
        if($this->app->user->dept) $path = $this->dao->select('path')->from(TABLE_DEPT)->where('id')->eq($this->app->user->dept)->fetch('path');
        $depts = explode(',', trim($path, ','));

        /* Roles. */
        $roles = $this->dao->select('id')->from(TABLE_APPROVALROLE)->where('users')->like('%,' . $this->app->user->account . ',%')->fetchAll('id');
        $roles = array_keys($roles);

        $this->submitter['account'] = $this->app->user->account;
        $this->submitter['depts']   = $depts;
        $this->submitter['roles']   = $roles;
    }

    foreach($conditions as $condition)
    {
        if($condition->type == 'user')
        {
            if($condition->selectType == 'account')
            {
                if(in_array('systemUser', $condition->users)) return true;
                if(in_array($this->app->user->account, $condition->users)) return true;
            }
            else if($condition->selectType == 'dept')
            {
                foreach($this->submitter['depts'] as $dept)
                {
                    if(in_array($dept, $condition->depts)) return true;
                }
            }
            else if($condition->selectType == 'role')
            {
                foreach($this->submitter['roles'] as $role)
                {
                    if(in_array($role, $condition->roles)) return true;
                }
            }
        }
    }

    return false;
}
