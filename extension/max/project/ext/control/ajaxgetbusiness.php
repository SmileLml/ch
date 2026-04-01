<?php
class myProject extends project
{
    public function ajaxGetBusiness($id)
    {
        $business = $this->dao->select('*')->from($this->config->db->prefix . 'flow_business')->where('id')->eq($id)->fetch();

        if($business)
        {
            $headBusiness = '';
            if($business->businessPM)
            {
                $headBusiness = $this->dao->select('*')->from(TABLE_USER)->where('account')->eq($business->businessPM)->fetch('realname');
            }

            $business->headBusinessUser = $headBusiness;
        }

        return $this->send(array('result' => true, 'business' => $business));
    }
}
