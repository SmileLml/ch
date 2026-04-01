<?php
$sceneName = $lang->testcase->scene;
$scenes    = array_values($cases);
$scenes    = array_values($scenes[0]);

js::set('sceneName', $sceneName);
js::set('scenes'   , $scenes);
?>
<script>
startColumn = 3;
$('#cases thead tr th:eq(' + startColumn + ')').after('<th>' + sceneName + '</th>');

scenesLength = Object.keys(scenes).length;
for(var i = 0; i < scenesLength; i++)
{
    sceneHtml = '<td>' + scenes[i]['sceneTitle'] + '</td>';
    $('#cases tbody tr:eq(' + i + ') td:eq(' + startColumn + ')').after(sceneHtml);
}
</script>

