<?php
helper::importControl('caselib');
class myCaselib extends caselib
{
    public function createWithoutCase($libID, $type = 'feature')
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->caselib->createWithoutCase($libID, $type);

        echo 'success';
    }
}
