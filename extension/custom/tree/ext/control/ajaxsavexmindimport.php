<?php
helper::import('tree');
class myTree extends tree
{
    /**
     * Save imported xmind.
     *
     * @param  string $viewType
     * @access public
     * @return void
     */
    public function ajaxSaveXmindImport($viewType)
    {
        if(!commonModel::hasPriv("tree", "importXmind")) $this->loadModel('common')->deny('tree', 'importXmind');
        if(!empty($_POST))
        {
            $result = $this->tree->saveXmindImport($viewType);
            return $this->send($result);
        }

        $this->send(array('result' => 'fail', 'message' => $this->lang->tree->errorSaveXmind));
    }
}
