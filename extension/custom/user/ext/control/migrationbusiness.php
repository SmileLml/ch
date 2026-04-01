<?php
helper::importControl('user');
class myuser extends user
{
    public function migrationBusiness()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->user->migrationBusiness();

        echo 'success';
    }
}
