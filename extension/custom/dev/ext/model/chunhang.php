<?php

/**
 * Get All tables.
 *
 * @access public
 * @return array
 */
public function getTables()
{
    $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    $tables     = array();
    $datatables = $this->dao->showTables();

    foreach($datatables as $table)
    {
        $table = current($table);
        if(empty($this->config->db->prefix) or strpos($table, $this->config->db->prefix) !== false)
        {
            if(strpos($table, $this->config->db->prefix . 'flow_') === 0 && !in_array($table,$this->config->dev->showFlowTables)) continue;

            $subTable = substr($table, strpos($table, '_') + 1);
            $group    = zget($this->config->dev->group, $subTable, 'other');
            $tables[$group][$subTable] = $table;
        }
    }

    $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
    return $tables;
}

/**
 * Get fields of table.
 *
 * @param  string $table
 * @access public
 * @return void
 */
public function getFields($table)
{
    $module      = substr($table, strpos($table, '_') + 1);
    $aliasModule = $subLang = '';
    $this->app->loadLang($module);
    try
    {
        if(isset($this->config->dev->tableMap[$module])) $aliasModule = $this->config->dev->tableMap[$module];
        if(strpos($aliasModule, '-') !== false) list($aliasModule, $subLang) = explode('-', $aliasModule);
        if(!empty($aliasModule) and strpos($module, 'im_') === false) $this->app->loadLang($aliasModule);
    }
    catch(PDOException $e)
    {
        $this->lang->$module = new stdclass();
    }

    try
    {
        $rawFields = $this->dao->descTable($table);
    }
    catch (PDOException $e)
    {
        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->sqlError($e);
    }

    $isShowFlow = in_array('zt_' . $module, $this->config->dev->showFlowTables);

    if($isShowFlow)
    {
        $fieldsName = $this->dao->select('field,name')->from(TABLE_WORKFLOWFIELD)->where('module')->eq(str_replace('zt_flow_', "", $table))->fetchPairs('field');
    }

    foreach($rawFields as $rawField)
    {
        $firstPOS = strpos($rawField->type, '(');
        $type     = substr($rawField->type, 0, $firstPOS > 0 ? $firstPOS : strlen($rawField->type));
        $type     = str_replace(array('big', 'small', 'medium', 'tiny'), '', $type);
        $field    = array();
        $field['name'] = (isset($this->lang->$module->{$rawField->field}) and is_string($this->lang->$module->{$rawField->field})) ? sprintf($this->lang->$module->{$rawField->field}, $this->lang->dev->tableList[$module]) : '';
        if((empty($field['name']) or !is_string($field['name'])) and $aliasModule) $field['name'] = isset($this->lang->$aliasModule->{$rawField->field}) ? $this->lang->$aliasModule->{$rawField->field} : '';
        if($subLang) $field['name'] = isset($this->lang->$aliasModule->$subLang->{$rawField->field}) ? $this->lang->$aliasModule->$subLang->{$rawField->field} : $field['name'];

        if(!is_string($field['name'])) $field['name'] = '';
        if($isShowFlow && !empty($fieldsName)) $field['name'] = isset($fieldsName[$rawField->field]) ? $fieldsName[$rawField->field] : $field['name'];

        $field['null']            = $rawField->null;
        $fields[$rawField->field] = $this->setField($field, $rawField, $type, $firstPOS);
    }
    return $fields;
}
