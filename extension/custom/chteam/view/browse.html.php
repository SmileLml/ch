<?php
/**
 * The browse view file of chteam module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     chteam
 * @version     $Id: browse.html.php 5102 2013-07-12 00:59:54Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolBar pull-left">
    <a class="btn btn-link querybox-toggle" id='bysearchTab'><i class="icon icon-search muted"></i> <?php echo $lang->search->common;?></a>
  </div>
  <div class="btn-toolbar pull-right">
    <?php common::printLink('chteam', 'create', '', '<i class="icon icon-plus"></i> ' . $lang->chteam->create, '', 'class="btn btn-primary create-project-btn"');?>
  </div>
  <div id='mainContent' class="main-row fade">
  </div>
</div>
<div id="mainContent" class="main-row fade">
  <div class="main-col">
    <div class="cell<?php if($browseType == 'bysearch') echo ' show';?>" id="queryBox" data-module='chteam'></div>
      <?php if(empty($chteams)):?>
      <div class="table-empty-tip">
        <p>
          <span class="text-muted"><?php echo $lang->chteam->empty;?></span>
            <?php if(common::hasPriv('chteam', 'create')) echo html::a($this->createLink('chteam', 'create', ''), "<i class='icon icon-plus'></i> " . $lang->chteam->create, '', "class='btn btn-info'");?>
        </p>
      </div>
      <?php else:?>
      <form class='main-table' id='chteamForm' method='post'>
        <div class="table-header fixed-right">
          <nav nav class="btn-toolbar pull-right"></nav>
        </div>
        <?php
        $vars             = "browseType=$browseType&param=$param&orderBy=%s&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}&pageID={$pager->pageID}";
        $datatableId      = $this->moduleName . ucfirst($this->methodName);
        $useDatatable     = (!commonModel::isTutorialMode() and (isset($config->datatable->$datatableId->mode) and $config->datatable->$datatableId->mode == 'datatable'));
        $setting          = $this->datatable->getSetting('chteam');
        $fixedFieldsWidth = $this->datatable->setFixedFieldWidth($setting);
        if($useDatatable) include $app->getModuleRoot() . '/common/view/datatable.html.php';
        ?>
        <?php if(!$useDatatable) echo '<div class="table-responsive">';?>
        <table id = "chteamList" class='table has-sort-head <?php if($useDatatable) echo 'datatable';?>' data-fixed-left-width='<?php echo $fixedFieldsWidth['leftWidth']?>' data-fixed-right-width='<?php echo $fixedFieldsWidth['rightWidth']?>'>
        <?php $canBatchEdit = false;?>
          <thead>
            <tr>
              <?php
              foreach($setting as $value) $this->datatable->printHead($value, $orderBy, $vars, $canBatchEditse);
              ?>
            </tr>
          </thead>
          <tbody class="sortable">
          <?php foreach($chteams as $chteam):?>
            <tr data-id="<?php echo $chteam->id;?>">
            <?php foreach($setting as $value) $this->chteam->printCell($value, $chteam, $users);?>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
        <?php if(!$useDatatable) echo '</div>';?>
        <div class='table-footer'>
        <div class="table-actions btn-toolbar">
        </div>
        <div class="table-statistic"><?php echo sprintf($lang->chteam->summary, count($chteams));?></div>
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
