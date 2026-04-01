<?php if($this->app->tab == 'chteam'):?>
<?php
$story = $this->story->appendChproject(array($story))[0];

$html  = '';
$html .= '<tr>';
$html .= '<th>' . $lang->story->project . '</th>';
$html .= '<td>' . $story->projectName . '</td>';
$html .= '</tr>';
?>
<script>
var link = '<?php echo helper::createLink('testcase', 'create', "productID={$story->product}&branch={$story->branch}&moduleID=0&from=&param=&storyID={$story->id}&extras=executionID=" . array_key_first($story->executions) . "&chprojectID={$this->session->chproject}", '', '', '', true);?>';
$('summary:contains("<?php echo $lang->story->legendBasicInfo;?>")').next().find('table tr:first').before('<?php echo json_encode($html);?>');
$("a[title='<?php echo $lang->testcase->create;?>']").attr('href', link);
</script>
<?php endif?>