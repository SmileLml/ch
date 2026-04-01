<?php

/**
 * Export by id.
 *
 * @param  int    $id
 * @access public
 * @return array
 */
public function export($id)
{
    $workFlow = $this->dao->select('*')->from(TABLE_WORKFLOW)->where('id')->eq($id)->fetch();
    if(!$workFlow) die('工作流资源不存在');

    /* 获取当前工作流的创建sql。 */
    $flowTable      = $workFlow->table;
    $getCreateTableSql = "SHOW CREATE TABLE `{$workFlow->table}`";

    global $dbh;
    $stmt   = $dbh->query($getCreateTableSql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $createTableSql = $result['Create Table'] . ';';

    /* 获取当前工作流的字段。 */
    $fields = $this->dao->select("*")->from(TABLE_WORKFLOWFIELD)
        ->where('module')->eq($workFlow->module)
        ->fetchAll();
    foreach($fields as $field)
    {
        if($field->control == 'select' && is_numeric($field->options))
        {
            $field->dataSourceCode = $this->dao->select('code')->from(TABLE_WORKFLOWDATASOURCE)->where('id')->eq($field->options)->fetch('code');
        }
    }
    $fields = array_map(function($field){ unset($field->id); return $field;}, $fields);

    /*  获取当前工作流的字段动作。 */
    $actions = $this->dao->select('*')->from(TABLE_WORKFLOWACTION)
        ->where('module')->eq($workFlow->module)
        ->andWhere('vision')->eq('rnd')
        ->fetchAll();
    $actions = array_map(function($action){ unset($action->id); return $action;}, $actions);
    unset($workFlow->actions);

    /*  获取当前工作流的标签。 */
    $labels = $this->dao->select("*")->from(TABLE_WORKFLOWLABEL)
        ->where('module')->eq($workFlow->module)
        ->fetchAll();
    $labels = array_map(function($label){ unset($label->id); return $label;}, $labels);

    /* 获取当前工作流的布局。 */
    $layouts = $this->dao->select("*")->from(TABLE_WORKFLOWLAYOUT)
        ->where('module')->eq($workFlow->module)
        ->andWhere('vision')->eq('rnd')
        ->fetchAll();
    $layouts = array_map(function($layout){ unset($layout->id); return $layout;}, $layouts);

    /* 获取当前工作流的关联数据。 */
    $linkDatas = $this->dao->select("*")->from(TABLE_WORKFLOWLINKDATA)
        ->where('objectType')->eq($workFlow->module)
        ->fetchAll();
    $linkDatas = array_map(function($linkData){ unset($linkData->id); return $linkData;}, $linkDatas);

    /* 获取当前工作流的联动。 */
    $relations = $this->dao->select("*")->from(TABLE_WORKFLOWRELATION)
        ->where('prev')->eq($workFlow->module)
        ->fetchAll();
    $relations = array_map(function($relation){ unset($relation->id); return $relation;}, $relations);

    /* 获取当前工作流的联动布局。 */
    $relationLayouts = $this->dao->select("*")->from(TABLE_WORKFLOWRELATION)
        ->where('prev')->eq($workFlow->module)
        ->fetchAll();
    $relationLayouts = array_map(function($relationLayout){ unset($relationLayout->id); return $relationLayout;}, $relationLayouts);

    /* 获取当前工作流的报表设计。 */
    $reports = $this->dao->select("*")->from(TABLE_WORKFLOWREPORT)
        ->where('module')->eq($workFlow->module)
        ->fetchAll();
    $reports = array_map(function($report){ unset($report->id); return $report;}, $reports);

    /* 获取当前工作流的sql相关。 */
    $sqls = $this->dao->select("*")->from(TABLE_WORKFLOWSQL)
        ->where('module')->eq($workFlow->module)
        ->fetchAll();
    $sqls = array_map(function($sql){ unset($sql->id); return $sql;}, $sqls);

    /* 获取当前工作流的版本。 */
    $versions = $this->dao->select("*")->from(TABLE_WORKFLOWVERSION)
        ->where('module')->eq($workFlow->module)
        ->fetchAll();
    $versions = array_map(function($version){ unset($version->id); return $version;}, $versions);

    unset($workFlow->id);
    unset($workFlow->positionModule);

    /* 获取工作流字表。 */
    $childTables = $this->dao->select('*')->from(TABLE_WORKFLOW)
        ->where('type')->eq('table')
        ->andWhere('parent')->eq($workFlow->module)
        ->fetchAll();
    $childTables = array_map(function($childTable){ unset($childTable->id); return $childTable;}, $childTables);

    /* 获取工作流sql。 */
    $sqlRows = $this->dao->select('*')->from(TABLE_WORKFLOWSQL)
        ->where('module')->eq($workFlow->module)
        ->fetchAll();
    $sqlRows = array_map(function($sqlRow){ unset($sqlRow->id); return $sqlRow;}, $sqlRows);

    $createSql            = array('command' => 'create_table',       'table' =>  $workFlow->table,        'sql'  => $createTableSql);
    $workFlowConfig       = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOW',         'rows' => array($workFlow), 'mode' => 'mainTable');
    $fieldConfig          = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWFIELD',    'rows' => $fields);
    $actionConfig         = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWACTION',   'rows' => $actions);
    $labelConfig          = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWLABEL',    'rows' => $labels);
    $layoutConfig         = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWLAYOUT',   'rows' => $layouts);
    $linkDataConfig       = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWLINKDATA', 'rows' => $linkDatas);
    $relationConfig       = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWRELATION', 'rows' => $relations);
    $relationLayoutConfig = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWRELATION', 'rows' => $relationLayouts);
    $reportConfig         = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWREPORT',   'rows' => $reports);
    $sqlConfig            = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWSQL',      'rows' => $sqls);
    $versionConfig        = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWVERSION',  'rows' => $versions);
    $childTablesConfig    = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOW',         'rows' => $childTables, 'mode' => 'childTable');
    $sqlConfig            = array('command' => 'insert_data',        'table' => 'TABLE_WORKFLOWSQL',      'rows' => $sqlRows);

    $configList = array('createSql', 'workFlowConfig', 'fieldConfig', 'actionConfig', 'labelConfig', 'layoutConfig', 'linkDataConfig', 'relationConfig', 'relationLayoutConfig', 'reportConfig', 'sqlConfig', 'versionConfig', 'childTablesConfig');

    /* 获取字表的创建sql。 */
    $createChildTableSql = array();
    if(count($childTables) >= 1)
    {
        $i = 1;
        foreach($childTables as $childTable)
        {
            $getCreateTableSql   = "SHOW CREATE TABLE `{$childTable->table}`";
            $stmt                = $dbh->query($getCreateTableSql);
            $result              = $stmt->fetch(PDO::FETCH_ASSOC);

            /* 处理字表的建表语句。 */
            $sqlCreateParamName    = $this->getSqlCreateParamName($i);
            $createChildTableSql   = $result['Create Table'] . ';';
            ${$sqlCreateParamName} = array('command' => 'create_child_table', 'table' => $childTable->table, 'sql' => $createChildTableSql);
            array_push($configList, $sqlCreateParamName);

            /* 处理字表的字段信息。 */
            $childParamName = $this->getChildParamName($i);
            $childFields = $this->dao->select('*')->from(TABLE_WORKFLOWFIELD)
                ->where('module')->eq($childTable->module)
                ->fetchAll();
            $childFields = array_map(function($childField) { unset($childField->id); return $childField; }, $childFields);
            ${$childParamName} = array('command' => 'insert_child_data', 'table' => 'TABLE_WORKFLOWFIELD', 'rows' => $childFields);
            array_push($configList, $childParamName);

            /* 处理字表的获取数据sql相关。 */
            $childSqlParamName = $this->getChildSqlParamName($i);
            $sqlRows = $this->dao->select('*')->from(TABLE_WORKFLOWSQL)
                ->where('module')->eq($childTable->module)
                ->fetchAll();
            $sqlRows = array_map(function($sqlRow){ unset($sqlRow->id); return $sqlRow;}, $sqlRows);
            ${$childSqlParamName} = array('command' => 'insert_data', 'table' => 'TABLE_WORKFLOWSQL', 'rows' => $sqlRows);
            array_push($configList, $childSqlParamName);

            /* 获取字表的界面设置信息。 */
            $childLayoutParamName = $this->getChildLayoutParamName($i);
            $childLayouts = $this->dao->select('*')->from(TABLE_WORKFLOWLAYOUT)
                ->where('module')->eq($childTable->module)
                ->andWhere('vision')->eq('rnd')
                ->fetchAll();
            $childLayouts = array_map(function($childLayout){ unset($childLayout->id); return $childLayout;}, $childLayouts);
            ${$childLayoutParamName} = array('command' => 'insert_data', 'table' => 'TABLE_WORKFLOWLAYOUT', 'rows' => $childLayouts);
            array_push($configList, $childLayoutParamName);

            $i++;
        }
    }

    return compact(...$configList);
}

/**
 * Get sql create param name.
 *
 * @param  int    $i
 * @access public
 * @return string
 */
public function getSqlCreateParamName($i)
{
    return 'childTableCreateSql' . $i;
}

/**
 * Get child param name.
 *
 * @param  int    $i
 * @access public
 * @return string
 */
public function getChildParamName($i)
{
    return 'childField' . $i;
}

/**
 * Get child sql param name.
 *
 * @param  int    $i
 * @access public
 * @return string
 */
public function getChildSqlParamName($i)
{
    return 'childSql' . $i;
}

/**
 * Get child layout param name.
 *
 * @param  int    $i
 * @access public
 * @return string
 */
public function getChildLayoutParamName($i)
{
    return 'childLayout' . $i;
}

/**
 * Get child data param name.
 *
 * @param  int    $i
 * @access public
 * @return string
 */
public function getChildDataParamName($id)
{
    return 'childData' . $i;
}

/**
 * Export service.
 *
 * @param  int    $id
 * @access public
 * @return string
 */
public function exportSerivce($id)
{
    $configs = $this->export($id);

    $workFlowConfigPath = $this->getWorkFlowConfigPath();
    $workFlowConfigFile = $workFlowConfigPath . 'workflow_' . $id . '_' . date('YmdHis') . '.php';

    if(!is_dir($workFlowConfigPath)) mkdir($workFlowConfigPath, 0777, true);
    file_put_contents($workFlowConfigFile, json_encode($configs, JSON_PRETTY_PRINT));

    return $workFlowConfigFile;
}

/**
 * Get work flow config path.
 *
 * @access public
 * @return string
 */
public function getWorkFlowConfigPath()
{
    return $this->app->getTmpRoot() . 'workflow' . DS;
}

/**
 * Get work flow file by name.
 *
 * @param  string $name
 * @access public
 * @return string
 */
public function getWorkFlowFileByName($name)
{
    return $this->getWorkFlowConfigPath() . $name;
}

/**
 * 根据表名获取表字段信息。
 *
 * @param  string $table
 * @access public
 * @return array
 */
public function showDescColumns($table)
{
    if(!$this->checkTableExist($table)) return array();

    $columns = $this->dao->query("show columns from `{$table}`")->fetchAll();
    $columns = array_filter($columns, function($column){ return $column->Field != 'id'; });

    return array_map(function($column){ return array('field' => $column->Field, 'type' => $column->Type, 'default' => $column->Default); }, $columns);
}

/**
 * Check table field.
 *
 * @param  string $module
 * @param  string $table
 * @param  array  $buildin
 * @param  array  $newFields
 * @access public
 * @return array
 */
public function checkTableField($module, $table, $buildin, $newFields = array())
{
    $oldFields = $this->showDescColumns($table);
    $oldFields = array_filter($oldFields, function($field){ return $field->Filed !== 'id'; });
    $newFields = array_filter($newFields, function($field){ return $field['field'] !== 'id'; });
    $newFields = array_map(function($column){ return array('field' => $column['field'], 'type' => $column['type'] . ($column['length'] ? "({$column['length']})" : ''), 'name' => $column['name'], 'default' => $column['default']);}, $newFields);

    $mapping = array_combine(array_column($oldFields, 'field'), array_map(function($column) { return $column['field'] . '/' . $column['type']; }, $oldFields));
    return array($newFields, $mapping);
}

/**
 * Check table exist.
 *
 * @param  string $table
 * @access public
 * @return bool
 */
public function checkTableExist($table)
{
    return $this->dao->query("show tables like '{$table}'")->rowCount() > 0;
}

/**
 * Get work flow file.
 *
 * @param  string $name
 * @access public
 * @return array
 */
public function getWorkFlowFile($name)
{
    $workFlowConfigFile = $this->getWorkFlowFileByName($name);
    if(!is_file($workFlowConfigFile)) die('工作流配置文件不存在');

    return json_decode(file_get_contents($workFlowConfigFile), true);
}

/**
 * Get work flow by config.
 *
 * @param  array  $configs
 * @access public
 */
public function getWorkFlowByConfig(&$configs)
{
    $workFlowConfig = $configs['workFlowConfig'];
    return empty($workFlowConfig) ? array() : current($workFlowConfig['rows']);
}

/**
 * Get child tables config by config.
 *
 * @param  array  $configs
 * @access public
 */
public function getChildTablesConfigByConfig(&$configs)
{
    $childTablesConfig = $configs['childTablesConfig'];
    return empty($childTablesConfig) ? array() : $childTablesConfig['rows'];
}

/**
 * Get table columns paris.
 *
 * @param  string $table
 * @access public
 */
public function getTableColumns($table)
{
    if(!$this->checkTableExist($table)) return array();

    $columns = $this->dao->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
    return array('' => '') + array_combine(array_column($columns, 'Field'), array_column($columns, 'Field'));
}

/**
 * Import by user operate.
 *
 * @param  array  $configs
 * @param  object $mapping
 * @param  array  $tableConfigs
 * @access public
 * @return array
 */
public function importByUserOperate($configs, $mapping, $tableConfigs)
{
    $workflow = $this->getWorkFlowByConfig($configs);
    /* 先判断表是否存在。 */
    foreach($tableConfigs as $key => $tableConfig)
    {
        list($table, $module, $mode) = $tableConfig;
        if($this->checkTableExist($table))
        {
            $createCommand = $mode == 'main' ? 'create_table'  : 'create_child_table';
            $configs = array_filter($configs, function($config) use($createCommand, $table) { return !($config['command'] === $createCommand && $config['table'] === $table); });

            $filedConfigs = $key == 0 ? $configs['fieldConfig'] : $configs[$this->getChildParamName($key)];
            list($newField, $_) = $this->checkTableField($module, $table, $workflow['buildin'], $filedConfigs['rows']);
            $configs = array_merge($configs, $this->buildTableOpreateCommand($table, $newField, $mapping->mapping[$table]));
        }
    }

    $deleteLayout = array();
    $layouts      = array();
    $actionMapping = $mapping->actionMapping;
    $allLayouts    = array_merge(...array_values(array_map(function($row){ return $row['rows'];}, array_filter($configs, function($config){ return $config['table'] === 'TABLE_WORKFLOWLAYOUT'; }))));
    $layoutTable   = TABLE_WORKFLOWLAYOUT;
    foreach($actionMapping as $action => $result)
    {
        if(!$result) continue;

        $deleteLayout[] = array('command' => 'delete_field_layout', 'table' => 'TABLE_WORKFLOWLAYOUT', 'sql' => "DELETE FROM {$layoutTable} WHERE `module` = '{$workflow['module']}' AND `action` = '{$action}'");

        $actionLayouts = array_filter($allLayouts, function($layout) use($action){ return $layout['action'] == $action; });

        foreach($actionLayouts as $layout)
        {
            if(strpos($layout->field, 'sub_') !== false)
            {
                $module = str_replace('sub_', '', $layout->field);
                $subLayouts = array_filter($actionLayouts, function($subLayout) use($module, $action){ return $subLayout->module == $module && $subLayout->action == $action; });

                $layouts = array_merge($layouts, $subLayouts);
            }
            else
            {
                $layouts[] = $layout;
            }
        }
    }
    //移除所有的布局相关数据
    $configs = array_filter($configs, function($config){ return $config['table'] !== 'TABLE_WORKFLOWLAYOUT' && $config['command'] !== 'delete_field_layout'; });
    $configs['layoutConfig'] = array('command' => 'insert_data', 'table' => 'TABLE_WORKFLOWLAYOUT', 'rows' => $layouts);
    $configs = array_merge($configs, $deleteLayout);

    //处理field的数据源问题
    $fields = &$configs['fieldConfig']['rows'];
    $dataSourceMapping = $this->dao->select('code, id')->from(TABLE_WORKFLOWDATASOURCE)->fetchPairs('code');

    foreach($fields as &$field)
    {
        if(isset($field['dataSourceCode']))
        {
            if($field['dataSourceCode']) $field['options'] = $dataSourceMapping[$field['dataSourceCode']];

            unset($field['dataSourceCode']);
        }
    }

    return $this->importByCommands($configs);
}

/**
 * Build table opreate command.
 *
 * @param  string $table
 * @param  array  $newField
 * @param  array  $mapping
 * @access public
 * @return array
 */
public function buildTableOpreateCommand($table, $newField, $mapping = array())
{
    $commands = array();
    $alterModifyTempalte = "ALTER TABLE `{$table}` CHANGE `%s` `%s` %s";
    $alterAddTempalte    = "ALTER TABLE `{$table}` ADD    `%s` %s";
    $dropColumnTempalte  = "ALTER TABLE `{$table}` DROP   `%s`";

    $oldFields = $this->showDescColumns($table);
    $oldFields = array_combine(array_column($oldFields, 'field'), array_map(function($column) { return array($column['field'], $column['type'], $column['default']); }, $oldFields));

    foreach($newField as $field)
    {
        $typeList = explode('(', $field['type']);
        list($type, $length) = array_map(function($item){ return trim(trim($item, ')'), '('); }, $typeList);

        $result = $this->compareFields($field['field'], array($type, $length), $oldFields[$field['field']] ?? array(), $mapping);
        if(!$result) continue;

        if($mapping[$field['field']])
        {
            $mappingField = $mapping[$field['field']] ? : $field['field'];
            $sql = sprintf($alterModifyTempalte, $field['field'], $mappingField, $type . ($length ? "({$length})" : ''));
        }
        else
        {
            $sql = sprintf($alterAddTempalte, $field['field'], $type . ($length ? "({$length})" : ''));
        }

        if($field['default']) $sql .= ' DEFAULT ' . "'" . $field['default'] . "'";

        $commands[] = array('command' => 'modify_column_sql', 'table' => $table, 'sql' => $sql);
    }

    return $commands;
}

/**
 * Compare fields.
 *
 * @param  string $fieldName
 * @param  array  $new
 * @param  array  $old
 * @param  array  $mapping
 * @access public
 * @return bool
 */
public function compareFields($fieldName, $new, $old, $mapping)
{
    if(empty($old))
    {
        return true;
    }
    else
    {
        if($mapping[$fieldName] !== $fieldName) return true;

        $oldType = $old[1];

        list($newType, $newLength) = $new;
        list($oldType, $oldLength) = array_map(function($item){ return trim(trim($item, ')'), '('); }, explode('(', $oldType));

        if($newType == 'enum' || $oldType == 'enum') return false;
        if($newType == 'mediumint' and strstr($oldType, $newType)) return false;

        if($newType != $oldType || $oldLength != $newLength)
        {
            return true;
        }
    }

    return false;
}

/**
 * Import by commands.
 *
 * @param  array  $configs
 * @access public
 * @return array
 */
public function importByCommands($configs)
{
    $createTableConfigs  = array_filter($configs, function($config){ return $config['command'] === 'create_table' || $config['command'] == 'create_child_table'; });
    $deleteColumnConfigs = array_filter($configs, function($config){ return $config['command'] === 'delete_column_sql' || $config['command'] == 'delete_field_layout'; });
    $modifyColumnConfigs = array_filter($configs, function($config){ return $config['command'] === 'modify_column_sql'; });
    $addColumnConfigs    = array_filter($configs, function($config){ return $config['command'] === 'add_column_sql'; });
    $insertDataConfigs   = array_filter($configs, function($config){ return $config['command'] === 'insert_data' || $config['command'] == 'insert_child_data'; });
    $insertChildConfigs  = array_filter($configs, function($config){ return $config['command'] === 'insert_child_row'; });
    $deleteTableConfigs  = array_filter($configs, function($config){ return $config['command'] === 'delete_table_field'; });

    $sqlConfigs = array('createTableConfigs', 'deleteColumnConfigs', 'modifyColumnConfigs', 'addColumnConfigs');
    foreach($sqlConfigs as $sqlConfig)
    {
        foreach(${$sqlConfig} as $config) $this->dao->exec($config['sql']);
    }

    $this->dao->begin();
    try
    {
        foreach($deleteTableConfigs as $config)
        {
            if(empty($config['rows'])) continue;

            foreach($config['rows'] as $row)
            {
                $this->dao->delete()->from(constant($config['table']))
                    ->where('module')->eq($row['module'])
                    ->andWhere('field')->eq($row['field'])
                    ->exec();
            }
        }

        foreach($insertDataConfigs as $config)
        {
            if(empty($config['rows'])) continue;

            if($config['table'] === 'TABLE_WORKFLOWLABEL')
            {
                $isDeleted = false;
                foreach($config['rows'] as $row)
                {
                    $isExists = $this->dao->select('*')->from(constant($config['table']))->where('module')->eq($row['module'])->andWhere('action')->eq($row['action'])->andWhere('code')->eq($row['code'])->fetch();
                    if(empty($isExists))
                    {
                        $this->dao->insert(constant($config['table']))->data($row)->exec();
                    }
                    else
                    {
                        $this->dao->update(constant($config['table']))->data($row)->where('module')->eq($row['module'])->andWhere('action')->eq($row['action'])->andWhere('code')->eq($row['code'])->exec();
                    }
                }
            }
            else
            {
                foreach($config['rows'] as $row)
                {
                    $sql = $this->dao->insert(constant($config['table']))->data($row)->get();
                    $sql .= ' ON DUPLICATE KEY UPDATE ';
                    foreach($row as $key => $value) $sql .= "`{$key}` = VALUES(`{$key}`),";
                    $sql = substr($sql, 0, -1);

                    $this->dao->exec($sql);
                }
            }
        }

        $this->dao->commit();
        return array(true, '');
    }
    catch(Exception $e)
    {
        $this->dao->rollback();
        return array(false, $e->getMessage());
    }
}
