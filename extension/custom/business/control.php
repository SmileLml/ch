<?php
/**
 *  The control file of business module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     business
 * @version     $Id: control.php 5094 2013-07-10 08:46:15Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class business extends control
{
    public function changeStatusProjecting()
    {
        $this->business->changeStatusProjecting();
    }

    public function changeStatusPortionPRD()
    {
        $this->business->changeStatusPortionPRD();
    }

    public function changeBusinessStatus()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->changeBusinessStatus();

        echo 'success';
    }

    public function changeVersion($type = 'business')
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->changeVersion($type);

        echo 'success';
    }

    public function cleaningApprovedProject()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->cleaningApprovedProject();

        echo 'success';
    }

    public function cleaningMigrationBusiness()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->cleaningMigrationBusiness();

        echo 'success';
    }

    public function changeStoryStatusToClosed()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->changeStoryStatusToClosed();

        echo 'success';
    }

    public function changeStatusToBeOnline()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->changeStatusToBeOnline();

        echo 'success';
    }

    /**
     * Delete draft business.
     *
     * @param  int    $projectID
     * @param  int    $businessID
     * @access public
     * @return void
     */
    public function deletedDraftBusiness($projectID, $businessID)
    {
        $this->dao->delete()->from('zt_copyflow_business')->where('project')->eq($projectID)->andWhere('business')->eq($businessID)->andWhere('operator')->eq($this->app->user->account)->exec();
    }

    public function updateErrorVersion()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->updateErrorVersion();

        echo 'success';
    }

    public function updateBusinessDate()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->updateBusinessDate();

        echo 'success';
    }

    public function changeDemand()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->changeDemand();

        echo 'success';
    }

    public function updatePRDconfirmDate()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->business->updatePRDconfirmDate();

        echo 'success';
    }

}
