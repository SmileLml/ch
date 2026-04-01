<?php
helper::import('testcase');
class myTestcase extends testcase
{
    /**
     * Import xmind.
     *
     * @param  int $productID
     * @param  int $branch
     * @access public
     * @return void
     */
    public function importXmind($productID, $branch)
    {
        if($_FILES)
        {
            $this->classXmind = $this->app->loadClass('xmind');
            if($_FILES['file']['size'] == 0) return print(js::alert($this->lang->testcase->errorFileNotEmpty));

            $configResult = $this->testcase->saveXmindConfig();
            if($configResult['result'] == 'fail') return print(js::alert($configResult['message']));

            $tmpName  = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $extName  = trim(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)));
            if($extName != 'xmind') return print(js::alert($this->lang->testcase->errorFileFormat));

            $newPureName  = $this->app->user->id."-xmind";
            $importFolder = $this->app->getTmpRoot() . "import";
            if(!is_dir($importFolder)) mkdir($importFolder, 0777, true);

            $dest = $this->app->getTmpRoot() . "import/".$newPureName.$extName;
            if(!move_uploaded_file($tmpName, $dest)) return print(js::alert($this->lang->testcase->errorXmindUpload));

            $extractFolder   = $this->app->getTmpRoot() . "import/".$newPureName;
            $this->classFile = $this->app->loadClass('zfile');
            if(is_dir($extractFolder)) $this->classFile->removeDir($extractFolder);

            $this->app->loadClass('pclzip', true);
            $zip = new pclzip($dest);

            if($zip->extract(PCLZIP_OPT_PATH, $extractFolder) == 0)
            {
                return print(js::alert($this->lang->testcase->errorXmindUpload));
            }

            $this->classFile->removeFile($dest);

            $jsonPath = $extractFolder."/content.json";
            if(file_exists($jsonPath) == true)
            {

                $fetchResult = $this->fetchByJSON($extractFolder, $productID, $branch);
            }
            else
            {
                $fetchResult = $this->fetchByXML($extractFolder, $productID, $branch);
            }

            if($fetchResult['result'] == 'fail')
            {
                return print(js::alert($fetchResult['message']));
            }

            $this->session->set('xmindImport', $extractFolder);
            $this->session->set('xmindImportType', $fetchResult['type']);

            $pId = $fetchResult['pId'];

            return print(js::locate($this->createLink('testcase', 'showXmindImport', "productID=$pId&branch=$branch"), 'parent.parent'));
        }

        $config = $this->testcase->getXmindConfig();

        $this->view->settings = $config;

        $this->display();
    }
}
