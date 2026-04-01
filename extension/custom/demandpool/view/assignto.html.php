<?php
/**
 * The complete file of demandpool module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Yidong Wang <yidong@cnezsoft.com>
 * @package     demandpool
 * @version     $Id: complete.html.php 935 2010-07-06 07:49:24Z jajacn@126.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2>
        <span class='label label-id'><?php echo $demandpool->id;?></span>
        <?php echo $demandpool->name;?>
      </h2>
    </div>
    <form method='post' target='hiddenwin'>
      <table class='table table-form'>
        <tr>
          <th class='w-80px'><?php echo $lang->demandpool->assignedTo;?></th>
          <td class='w-p25-f'><?php echo html::select('assignedTo', $users, $demandpool->assignedTo, "class='form-control chosen'");?></td><td></td>
        </tr>  
        <tr>
          <th><?php echo $lang->comment;?></th>
          <td colspan='2'><?php echo html::textarea('comment', '', "rows='6' class='form-control w-p98'");?></td>
        </tr>
        <tr>
          <td colspan='3' class='text-center form-actions'>
            <?php echo html::submitButton();?>
          </td>
        </tr>
      </table>
    </form>
    <hr class='small' />
    <?php include '../../common/view/action.html.php';?>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
