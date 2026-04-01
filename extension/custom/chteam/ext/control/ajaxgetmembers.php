<?php
helper::importControl('chteam');
class myChteam extends chteam
{
    function ajaxGetMembers($chteamID, $dropdownName = 'mailto', $oldUsers = '')
    {
        $chteam       = $this->chteam->getByID($chteamID);
        $defaultUsers = empty($chteamID) ? '' : $chteam->members . ',' . trim($oldUsers);
        $users        = $this->loadModel('user')->getPairs('noclosed');

        return print(html::select($dropdownName . "[]", $users, $defaultUsers, "class='form-control picker-select' multiple"));
    }
}