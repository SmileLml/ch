<?php
class company extends control
{
    public function syncInfo()
    {
        if($_POST)
        {
            $begin = (isset($_POST['begin']) && empty($_POST['begin'])) ? $_POST['begin'] : '';
            $begin = empty($begin) ? '2000-01-01 00:00:00' : $begin . ' 00:00:00';
            $end   = (isset($_POST['end']) && empty($_POST['end'])) ? $_POST['end'] : '';
            if(empty($end)) $end = date('Y-m-d') . ' 23:59:59';

            $this->loadModel('user')->syncAllDepts($begin, $end);
            $this->user->syncAllUsers();

            return $this->send(array('result' => 'success', 'message' => $this->lang->company->syncSuccess, 'locate' => $this->createLink('company', 'browse')));
        }

        $this->display();
    }
}
