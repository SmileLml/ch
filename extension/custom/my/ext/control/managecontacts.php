<?php
class myMy extends my
{
    /**
     * Manage contacts.
     *
     * @param  int    $listID
     * @param  string $mode
     * @access public
     * @return void
     */
    public function manageContacts($listID = 0, $mode = 'new')
    {
        if($_POST)
        {
            $data = fixer::input('post')->setDefault('users', array())->get();
            if($data->mode == 'new')
            {
                if(empty($data->newList))
                {
                    dao::$errors[] = sprintf($this->lang->error->notempty, $this->lang->user->contacts->listName);

                    $response['result']  = 'fail';
                    $response['message'] = dao::getError();
                    return $this->send($response);
                }
                $listID = $this->user->createContactList($data->newList, $data->users);
                if(dao::isError())
                {
                    return $this->send(array('result' => 'fail', 'message' => dao::getError()));
                }
                $this->user->setGlobalContacts($listID, isset($data->share));
                if(isonlybody()) return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.parent.ajaxGetContacts('#mailto')"));
                return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => inlink('manageContacts', "listID=$listID&mode=edit")));
            }
            elseif($data->mode == 'edit')
            {
                $response['result']  = 'success';
                $response['message'] = $this->lang->saveSuccess;

                $this->user->updateContactList($data->listID, $data->listName, $data->users);
                $this->user->setGlobalContacts($data->listID, isset($data->share));

                if(dao::isError())
                {
                    $response['result']  = 'fail';
                    $response['message'] = dao::getError();
                    return $this->send($response);
                }

                $response['locate'] = inlink('manageContacts', "listID=$listID&mode=edit");
                return $this->send($response);
            }
        }

        $mode  = empty($mode) ? 'edit' : $mode;
        $lists = $this->user->getContactLists($this->app->user->account);

        $globalContacts = isset($this->config->my->global->globalContacts) ? $this->config->my->global->globalContacts : '';
        $globalContacts = !empty($globalContacts) ? explode(',', $globalContacts) : array();

        $myContacts = $this->user->getListByAccount($this->app->user->account);
        $disabled   = $globalContacts;

        if(!empty($myContacts) && !empty($globalContacts))
        {
            foreach($globalContacts as $id)
            {
                if(in_array($id, array_keys($myContacts))) unset($disabled[array_search($id, $disabled)]);
            }
        }

        $listID = $listID ? $listID : key($lists);

        /* Create or manage list according to mode. */
        if($mode == 'new')
        {
            $this->view->title      = $this->lang->my->common . $this->lang->colon . $this->lang->user->contacts->createList;
            $this->view->position[] = $this->lang->user->contacts->createList;
        }
        else
        {
            $this->view->title      = $this->lang->my->common . $this->lang->colon . $this->lang->user->contacts->manage;
            $this->view->position[] = $this->lang->user->contacts->manage;
            $this->view->list       = $this->user->getContactListByID($listID);
        }

        $userParams = empty($this->config->user->showDeleted) ? 'noletter|noempty|noclosed|noclosed|nodeleted' : 'noletter|noempty|noclosed|noclosed';
        $users      = $this->user->getPairs($userParams, $mode == 'new' ? '' : $this->view->list->userList, $this->config->maxCount);
        $depts      = $this->loadModel('dept')->getDeptPairs();
        if(isset($this->config->user->moreLink)) $this->config->moreLinks['users[]'] = $this->config->user->moreLink;

        $this->view->mode           = $mode;
        $this->view->lists          = $lists;
        $this->view->listID         = $listID;
        $this->view->users          = $users;
        $this->view->depts          = array('' => '') + $depts;
        $this->view->disabled       = $disabled;
        $this->view->globalContacts = $globalContacts;
        $this->display();
    }
}