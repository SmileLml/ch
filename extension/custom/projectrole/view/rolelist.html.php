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
          <strong><?php echo $lang->projectrole->role;?></strong>
        </div>
      </div>
      <table class='table table-form mw-600px'>
        <tr class="text-center">
          <td class="w-120px"><strong><?php echo $lang->projectrole->name;?></strong></td>
          <td><strong><?php echo $lang->projectrole->desc;?></strong></td>
        </tr>
        <?php foreach($roleList as $role => $title):?>
        <tr class="text-center">
          <td class="w-120px"><?php echo $title;?></td>
          <td><?php echo html::input($role, zget($this->config, $role, ''), "class='form-control'");?></td>
        </tr>
        <?php endforeach;?>
        <tr>
          <td colspan='2' class='text-center'><?php echo html::submitButton();?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
