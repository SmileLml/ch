<?php
class myProject extends project
{
    public function ajaxGetProjectCost()
    {
        $projectCosts = $this->config->costType;

        $this->loadModel('projectrole');
        $result = [];
        foreach($projectCosts as $key => $projectCost)
        {
            $config = json_decode($projectCost);
            $config->unit = zget($this->lang->projectrole->costUnitList, $config->unit, '');
            $result[$key] = $config;
        }

        return $this->send(array('result' => 'success', 'projectCost' => $result));
    }
}
