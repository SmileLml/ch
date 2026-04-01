<?php
helper::importControl('user');

class myUser extends user
{
    public function syncAllUsers()
    {
        $result = $this->user->syncAllUsers();
        echo "OK\n";
    }
}
