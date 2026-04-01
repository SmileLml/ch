<?php $chProjectID = $this->execution->getChProjectByExecution($execution->id);?>
<?php if($chProjectID):?>
<script>
$('.btn-link .icon-edit').parent().addClass('hidden');
$('.btn-link .icon-off').parent().addClass('hidden');
$('.btn-link .icon-magic').parent().addClass('hidden');
$('.btn-link .icon-magic').parent().next().addClass('hidden');
$('#mainContent > div.col-4.side-col > div > div > div > div:nth-child(4) > div.detail-title > a').hide();
</script>
<?php endif;?>
