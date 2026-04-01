<?php
/**
 * The copy view file of workflow module of ZDOO.
 *
 * @copyright   Copyright 2009-2016 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     商业软件，非开源软件
 * @author      Gang Liu <liugang@cnezsoft.com>
 * @package     workflow 
 * @version     $Id$
 * @link        http://www.zdoo.com
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<table class='table table-form'>
  <tr>
    <th>选择文件：</th>
    <td><?php echo html::select('file', $files, '', "class='form-control chosen'");?><td>
  </tr>
  <tr>
    <th></th>
    <td class='form-actions'>
      <?php echo html::commonButton($lang->import, 'hiddenwin', 'btn btn-primary export');?>
    </td>
    <td></td>
  </tr>
</table>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
