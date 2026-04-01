<?php
$config->apiRequest = new stdclass();
if(isset($config->environment) and ($config->environment == 'prod'))
{
    $config->apiRequest->esbHost         = 'http://f5-md.esb.chinner.com:9001/esb_md/service/';
    $config->apiRequest->ebsUser         = 'ztpms';
    $config->apiRequest->ebsPwd          = 'ztpmsPassword1';
    $config->apiRequest->flashMessageUrl = 'http://f5-all.springlmapl.chinner.com:31740/';

    $config->apiRequest->flashMessageAppkey    = '9991900';
    $config->apiRequest->flashMessageSecretKey = 'D7#pQ9@k';
}
else
{
    /* We don't hava pre-production environment now. */
    $config->apiRequest->esbHost         = 'http://192.168.210.152:8496/esb_md/service/';
    $config->apiRequest->ebsUser         = 'test';
    $config->apiRequest->ebsPwd          = 'test';
    $config->apiRequest->flashMessageUrl = 'http://10.131.0.116:31740/';

    $config->apiRequest->flashMessageAppkey    = '19991900';
    $config->apiRequest->flashMessageSecretKey = '88888888';
}

$config->apiRequest->soapDataTemplate = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="%packageUrl%">
    <soapenv:Header>
        <username>%esbUser%</username>
        <password>%esbPwd%</password>
    </soapenv:Header>
    <soapenv:Body>
        %bodyData%
    </soapenv:Body>
</soapenv:Envelope>';

$config->apiRequest->interfaces = array();
$config->apiRequest->interfaces['baseEmployeeService'] = array(
    'url' => $config->apiRequest->esbHost . 'baseEmployeeService?wsdl',
    'soapData' => str_replace(
        array('%packageUrl%',
            '%esbUser%',
            '%esbPwd%',
            '%bodyData%'),
        array('http://ws.baseemployee.md.ws.ch.com/',
            $config->apiRequest->ebsUser,
            $config->apiRequest->ebsPwd,
            '<ws:queryBaseEmployee>
           <startDate>%startDate%</startDate>
           <endDate>%endDate%</endDate>
           <tableName>%tableName%</tableName>
           <pageNo>%pageNo%</pageNo>
           <pageSize>%pageSize%</pageSize>
       </ws:queryBaseEmployee>'),
        $config->apiRequest->soapDataTemplate
    )
);
$config->apiRequest->interfaces['departmentFService'] = array(
    'url' => $config->apiRequest->esbHost . 'departmentFService?wsdl',
    'soapData' => str_replace(
        array('%packageUrl%',
            '%esbUser%',
            '%esbPwd%',
            '%bodyData%'),
        array('http://ws.departmentf.md.ws.ch.com/',
            $config->apiRequest->ebsUser,
            $config->apiRequest->ebsPwd,
            '<ws:queryDepartmentF>
            <startDate>%startDate%</startDate>
            <endDate>%endDate%</endDate>
            <tableName>%tableName%</tableName>
            <pageNo>%pageNo%</pageNo>
            <pageSize>%pageSize%</pageSize>
        </ws:queryDepartmentF>'),
        $config->apiRequest->soapDataTemplate
    )
);
