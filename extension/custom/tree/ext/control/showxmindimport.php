<?php
helper::import('tree');
class myTree extends tree
{
    /**
     * Show imported xmind.
     *
     * @param  int    $rootID
     * @param  string $viewType
     * @param  int    $branch
     * @access public
     * @return void
     */
    public function showXmindImport($rootID, $viewType, $branch)
    {
        if(!commonModel::hasPriv("tree", "importXmind")) $this->loadModel('common')->deny('tree', 'importXmind');

        $this->loadModel('testcase');
        $config = $this->testcase->getXmindConfig();

        $jsLng = array();
        $jsLng['caseNotExist'] = $this->lang->testcase->caseNotExist;
        $jsLng['saveFail']     = $this->lang->testcase->saveFail;
        $jsLng['set2Scene']    = $this->lang->testcase->set2Scene;
        $jsLng['set2Testcase'] = $this->lang->testcase->set2Testcase;
        $jsLng['clearSetting'] = $this->lang->testcase->clearSetting;
        $jsLng['setModule']    = $this->lang->testcase->setModule;
        $jsLng['pickModule']   = $this->lang->testcase->pickModule;
        $jsLng['clearBefore']  = $this->lang->testcase->clearBefore;
        $jsLng['clearAfter']   = $this->lang->testcase->clearAfter;
        $jsLng['clearCurrent'] = $this->lang->testcase->clearCurrent;
        $jsLng['removeGroup']  = $this->lang->testcase->removeGroup;
        $jsLng['set2Group']    = $this->lang->testcase->set2Group;

        $this->view->title     = $this->lang->tree->importXmind;
        $this->view->settings  = $config;
        $this->view->productID = 0;
        $this->view->branch    = 0;
        $this->view->jsLng     = $jsLng;
        $this->view->rootID    = $rootID;
        $this->view->viewType  = $viewType;

        $this->display();
    }
}
