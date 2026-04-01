<?php
helper::importControl('caselib');
class myCaselib extends caselib
{
    public function batchClone($lib)
    {
        $this->loadModel('testcase');
        if(!$this->post->caseIDList) return print(js::locate($this->session->caseList));
        if($this->post->title)
        {
            $caseID = $this->caselib->batchClone();
            if(dao::isError()) return print(js::error(dao::getError()));
            if(isonlybody()) return print(js::closeModal('parent.parent', 'this'));
            return print(js::locate($this->createLink('caselib', 'browse', "libID=$libID&browseType=byModule&param=$moduleID"), 'parent'));
        }
        $fromCases = $this->dao->select('*')->from('zt_case')->where('id')->in($this->post->caseIDList)->fetchAll();
        $libs      = $this->caselib->getLibraries();

        $this->view->fromCases = $fromCases;
        $this->view->libs      = $libs;
        $this->view->title     = $this->lang->testcase->batchClone;
        $this->view->libID     = $lib;

        return $this->display();
    }
}