<?php
/**
 * Enable approval of a flow.
 *
 * @param  string $module
 * @access public
 * @return bool
 */
public function enableApproval($module)
{
    if(!array_filter($_POST['approvalFlow'])) return array('result' => 'fail', 'message' => array('approvalFlow' => sprintf($this->lang->error->notempty, $this->lang->workflowapproval->approvalFlow)));

    if(in_array($module, $this->config->workflow->buildin->noApproval)) return array('result' => 'fail', 'message' => $this->lang->workflowapproval->disableApproval);

    $exists = $this->checkApproval($module);
    $flow   = $this->getByModule($module);

    if(!empty($exists['fields']) || !empty($exists['actions']))
    {
        if($this->post->cover)
        {
            $this->cover($flow, $exists);
        }
        else
        {
            $message = $this->createMessage($exists);
            return array('result' => 'fail', 'coverMessage' => $message);
        }
    }

    $this->createApprovalRelation($flow);

    $this->dao->delete()->from(TABLE_APPROVALFLOWOBJECT)->where('objectType')->eq($module)->exec();

    $index = 1;
    $allApprovalActions = array();
    foreach($_POST['approvalFlow'] as $key => $value)
    {
        if(!$value) continue;

        $condition = $this->getConditions($key, $index);

        $this->createActions($flow, 'approval', $index);
        $this->createLayouts($flow, 'approval', $index);

        $this->createApprovalObject($value, $module, $condition);

        foreach($this->lang->workflowaction->approval->actions as $action => $name)
        {
            $allApprovalActions[] = $action . $index;
        }

        $index ++;
    }


    $this->dao->update(TABLE_WORKFLOW)->set('approval')->eq('enabled')->where('module')->eq($module)->exec();
    $this->dao->update(TABLE_WORKFLOWACTION)
         ->set('status')->eq('enable')
         ->where('module')->eq($module)
         ->andWhere('role')->eq('approval')
         ->andWhere('action')->notin(array_keys($this->lang->workflowaction->approval->actions))
         ->exec();

    $this->dao->delete()->from(TABLE_WORKFLOWACTION)
         ->where('module')->eq($module)
         ->andWhere('role')->eq('approval')
         ->andWhere('action')->notin($allApprovalActions)
         ->exec();

    if(dao::isError()) return array('result' => 'fail', 'message' => dao::getError());

    return array('result' => 'success');
}

public function createApprovalRelation($flow)
{
    $this->createFields($flow, 'approval', 'edit');
    $this->createLabels($flow, 'approval');
    return !dao::isError();
}

/**
 * Create approval object.
 *
 * @param  int    $approvalFlow
 * @param  string $module
 * @access public
 * @return bool
 */
public function createApprovalObject($approvalFlow, $module, $condition = null)
{
    $data = new stdclass();
    $data->flow       = $approvalFlow;
    $data->objectType = $module;
    $data->condition  = json_encode($condition);

    $this->dao->insert(TABLE_APPROVALFLOWOBJECT)->data($data)->exec();

    return !dao::isError();
}

public function getConditions($key, $actionIndex)
{
    $condition = new stdclass();
    $condition->conditionType = 'data';
    $condition->actionKey     = $actionIndex;

    if(isset($this->post->field[$key]))
    {
        foreach($this->post->field[$key] as $conditionKey => $data)
        {
            if(empty($data)) continue;

            $param = $this->post->param[$key][$conditionKey];
            if(is_array($param)) $param = implode(',', array_values(array_filter($param)));

            $field = new stdclass();
            $field->field           = $data;
            $field->operator        = $this->post->operator[$key][$conditionKey];
            $field->param           = $param;
            $field->logicalOperator = $this->post->logicalOperator[$key][$conditionKey];

            $condition->fields[] = $field;
        }
    }

    return $condition;
}

