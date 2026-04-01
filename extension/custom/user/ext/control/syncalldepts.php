<?php
class myUser extends user
{
    public function syncAllDepts()
    {
        $result = $this->user->syncAllDepts();

        if($result['state'] != 'success') return $this->send(array('result' => 'fail', 'message' => $result['message'], $this->createLink('my', 'index')));

        return $this->send(array('result' => 'success', 'message' => $this->lang->user->sync->success->dept, 'locate' => $this->createLink('dept', 'browse')));
    }
}
