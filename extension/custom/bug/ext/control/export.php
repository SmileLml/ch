<?php
helper::importControl('bug');
class mybug extends bug
{
    /**
     * Get data to export
     *
     * @param  string $productID
     * @param  string $orderBy
     * @param  string $browseType
     * @param  int    $executionID
     * @param  int    $chprojectID
     * @access public
     * @return void
     */
    public function export($productID, $orderBy, $browseType = '', $executionID = 0, $chprojectID = 0)
    {
        if($_POST)
        {
            $this->loadModel('transfer');
            $this->session->set('bugTransferParams', array('productID' => $productID, 'executionID' => $executionID, 'branch' => 'all'));
            if(!$productID or $browseType == 'bysearch')
            {
                $this->config->bug->datatable->fieldList['module']['dataSource']['method'] = 'getAllModulePairs';
                $this->config->bug->datatable->fieldList['module']['dataSource']['params'] = 'bug';

                if($executionID)
                {
                    $object    = $this->dao->findById($executionID)->from(TABLE_EXECUTION)->fetch();
                    $projectID = $object->type == 'project' ? $object->id : $object->parent;
                    $this->config->bug->datatable->fieldList['project']['dataSource']   = array('module' => 'project', 'method' => 'getPairsByIdList', 'params' => $projectID);
                    $this->config->bug->datatable->fieldList['execution']['dataSource'] = array('module' => 'execution', 'method' => 'getPairs', 'params' => $projectID);
                }
            }

            $this->transfer->export('bug');
            $this->fetch('file', 'export2' . $_POST['fileType'], $_POST);
        }
        $product = $this->loadModel('product')->getByID($productID);
        if(isset($product->type) and $product->type == 'normal') $this->config->bug->exportFields = str_replace('branch,', '', $this->config->bug->exportFields);

        if($this->app->tab == 'project' or $this->app->tab == 'execution')
        {
            $execution = $this->loadModel('execution')->getByID($executionID);
            if(empty($execution->multiple)) $this->config->bug->exportFields = str_replace('execution,', '', $this->config->bug->exportFields);
            if(!empty($product->shadow)) $this->config->bug->exportFields = str_replace('product,', '', $this->config->bug->exportFields);
        }

        $fileName = $this->lang->bug->common;
        if($executionID)
        {
            $executionName = $this->dao->findById($executionID)->from(TABLE_EXECUTION)->fetch('name');
            $fileName      = $executionName . $this->lang->dash . $fileName;
        }
        elseif($chprojectID)
        {
            $chprojectName = $this->dao->findById($chprojectID)->from(TABLE_CHPROJECT)->fetch('name');
            $fileName      = $chprojectName . $this->lang->dash . $fileName;
        }
        else
        {
            $productName = !empty($product->name) ? $product->name : '';
            $browseType  = isset($this->lang->bug->featureBar['browse'][$browseType]) ? $this->lang->bug->featureBar['browse'][$browseType] : zget($this->lang->bug->moreSelects, $browseType, '');

            $fileName = $productName . $this->lang->dash . $browseType . $fileName;
        }

        $this->view->fileName        = $fileName;
        $this->view->allExportFields = $this->config->bug->exportFields;
        $this->view->customExport    = true;
        $this->display();
    }
}
