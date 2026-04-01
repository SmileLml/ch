<?php
class mytesttask extends testtask
{
    public function delete($taskID, $confirm = 'no', $chproject = 0)
    {
        if($confirm == 'no')
        {
            return print(js::confirm($this->lang->testtask->confirmDelete, inlink('delete', "taskID=$taskID&confirm=yes&project={$chproject}")));
        }
        else
        {
            $task = $this->testtask->getByID($taskID);
            $this->testtask->delete(TABLE_TESTTASK, $taskID);

            $message = $this->executeHooks($taskID);
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

            $browseList = $this->createLink('testtask', 'browse', "productID=$task->product");
            if($this->app->tab == 'execution') $browseList = $this->createLink('execution', 'testtask', "executionID=$task->execution");
            if($this->app->tab == 'project')   $browseList = $this->createLink('project', 'testtask', "projectID=$task->project");
            if($this->app->tab == 'chteam')    $browseList = $this->createLink('chproject', 'testtask', "project=$chproject");
            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));
            return print(js::locate($browseList, 'parent'));
        }
    }
}
