<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datatable.fix.html.php';?>
<?php
$canIntegration = common::hasPriv('demand', 'integration');
?>
<div id="mainMenu" class="clearfix">
  <div id="sidebarHeader">
    <div class="title">
      <?php
      echo $moduleName;
      if($moduleID)
      {
          $removeLink = $browseType == 'bymodule' ? inlink('browse', "poolID=$poolID&browseType=$browseType&param=0&orderBy=$orderBy&recTotal=0&recPerPage={$pager->recPerPage}") : 'javascript:removeCookieByKey("demandModule")';
          echo html::a($removeLink, "<i class='icon icon-sm icon-close'></i>", '', "class='text-muted'");
      }
      ?>
    </div>
  </div>
  <div class="btn-toolbar pull-left">
    <?php
      foreach($lang->demand->labelList as $label => $labelName)
      {
          $active = $browseType == $label ? 'btn-active-text' : '';
          echo html::a($this->createLink('demand', 'browse', "poolID=$poolID&browseType=$label&param=0&orderBy=$orderBy&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}"), '<span class="text">' . $labelName . '</span>' . ($browseType == $label ? " <span class='label label-light label-badge'>{$pager->recTotal}</span>" : ''), '', "class='btn btn-link $active'");
      }
    ?>
    <a class="btn btn-link querybox-toggle" id='bysearchTab'><i class="icon icon-search muted"></i> <?php echo $lang->searchAB;?></a>
  </div>
  <div class="btn-toolbar pull-right">
    <div class='btn-group'>
      <?php if(common::hasPriv('demand', 'export') ||  common::hasPriv('demand', 'exportTemplate')):?>
      <button class="btn btn-link" data-toggle="dropdown"><i class="icon icon-export muted"></i> <span class="text"><?php echo $lang->export ?></span> <span class="caret"></span></button>
      <ul class="dropdown-menu" id='exportActionMenu'>
        <?php
        $class = common::hasPriv('demand', 'export') ? '' : "class=disabled";
        $misc  = common::hasPriv('demand', 'export') ? "data-toggle='modal' data-type='iframe' class='export'" : "class=disabled";
        $link  = common::hasPriv('demand', 'export') ? $this->createLink('demand', 'export', "poolID=$poolID&orderBy=$orderBy&browseType=$browseType") : '#';
        echo "<li $class>" . html::a($link, $lang->demand->export, '', $misc) . "</li>";

        $class = common::hasPriv('demand', 'exportTemplate') ? '' : "class='disabled'";
        $link  = common::hasPriv('demand', 'exportTemplate') ? $this->createLink('demand', 'exportTemplate', "poolID=$poolID") : '#';
        $misc  = common::hasPriv('demand', 'exportTemplate') ? "data-toggle='modal' data-type='iframe' data-width='30%' class='exportTemplate'" : "class='disabled'";
        echo "<li $class>" . html::a($link, $lang->demand->exportTemplate, '', $misc) . '</li>';
        ?>  
      </ul>
      <?php endif;?>
      <?php if(common::hasPriv('demand', 'import')) echo html::a($this->createLink('demand', 'import', "poolID=$poolID"), '<i class="icon-import muted"></i> <span class="text">' . $lang->demand->import . '</span>', '', "class='btn btn-link import' data-toggle='modal' data-type='iframe'");?>
    </div>
    <div class='btn-group dropdown'>
      <?php
      $createLink      = $this->createLink('demand', 'create', "poolID=$poolID");
      $batchCreateLink = $this->createLink('demand', 'batchCreate', "poolID=$poolID");
      $buttonLink  = '';
      $buttonTitle = '';
      if(common::hasPriv('demand', 'batchCreate'))
      {
          $buttonLink  = $batchCreateLink;
          $buttonTitle = $lang->demand->batchCreate;
      }

      if(common::hasPriv('demand', 'create'))
      {
          $buttonLink  = $createLink;
          $buttonTitle = $lang->demand->create;
      }

      $hidden = empty($buttonLink) ? 'hidden' : '';
      echo html::a($buttonLink, "<i class='icon icon-plus'></i> $buttonTitle", '', "class='btn btn-primary $hidden create-demand-btn'");
      ?>

      <?php if(!empty($poolID) and common::hasPriv('demand', 'batchCreate') and common::hasPriv('demand', 'create')): ?>
      <button type='button' class="btn btn-primary dropdown-toggle" data-toggle='dropdown'><span class='caret'></span></button>
      <ul class='dropdown-menu pull-right'>
        <li><?php echo html::a($createLink, $lang->demand->create);?> </li>
        <li><?php echo html::a($batchCreateLink, $lang->demand->batchCreate);?></li>
      </ul>
      <?php endif;?>
    </div>
  </div>
