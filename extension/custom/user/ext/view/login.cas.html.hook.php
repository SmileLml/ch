<?php
$casBtn = '<tr><td class="form-actions" colspan="2">';
$systemUrl = common::getSysURL();
if(strpos('|/zentao/|/biz/|/max/|', "|{$app->config->webRoot}|") !== false) $systemUrl .= rtrim($app->config->webRoot,'/');
$systemUrl .= '/cas.php?referer=' . urlencode($referer);
$casBtn .= html::linkButton($lang->user->casLogin, $systemUrl);
$casBtn .= '</td></tr>';
?>

<script>
var casBtn  = <?php echo json_encode($casBtn);?>;
var trAfter = $('.form-actions').closest('tr');
trAfter.after(casBtn);
</script>