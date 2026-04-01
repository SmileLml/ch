<?php
class apiRequestModel extends model
{
    public function post($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        /* Add log. */
        file_put_contents($this->app->getTmpRoot() . 'apiRequest' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n"
            . "url: " . $url . "\n"
            . "data: " . json_encode($data) . "\n"
            . "headers: " . json_encode($headers) . "\n"
            . "response: " . $response . "\n\n"
            , FILE_APPEND);

        return $response;
    }

    public function getUsers($startDate = '', $endDate = '', $tableName = '', $pageNo = '', $pageSize = '')
    {
        /* Set the request url. */
        $url = $this->config->apiRequest->interfaces['baseEmployeeService']['url'];

        /* Set the post datas. */
        $datas = str_replace(
            array('%startDate%',
                '%endDate%',
                '%tableName%',
                '%pageNo%',
                '%pageSize%'),
            array($startDate,
                $endDate,
                $tableName,
                $pageNo,
                $pageSize),
            $this->config->apiRequest->interfaces['baseEmployeeService']['soapData']
        );

        $response = $this->post($url, $datas);
        $response = $this->extractSoapResult($response);
        $response = $this->checkSoapResult($response);

        return $response;
    }

    /**
     * Http.
     *
     * @param  string       $url
     * @param  string|array $data
     * @param  string       $method    GET|POST|PATCH
     * @param  string       $dataType  data|json
     * @param  array        $headers   Set request headers.
     * @param  array        $options   This is option and value pair, like CURLOPT_HEADER => true. Use curl_setopt function to set options.
     * @static
     * @access public
     * @return string
     */
    public function http($url, $data = array(), $method = 'POST', $dataType = 'data', $headers = array(), $options = array())
    {
        if(!is_array($headers)) $headers = (array)$headers;
        $headers[] = "API-RemoteIP: " . zget($_SERVER, 'REMOTE_ADDR', '');
        if($dataType == 'json')
        {
            $headers[] = 'Content-Type: application/json;charset=utf-8';
            if(!empty($data)) $data = json_encode($data);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Sae T OAuth2 v0.1');

        curl_setopt($curl, CURLOPT_NOSIGNAL, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if($options) curl_setopt_array($curl, $options);

        if(strpos($url, 'https://') !== false)
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        if(!empty($data) and $method != 'GET')
        {
            if($method == 'POST')  curl_setopt($curl, CURLOPT_POST, true);
            if($method == 'PATCH') curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if($dataType == 'build') $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function sendFlasgMessage($account, $title, $content)
    {
        /* Set the request url. */
        $url = $this->config->apiRequest->flashMessageUrl;

        /* headers. */
        $headers = array();
        $headers["Content-Type"] = "application/json;charset=utf-8";
        $headers["Appkey"]       = $this->config->apiRequest->flashMessageAppkey;
        $headers["Timestamp"]    = round(microtime(true) *1000);
        $headers["Signature"]    = strtoupper(md5($headers["Appkey"] . $headers["Timestamp"] . $this->config->apiRequest->flashMessageSecretKey));

        /* data */
        $data = array();
        $data["chattype"]          = "1"; // 单聊
        $data["chatid"]            = $account;
        $data["msgType"]           = "text";
        $data["msgObj"]["content"] = $content;
        $data["pushContent"]       = $title;

        $response = $this->http2($url, $data, 'POST', 'json', $headers);

        $status = $response ? 'success' : 'fail';
        $this->saveRequestLog($url, 'POST', json_encode($headers), json_encode($data), $response, $status);

        return $response;
    }

    public function http2($data,$headers) {
            $url = $this->config->apiRequest->flashMessageUrl . '/api/OpenMessage/Send';
            $ch = curl_init($url);
            $data = json_encode($data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;






    }

    /**
     * send openMessage
     * @param $account
     * @param $content
     * @return mixed
     */
    public function sendOpenMessage($account,$content)
    {
        /* Set the request url. */
        $url = $this->config->apiRequest->flashMessageUrl . '/api/OpenMessage/Send';

        /* headers. */
        $headers = array();
        $appkey = $this->config->apiRequest->flashMessageAppkey;
        $now = round(microtime(true) *1000);
        $secret = $this->config->apiRequest->flashMessageSecretKey;
        $headers = array(
            "Content-Type:" . "application/json;charset=utf-8",
            "Appkey:" . $appkey,
            "Timestamp:" . $now,
            "Signature:" . strtoupper(md5($appkey . $now . $secret))
        );

        /* data */
        $data = array();
        $data["chattype"]          = 1; // 单聊
        $data["chatid"]            = $account;
        $data["msgType"]           = "newtext";
        $data["msgObj"]["content"] = $content;
 
        $response = $this->http2($data,$headers);

        $status = $response ? 'success' : 'fail';
        $this->saveRequestLog($url, 'POST', json_encode($headers), json_encode($data), $response, $status);

        return $response;
    }


    /**
     * Save the request log.
     * 保存请求日志。
     *
     * @param  string $url
     * @param  string $requestType
     * @param string $params
     * @param  string $params
     * @param  string $response
     * @param  string $status
     * @param  string $extra
     * @access public
     * @return int
     */
    public function saveRequestLog($url, $requestType = '', $headers = array(), $params = array(), $response = '', $status = '', $extra = '')
    {
        $log              = new stdClass();
        $log->url         = $url;
        $log->requestType = $requestType;
        $log->status      = $status;
        $log->headers     = json_encode($headers);
        $log->params      = json_encode($params);
        $log->response    = empty($response) ? 'Null' : $response;
        $log->requestDate = helper::now();
        $log->extra       = $extra;

        $this->dao->insert('zt_requestlog')->data($log)->exec();
        return $this->dao->lastInsertId();
    }

    public function getDepts($startDate = '', $endDate = '', $tableName = '', $pageNo = '', $pageSize = '')
    {
        /* Set the request url. */
        $url = $this->config->apiRequest->interfaces['departmentFService']['url'];

        /* Set the post datas. */
        $datas = str_replace(
            array('%startDate%',
                '%endDate%',
                '%tableName%',
                '%pageNo%',
                '%pageSize%'),
            array($startDate,
                $endDate,
                $tableName,
                $pageNo,
                $pageSize),
            $this->config->apiRequest->interfaces['departmentFService']['soapData']
        );

        $response = $this->post($url, $datas);
        $response = $this->extractSoapResult($response);
        $response = $this->checkSoapResult($response);

        return $response;
    }

    public function extractSoapResult($response)
    {
        /* Suppress the warning of libxml. */
        libxml_use_internal_errors(true);
        /* Set the flag LIBXML_PARSEHUGE to allow simeleXML to resolve the huge text node. */
        $xmlObj = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_PARSEHUGE);

        /* Output the error message if the xml is invalid. */
        if($xmlObj === false)
        {
            $errors   = libxml_get_errors();
            $errorMsg = '';
            foreach($errors as $error) $errorMsg .= $error->message . "\n";
            libxml_clear_errors();
            die(js::alert($errorMsg));
        }

        $xmlObj->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xmlBody = $xmlObj->xpath("//soap:Body");
        if(!empty($xmlBody))
        {
            $returnData = $xmlBody[0]->xpath("//return");
            if (!empty($returnData))
            {
                $jsonData = (string)$returnData[0];
            }

            return $jsonData;
        }

        return '';
    }

    public function checkSoapResult($jsonData)
    {
        $result   = array();
        $jsonData = json_decode($jsonData);

        if(empty($jsonData->state))
        {
            $result['state']   = 'failed';
            $result['message'] = $this->lang->apiRequest->error->emptyReturn;
        }

        if(isset($jsonData->state) and $jsonData->state != 'success')
        {
            $result['state']   = 'failed';
            $result['message'] = '';
            if(isset($jsonData->code)) $result['message'] .= $this->lang->apiRequest->error->code . $jsonData->error_code;
            if(isset($jsonData->message)) $result['message'] .= $this->lang->apiRequest->error->message . $jsonData->error_msg;
        }
        elseif($jsonData->state == 'success')
        {
            $result['state']      = 'success';
            $result['result']     = $jsonData->result;
            $result['tableName']  = $jsonData->tableName ?? '';
            $result['totalCount'] = $jsonData->totalCount;
        }
        else
        {
            $result['state']   = 'failed';
            $result['message'] = $this->lang->apiRequest->error->unknownReturn;
        }

        return $result;
    }
}
