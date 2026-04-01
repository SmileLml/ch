<?php
if(in_array($flow->module, array('business', 'projectapproval')))
{
    $transToUsers = $this->loadModel('user')->getPairs('noletter|noempty|nodeleted|noclosed');
    if(isset($transToUsers[$this->app->user->account])) unset($transToUsers[$this->app->user->account]);

    $this->view->canTransTo   = true;
    $this->view->transToUsers = $transToUsers;
}