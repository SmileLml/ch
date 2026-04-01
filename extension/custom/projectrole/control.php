<?php
class projectrole extends control
{
    /**
     * Set role list.
     *
     * @access public
     * @return void
     */
    public function roleList()
    {
        if($_POST)
        {
            $configItems = fixer::input('post')->get();
            foreach($configItems as $config => $value) $configItems->{$config} = trim($value);

            $this->loadModel('setting');
            $this->setting->setItems('system.common', $configItems);

            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'reload'));
        }

        $this->view->title    = $this->lang->projectrole->role;
        $this->view->roleList = $this->projectrole->getRoleList();
        $this->view->module   = 'projectrole';

        $this->display();
    }

    /**
     * Set the unit price of the project.
     *
     * @access public
     * @return void
     */
    public function unitPrice()
    {
        if($_POST)
        {
            $configItems = fixer::input('post')->get();
            foreach($configItems as $config => $value) $configItems->{$config} = trim($value);

            $this->loadModel('setting');
            $this->setting->setItems('system.common', $configItems);

            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'reload'));
        }

        $this->view->title            = $this->lang->projectrole->unit;
        $this->view->projectUnitPrice = isset($this->config->projectUnitPrice) ? $this->config->projectUnitPrice : '';
        $this->view->module           = 'projectrole';

        $this->display();
    }

    /**
     * Set role list.
     *
     * @access public
     * @return void
     */
    public function projectCost()
    {
        if($_POST)
        {
            $configItems  = fixer::input('post')->get();
            $costTypeList = $this->projectrole->getCostTypeList();

            $insertConfig = new stdclass();
            foreach($costTypeList as $typeValue => $costType)
            {
                $configItem = [];
                $configItem['costDesc']  = $configItems->costDescs[$typeValue];
                $configItem['costUnit']  = $configItems->costUnits[$typeValue];
                $configItem['costPrice'] = $configItems->costPrices[$typeValue];

                $insertConfig->{$typeValue} = json_encode($configItem);

            }

            $this->loadModel('setting');
            $this->setting->setItems('system.common.costType', $insertConfig);

            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'reload'));
        }

        $this->view->title        = $this->lang->projectrole->cost;
        $this->view->costTypeList = $this->projectrole->getCostTypeList();
        $this->view->module       = 'projectrole';

        $this->display();
    }
}
