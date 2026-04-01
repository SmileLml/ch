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
