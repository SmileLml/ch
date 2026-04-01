<?php
$recorder = array_filter($data->recorder);
if(empty($recorder)) $data->recorder[] = $this->app->user->account;
