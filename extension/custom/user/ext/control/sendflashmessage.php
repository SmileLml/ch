<?php
class myUser extends user
{
    public function sendflashmessage()
    {
        $this->loadModel('apiRequest');
        $this->apiRequest->sendFlasgMessage("023695","标题","测试内容");

        return $this->send(array('result' => 'success', 'message' => '测试发送消息', 'locate' => $this->createLink('my', 'index')));
    }
}
