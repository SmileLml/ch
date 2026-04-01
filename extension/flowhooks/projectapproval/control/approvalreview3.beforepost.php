<?php
$result = $this->flow->checkTransTo();
if($result['result'] != 'success') $this->send($result);