public function createActions($flow, $type = 'default', $approvalKey = 0)
{
    if(!$flow->type == 'table') return true;

    if($type == 'approval')
    {
        $existedActions = $this->dao->select('action')->from(TABLE_WORKFLOWACTION)->where('module')->eq($flow->module)->andWhere('role')->eq('approval')->fetchPairs();
    }

    $this->loadModel('action');
    $this->loadModel('workflowaction');

    $actionLang   = $this->lang->workflowaction->$type;
    $actionConfig = $this->config->workflowaction->$type;

    if($flow->buildin)
    {
        foreach(array('conditions', 'hooks', 'linkages', 'verifications') as $item)
        {
            if(empty($actionConfig->$item)) continue;
            $actionConfigItem = $actionConfig->$item;

            foreach($actionConfigItem as $code => $itemConfigs)
            {
                foreach($itemConfigs as $configIndex => $itemConfig)
                {
                    if(empty($itemConfig['fields'])) continue;
                    foreach($itemConfig['fields'] as $fieldIndex => $field)
                    {
                        if($field['field'] != 'createdBy' || empty($this->config->workflow->buildin->createdBy[$flow->module])) continue;
                        $actionConfigItem[$code][$configIndex]['fields'][$fieldIndex]['field'] = $this->config->workflow->buildin->createdBy[$flow->module];
                    }
                }
            }
            $actionConfig->$item = $actionConfigItem;
        }
    }

    $action = new stdclass();
    $action->module      = $flow->module;
    $action->conditions  = '[]';
    $action->hooks       = '[]';
    $action->linkages    = '';
    $action->createdBy   = $this->app->user->account;
    $action->createdDate = helper::now();
    $action->order       = 0;
    $action->role        = $type;
    foreach($actionLang->actions as $code => $name)
    {
        if($type == 'approval' && $approvalKey)
        {
            $actionCode = $code . $approvalKey;
            $actionName = $name . $approvalKey;
            if(isset($existedActions[$actionCode])) continue;
        }

        $action->action     = $type == 'approval' && $approvalKey ? $actionCode : $code;
        $action->name       = $type == 'approval' && $approvalKey ? $actionName : $name;
        $action->method     = zget($actionConfig->methods, $code, 'operate');
        $action->type       = zget($actionConfig->types, $code, 'single');
        $action->batchMode  = zget($actionConfig->batchModes, $code, 'same');
        $action->open       = zget($actionConfig->opens, $code, 'normal');
        $action->position   = zget($actionConfig->positions, $code, 'browseandview');
        $action->show       = zget($actionConfig->shows, $code, 'direct');
        $action->status     = zget($actionConfig->statuses, $code, 'enable');
        $action->buildin    = zget($actionConfig->buildin, $code, '0');
        $action->conditions = helper::jsonEncode(zget($actionConfig->conditions, $code, array()));
        $action->linkages   = helper::jsonEncode(zget($actionConfig->linkages, $code, array()));

        if(!empty($this->config->vision)) $action->vision = $this->config->vision;

        $this->dao->insert(TABLE_WORKFLOWACTION)->data($action)->autoCheck()->exec();

        $actionID = $this->dao->lastInsertID();
        $this->action->create('workflowaction', $actionID, 'created');

        $action->order++;
    }

    return !dao::isError();
}

/**
 * Create default layouts.
 *
 * @param  object $flow
 * @param  string $type     default | approval
 * @access public
 * @return bool
 */
public function createLayouts($flow, $type = 'default', $approvalKey = 0)
{
    if(!$flow->type == 'table') return true;

    if($type == 'approval') $existedActions = $this->dao->select('action')->from(TABLE_WORKFLOWACTION)->where('module')->eq($flow->module)->andWhere('role')->eq('approval')->fetchPairs();

    $this->loadModel('workflowlayout', 'flow');

    $layoutConfig = $this->config->workflowlayout->$type;
    $notEmptyRule = $this->loadModel('workflowrule', 'flow')->getByTypeAndRule('system', 'notempty');

    $layout = new stdclass();
    $layout->module = $flow->module;

    foreach($layoutConfig->layouts as $action => $fields)
    {
        if($type == 'approval' && $approvalKey)
        {
            $actionCode = $action . $approvalKey;
            if(isset($existedActions[$actionCode])) continue;
        }

        $layout->action = $type == 'approval' && $approvalKey ? $actionCode : $action;

        $order = 1;
        foreach($fields as $field => $options)
        {
            $layout->field        = $field;
            $layout->order        = $order++;
            $layout->defaultValue = zget($options, 'default', '');
            $layout->layoutRules  = zget($options, 'require', '', zget($notEmptyRule, 'id', ''));

            $this->dao->insert(TABLE_WORKFLOWLAYOUT)->data($layout)->exec();
        }
    }
    return !dao::isError();
}

/**
 * Check fields, actions and labels before open an approval.
 *
 * @param  string module
 * @access public
 * @return array
 */
public function checkApproval($module)
{
    $this->loadModel('workflowfield', 'flow');
    $this->loadModel('workflowaction', 'flow');
    $this->loadModel('workflowlayout', 'flow');
    $this->loadModel('workflowlabel', 'flow');
    $existFields  = array();
    $existActions = array();

    $fields = $this->dao->select('name, field, role')->from(TABLE_WORKFLOWFIELD)
        ->where('module')->eq($module)
        ->andWhere('field')->in(array_keys($this->config->workflowfield->approval->fields))
        ->fetchAll();

    foreach($fields as $field)
    {
        if($field->role == 'approval')
        {
            unset($this->lang->workflowfield->approval->fields[$field->field]);
        }
        else
        {
            $existFields[$field->field] = $field;
        }
    }

    $actions = $this->dao->select('name, action, role')->from(TABLE_WORKFLOWACTION)
        ->where('module')->eq($module)
        ->andWhere('action')->in(array_keys($this->lang->workflowaction->approval->actions))
        ->fetchAll();

    foreach($actions as $action)
    {
        if($action->role == 'approval') continue;

        $existActions[$action->action] = $action;
    }

    $label = $this->dao->select('id')->from(TABLE_WORKFLOWLABEL)
        ->where('module')->eq($module)
        ->andWhere('role')->eq('approval')
        ->andWhere('code')->eq('review')
        ->fetch();
    if($label) unset($this->lang->workflowlabel->approval->labels['review']);

    return array('fields' => $existFields, 'actions' => $existActions);
}
