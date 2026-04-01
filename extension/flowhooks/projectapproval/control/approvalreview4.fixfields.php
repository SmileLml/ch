<?php
$fields['reviewResult']->options['noReview'] = '不评审';

$data->cancelReviewDate = helper::today();
unset($fields['reviewResult']->options['adjust']);
$data->cancelRemark = '';