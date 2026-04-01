<?php
helper::importControl('user');
class myuser extends user
{
    /**
     * AJAX: get users from a contact list.
     *
     * @param  int    $contactListID
     * @access public
     * @return string
     */
    public function ajaxGetProjectMembers($accounts, $field)
    {
        $users = [];
        if($accounts) $users = $this->user->getPairs('noclosed|nodeleted', '', $this->config->maxCount, $accounts);

        $prefix = 'approval_reviewer';
        $pos    = strpos($field, $prefix);

        $fieldName = '';
        if($pos !== false) $fieldName = substr($field, $pos + strlen($prefix));

        return print(html::select($prefix . '[' . $fieldName . '][]', $users, '', "class='form-control picker-select' multiple id=$field"));
    }
}
