<div id='exportMind' class='hidden'>
  <?php
  //$class = common::hasPriv('testcase', 'exportLowMind') ? '' : "class=disabled";
  //$misc  = common::hasPriv('testcase', 'exportLowMind') ? "class='export'" : "class=disabled";
  //$link  = common::hasPriv('testcase', 'exportLowMind') ?  $this->createLink('testcase', 'exportLowMind', "productID=$productID&moduleID=$moduleID&branch=$branch") : '#';
  //echo "<li $class>" . html::a($link, $lang->testcase->exportLowMind, '', $misc . "data-app={$this->app->tab}") . "</li>";

  $class = common::hasPriv('testcase', 'exportMind') ? '' : "class=disabled";
  $misc  = common::hasPriv('testcase', 'exportMind') ? "class='export'" : "class=disabled";
  $link  = common::hasPriv('testcase', 'exportMind') ?  $this->createLink('testcase', 'exportMind', "productID=$productID&moduleID=$moduleID&branch=$branch") : '#';
  echo "<li $class>" . html::a($link, $lang->testcase->exportMind, '', $misc . "data-app={$this->app->tab}") . "</li>";
  ?>
</div>
<script>
$('#exportActionMenu').append($('#exportMind').html());
</script>
