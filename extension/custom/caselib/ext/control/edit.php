<?php
helper::importControl('caselib');
class myCaselib extends caselib
{
        /**
     * Edit a case lib.
     *
     * @param  int    $lib
     * @access public
     * @return void
     */
    public function edit($libID)
    {
        $lib = $this->caselib->getById($libID);

        $checkPriv = $this->caselib->checkPriv($libID);
        if(!$checkPriv) return print(js::error($this->lang->caselib->noPriv) . js::locate($this->createLink('my', 'index'), 'parent'));

        if(!empty($_POST))
        {
            $response['result']  = 'success';
            $response['message'] = $this->lang->saveSuccess;
            $changes = $this->caselib->update($libID);
            if(dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }
            if($changes)
            {
                $actionID = $this->loadModel('action')->create('caselib', $libID, 'edited');
                $this->action->logHistory($actionID, $changes);
            }

            $message = $this->executeHooks($libID);
            if($message) $response['message'] = $message;

            $response['locate']  = inlink('view', "libID=$libID");
            return $this->send($response);
        }

        /* Set lib menu. */
        $libraries = $this->caselib->getLibraries();
        $libID     = $this->caselib->saveLibState($libID, $libraries);
        $this->caselib->setLibMenu($libraries, $libID);

        $this->view->title      = $libraries[$libID] . $this->lang->colon . $this->lang->caselib->edit;
        $this->view->position[] = html::a($this->createLink('caselib', 'browse', "libID=$libID"), $libraries[$libID]);
        $this->view->position[] = $this->lang->caselib->common;
        $this->view->position[] = $this->lang->caselib->edit;

        $this->view->lib     = $lib;
        $this->view->users   = $this->loadModel('user')->getPairs('noclosed');
        $this->view->chteams = array('' => $this->lang->caselib->chteam) + $this->loadModel('chteam')->getPairs();
        $this->display();
    }
}
