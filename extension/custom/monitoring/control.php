<?php
/**
 * The control file of xxx module of chandao.net.
 *
 * @copyright   Copyright 2009-2022 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      wangxiaomeng <wangxiaomeng@chandao.com>
 * @package     xxx
 * @version     $Id$
 * @link        https://www.chandao.net
 */
class monitoring extends control
{
    /**
     * Monitoring of project approval.
     *
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function browse($params = '', $recTotal = 0, $recPerPage = 10, $pageID = 1)
    {
        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        $searchParams = $this->monitoring->parseParams($params);

        $this->view->title                = $this->lang->monitoring->projectMonitoring;
        $this->view->position[]           = $this->lang->monitoring->projectMonitoring;
        $this->view->users                = $this->loadModel('user')->getPairs('noclosed|noletter');
        $this->view->depts                = ['' => ''] + $this->loadModel('dept')->getOptionMenu();
        $this->view->gradeDepts           = ['' => ''] + $this->loadModel('dept')->getOptionMenuByGrade('', 3);
        $this->view->userDepts            = $this->dao->select('account, dept')->from(TABLE_USER)->fetchPairs('account', 'dept');
        $this->view->projectApprovals     = $this->monitoring->getProjectApprovalList($searchParams, $pager);
        $this->view->workflowFieldOptions = $this->monitoring->getWorkflowFieldOptions();
        $this->view->projectapprovalList  = ['' => ''] + $this->monitoring->getProjectApprovalPairs();
        $this->view->pager                = $pager;
        $this->view->projectapproval      = empty($searchParams['projectapproval']) ? '' : $searchParams['projectapproval'];
        $this->view->projectpri           = empty($searchParams['projectpri'])      ? '' : $searchParams['projectpri'];
        $this->view->responsibleDept      = empty($searchParams['responsibleDept']) ? '' : $searchParams['responsibleDept'];
        $this->view->businessPM           = empty($searchParams['businessPM'])      ? '' : $searchParams['businessPM'];
        $this->view->itPM                 = empty($searchParams['itPM'])            ? '' : $searchParams['itPM'];
        $this->view->productManager       = empty($searchParams['productManager'])  ? '' : $searchParams['productManager'];
        $this->view->itDevM               = empty($searchParams['itDevM'])          ? '' : $searchParams['itDevM'];
        $this->view->businessDept         = empty($searchParams['businessDept'])    ? '' : $searchParams['businessDept'];
        $this->view->deferredType         = empty($searchParams['deferredType'])    ? '' : $searchParams['deferredType'];
        $this->view->params               = $params;

        $this->display();
    }

    /**
     * Export monitoring.
     *
     * @param  string $params
     * @access public
     * @return void
     */
    public function export($params = '')
    {
        if($_POST)
        {
            $this->loadModel('file');

            $monitoringLang = $this->lang->monitoring;
            $exportFields   = $this->config->monitoring->exportFields;

            foreach(explode(',', $exportFields) as $field) $fields[$field] = isset($monitoringLang->$field) ? $monitoringLang->$field : $field;

            list($projectapprovalList, $rowspans) = $this->monitoring->getProjectApprovalExportList($this->post->exportType, $params);

            if($rowspans) $this->post->set('rowspan', $rowspans);

            $this->post->set('fields', $fields);
            $this->post->set('rows', $projectapprovalList);
            $this->post->set('kind', $this->lang->monitoring->projectMonitoring);
            $this->fetch('file', 'export2' . $this->post->fileType, $_POST);
        }

        $fileName = $this->lang->monitoring->projectMonitoring;

        $this->view->title    = $this->lang->export;
        $this->view->fileName = $fileName;
        $this->display();
    }

    /**
     * Batch update overdue warning.
     *
     * @access public
     * @return mixed
     */
    public function batchUpdateOverdueWarning()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->monitoring->batchUpdateOverdueWarning();

        echo 'success';
    }

    /**
     * PRD overdue reminder.
     *
     * @access public
     * @return mixed
     */
    public function PRDoverdueReminder()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->monitoring->sendOverdueReminder('PRDWarning');

        echo 'success';
    }

    /**
     * GoLive overdue reminder.
     *
     * @access public
     * @return mixed
     */
    public function goLiveOverdueReminder()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->monitoring->sendOverdueReminder('goLiveWarning');

        die;
        echo 'success';
    }

    /**
     * Acceptance overdue reminder.
     *
     * @access public
     * @return mixed
     */
    public function acceptanceOverdueReminder()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->monitoring->sendOverdueReminder('acceptanceWarning');

        echo 'success';
    }

    /**
     * Termination overdue reminder.
     *
     * @access public
     * @return mixed
     */
    public function terminationOverdueReminder()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->monitoring->sendTerminationWarning();

        echo 'success';
    }
}
