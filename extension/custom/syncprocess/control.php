<?php
class syncProcess extends control
{
    /**
     * Send get token request.
     */
    public function sendGetTokenRequest()
    {
        $url = $this->config->syncprocess->host . $this->config->syncprocess->getToken . 'appid=' . $this->config->syncprocess->appid . '&secret=' . $this->config->syncprocess->secret;
        
        $response = $this->loadModel('apirequest')->http($url, array(), 'GET');

        $this->loadModel('apirequest')->saveRequestLog($url, 'GET', array(), array(), $response);
        return $response;
    }

    /**
     * Send get process request.
     */
    public function sendGetProcessRequest($parentID = '-1')
    {
        static $chData = array();

        $token = $this->sendGetTokenRequest();
        $url   = $this->config->syncprocess->host . $this->config->syncprocess->getprocess . 'parentId=' . $parentID . '&eco-oauth2-token=' . $token;

        $response = $this->loadModel('apirequest')->http($url, array(), 'GET');
        $this->loadModel('apirequest')->saveRequestLog($url, 'GET', array(), array(), $response);

        $response = json_decode($response);

        if(isset($response->data) && !empty($response->data))
        {
            foreach($response->data as $process)
            {
                $tempProcess = new stdclass();
                $tempProcess->id             = $process->id;
                $tempProcess->name           = $process->name;
                $tempProcess->path           = $process->path;
                $tempProcess->code           = $process->code;
                $tempProcess->parentId       = $process->parentId;
                $tempProcess->order          = $process->order;
                $tempProcess->type           = $process->type;
                $tempProcess->version        = $process->version;
                $tempProcess->deleted        = 0;
                $tempProcess->lastUpdateDate = helper::now();
                
                $chData[$process->id] = $tempProcess;
                $this->sendGetProcessRequest($process->id);
            }
        }
        else
        {
            return array();
        }

        if($parentID == '-1')
        {
            $processList = $this->dao->select('*')->from('zt_flow_process')->fetchPairs('id');
            foreach($processList as $processID => $process)
            {
                if(!isset($chData[$processID]))
                {
                    $this->dao->update('zt_flow_process')->set('deleted')->eq('1')->where('id')->eq($processID)->exec();
                }
            }
            foreach($chData as $data)
            {
                $this->dao->replace('zt_flow_process')->data($data)->exec();
            }
        }
    }
}