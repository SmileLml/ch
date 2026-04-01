<?php
class syncProcessModel extends model
{
    public function getAllNameProcess()
    {
        $newData = array();

        $processList = $this->dao->select('*')->from('zt_flow_process')->where('deleted')->eq('0')->fetchParis('id');

        foreach($processList as $id => $process)
        {
            
            $processName = $process->code . '_' . $this->processData($process, $processList, $process->name);
            

            if($process->type == 'WORKFLOW') $newData[$id] = $processName;
        }

        return $newData;
    }

    public function processData($process, $processList, $name)
    {
        if($process->parentId != '-1')
        {
            $name = $processList[$process->parentId]->name . '/' . $name;
            $name = $this->processData($processList[$process->parentId], $processList, $name);
        }

        return $name;
    }
}