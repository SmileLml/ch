<?php
helper::importControl('datatable');
class myDatatable extends datatable
{
   /**
     * custom fields.
     *
     * @param  string $module
     * @param  string $method
     * @param  string $extra
     * @access public
     * @return void
     */
    public function ajaxCustom($module, $method, $extra = '')
    {
        $moduleName = $module;
        $target     = $module . ucfirst($method);
        $mode       = isset($this->config->datatable->$target->mode) ? $this->config->datatable->$target->mode : 'table';
        $key        = $mode == 'datatable' ? 'cols' : 'tablecols';


        if($extra == 'requirement')
        {
            $this->loadModel('story');
            $this->config->story->datatable->defaultField = array_merge($this->config->story->datatable->defaultField, $this->config->story->datatable->defaultFieldRequirement);
        }

        if($module == 'testtask')
        {
            $this->loadModel('testcase');
            $this->app->loadConfig('testtask');
            $this->config->testcase->datatable->defaultField = $this->config->testtask->datatable->defaultField;
            $this->config->testcase->datatable->fieldList['actions']['width'] = '100';
            $this->config->testcase->datatable->fieldList['status']['width']  = '90';
        }
        if($module == 'testcase')
        {
            $this->loadModel('testcase');
            unset($this->config->testcase->datatable->fieldList['assignedTo']);
        }

        $this->view->module = $module;
        $this->view->method = $method;
        $this->view->mode   = $mode;

        $module  = zget($this->config->datatable->moduleAlias, "$module-$method", $module);
        $setting = '';
        if(isset($this->config->datatable->$target->$key)) $setting = $this->config->datatable->$target->$key;
        if(empty($setting))
        {
            $this->loadModel($module);
            $setting = json_encode($this->config->$module->datatable->defaultField);
        }

        $cols = $this->datatable->getFieldList($module);

        if($module == 'story' && $extra != 'requirement') unset($cols['SRS']);

        if($extra == 'requirement')
        {
            unset($cols['plan']);
            unset($cols['stage']);
            unset($cols['taskCount']);
            unset($cols['bugCount']);
            unset($cols['caseCount']);
            unset($cols['URS']);
            unset($cols['relatedRequirement']);
            unset($cols['actualConsumed']);

            $cols['title']['title'] = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->title);
        }

        if($moduleName == 'project' and $method == 'bug')
        {
            $project = $this->loadModel('project')->getByID($this->session->project);

            if(!$project->multiple) unset($cols['execution']);
            if(!$project->hasProduct && ($project->model != 'scrum' || !$project->multiple)) unset($cols['plan']);
            if(!$project->hasProduct) unset($cols['branch']);
        }

        if($moduleName == 'execution' and $method == 'bug')
        {
            $execution = $this->loadModel('execution')->getByID($this->session->execution);
            $project   = $this->loadModel('project')->getByID($execution->project);
            if(!$project->hasProduct and $project->model != 'scrum') unset($cols['plan']);
            if(!$project->hasProduct) unset($cols['branch']);
        }

        if($moduleName == 'execution' and $method == 'story')
        {
            $execution = $this->loadModel('execution')->getByID($this->session->execution);
            if(!$execution->hasProduct and !$execution->multiple) unset($cols['plan']);
            if(!$execution->hasProduct) unset($cols['branch']);
        }
        if($extra == 'unsetStory' and isset($cols['story'])) unset($cols['story']);

        if($moduleName == 'product' and $method == 'browse' and $extra != 'requirement')
        {
            unset($cols['roadmap']);

            foreach($this->config->story->datatable->defaultFieldRequirement as $k => $v) unset($cols[$v]);
        }

        if($extra == 'requirement')
        {
            $requirementSetting = array();
            foreach ($this->config->story->datatable->defaultFieldRequirement as $k => $v) {

                $requirementSetting[$k]         = $this->config->story->datatable->fieldList[$v];
                $requirementSetting[$k]['id']   = $v;
                $requirementSetting[$k]['show'] = true;
            }

            $setting = json_encode(array_merge(json_decode($setting, 1), $requirementSetting));
        }

        $this->view->cols    = $cols;
        $this->view->setting = $setting;
        $this->view->extra   = $extra;
        $this->display();
    }
}
