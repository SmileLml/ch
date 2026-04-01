<?php
helper::importControl('caselib');
class myCaselib extends caselib
{
        /**
     * Create lib
     *
     * @access public
     * @return void
     */
    public function create()
    {
        if(!empty($_POST))
        {
            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $libID = $this->caselib->create();
            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }
            $this->loadModel('action')->create('caselib', $libID, 'opened');

            /* Return lib id when call the API. */
            if($this->viewType == 'json')
            {
                $response['id'] = $libID;
                return $this->send($response);
            }

            $response['locate']  = $this->createLink('caselib', 'browse', "libID=$libID");
            return $this->send($response);
        }

        /* Set menu. */
        $libraries = $this->caselib->getLibraries();
        $libID     = $this->caselib->saveLibState(0, $libraries);
        $this->caselib->setLibMenu($libraries, $libID);

        $this->view->title      = $this->lang->caselib->common . $this->lang->colon . $this->lang->caselib->create;
        $this->view->position[] = $this->lang->caselib->common;
        $this->view->position[] = $this->lang->caselib->create;
        $this->view->users      = $this->loadModel('user')->getPairs('noclosed');
        $this->view->chteams    = array('' => $this->lang->caselib->chteam) + $this->loadModel('chteam')->getPairs();
        $this->display();
    }
}