<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php
      foreach($lang->yearplan->labelList as $label => $labelName)
      {
          $active = $browseType == $label ? 'btn-active-text' : '';
          echo html::a($this->createLink('yearplan', 'browse', "browseType=$label&param=0&orderBy=$orderBy&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}"), '<span class="text">' . $labelName . '</span>' . ($browseType == $label ? " <span class='label label-light label-badge'>{$pager->recTotal}</span>" : ''), '', "class='btn btn-link $active'");
      }
    ?>
    <a class="btn btn-link querybox-toggle" id='bysearchTab'><i class="icon icon-search muted"></i> <?php echo $lang->searchAB;?></a>
  </div>
  <div class="btn-toolbar pull-right">
    <?php if(common::hasPriv('yearplan', 'create')) echo html::a($this->createLink('yearplan', 'create'), "<i class='icon-plus'></i> {$lang->yearplan->create}", '', "class='btn btn-primary'");?>
  </div>
</div>
<div id="mainContent" class="main-row fade">
  <div class="main-col">
    <div class="cell<?php if($browseType == 'bysearch') echo ' show';?>" id="queryBox" data-module='yearplan'></div>
    <?php if(empty($yearplans)):?>
    <div class="table-empty-tip">
      <p><span class="text-muted"><?php echo $lang->noData;?></span></p>
    </div>
    <?php else:?>
    <form class='main-table' method='post' id='yearplanForm'>
      <?php $vars = "browseType=$browseType&param=0&orderBy=%s&recTotal=$pager->recTotal&recPerPage=$pager->recPerPage&pageID=$pager->pageID"; ?>
      <table class='table has-sort-head' id='yearplanList'>
        <thead>
          <tr>
            <th class='c-id'><?php common::printOrderLink('id', $orderBy, $vars, $lang->yearplan->id);?></th>
            <th><?php common::printOrderLink('name', $orderBy, $vars, $lang->yearplan->name);?></th>
            <th class='w-100px'><?php common::printOrderLink('status', $orderBy, $vars, $lang->yearplan->status);?></th>
            <th class='w-120px'><?php common::printOrderLink('owner',  $orderBy, $vars, $lang->yearplan->owner);?></th>
            <th class='w-120px'><?php common::printOrderLink('createdBy',   $orderBy, $vars, $lang->yearplan->createdBy);?></th>
            <th class='c-actions-3'><?php echo $lang->actions;?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($yearplans as $yearplan):?>
          <tr>
            <td><?php echo sprintf('%03d', $yearplan->id);?></td>
            <td title="<?php echo $yearplan->name;?>">
              <?php echo html::a($this->createLink('yearplandemand', 'browse', "yearplanID=$yearplan->id"), $yearplan->name);?>
            </td>
            <td><?php echo zget($lang->yearplan->statusList, $yearplan->status);?></td>
            <td><?php echo zget($users, $yearplan->owner, '');?></td>
            <td><?php echo zget($users, $yearplan->createdBy, $yearplan->createdBy);?></td>
            <td class='c-actions'>
              <?php
              common::printIcon('yearplan', 'edit', "yearplanID=$yearplan->id", $yearplan, 'list');
              common::printIcon('yearplan', 'close', "yearplanID=$yearplan->id", $yearplan, 'list', 'off', '', 'iframe', true);
              common::printIcon('yearplan', 'delete', "yearplanID=$yearplan->id", $yearplan, 'list', 'trash', 'hiddenwin');
              ?>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <div class="table-footer">
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
