<?php
/**
 * The create view of case module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     case
 * @version     $Id: create.html.php 4904 2013-06-26 05:37:45Z wyd621@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/mindmap.html.php' ?>
<?php js::set('productID', $productID);?>
<?php js::set('branch', $branch);?>
<?php js::set('viewType', $viewType);?>
<?php js::set('userConfig_module', $settings['module']);?>
<?php js::set('userConfig_scene', $settings['scene']);?>
<?php js::set('userConfig_case', $settings['case']);?>
<?php js::set('userConfig_pri', $settings['pri']);?>
<?php js::set('userConfig_group', $settings['group']);?>

<?php js::set('jsLng', $jsLng);?>

<div id='mainContent' class='main-content' style="min-width:100%; min-height:650px;">
  <div class='center-block'>
    <div class='main-header'>
      <h2><?php echo $lang->tree->importXmind;?></h2>
      <div class="pull-right btn-toolbar">
        <!-- Place buttons for switching between XMind and table. -->
      </div>
    </div>
    <form class='load-indicator main-form'>
      <table class='table table-form'>
        <tbody>
         <tr><td>
          <div id="mindmap" class="mindmap" style="height:calc(100vh - 230px)"></div>
         </td></tr>
        </tbody>
        <tfoot>
          <tr>
            <td class='text-center form-actions'>
              <button id="xmindmapSave" type="button" class="btn btn-wide btn-primary"><?php echo $lang->testcase->save;?></button>
            </td>
          </tr>
        </tfoot>
      </table>
    </form>
  </div>
</div>

<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
