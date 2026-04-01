<?php
helper::importControl('user');
class myUser extends user
{
    public function login($referer = '', $from = '', $type = 'ldap')
    {
        $systemUrl = common::getSysURL();

        $systemUrl .= '/cas.php';

        if($this->config->cas->compulsory) return  print('<script language="JavaScript">window.location.href="' . $systemUrl . '"</script>');
        $ldapConfig = $this->user->getLDAPConfig();
        if(!empty($ldapConfig->turnon) && $type == 'ldap') $this->config->notMd5Pwd = true;

        return parent::login($referer, $from);
    }
}