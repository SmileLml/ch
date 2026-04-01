<?php
$fields['reviewResult']->options['noReview'] = '不评审';

$recorder = array_filter($data->recorder);
if(empty($recorder)) $data->recorder[] = $this->app->user->account;
