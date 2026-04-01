<?php
class workflow extends control
{
    /**
     * Delete a flow or table.
     *
     * @param  int    $id
     * @access public
     * @return void
     */
    public function import($id = '')
    {
        $workflowPath = $this->workflow->getWorkFlowConfigPath();
        $files = scandir($workflowPath);
        if(!$files) return $this->send(array('result' => 'fail', 'message' => 'No workflow file found.'));

        if(!$id)
        {
            $files = array_filter($files, function($file){ return $file != '.' && $file != '..';});
            $this->view->files = $files;
            $this->display('workflow', 'showfiles');
            die;
        }

        $configs     = $this->workflow->getWorkFlowFile($files[$id]);
        $workflow    = $this->workflow->getWorkFlowByConfig($configs);
        $childTables = $this->workflow->getChildTablesConfigByConfig($configs);
        $allTables   = array();
        $allTables[] = array($workflow['table'], $workflow['module'], 'main');
        foreach($childTables as $childTable) $allTables[] = array($childTable['table'], $childTable['module'], 'child');

        if($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $post = fixer::input('post')->get();

            list($result, $message) = $this->workflow->importByUserOperate($configs, $post, $allTables);
            if($result) return $this->send(array('result' => 'success', 'message' => 'Import success!', 'locate' => 'back'));
            return $this->send(array('result' => 'fail', 'message' => 'Import faile: ' . $message));
        }

        //处理表字段
        $needUserOperateList = array();
        $tableColumns        = array();
        $needOperate         = false;
        foreach($allTables as $key => $value)
        {
            list($table, $module) = $value;
            if($this->workflow->checkTableExist($table)) $configs = array_filter($configs, function($config) use($table) { return !(in_array($config['command'], array('create_table', 'create_child_table')) && $config['table'] == $table);});

            $filedConfigs = $key == 0 ? $configs['fieldConfig'] : $configs[$this->workflow->getChildParamName($key)];
            list($newField, $mapping) = $this->workflow->checkTableField($module, $table, $workflow->buildin, $filedConfigs['rows']);

            $needUserOperateList[$table] = array('newField' => $newField, 'mapping' => $mapping);
        }
        $this->view->needUserOperateList = $needUserOperateList;

        //处理action的布局问题
        $actionConfig = array_filter($configs, function($config){ return $config['table'] == 'TABLE_WORKFLOWACTION';});
        $this->view->actions = isset($actionConfig['actionConfig']) ? $actionConfig['actionConfig']['rows'] : array();

        $this->display();
    }
}
