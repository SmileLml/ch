<?php
/**
 * Save layout.
 *
 * @param  string $module
 * @param  string $action
 * @access public
 * @return bool
 */
public function saveLayout($module, $action)
{
    $fields = $this->loadModel('workflowfield')->getList($module);

    foreach($this->post->show as $field => $show)
    {
        $defaultValue = isset($this->post->defaultValue[$field]) ? $this->post->defaultValue[$field] : '';

        if($defaultValue)
        {
            $fieldInfo = $fields[$field];
            $fieldInfo->default = $defaultValue;
            $result = $this->workflowfield->checkDefaultValue($fieldInfo);

            if(is_array($result) && $result['result'] == 'fail' && is_array($result['message'])) dao::$errors["defaultValue$field"] = $result['message']['default'];
            if(is_array($result) && $result['result'] == 'fail' && is_string($result['message'])) dao::$errors["defaultValue$field"] = $result['message'];
        }
    }

    if(!empty(dao::$errors)) return false;

    $this->dao->delete()->from(TABLE_WORKFLOWLAYOUT)
        ->where('module')->eq($module)
        ->andWhere('action')->eq($action)
        ->beginIF(!empty($this->config->vision))->andWhere('vision')->eq($this->config->vision)->fi()
        ->exec();

    $order  = 1;
    $layout = new stdclass();
    $layout->module = $module;
    $layout->action = $action;
    foreach($this->post->show as $field => $show)
    {
        if(!$show) continue;

        /* Check width validate. */
        if(isset($this->post->width[$field]) && filter_var($this->post->width[$field], FILTER_VALIDATE_INT) === false && $this->post->width[$field] != 'auto')
        {
            dao::$errors['width' . $field] = sprintf($this->lang->error->int[0], $this->lang->workflowlayout->width);
            return false;
        }

        $defaultValue = isset($this->post->defaultValue[$field]) ? $this->post->defaultValue[$field] : '';
        if(is_array($defaultValue)) $defaultValue = implode(',', array_values(array_unique(array_filter($defaultValue))));

        $summary = isset($this->post->summary[$field]) ? $this->post->summary[$field] : '';
        if(is_array($summary)) $summary = implode(',', $summary);

        $layout->field        = $field;
        $layout->order        = $order++;
        $layout->width        = (isset($this->post->width[$field]) && $this->post->width[$field] != 'auto' && $this->post->width[$field] != '') ? $this->post->width[$field] : 0;
        $layout->position     = isset($this->post->position[$field])     ? $this->post->position[$field]     : '';
        $layout->readonly     = isset($this->post->readonly[$field])     ? $this->post->readonly[$field]     : '0';
        $layout->mobileShow   = isset($this->post->mobileShow[$field])   ? $this->post->mobileShow[$field]   : '0';
        $layout->summary      = $summary;
        $layout->defaultValue = $defaultValue;
        $layout->layoutRules  = isset($this->post->layoutRules[$field]) ? implode(',', $this->post->layoutRules[$field]) : '';
        if(!empty($this->config->vision)) $layout->vision = $this->config->vision;

        $layout->colspan      = (isset($this->post->colspan[$field]) && !empty($this->post->colspan[$field]))           ? $this->post->colspan[$field]      : '1';
        $layout->titleWidth   = (isset($this->post->titleWidth[$field]) && !empty($this->post->titleWidth[$field]))     ? $this->post->titleWidth[$field]   : 'auto';
        $layout->titleColspan = (isset($this->post->titleColspan[$field]) && !empty($this->post->titleColspan[$field])) ? $this->post->titleColspan[$field] : '1';

        $this->dao->insert(TABLE_WORKFLOWLAYOUT)->data($layout)->autoCheck()->exec();
    }

    if($this->post->columns)
    {
        $action = $this->loadModel('workflowaction')->getByModuleAndAction($module, $action);

        $this->dao->update(TABLE_WORKFLOWACTION)->set('columns')->eq($this->post->columns)->where('id')->eq($action->id)->exec();
    }

    return !dao::isError();
}
