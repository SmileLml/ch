<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datatable.fix.html.php';?>
<?php
$canIntegration = common::hasPriv('yearplandemand', 'integration');
?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php
      foreach($lang->yearplandemand->labelList as $label => $labelName)
      {
          $active = $browseType == $label ? 'btn-active-text' : '';
          echo html::a($this->createLink('yearplandemand', 'browse', "yearplanID=$yearplanID&browseType=$label&param=0&orderBy=$orderBy&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}"), '<span class="text">' . $labelName . '</span>' . ($browseType == $label ? " <span class='label label-light label-badge'>{$pager->recTotal}</span>" : ''), '', "class='btn btn-link $active'");
      }
    ?>
    <a class="btn btn-link querybox-toggle" id='bysearchTab'><i class="icon icon-search muted"></i> <?php echo $lang->searchAB;?></a>
  </div>
  <div class="btn-toolbar pull-right">
    <div class='btn-group'>
      <button class="btn btn-link" data-toggle="dropdown"><i class="icon icon-export muted"></i> <span class="text"><?php echo $lang->export;?></span> <span class="caret"></span></button>
      <ul class="dropdown-menu pull-right" id='exportActionMenu'>
        <?php
        $class = common::hasPriv('yearplandemand', 'export') ? '' : "class=disabled";
        $misc  = common::hasPriv('yearplandemand', 'export') ? "class='export'" : "class=disabled";
        $link  = common::hasPriv('yearplandemand', 'export') ? $this->createLink('yearplandemand', 'export', "yearplanID=$yearplanID&orderBy=$orderBy&browseType=$browseType") : '#';
        echo "<li $class>" . html::a($link, $lang->yearplandemand->export, '', $misc) . "</li>";
        $class = common::hasPriv('yearplandemand', 'exportTemplate') ? '' : "class='disabled'";
        $link  = common::hasPriv('yearplandemand', 'exportTemplate') ? $this->createLink('yearplandemand', 'exportTemplate', "yearplanID=$yearplanID", '', true) : '#';
        $misc  = common::hasPriv('yearplandemand', 'exportTemplate') ? "class='exportTemplate'  data-toggle='modal' data-type='iframe'" : "class='disabled'";
        echo "<li $class>" . html::a($link, $lang->yearplandemand->exportTemplate, '', $misc) . '</li>';
        ?>
      </ul>
      <?php if(common::hasPriv('yearplandemand', 'import')) echo html::a($this->createLink('yearplandemand', 'import', "yearplanID=$yearplanID"), '<i class="icon-import muted"></i> <span class="text">' . $lang->yearplandemand->import . '</span>', '', "class='btn btn-link import' data-toggle='modal' data-type='iframe'");?>
    </div>
    <div class='btn-group dropdown'>
      <?php if(common::hasPriv('yearplandemand', 'create')) echo html::a($this->createLink('yearplandemand', 'create', "yearplanID={$yearplanID}"), "<i class='icon-plus'></i> {$lang->yearplandemand->create}", '', "class='btn btn-primary'");?>
    </div>
  </div>
</div>
<div id="mainContent" class="main-row fade">
  <div class="main-col">
    <div class="cell<?php if($browseType == 'bysearch') echo ' show';?>" id="queryBox" data-module='yearplandemand'></div>
    <?php if(empty($yearplandemands)):?>
    <div class="table-empty-tip">
      <p><span class="text-muted"><?php echo $lang->noData;?></span></p>
    </div>
    <?php else:?>
    <?php
    $vars = "yearplanID=$yearplanID&browseType=$browseType&param=0&orderBy=%s&recTotal=$pager->recTotal&recPerPage=$pager->recPerPage&pageID=$pager->pageID";

    $datatableId  = $this->moduleName . ucfirst($this->methodName);
    $useDatatable = (isset($config->datatable->$datatableId->mode) and $config->datatable->$datatableId->mode == 'datatable');

    if($useDatatable) include $app->getModuleRoot() . 'common/view/datatable.html.php';

    $setting = $this->datatable->getSetting('yearplandemand');
    $widths  = $this->datatable->setFixedFieldWidth($setting);
    $columns = 0;
    ?>
    <form class='main-table' method='post' id='yearplandemandForm' <?php if(!$useDatatable) echo "data-ride='table'";?>>
      <div class="table-header fixed-right">
        <nav class="btn-toolbar pull-right setting"></nav>
      </div>
      <?php if(!$useDatatable) echo '<div class="table-responsive">';?>
    <table class='table has-sort-head table-fixed <?php if($useDatatable) echo ' datatable';?>' id='yearplandemandList' data-fixed-left-width='<?php echo $widths['leftWidth']?>' data-fixed-right-width='<?php echo $widths['rightWidth']?>'>
        <thead>
          <tr>
          <?php
          foreach($setting as $value)
          {
              if($value->show) $this->datatable->printHead($value, $orderBy, $vars);
          }
          ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach($yearplandemands as $yearplandemand):?>
          <tr data-id='<?php echo $yearplandemand->id?>'>
            <?php foreach($setting as $value) $this->yearplandemand->printCell($value, $yearplandemand, $users, $modulePairs, $useDatatable ? 'datatable' : 'table');?>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php if(!$useDatatable) echo '</div>';?>
      <div class="table-footer">
        <?php if($canIntegration):?>
        <div class="checkbox-primary check-all"><label><?php echo $lang->selectAll?></label></div>
        <?php endif;?>
        <div class="table-actions btn-toolbar">
          <div class='btn-group dropup'>
            <?php
              $actionLink = $this->createLink('yearplandemand', 'integration', 'yearplanID=' . $yearplanID);
              $disabled   = $canIntegration ? '' : "disabled='disabled'";

              echo html::commonButton($lang->yearplandemand->integration, "data-form-action='$actionLink' $disabled");
            ?>
          </div>
        </div>
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>