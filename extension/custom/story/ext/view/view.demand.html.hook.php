<?php $demand = $this->loadModel('demand')->getByID($story->fromDemand);?>
<?php if($demand):?>
<div class='cell' id='demand'>
  <div class='detail'>
    <div class='detail-title'>
      <?php echo $lang->demand->common;?> 
    </div>
    <div class='detail-content'>
      <?php echo html::a($this->createLink('demand', 'view', "demandID=$demand->id"), "#$demand->id " . $demand->name);?>
    </div>
  </div>
</div>
<script>
$('.main-col .cell').first().after($('#demand'));
</script>
<?php endif;?>