</div>
<div id="mainContent" class="main-row fade">
  <div class="side-col" id="sidebar">
    <div class="sidebar-toggle"><i class="icon icon-angle-left"></i></div>
    <div class="cell">
      <?php if(empty($moduleTree)):?>
      <hr class="space">
      <div class="text-center text-muted"><?php echo $lang->demand->noModule;?></div>
      <hr class="space">
      <?php endif;?>
      <?php echo $moduleTree;?>
      <div class="text-center">
        <?php common::printLink('demand', 'manageTree', "poolID=$poolID", $lang->demand->manageTree, '', "class='btn btn-info btn-wide'");?>
        <hr class="space-sm" />
      </div>
    </div>
  </div>
  <div class="main-col">
    <div class="cell<?php if($browseType == 'bysearch') echo ' show';?>" id="queryBox" data-module='demand'></div>
    <?php if(empty($demands)):?>
    <div class="table-empty-tip">
      <p><span class="text-muted"><?php echo $lang->noData;?></span></p>
    </div>
    <?php else:?>
    <?php
    $vars = "poolID=$poolID&browseType=$browseType&param=0&orderBy=%s&recTotal=$pager->recTotal&recPerPage=$pager->recPerPage&pageID=$pager->pageID";

    $datatableId  = $this->moduleName . ucfirst($this->methodName);
    $useDatatable = (isset($config->datatable->$datatableId->mode) and $config->datatable->$datatableId->mode == 'datatable');

    if($useDatatable) include $app->getModuleRoot() . 'common/view/datatable.html.php';

    $setting = $this->datatable->getSetting('demand');
    $widths  = $this->datatable->setFixedFieldWidth($setting);
    $columns = 0;
    ?>
    <form class='main-table' method='post' id='demandForm' <?php if(!$useDatatable) echo "data-ride='table'";?>>
      <div class="table-header fixed-right">
        <nav class="btn-toolbar pull-right setting"></nav>
      </div>
      <?php if(!$useDatatable) echo '<div class="table-responsive">';?>
    <table class='table has-sort-head table-fixed <?php if($useDatatable) echo ' datatable';?>' id='demandList' data-fixed-left-width='<?php echo $widths['leftWidth']?>' data-fixed-right-width='<?php echo $widths['rightWidth']?>'>
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
          <?php foreach($demands as $demand):?>
          <tr data-id='<?php echo $demand->id?>'>
            <?php foreach($setting as $value) $this->demand->printCell($value, $demand, $users, $modulePairs, $useDatatable ? 'datatable' : 'table');?>
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
              $actionLink = $this->createLink('demand', 'integration', 'pool=' . $poolID);
              $disabled   = $canIntegration ? '' : "disabled='disabled'";

              echo html::commonButton($lang->demand->integration, "data-form-action='$actionLink' $disabled");
            ?>
          </div>
        </div>
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>
<div class="modal fade" id="toStory">
  <div class="modal-dialog mw-500px">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h4 class="modal-title"><?php echo $lang->demand->chooseType;?></h4>
      </div>
      <div class="modal-body">
        <table class='table table-form'>
          <tr>
            <th><?php echo $lang->demand->storyType;?></th>
            <td><?php echo html::radio('totype', $lang->demand->storyTypeList, 'story');?></td>
            <td class='text-center'>
              <?php echo html::commonButton($lang->demand->next, "id='toStoryBtn'", 'btn btn-primary');?>
              <?php echo html::hidden('demand', '');?>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
<?php js::set('moduleID', $moduleID);?>
<script>
$('#toStoryBtn').on('click', function()
{
    var demandID = $('#demand').val();
    var type = $("input[name*='totype']:checked").val();
    var link = createLink('demand', 'tostory', 'demandID=' + demandID + '&type=' + type);
    location.href = link;
})

function getDemandID(obj)
{
    var demandID = $(obj).attr("data-id");
    $('#demand').val(demandID);
}
$('#module' + moduleID).closest('li').addClass('active');
</script>
<?php if(!empty($useDatatable)):?>
<script>
$(function(){$('#demandForm').table();})
</script>
<?php endif;?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
