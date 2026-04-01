<?php
/**
 * The browse view file of release module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     release
 * @version     $Id: browse.html.php 4129 2013-01-18 01:58:14Z wwccss $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div id='mainContent' class='main-row'>
  <?php include $app->getModuleRoot() . 'custom/view/sidebar.html.php';?>
  <div class='main-col main-content'>
    <form class="load-indicator main-form form-ajax" method='post'>
      <div class='main-header'>
        <div class='heading'>
          <strong><?php echo $lang->projectrole->cost;?></strong>
        </div>
      </div>
      <table class='table table-form w-800px'>
        <tr class="text-center">
          <td><?php echo $lang->projectrole->costType;?></td>
          <td><?php echo $lang->projectrole->costDesc;?></td>
          <td class='w-100px'><?php echo $lang->projectrole->costUnit;?></td>
          <td><?php echo $lang->projectrole->costPrice;?></td>
        </tr>
        <?php foreach($costTypeList as $typeValue => $title):?>
        <tr class='text-center'>
          <td><?php echo $title;?></td>
          <?php
              $projectCost = $this->config->costType->$typeValue;
              if(!empty($projectCost)) $projectCost = json_decode($projectCost, true);
          ?>
          <td>
            <?php echo html::input("costDescs[$typeValue]", $projectCost['costDesc'], "class='form-control'");?>
          </td>
          <td class='w-100px'>
            <?php echo html::select("costUnits[$typeValue]", $lang->projectrole->costUnitList, $projectCost['costUnit'], "class='form-control chosen'");?>
          </td>
          <td>
            <?php echo html::number("costPrices[$typeValue]", $projectCost['costPrice'], "class='form-control'");?>
          </td>
        </tr>
        <?php endforeach;?>
        <tr>
          <td colspan='4' class='text-center'><?php echo html::submitButton();?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
