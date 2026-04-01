<?php
class mytesttask extends testtask
{
    /**
     * Batch confirm change cases.
     *
     * @param  int    $taskID
     * @access public
     * @return mixed
     */
    public function batchConfirmChangeCases($taskID)
    {
        if(isset($_POST['caseIDList']))
        {
            foreach($_POST['caseIDList'] as $caseID)
            {
                $case = $this->loadModel('testcase')->getById($caseID);
                $this->dao->update(TABLE_TESTRUN)->set('version')->eq($case->version)->where('`case`')->eq($caseID)->exec();
            }
        }

        return print(js::locate($this->createLink('testtask', 'cases', "taskID=$taskID")));
    }
}
