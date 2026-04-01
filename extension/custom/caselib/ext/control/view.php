<?php
helper::importControl('caselib');
class myCaselib extends caselib
{
    /**
     * View library
     *
     * @param  int    $libID
     * @access public
     * @return void
     */
    public function view($libID)
    {
        $libID = (int)$libID;
        $lib   = $this->caselib->getById($libID, true);
        if(!isset($lib->id)) return print(js::error($this->lang->notFound) . js::locate($this->createLink('qa', 'index')));

        $checkPriv = $this->caselib->checkPriv($libID);
        if(!$checkPriv) return print(js::error($this->lang->caselib->noPriv) . js::locate($this->createLink('my', 'index'), 'parent'));

        return parent::view($libID);
    }
}
