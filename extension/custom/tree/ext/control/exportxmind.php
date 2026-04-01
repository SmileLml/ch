<?php
helper::import('tree');
class myTree extends tree
{
    /**
     * Export xmind.
     *
     * @param  int    $rootID
     * @param  string $viewType
     * @param  int    $branch
     * @access public
     * @return void
     */
    public function exportXmind($rootID, $viewType, $branch)
    {
        $fileName = 'default';
        if(in_array($viewType, $this->config->tree->productObjects))
        {
            $fileName    = $this->dao->findById($rootID)->from(TABLE_PRODUCT)->fields('name')->fetch('name');
            $nodeID      = "product$rootID";
            $topNodeName = $fileName . "[product:$rootID]";
        }
        elseif($viewType = 'host')
        {
            $fileName    = $this->lang->tree->host;
            $nodeID      = "topHost$rootID";
            $topNodeName = $fileName . "[topHost:$rootID]";
        }

        if($_POST)
        {
            $fileName = trim($_POST['fileName']);
            $fileType = $_POST['fileType'];
            if(empty($fileName)) echo js::alert($this->lang->tree->emptyFileName);

            $treeList = $this->tree->getProductStructure($rootID, $viewType, $branch);
            $content  = $this->tree->getModuleLevelData($treeList);

            $this->loadModel('file');
            $savePath = $this->file->savePath . 'xmind/';
            $filePath = $savePath . time() . '.xmind';
            $fileName = $fileName . '.xmind';

            if($fileType == 'low')
            {
                $this->classXmind = $this->app->loadClass('lowmind');

                $xmlDoc = new DOMDocument('1.0', 'UTF-8');
                $xmlDoc->formatOutput = true;

                $sheet = $this->classXmind->createXmlBody($xmlDoc);
                $body  = $this->classXmind->createXmlTopic($xmlDoc, $sheet);

                $this->classXmind->createXmlTitle($xmlDoc, $body, $topNodeName, $nodeID);
                $this->classXmind->createTreeNode($xmlDoc, $content, $body);

                $this->classXmind->insertTopicsNode($xmlDoc);
                $this->classXmind->insertChildrenNode($xmlDoc);
                $this->classXmind->insertExtensionsNode($xmlDoc);

                $this->classXmind->createMindContent($filePath, $xmlDoc);
                $this->classXmind->export($filePath);
            }
            else
            {
                $this->classXmind = $this->app->loadClass('mind');

                $mindBody = $this->classXmind->createMindBody($topNodeName, $nodeID);
                $this->classXmind->createMindContent($filePath, $content, $mindBody, 'module');
                $this->classXmind->export($filePath);
            }

            $this->fetch('file', 'sendDownHeader', array('fileName' => $fileName, 'fileType' => '', 'content' => $filePath, 'type' => 'file'));
        }

        $this->view->rootID   = $rootID;
        $this->view->viewType = $viewType;
        $this->view->branch   = $branch;
        $this->view->fileName = $fileName;
        $this->display();
    }
}
