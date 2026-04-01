<?php
public function export($model = '')
{
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time','100');

    $fields = $this->post->exportFields;

    /* Init config fieldList. */
    $fieldList = $this->initFieldList($model, $fields);

    $rows = $this->getRows($model, $fieldList);
    if($model == 'story')
    {
        $product = $this->loadModel('product')->getByID((int)$this->session->storyTransferParams['productID']);
        if($product and $product->shadow)
        {
            foreach($rows as $id => $row)
            {
                $rows[$id]->product = '';
            }
        }
    }

    $list = $this->setListValue($model, $fieldList);
    if($list) foreach($list as $listName => $listValue) $this->post->set($listName, $listValue);

    /* Get export rows and fields datas. */
    $exportDatas = $this->getExportDatas($fieldList, $rows);

    $fields = $exportDatas['fields'];
    $rows   = !empty($exportDatas['rows']) ? $exportDatas['rows'] : array();
    if($model == 'story')
    {
        foreach($rows as $key => $row)
        {
            $haveEstimateID = $this->dao->select('id,BID')->from(TABLE_RELATION)->where('AID')->eq($row->id)->andWhere('AType')->eq('requirement')->fetchPairs('id', 'BID');
            $haveEstimate   = $this->dao->select('estimate')->from(TABLE_STORY)->where('id')->in($haveEstimateID)->andWhere('deleted')->eq(0)->fetchAll();
            $haveEstimate   = array_reduce($haveEstimate, function($carry, $item){return bcadd($carry, $item->estimate, 2);}, '0.0');

            $rows[$key]->residueEstimate = bcsub($row->estimate, $haveEstimate, 2);
        }
    }
    if($this->config->edition != 'open') list($fields, $rows) = $this->loadModel('workflowfield')->appendDataFromFlow($fields, $rows, $model);

    $this->post->set('rows',   $rows);
    $this->post->set('fields', $fields);
    $this->post->set('kind',   $model);
}

public function getExportDatas($fieldList, $rows = array())
{
    $exportDatas    = array();
    $dataSourceList = array();

    foreach($fieldList as $key => $field)
    {
        $exportDatas['fields'][$key] = $field['title'];
        if($field['values'])
        {
            $exportDatas[$key] = $field['values'];
            $dataSourceList[]  = $key;
        }
    }

    if(empty($rows)) return $exportDatas;

    $exportDatas['user'] = $this->loadModel('user')->getPairs('noclosed|nodeleted|noletter');

    foreach($rows as $id => $values)
    {
        foreach($values as $field => $value)
        {
            if(isset($fieldList[$field]['from']) and $fieldList[$field]['from'] == 'workflow') continue;
            if(in_array($field, $dataSourceList))
            {
                if($fieldList[$field]['control'] == 'multiple')
                {
                    $multiple     = '';
                    $separator    = $field == 'mailto' ? ',' : "\n";
                    $multipleLsit = explode(',', $value);

                    foreach($multipleLsit as $key => $tmpValue) $multipleLsit[$key] = zget($exportDatas[$field], $tmpValue);
                    $multiple = join($separator, $multipleLsit);
                    $rows[$id]->$field = $multiple;
                }
                else
                {
                    $rows[$id]->$field = zget($exportDatas[$field], $value, $value);
                }
            }
            elseif(strpos($this->config->transfer->userFields, $field) !== false)
            {
                /* if user deleted when export set userFields is itself. */
                $rows[$id]->$field = zget($exportDatas['user'], $value);
            }

            $this->commonActions($model);
            /* if value = 0 or value = 0000:00:00 set value = ''. */
            if(is_string($value) and ($value == '0' or substr($value, 0, 4) == '0000') and strpos($this->modelConfig->userFields, $field) === false) $rows[$id]->$field = '';
        }
    }

    $exportDatas['rows'] = array_values($rows);
    return $exportDatas;
}