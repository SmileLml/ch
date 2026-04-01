<?php
/**
 * Get related data by actions.
 *
 * @param  array    $actions
 * @access public
 * @return array
 */
public function getRelatedDataByActions($actions)
{
    $this->loadModel('user');

    $objectNames     = array();
    $relatedProjects = array();
    $requirements    = array();
    $objectTypes     = array();

    foreach($actions as $object) $objectTypes[$object->objectType][$object->objectID] = $object->objectID;
    foreach($objectTypes as $objectType => $objectIdList)
    {
        if(!isset($this->config->objectTables[$objectType]) and $objectType != 'makeup') continue;    // If no defination for this type, omit it.

        $table = $objectType == 'makeup' ? '`' . $this->config->db->prefix . 'overtime`' : $this->config->objectTables[$objectType];
        $field = zget($this->config->action->objectNameFields, $objectType, '');
        if(empty($field)) continue;

        if($table != TABLE_TODO)
        {
            $objectName     = array();
            $relatedProject = array();
            if(strpos(",{$this->config->action->needGetProjectType},", ",{$objectType},") !== false)
            {
                $objectInfo = $this->dao->select("id, project, `$field` AS name")->from($table)->where('id')->in($objectIdList)->fetchAll();
                if($objectType == 'gapanalysis') $users = $this->user->getPairs('noletter');
                foreach($objectInfo as $object)
                {
                    $objectName[$object->id]     = $objectType == 'gapanalysis' ? zget($users, $object->name) : $object->name;
                    $relatedProject[$object->id] = $object->project;
                }
            }
            elseif($objectType == 'project' or $objectType == 'execution')
            {
                $objectInfo = $this->dao->select("id, project, `$field` AS name")->from($table)->where('id')->in($objectIdList)->fetchAll();
                foreach($objectInfo as $object)
                {
                    $objectName[$object->id]     = $object->name;
                    $relatedProject[$object->id] = $object->project > 0 ? $object->project : $object->id;
                }
            }
            elseif($objectType == 'story')
            {
                $objectInfo = $this->dao->select('id,title,type')->from($table)->where('id')->in($objectIdList)->fetchAll();
                foreach($objectInfo as $object)
                {
                    $objectName[$object->id] = $object->title;
                    if($object->type == 'requirement') $requirements[$object->id] = $object->id;
                }
            }
            elseif($objectType == 'reviewcl')
            {
                $objectInfo = $this->dao->select('id,title')->from($table)->where('id')->in($objectIdList)->fetchAll();
                foreach($objectInfo as $object) $objectName[$object->id] = $object->title;
            }
            elseif($objectType == 'team')
            {
                $objectInfo = $this->dao->select('id,team,type')->from(TABLE_PROJECT)->where('id')->in($objectIdList)->fetchAll();
                foreach($objectInfo as $object)
                {
                    $objectName[$object->id] = $object->team;
                    if($object->type == 'project') $relatedProject[$object->id] = $object->id;
                }
            }
            elseif($objectType == 'stakeholder')
            {
                $objectName = $this->dao->select("t1.id, t2.realname")->from($table)->alias('t1')
                    ->leftJoin(TABLE_USER)->alias('t2')->on("t1.`$field` = t2.account")
                    ->where('t1.id')->in($objectIdList)
                    ->fetchPairs();
            }
            elseif($objectType == 'branch')
            {
                $this->app->loadLang('branch');
                $objectName = $this->dao->select("id,name")->from(TABLE_BRANCH)->where('id')->in($objectIdList)->fetchPairs();
                if(in_array(BRANCH_MAIN, $objectIdList)) $objectName[BRANCH_MAIN] = $this->lang->branch->main;
            }
            elseif($objectType == 'privlang')
            {
                $objectName = $this->dao->select("objectID AS id, `$field` AS name")->from($table)->where('objectID')->in($objectIdList)->andWhere('objectType')->eq('priv')->fetchPairs();
            }
            else
            {
                $objectName = $this->dao->select("id, `$field` AS name")->from($table)->where('id')->in($objectIdList)->fetchPairs();
            }

            $objectNames[$objectType]     = $objectName;
            $relatedProjects[$objectType] = $relatedProject;
        }
        else
        {
            $todos = $this->dao->select("id, $field AS name, account, private, type, idvalue")->from($table)->where('id')->in($objectIdList)->fetchAll('id');
            foreach($todos as $id => $todo)
            {
                if($todo->type == 'task') $todo->name = $this->dao->findById($todo->idvalue)->from(TABLE_TASK)->fetch('name');
                if($todo->type == 'bug')  $todo->name = $this->dao->findById($todo->idvalue)->from(TABLE_BUG)->fetch('title');

                $objectNames[$objectType][$id] = $todo->name;
                if($todo->private == 1 and $todo->account != $this->app->user->account) $objectNames[$objectType][$id] = $this->lang->todo->thisIsPrivate;
            }
        }
    }
    $objectNames['user'][0] = 'guest';    // Add guest account.

    $relatedData['objectNames']     = $objectNames;
    $relatedData['relatedProjects'] = $relatedProjects;
    $relatedData['requirements']    = $requirements;
    return $relatedData;
}

