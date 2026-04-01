<?php
helper::importControl('testcase');
class mytestcase extends testcase
{
    /**
     * Delete a test case
     *
     * @param  int    $caseID
     * @param  string $confirm yes|noe
     * @access public
     * @return void
     */
    public function delete($caseID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            return print(js::confirm($this->lang->testcase->confirmDelete, inlink('delete', "caseID=$caseID&confirm=yes")));
        }
        else
        {
            $case = $this->testcase->getById($caseID);
            $this->testcase->delete(TABLE_CASE, $caseID);

            $message = $this->executeHooks($caseID);
            if($message) $response['message'] = $message;

            /* if ajax request, send result. */
            if($this->server->ajax)
            {
                if(dao::isError())
                {
                    $response['result']  = 'fail';
                    $response['message'] = dao::getError();
                }
                else
                {
                    $response['result']  = 'success';
                    $response['message'] = '';
                }
                return $this->send($response);
            }

            $locateLink = $this->session->caseList ? $this->session->caseList : inlink('browse', "productID={$case->product}");
            if($this->app->tab == 'chteam') $locateLink = $this->session->teamTestcaseList;
            
            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));
            return print(js::locate($locateLink, 'parent'));
        }
    }
}