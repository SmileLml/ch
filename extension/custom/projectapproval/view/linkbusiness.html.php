<?php
/**
 * The create view file of leave module of Ranzhi.
 *
 * @copyright   Copyright 2009-2018 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      chujilu <chujilu@cnezsoft.com>
 * @package     linkBusiness
 * @version     $Id$
 * @link        http://www.ranzhi.org
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datepicker.html.php';?>
<div class="modal-header" id="effortBatchAddHeader">
  <div class="modal-actions">
    <button type="button" class="btn btn-link" data-dismiss="modal"><i class="icon icon-close"></i></button>
  </div>
  <h4 class="modal-title pull-left"><?php echo $title;?></h4>
</div>
<form id='ajaxForm' method='post' action="<?php echo $this->createLink('projectapproval', 'linkBusiness', 'projectapprovalID=' . $projectapprovalID)?>">
  <table  class='table table-form table-condensed'>
    <tr>
      <th class='w-80px'><?php echo $lang->common->business?></th>
      <td colspan="2" class="child">
        <table class="table table-bordered table-child">
          <thead>
            <tr>
                <th style="border:none;"><?php echo $lang->common->business?></th>
                <th style="border:none;"><?php echo $lang->projectapproval->business->developmentBudget;?></th>
                <th style="border:none;"><?php echo $lang->projectapproval->business->headBusiness;?></th>
                <th style="border:none;"><?php echo $lang->projectapproval->business->outsourcingBudget;?></th>
                <th style="border:none;"></th>
            </tr>
          </thead>  
          <tbody>
            <tr>
                <td style='width:400px;'><?php echo html::select("business[]", $businessList, '', "class='form-control picker-select businessList'");?></td>
                <td><?php echo html::input('developmentBudget[]', '', "class='form-control' readonly");?></td>
                <td><?php echo html::input('headBusiness[]', '', "class='form-control' readonly");?></td>
                <td><?php echo html::input('outsourcingBudget[]', '', "class='form-control' readonly");?></td>
                <td style='width:100px;'>
                  <a href='javascript:;' onclick='addNewLine(this)' class='btn btn-default addLine'><i class='icon-plus'></i></a>
                  <a href='javascript:;' onclick='removeLine(this)' class='btn btn-default removeLine' style='visibility: hidden'><i class='icon-close'></i></a>
                </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <th></th>
      <td><?php echo baseHTML::submitButton();?></td>
      <td></td>
    </tr>
  </table>
</form>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>