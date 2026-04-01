<?php
$result = $this->flow->checkTransTo();
if($result['result'] != 'success') $this->send($result);

if($_POST['reviewResult'] == 'pass' && empty($_POST['projectNumber']))
{
    dao::$errors['projectNumber'][] = sprintf($this->lang->error->notempty, $this->lang->flow->projectNumber);
    return $this->send(array('result' => 'fail', 'message' => dao::getError()));
}
