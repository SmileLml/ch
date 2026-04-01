<?php if(in_array($viewType, $config->tree->exportObjects)):?>
<div id="exportXmindElement1" class="hidden">
  <div class="panel-actions btn-toolbar">
    <?php echo html::a($this->createLink('tree', 'exportXmind', "rootID=$rootID&viewType=$viewType&branch=$branch", '', true), $lang->tree->exportXmind, '', "class='btn btn-sm btn-primary iframe'");?>
    <?php echo html::a($this->createLink('tree', 'importXmind', "rootID=$rootID&viewType=$viewType&branch=$branch", '', true), $lang->tree->importXmind, '', "class='btn btn-sm btn-primary iframe'");?>
  </div>
</div>
<div id="exportXmindElement2" class="hidden">
  <?php echo html::a($this->createLink('tree', 'exportXmind', "rootID=$rootID&viewType=$viewType&branch=$branch", '', true), $lang->tree->exportXmind, '', "class='btn btn-sm btn-primary iframe'");?>
  <?php echo html::a($this->createLink('tree', 'importXmind', "rootID=$rootID&viewType=$viewType&branch=$branch", '', true), $lang->tree->importXmind, '', "class='btn btn-sm btn-primary iframe'");?>
</div>
<script>
var elements = $('#mainContent > .side-col > .panel > .panel-heading .panel-actions');
if(elements.length == 0)
{
    var addElements = $('#exportXmindElement1').html();
    $('.side-col > .panel > .panel-heading > .panel-title').after(addElements);
}
else
{
    var addElements = $('#exportXmindElement2').html();
    elements.eq(0).append(addElements);
}
</script>
<?php endif;?>
