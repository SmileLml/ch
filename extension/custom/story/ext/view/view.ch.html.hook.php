<?php if($this->app->tab == 'chteam'):?>
<script>
var link = '<?php echo $this->session->teamStoryList;?>';

$('a.btn-secondary').attr('href', link);
$('.btn-toolbar .icon-back').parent().attr('href', link);

$('#mainContent > div.side-col.col-4 > div:nth-child(2) > div > ul > li.active').hide();
$('#mainContent > div.side-col.col-4 > div:nth-child(2) > div > ul > li:nth-child(2)').addClass('active');
$('#legendStories').hide();
$('#legendProjectAndTask').addClass('active');
$("#legendProjectAndTask > ul a.text-muted").removeAttr("href");
$("#legendProjectAndTask > ul a.text-muted").removeClass("iframe");
$('#mainMenu > div.btn-toolbar.pull-right').hide();
</script>
<?php endif;?>

<?php
$story = $this->story->appendChproject(array($story))[0];

$html  = '';
$html .= '<tr>';
$html .= '<th>' . $lang->story->project . '</th>';
$html .= '<td>' . $story->projectName . '</td>';
$html .= '</tr>';
?>
<script>
$('#legendBasicInfo > table > tbody').prepend('<?php echo json_encode($html);?>');
</script>
