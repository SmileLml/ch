<?php
helper::import('tree');      
class myTree extends tree
{
    /**
     * Get xmind content.
     *
     * @access public
     * @return void
     */
    public function ajaxGetXmindImport()
    {
        if(!commonModel::hasPriv("tree", "importXmind")) $this->loadModel('common')->deny('tree', 'importXmind');
        $folder = $this->session->xmindImport;
        $type   = $this->session->xmindImportType;

        if($type == 'xml')
        {
            $xmlPath = "$folder/content.xml";
            $results = $this->tree->getXmindImport($xmlPath);

            echo $results;
        }
        else
        {
            $jsonPath = "$folder/content.json";
            $jsonStr  = file_get_contents($jsonPath);

            echo $jsonStr;
        }
    }
}
