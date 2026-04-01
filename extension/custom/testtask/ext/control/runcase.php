<?php
helper::importControl('testtask');
class mytesttask extends testtask
{
    /**
     * Run case.
     *
     * @param  int    $runID
     * @param  int    $caseID
     * @param  int    $version
     * @param  string $confirm
     * @param  int    $chprojectID
     * @access public
     * @return void
     */
    public function runCase($runID, $caseID = 0, $version = 0, $confirm = '', $chprojectID = 0)
    {
        if($runID)
        {
            $run = $this->testtask->getRunById($runID);
        }
        else
        {
            $run = new stdclass();
            $run->case = $this->loadModel('testcase')->getById($caseID, $version);
            
            if($this->app->tab == 'chteam') $this->view->projectID = $run->case->project;
        }

        $caseID     = $caseID ? $caseID : $run->case->id;
        $preAndNext = $this->loadModel('common')->getPreAndNextObject('testcase', $caseID);
        $automation = $this->loadModel('zanode')->getAutomationByProduct($run->case->product);
        $confirmURL = inlink('runCase', "runID=$runID&caseID=$caseID&version=$version&confirm=yes");
        $cancelURL  = inlink('runCase', "runID=$runID&caseID=$caseID&version=$version&confirm=no");

        if($automation and $confirm == '' and $run->case->auto == 'auto') return print(js::confirm($this->lang->zanode->runCaseConfirm, $confirmURL, $cancelURL));
        if($confirm == 'yes')
        {
            $resultID = $this->testtask->initResult($runID, $caseID, $run->case->version, $automation->node);
            if(!dao::isError()) $this->zanode->runZTFScript($automation->id, $caseID, $resultID);
            if(dao::isError()) return print(js::error(dao::getError()) . js::locate($this->createLink('zanode', 'browse'), 'parent'));
        }

        if(!empty($_POST))
        {
            $caseResult = $this->testtask->createResult($runID);
            if(dao::isError()) return print(js::error(dao::getError()));

            $taskID = empty($run->task) ? 0 : $run->task;
            $this->loadModel('action')->create('case', $caseID, 'run', '', $taskID);
            if($caseResult == 'fail')
            {

                $response['result']  = 'success';
                $response['locate']  = $this->createLink('testtask', 'results',"runID=$runID&caseID=$caseID&version=$version");
                return $this->send($response);
            }
            else
            {
                /* set cookie for ajax load caselist when close colorbox. */
                setcookie('selfClose', 1, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);

                if($preAndNext->next and $this->app->tab != 'my')
                {
                    $nextRunID   = $runID ? $preAndNext->next->id : 0;
                    $nextCaseID  = $runID ? $preAndNext->next->case : $preAndNext->next->id;
                    $nextVersion = $preAndNext->next->version;

                    $response['result'] = 'success';
                    $response['next']   = 'success';
                    $response['locate'] = inlink('runCase', "runID=$nextRunID&caseID=$nextCaseID&version=$nextVersion");
                    return $this->send($response);
                }
                else
                {
                    $response['result'] = 'success';
                    $response['locate'] = 'reload';
                    $response['target'] = 'parent';
                    return $this->send($response);
                }
            }
        }

        $preCase  = array();
        $nextCase = array();
        if($preAndNext->pre and $this->app->tab != 'my')
        {
            $preCase['runID']   = $runID ? $preAndNext->pre->id : 0;
            $preCase['caseID']  = $runID ? $preAndNext->pre->case : $preAndNext->pre->id;
            $preCase['version'] = $preAndNext->pre->version;
        }
        if($preAndNext->next and $this->app->tab != 'my')
        {
            $nextCase['runID']   = $runID ? $preAndNext->next->id : 0;
            $nextCase['caseID']  = $runID ? $preAndNext->next->case : $preAndNext->next->id;
            $nextCase['version'] = $preAndNext->next->version;
        }

        $this->view->run         = $run;
        $this->view->preCase     = $preCase;
        $this->view->nextCase    = $nextCase;
        $this->view->users       = $this->loadModel('user')->getPairs('noclosed, noletter');
        $this->view->caseID      = $caseID;
        $this->view->version     = $version;
        $this->view->runID       = $runID;
        $this->view->confirm     = $confirm;
        $this->view->chprojectID = $chprojectID;

        $this->display();
    }
}