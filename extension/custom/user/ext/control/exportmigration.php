<?php
helper::importControl('user');
class myuser extends user
{
    public function exportMigration($module)
    {
        $fileName = '数据模板' . $module;

        $this->config->flowLimit = 0;

        $fields       = array();
        $rows         = array();
        $listFields   = array();
        $fieldList    = array();

        $actionFields = $this->loadModel('workflowaction')->getMigrationFields($module);

        foreach($actionFields as $field)
        {
            if(!$field->show) continue;

            $fields[$field->field] = $field->name;

            for($i = 0; $i < 1000; $i++) $rows[$i][$field->field] = '';

            if(!empty($field->options) && is_array($field->options))
            {
                $listFields[] = $field->field;

                $fieldList[$field->field . 'List'] = $field->options;
            }
        }

        $data = new stdclass();
        $data->kind        = $flow->module;
        $data->title       = $fileName;
        $data->fields      = $fields;
        $data->rows        = $rows;
        $data->sysDataList = $listFields;
        $data->listStyle   = $listFields;

        foreach($fieldList as $listName => $listArray) $data->$listName = $listArray;

        $excelData = new stdclass();
        $excelData->dataList[] = $data;
        $excelData->fileName   = $fileName;

        $this->loadModel('flow')->setExcelFields($module, $actionFields);

        $this->app->loadClass('excel')->export($excelData, 'xlsx');
    }
}
