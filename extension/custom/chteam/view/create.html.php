<?php
/**
 * The create view file of chteam module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Yidong Wang <yidong@cnezsoft.com>
 * @package     chteam
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/chosen.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='main-header'>
    <h2><?php echo $lang->chteam->create?></h2>
  </div>
  <form method='post' class='form-ajax' enctype='multipart/form-data'>
    <table class='table table-form'>
      <tr>
        <th class='w-100px'><?php echo $lang->chteam->name?></th>
          <td><?php echo html::input('name', '', "class='form-control'")?></td>
          <td></td>
      </tr>
      <tr>
        <th class='w-100px'><?php echo $lang->chteam->leader?></th>
        <td><?php echo html::select('leader', $users, '', "class='form-control chosen'")?></td>
      </tr>
      <tr>
        <th class='w-100px'><?php echo $lang->chteam->members?></th>
        <td><?php echo html::select('members[]', $users, '', "class='form-control picker-select' multiple");?></td>
        </tr>
      <tr>
        <th class='w-100px'><?php echo $lang->chteam->desc?></th>
        <td colspan='3'><?php echo html::textarea('desc', '', "rows='6' class='form-control kindeditor' hidefocus='true'")?></td>
      </tr>
      <tr>
        <td colspan='3' class='text-center form-actions'>
        <?php echo html::submitButton();?>
        <?php echo html::backButton();?>
        </td>
      </tr>
    </table>
  </form>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>