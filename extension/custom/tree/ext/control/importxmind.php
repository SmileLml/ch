<?php
helper::import('tree');
class myTree extends tree
{
    /**
     * Import xmind.
     *
     * @param  int    $rootID
     * @param  string $viewType
     * @param  int    $branch
     * @access public
     * @return void
     */
    public function importXmind($rootID, $viewType, $branch)
    {
        if($_FILES)
        {
            if($_FILES['file']['size'] == 0) return print(js::alert($this->lang->tree->errorFileNotEmpty));

            $tmpName  = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $extName  = trim(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)));
            if($extName != 'xmind') return print(js::alert($this->lang->tree->errorFileFormat));

            $newPureName  = $this->app->user->id . "-xmind";
            $importFolder = $this->app->getTmpRoot() . "import";
            if(!is_dir($importFolder)) mkdir($importFolder, 0755, true);

            $dest = $this->app->getTmpRoot() . "import/" . $newPureName . $extName;
            if(!move_uploaded_file($tmpName, $dest)) return print(js::alert($this->lang->testcase->errorXmindUpload));

            $extractFolder   = $this->app->getTmpRoot() . "import/" . $newPureName;
            $this->classFile = $this->app->loadClass('zfile');
            if(is_dir($extractFolder)) $this->classFile->removeDir($extractFolder);

            $this->app->loadClass('pclzip', true);
            $zip = new pclzip($dest);

            if($zip->extract(PCLZIP_OPT_PATH, $extractFolder) == 0)
            {
                return print(js::alert($this->lang->testcase->errorXmindUpload));
            }

            $this->classFile->removeFile($dest);

            $jsonPath = $extractFolder . "/content.json";
            if(file_exists($jsonPath) == true)
            {
                $this->classXmind = $this->app->loadClass('mind');
                $fetchResult = $this->fetchByJSON($extractFolder, $rootID, $branch);
            }
            else
            {
                $this->classXmind = $this->app->loadClass('lowmind');
                $fetchResult = $this->fetchByXML($extractFolder, $rootID, $branch);
            }

            if($fetchResult['result'] == 'fail')
            {
                return print(js::alert($fetchResult['message']));
            }

            $this->session->set('xmindImport', $extractFolder);
            $this->session->set('xmindImportType', $fetchResult['type']);

            return print(js::locate($this->createLink('tree', 'showXmindImport', "rootID=$rootID&viewType=$viewType&branch=$branch"), 'parent'));
        }

        $this->display();
    }

    /**
     * Fetch by xml.
     *
     * @param  string $extractFolder
     * @param  int    $rootID
     * @param  string $viewType
     * @access public
     * @return array
     */
    public function fetchByXML($extractFolder, $rootID, $viewType)
    {
        $filePath = $extractFolder . "/content.xml";
        $xmlNode  = simplexml_load_file($filePath);
        $title    = $xmlNode->sheet->topic->title;
        if(strlen($title) == 0)
        {
            return array('result' => 'fail', 'message' => $this->lang->tree->errorXmindUpload);
        }

        $pId = $rootID;
        if($this->classXmind->endsWith($title, "]") == true)
        {
            $tmpId = $this->classXmind->getRootID($title);
            if(empty($tmpId) == false)
            {
                $projectCount = $this->dao->select('count(*) as count')
                    ->from(TABLE_PRODUCT)
                    ->where('id')
                    ->eq((int)$tmpId)
                    ->andWhere('deleted')->eq('0')
                    ->fetch('count');

                if((int)$projectCount == 0) return array('result' => 'fail', 'message' => $this->lang->tree->errorImportBadProduct);

                $pId = $tmpId;
            }
        }

        return array('result' => 'success', 'pId' => $pId, 'type' => 'xml');
    }

    /**
     * Fetch by json.
     *
     * @param  string $extractFolder
     * @param  int    $rootID
     * @param  string $viewType
     * @access public
     * @return void
     */
    public function fetchByJSON($extractFolder, $rootID, $viewType)
    {
        $filePath  = $extractFolder . "/content.json";
        $jsonStr   = file_get_contents($filePath);
        $jsonDatas = json_decode($jsonStr, true);

        $title = $jsonDatas[0]['rootTopic']['title'];
        if(strlen($title) == 0)
        {
            return array('result' => 'fail', 'message' => $this->lang->tree->errorXmindUpload);
        }

        $pId = $rootID;
        if($this->classXmind->endsWith($title, "]") == true)
        {
            $tmpId = $this->classXmind->getBetween($title, "[product:", "]");
            if(empty($tmpId) == false)
            {
                $projectCount = $this->dao->select('count(*) as count')
                    ->from(TABLE_PRODUCT)
                    ->where('id')
                    ->eq((int)$tmpId)
                    ->andWhere('deleted')->eq('0')
                    ->fetch('count');

                if((int)$projectCount == 0) return array('result' => 'fail', 'message' => $this->lang->tree->errorImportBadProduct);

                $pId = $tmpId;
            }
        }

        return array('result' => 'success','pId' => $pId, 'type' => 'json');
    }
}
