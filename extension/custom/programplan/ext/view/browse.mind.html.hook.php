<div id='exportMind' class='hidden'>
  <?php
  $class = common::hasPriv('programplan', 'exportXmind') ? '' : "class=disabled";
  $misc  = common::hasPriv('programplan', 'exportXmind') ? "class='export'" : "class=disabled";
  $link  = common::hasPriv('programplan', 'exportXmind') ?  $this->createLink('programplan', 'exportXmind', "rootID=$projectID") : '#';
  echo "<li $class>" . html::a($link, $lang->programplan->exportXmind, '', $misc . "data-app={$this->app->tab}") . "</li>";
  ?>
</div>
<script>
$('#exportActionMenu').append($('#exportMind').html());
</script>
