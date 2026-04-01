<?php
unset($lang->release->statusList['terminate']);
$lang->release->statusList['']          = '';
$lang->release->statusList['draft']     = '草稿';
$lang->release->statusList['running']   = '发布中';
$lang->release->statusList['failed']    = '发布失败';
$lang->release->statusList['normal']    = '发布成功';
$lang->release->statusList['rolling']   = '回滚中';
$lang->release->statusList['rolled']    = '已回滚';
$lang->release->statusList['cancelled'] = '已取消';

unset($lang->release->featureBar['browse']['terminate']);
$lang->release->featureBar['browse']['draft']     = $lang->release->statusList['draft'];
$lang->release->featureBar['browse']['running']   = $lang->release->statusList['running'];
$lang->release->featureBar['browse']['failed']    = $lang->release->statusList['failed'];
$lang->release->featureBar['browse']['normal']    = $lang->release->statusList['normal'];
$lang->release->featureBar['browse']['rolling']   = $lang->release->statusList['rolling'];
$lang->release->featureBar['browse']['rolled']    = $lang->release->statusList['rolled'];
$lang->release->featureBar['browse']['cancelled'] = $lang->release->statusList['cancelled'];