/**
 * Get deleted objects by search.
 *
 * @param  string $objectType
 * @param  string $type all|hidden
 * @param  int    $queryID
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getTrashesBySearch($objectType, $type, $queryID, $orderBy, $pager = null)
{
    if($objectType == 'all') return array();
    if($queryID and $queryID != 'myQueryID')
    {
        $query = $this->loadModel('search')->getQuery($queryID);
        if($query)
        {
            $this->session->set('trashQuery', $query->sql);
            $this->session->set('trashForm', $query->form);
        }
        else
        {
            $this->session->set('trashQuery', ' 1 = 1');
        }
    }
    else
    {
        if($this->session->trashQuery == false) $this->session->set('trashQuery', ' 1 = 1');
    }

    $extra      = $type == 'hidden' ? self::BE_HIDDEN : self::CAN_UNDELETED;
    $trashQuery = $this->session->trashQuery;
    $trashQuery = str_replace(array('`objectID`', '`actor`', '`date`'), array('t1.`objectID`', 't1.`actor`', 't1.`date`'), $trashQuery);
    $table      = $this->config->objectTables[$objectType];
    $nameField  = isset($this->config->action->objectNameFields[$objectType]) ? 't2.' . '`' . $this->config->action->objectNameFields[$objectType] . '`' : '';

    if($nameField) $trashQuery = preg_replace("/`objectName`/", $nameField, $trashQuery);

    if($objectType != 'pipeline')
    {
        $trashes = $this->dao->select("t1.*, $nameField as objectName")->from(TABLE_ACTION)->alias('t1')
            ->leftJoin($table)->alias('t2')->on('t1.objectID=t2.id')
            ->where('t1.action')->eq('deleted')
            ->andWhere($trashQuery)
            ->andWhere('t1.extra')->eq($extra)
            ->andWhere('t1.vision')->eq($this->config->vision)
            ->beginIF($objectType != 'all')->andWhere('t1.objectType')->eq($objectType)->fi()
            ->andWhere('t1.objectType')->notin('chproject,requirement,story')
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('objectID');
    }
    else
    {
        $trashes = $this->dao->select("t1.*, t1.objectType as type, t2.name as objectName, t2.type as objectType")->from(TABLE_ACTION)->alias('t1')
            ->leftJoin(TABLE_PIPELINE)->alias('t2')->on('t1.objectID=t2.id')
            ->where('t1.action')->eq('deleted')
            ->andWhere($trashQuery)
            ->andWhere('t1.extra')->eq($extra)
            ->andWhere('t1.vision')->eq($this->config->vision)
            ->andWhere('t1.objectType')->ne('chproject')
            ->andWhere('(t2.type')->eq('gitlab')
            ->orWhere('t2.type')->eq('jenkins')
            ->markRight(1)
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('objectID');
    }

    return $trashes;
}

/**
 * Log histories for an action.
 *
 * @param  int    $actionID
 * @param  array  $changes
 * @access public
 * @return void
 */
