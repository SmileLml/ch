<?php
/**
 * The suspend file of project module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang<wwccss@gmail.com>
 * @package     project
 * @version     $Id: suspend.html.php 935 2013-01-16 07:49:24Z wwccss@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getExtensionRoot() . 'max/common/view/header.modal.html.php';?>
<?php include $app->getExtensionRoot() . 'max/common/view/picker.html.php';?>
<?php
js::set('projectID', $projectID);
js::set('businessID', $businessID);
?>
<form method='post' target='hiddenwin'>
  <table class='table table-form'>
    <tr>
      <th class='w-100px'><?php echo $lang->project->selectProduct;?></th>
      <td style="overflow: visible"><?php echo html::select('product', $products, '', "class='chosen form-control'");?></td>
    </tr>
    <tr>
      <td class='text-center form-actions' colspan='2'><?php echo html::commonButton($lang->confirm, "id='toRelationButton'", 'btn btn-primary btn-wide'); ?></td>
    </tr>
  </table>
</form>
<?php include $app->getExtensionRoot() . 'max/common/view/footer.modal.html.php';?>
