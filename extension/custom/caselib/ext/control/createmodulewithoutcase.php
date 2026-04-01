<?php
helper::importControl('caselib');
class myCaselib extends caselib
{
    public function createModuleWithoutCase($libID)
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->caselib->createModuleWithoutCases($libID);

        echo 'success';
    }
}
