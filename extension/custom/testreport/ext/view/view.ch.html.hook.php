<?php if($this->app->tab == 'chteam'):?>
<?php
js::set('chprojectID', $chprojectID);

$createLink = inLink('create', "objectID=$report->objectID&objectType=$report->objectType" . $extra . "&begin=&end=&chprojctID=$chprojectID");
$editLink   = inLink('edit', "reportID={$report->id}&begin=&end=&chprojectID=$chprojectID");
$deleteLink = inLink('delete', "reportID={$report->id}&confirm=no&project=$chprojectID");

js::set('createLink', $createLink);
js::set('editLink', $editLink);
js::set('deleteLink', $deleteLink);
?>
<script>
$('.icon-refresh').parent('a').attr('href', createLink);
$('.icon-common-edit').parent('a').attr('href', editLink);
$('.icon-common-delete').parent('a').attr('href', deleteLink);
</script>
<?php endif;?>

<?php
$sceneName = $lang->testcase->scene;
$scenes    = array_values($cases);
$scenes    = $scenes[0];
$scenes    = array_values($scenes);

js::set('sceneName', $sceneName);
js::set('scenes'   , $scenes);
?>
<script>
startColumn = 3;
$('#cases thead tr th:eq(' + startColumn + ')').after('<th class="c-status">' + sceneName + '</th>');

scenesLength = Object.keys(scenes).length;
for(var i = 0; i < scenesLength; i++)
{
    sceneHtml = '<td>' + scenes[i]['sceneTitle'] + '</td>';
    $('#cases tbody tr:eq(' + i + ') td:eq(' + startColumn + ')').after(sceneHtml);
}
</script>

