<?php
helper::importControl('user');
class myuser extends user
{
    /**
     * AJAX: get users from a contact list.
     *
     * @param  int    $contactListID
     * @param  string $dropdownName mailto|whitelist
     * @param  string $oldUsers
     * @access public
     * @return string
     */
    public function ajaxGetContactUsers($contactListID, $dropdownName = 'mailto', $oldUsers = '')
    {
        $list = $contactListID ? $this->user->getContactListByID($contactListID) : '';
        $attr = $dropdownName == 'mailto' ? "data-placeholder='{$this->lang->chooseUsersToMail}' data-drop-direction='bottom'" : '';

        $defaultUsers = empty($contactListID) ? '' : $list->userList . ',' . trim($oldUsers);
        $users        = $this->user->getPairs('devfirst|nodeleted|noclosed', $defaultUsers, $this->config->maxCount);
        if(isset($this->config->user->moreLink)) $this->config->moreLinks[$dropdownName . "[]"] = $this->config->user->moreLink;

        return print(html::select($dropdownName . "[]", $users, $defaultUsers, "class='form-control picker-select' multiple $attr"));
    }
}