public function logHistory($actionID, $changes)
{
    if(empty($actionID)) return false;
    foreach($changes as $change)
    {
        if(is_object($change))
        {
            $change->action = $actionID;
        }
        else
        {
            $change['action'] = $actionID;
        }

        $this->dao->insert(TABLE_HISTORY)->data($change)->exec();

        if(is_array($change)) $change = (object)$change;
        if($change->field == 'status' || $change->field == 'stage')
        {
            $action = $this->getById($actionID);
            if($action->objectType == 'story')
            {
                if($change->field == 'status') $this->loadModel('action')->create('story', $action->objectID, 'changestatus' . $change->new);
                if($change->field == 'stage')  $this->loadModel('action')->create('story', $action->objectID, 'changestage' . $change->new);
            }
        }
    }

    if(isset($this->session->callbackActionList[$actionID]))
    {
        $callbackMethod = $this->session->callbackActionList[$actionID];
        unset($this->session->callbackActionList[$actionID]);
        if(method_exists($this, $callbackMethod)) call_user_func_array(array($this, $callbackMethod), array($actionID));
    }
}
/**
     * Print changes of every action.
     *
     * @param  string    $objectType
     * @param  array     $histories
     * @param  bool      $canChangeTag
     * @access public
     * @return void
     */
    public function printChanges($objectType, $histories, $canChangeTag = true)
    {
        $this->loadModel('workflowaction');

        if($objectType == 'business' or $objectType == 'projectapproval')
        {
            $fields = $this->workflowaction->getFields($objectType, 'view');

            if($objectType == 'business') $fields['project']->options = $this->loadModel('demand')->getBusinessProject(true);
        }

        if(empty($histories)) return;

        $maxLength            = 0;          // The max length of fields names.
        $historiesWithDiff    = array();    // To save histories without diff info.
        $historiesWithoutDiff = array();    // To save histories with diff info.
        /* Diff histories by hasing diff info or not. Thus we can to make sure the field with diff show at last. */
        foreach($histories as $history)
        {
            $fieldName = $history->field;
            $history->fieldLabel = (isset($this->lang->$objectType) && isset($this->lang->$objectType->$fieldName)) ? $this->lang->$objectType->$fieldName : $fieldName;
            if($objectType == 'module') $history->fieldLabel = $this->lang->tree->$fieldName;
            if($fieldName == 'fileName') $history->fieldLabel = $this->lang->file->$fieldName;

            if($objectType == 'business' or $objectType == 'projectapproval')
            {
                if(in_array($fields[$history->field]->control, array('select', 'radio', 'multi-select')) && !empty($fields[$history->field]->options))
                {
                    if($fields[$history->field]->control == 'multi-select')
                    {
                        $history->old = explode(',', $history->old);
                        $newString = '';
                        foreach($history->old as $key => $value) $newString .= $fields[$history->field]->options[$value] . ',';
                        $history->old = rtrim($newString, ',');

                        $history->new = explode(',', $history->new);
                        $newString = '';
                        foreach($history->new as $key => $value) $newString .= $fields[$history->field]->options[$value] . ',';
                        $history->new = rtrim($newString, ',');
                    }
                    else
                    {
                        $history->old = $fields[$history->field]->options[$history->old];

                        if($history->field == 'reviewStatus' && $history->new == 'pass' && $objectType == 'projectapproval')
                        {
                            $statusHistory = $this->dao->select('new')->from(TABLE_HISTORY)->where('action')->eq($history->action)->andWhere('field')->eq('status')->fetch('new');
                            if($statusHistory == 'toBeEvaluated') $history->new = $this->lang->action->projectapprovalStatusList['firstPass'];
                            if($statusHistory != 'toBeEvaluated') $history->new = $this->lang->action->projectapprovalStatusList['approvedPass'];
                        }
                        else
                        {
                            $history->new = $fields[$history->field]->options[$history->new];
                        }

                    }
                }
            }
            if($objectType == 'story' && $history->field == 'status')
            {
                $this->loadModel('story');
                $history->old = $this->lang->story->statusList[$history->old];
                $history->new = $this->lang->story->statusList[$history->new];
            }

            if(($length = strlen($history->fieldLabel)) > $maxLength) $maxLength = $length;
            $history->diff ? $historiesWithDiff[] = $history : $historiesWithoutDiff[] = $history;
        }
        $histories = array_merge($historiesWithoutDiff, $historiesWithDiff);

        foreach($histories as $history)
        {
            $history->fieldLabel = str_pad($history->fieldLabel, $maxLength, $this->lang->action->label->space);
            if($history->diff != '')
            {
                $history->diff      = str_replace(array('<ins>', '</ins>', '<del>', '</del>'), array('[ins]', '[/ins]', '[del]', '[/del]'), $history->diff);
                $history->diff      = ($history->field != 'subversion' and $history->field != 'git') ? htmlSpecialString($history->diff) : $history->diff;   // Keep the diff link.
                $history->diff      = str_replace(array('[ins]', '[/ins]', '[del]', '[/del]'), array('<ins>', '</ins>', '<del>', '</del>'), $history->diff);
                $history->diff      = nl2br($history->diff);
                $history->noTagDiff = $canChangeTag ? preg_replace('/&lt;\/?([a-z][a-z0-9]*)[^\/]*\/?&gt;/Ui', '', $history->diff) : '';
                printf($this->lang->action->desc->diff2, $history->fieldLabel, $history->noTagDiff, $history->diff);
            }
            else
            {
                printf($this->lang->action->desc->diff1, $history->fieldLabel, $history->old, $history->new);
            }
        }
    }
