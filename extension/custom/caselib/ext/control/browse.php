<?php
helper::importControl('caselib');
class myCaselib extends caselib
{
    /**
     * Show library case.
     *
     * @param  int    $libID
     * @param  string $browseType
     * @param  int    $param
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID *
     * @access public
     * @return void
     */
    public function browse($libID = 0, $browseType = 'all', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        /* Set browse type. */
        $browseType = strtolower($browseType);

        $libraries = $this->caselib->getLibraries();
        if(empty($libraries)) $this->locate(inlink('create'));

        /* Save session. */
        $this->session->set('caseList', $this->app->getURI(true), 'qa');
        $this->session->set('caselibList', $this->app->getURI(true), 'qa');

        /* Set menu. */
        $libID = $this->caselib->saveLibState($libID, $libraries);

        $checkPriv = $this->caselib->checkPriv($libID);
        if(!$checkPriv) return print(js::error($this->lang->caselib->noPriv) . js::locate($this->createLink('my', 'index'), 'parent'));

        return parent::browse($libID, $browseType, $param, $orderBy, $recTotal, $recPerPage, $pageID);
    }
}
