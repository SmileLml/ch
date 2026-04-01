<?php
helper::import('testcase');
class myTestcase extends testcase
{
    /**
     * Export xmind.
     *
     * @param  int $productID
     * @param  int $moduleID
     * @param  int $branch
     * @access public
     * @return void
     */
    public function exportMind($productID, $moduleID, $branch)
    {
        if($_POST)
        {
            $this->classXmind = $this->app->loadClass('mind');
            if (isset($_POST['imodule'])) $imoduleID = $_POST['imodule'];

            $configResult = $this->testcase->saveXmindConfig();
            if($configResult['result'] == 'fail') return print(js::alert($configResult['message']));

            $context = $this->testcase->getXmindExport($productID, $imoduleID, $branch);

            $productName = '';
            if(count($context['caseList']))
            {
                $productName = $context['caseList'][0]->productName;
            }
            else
            {
                $product     = $this->product->getById($productID);
                $productName = $product->name;
            }

            $this->loadModel('file');
            $savePath = $this->file->savePath . 'xmind/';
            $filePath = $savePath . time() . '.xmind';
            $fileName = $productName . '.xmind';

            $sceneNodes  = array();
            $moduleNodes = array();

            $this->classXmind->createModuleNode($context, $moduleNodes);
            $this->classXmind->createSceneNode($context, $moduleNodes, $sceneNodes);

            $mindData = $this->classXmind->createTestcaseNode($context, $moduleNodes, $sceneNodes);
            $mindData = $this->classXmind->mergeNodeData($mindData);
            $mindBody = $this->classXmind->createMindBody($productName . "[$productID]", 'product' . $productID);

            $this->classXmind->createMindContent($filePath, $mindData, $mindBody, 'testcase');
            $this->classXmind->export($filePath);

            $this->fetch('file', 'sendDownHeader', array('fileName' => $fileName, 'fileType' => '', 'content' => $filePath, 'type' => 'file'));
        }

        $tree    = $moduleID ? $this->tree->getByID($moduleID) : '';
        $product = $this->product->getById($productID);
        $config  = $this->testcase->getXmindConfig();

        $this->view->settings         = $config;
        $this->view->moduleName       = $tree != '' ? $tree->name : '/';
        $this->view->productName      = $product->name;
        $this->view->moduleID         = $moduleID;
        $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0, $branch);

        $this->display();
    }
}
