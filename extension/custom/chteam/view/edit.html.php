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
<?php include $app->getModuleRoot() . 'common/view/header.lite.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2>
        <?php echo $lang->chteam->edit;?>
      </h2>
    </div>
    <form method='post' target='hiddenwin' style='padding-bottom:80px;'>
      <table class='table table-form'>
        <tr>
          <th class='w-100px'><?php echo $lang->chteam->name?></th>
          <td><?php echo html::input('name', $chteam->name, "class='form-control'")?></td>
          <td></td>
        </tr>
        <tr>
          <th class='w-100px'><?php echo $lang->chteam->leader?></th>
          <td><?php echo html::select('leader', $users, $chteam->leader, "class='form-control chosen'")?></td>
        </tr>
        <tr>
          <th class='w-100px'><?php echo $lang->chteam->members?></th>
          <td><?php echo html::select('members[]', $users, $chteam->members, "class='form-control picker-select' multiple");?></td>
        </tr>
        <tr>
          <th class='w-100px'><?php echo $lang->chteam->desc?></th>
          <td colspan='2'><?php echo html::textarea('desc', $chteam->desc, "rows='6'   class='form-control'")?></td>
        </tr>
        <tr>
          <td colspan='2' class='text-center form-actions'>
          <?php echo html::submitButton();?>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.lite.html.php';?>
