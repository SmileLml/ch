<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datepicker.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainContent" class="main-content fade">
  <div class="center-block">
  <div class="main-header">
    <h2><?php echo $lang->yearplandemand->edit;?></h2>
  </div>
  <form class="load-indicator main-form form-ajax" method='post' enctype='multipart/form-data' id='dataform'>
    <table class="table table-form">
      <tbody>
        <tr>
          <th><?php echo $lang->yearplandemand->name;?></th>
          <td >
            <div class='input-group'>
              <?php echo html::input('name', $yearplanDemand->name, "class='form-control'");?>
            </div>
          </td>
          <td></td>
        </tr>
        <?php if(isset($extendFields['class'])):?>
        <tr>
          <th><?php echo $extendFields['class']->name;?></th>
          <td>
            <?php echo $this->flow->buildControl($extendFields['class'], $yearplanDemand->class);?>
          </td>
          <td></td>
        </tr>
        <?php endif;?>
        <tr>
          <th><?php echo $lang->yearplandemand->level;?></th>
          <td>
            <div class='table-row'>
              <div class='table-col input-size'>
                <?php echo html::select('level', $lang->yearplandemand->levelList, $yearplanDemand->level, "class='form-control chosen'");?>
              </div>
            </div>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->yearplandemand->category;?></span>
              <?php echo html::select('category', $lang->yearplandemand->categoryList, $yearplanDemand->category, "class='form-control chosen'");?>
            </div>
          </td>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->yearplandemand->initDept;?></th>
          <td>
            <div class='table-row'>
              <div class='table-col input-size'>
                <?php echo html::select('initDept', $depts, $yearplanDemand->initDept, "class='form-control chosen'");?>
              </div>
            </div>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->yearplandemand->dept;?></span>
              <?php echo html::select('dept[]', $depts, $yearplanDemand->dept, "class='form-control picker-select' multiple");?>
            </div>
          </td>
          </td>
        </tr>
        <?php if(isset($extendFields['businessLine'])):?>
        <tr>
          <th><?php echo $extendFields['businessLine']->name;?></th>
          <td>
            <?php echo $this->flow->buildControl($extendFields['businessLine'], $yearplanDemand->businessLine);?>
          </td>
          <td></td>
        </tr>
        <?php endif;?>
        <tr>
          <th><?php echo $lang->yearplandemand->approvalDate;?></th>
          <td>
            <div class='table-row'>
              <div class='table-col input-size'>
                <?php echo html::input('approvalDate', formatTime($yearplanDemand->approvalDate), "class='form-control form-date'");?>
              </div>
            </div>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->yearplandemand->planConfirmDate;?></span>
              <?php echo html::input('planConfirmDate', formatTime($yearplanDemand->planConfirmDate), "class='form-control form-date'");?>
            </div>
          </td>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->yearplandemand->goliveDate;?></th>
          <td>
            <div class='table-row'>
              <div class='table-col input-size'>
                <?php echo html::input('goliveDate', formatTime($yearplanDemand->goliveDate), "class='form-control form-date'");?>
              </div>
            </div>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->yearplandemand->itPlanInto;?></span>
              <?php echo html::input('itPlanInto', $yearplanDemand->itPlanInto, "class='form-control'");?>
            </div>
          </td>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->yearplandemand->milestone;?></th>
          <td colspan="2" class="child">
            <table class='table table-form table-child'>
              <tr>
                <th style="width: 250px;text-align :left;"><?php echo $lang->yearplandemand->milestoneFields['batch']?></th>
                <th style="width: 250px;text-align :left;"><?php echo $lang->yearplandemand->milestoneFields['name']?></th>
                <th style="width: 250px;text-align :left;"><?php echo $lang->yearplandemand->milestoneFields['planConfirmDate']?></th>
                <th style="width: 250px;text-align :left;"><?php echo $lang->yearplandemand->milestoneFields['goliveDate']?></th>
              </tr>
              <?php if(!empty($yearplanmilestones)):?>
              <?php foreach($yearplanmilestones as $key => $yearplanmilestone):?>
              <tr>
                <td style="width: 250px;">
                  <?php echo html::input("yearplanmilestone[batch][{$key}]", $yearplanmilestone->batch, "class='form-control'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input("yearplanmilestone[name][{$key}]", $yearplanmilestone->name, "class='form-control'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input("yearplanmilestone[planConfirmDate][{$key}]", formatTime($yearplanmilestone->planConfirmDate), "class='form-control form-date'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input("yearplanmilestone[goliveDate][{$key}]", formatTime($yearplanmilestone->goliveDate), "class='form-control form-date'");?>
                </td>
                <td class='w-100px'>
                  <?php echo html::hidden("yearplanmilestone[id][{$key}]", $yearplanmilestone->id);?>
                  <a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a>
                  <?php if(count($yearplanmilestones) == $key+1):?>
                  <a href='javascript:;' class='btn btn-default delItem'><i class='icon-close'></i></a>
                  <?php endif;?>
                </td>
              </tr>
              <?php endforeach;?>
              <?php else:?>
                <tr>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[batch][0]', '', "class='form-control'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[name][0]', '', "class='form-control'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[planConfirmDate][0]', '', "class='form-control form-date'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[goliveDate][0]', '', "class='form-control form-date'");?>
                </td>
                <td class='w-100px'>
                  <?php echo html::hidden("yearplanmilestone[id][0]");?>
                  <a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a>
                </td>
              </tr>
              <tr>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[batch][1]', '', "class='form-control'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[name][1]', '', "class='form-control'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[planConfirmDate][1]', '', "class='form-control form-date'");?>
                </td>
                <td style="width: 250px;">
                  <?php echo html::input('yearplanmilestone[goliveDate][1]', '', "class='form-control form-date'");?>
                </td>
                <td class='w-100px'>
                  <?php echo html::hidden("yearplanmilestone[id][1]");?>
                  <a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a>
                  <a href='javascript:;' class='btn btn-default delItem'><i class='icon-close'></i></a>
                </td>
              </tr>
              <?php endif;?>
            </table>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->yearplandemand->itPM;?></th>
          <td>
            <div class='table-row'>
              <div class='table-col input-size'>
                <?php echo html::select('itPM', $users, $yearplanDemand->itPM, "class='form-control chosen'");?>
              </div>
            </div>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->yearplandemand->businessArchitect;?></span>
              <?php echo html::select('businessArchitect', $businessArchitect, $yearplanDemand->businessArchitect, "class='form-control chosen'");?>
            </div>
          </td>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->yearplandemand->businessManager;?></th>
          <td >
            <div class='input-group'>
              <?php echo html::select('businessManager[]', $users, $yearplanDemand->businessManager, "class='form-control picker-select' multiple");?>
            </div>
          </td>
          <td></td>
        </tr>
        <tr>
          <th><?php echo $lang->yearplandemand->isPurchased;?></th>
          <td >
            <div class='input-group'>
              <?php echo html::radio('isPurchased', $lang->yearplandemand->isPurchasedList, $yearplanDemand->isPurchased, '', 'block');?>
            </div>
          </td>
          <td></td>
        </tr>
        <tr>
          <th><?php echo $lang->yearplandemand->purchasedContents;?></th>
          <td colspan="2">
              <?php echo html::textarea('purchasedContents', $yearplanDemand->purchasedContents, "class='form-control' rows='5'");?>
          </td>
        </tr>
        <?php if(isset($extendFields['problems'])):?>
        <tr>
          <th><?php echo $extendFields['problems']->name;?></th>
          <td colspan="2">
            <?php echo $this->flow->buildControl($extendFields['problems'], $yearplanDemand->problems);?>
          </td>
        </tr>
        <?php endif;?>
        <tr>
          <th><?php echo $lang->yearplandemand->desc;?></th>
          <td colspan="2">
              <?php echo html::textarea('desc', $yearplanDemand->desc, "class='form-control' rows='5'");?>
          </td>
        </tr>
        <?php if(isset($extendFields['projectCost'])):?>
        <tr>
          <th><?php echo $extendFields['projectCost']->name;?></th>
          <td colspan="2">
            <?php echo $this->flow->buildControl($extendFields['projectCost'], $yearplanDemand->projectCost);?>
          </td>
        </tr>
        <?php endif;?>
        <?php $this->printExtendFields($yearplanDemand, 'table', 'columns=1');?>
        <tr>
          <th><?php echo $lang->files;?></th>
          <td colspan='2'>
            <?php echo $this->fetch('file', 'printFiles', array('files' => $yearplanDemand->files, 'fieldset' => 'false', 'object' => $yearplanDemand));?>
              <?php echo $this->fetch('file', 'buildform', 'fileCount=1&percent=0.85');?>
          </td>
        </tr>
        <tr>
          <td class='form-actions text-center' colspan='3'><?php echo html::submitButton() . html::backButton();?></td>
        </tr>
      </tbody>
    </table>
  </form>
</div>
<?php
$itemRow  = '<tr class="field-row">';
$itemRow .= "<td style='width: 250px'>";
$itemRow .= html::input('yearplanmilestone[batch][KEY]', '', "class='form-control'");
$itemRow .= '</td>';
$itemRow .= "<td style='width: 250px'>";
$itemRow .= html::input('yearplanmilestone[name][KEY]', '', "class='form-control'");
$itemRow .= '</td>';
$itemRow .= "<td style='width: 250px'>";
$itemRow .= html::input('yearplanmilestone[planConfirmDate][KEY]', '', "class='form-control form-date'");
$itemRow .= '</td>';
$itemRow .= "<td style='width: 250px'>";
$itemRow .= html::input('yearplanmilestone[goliveDate][KEY]', '', "class='form-control form-date'");
$itemRow .= '</td>';
$itemRow .= "<td class='w-100px'>";
$itemRow .= html::hidden("yearplanmilestone[id][KEY]");
$itemRow .= "<a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a> ";
$itemRow .= "<a href='javascript:;' class='btn btn-default delItem'><i class='icon-close'></i></a>";
$itemRow .= '</td>';
$itemRow .= '</tr>';

$childKey = empty($yearplanmilestones) ? 2 : count($yearplanmilestones);
js::set('itemRow', $itemRow);
js::set('childKey', $childKey);
?>
<script>
$(function()
{
    $('input[name="itPlanInto"]').on('input', function()
    {
      var value = $(this).val().replace(/[^0-9.]/g, '');
      var parts = value.split('.');

      if (parts.length > 2)
      {
          value = parts[0] + '.' + parts.slice(1).join('');
      }

      if (parts[1] && parts[1].length > 2)
      {
          value = `${parts[0]}.${parts[1].slice(0, 2)}`;
      }

      $(this).val(value)
    })

    $('input[name="itQuotedPrice"]').on('input', function()
    {
      var value = $(this).val().replace(/[^0-9.]/g, '');
      var parts = value.split('.');

      if (parts.length > 2)
      {
          value = parts[0] + '.' + parts.slice(1).join('');
      }

      if (parts[1] && parts[1].length > 2)
      {
          value = `${parts[0]}.${parts[1].slice(0, 2)}`;
      }

      $(this).val(value)
    })
    $('input[name="planBudget"]').on('input', function()
    {
      var value = $(this).val().replace(/[^0-9.]/g, '');
      var parts = value.split('.');

      if (parts.length > 2)
      {
          value = parts[0] + '.' + parts.slice(1).join('');
      }

      if (parts[1] && parts[1].length > 2)
      {
          value = `${parts[0]}.${parts[1].slice(0, 2)}`;
      }

      $(this).val(value)
    })
    $('input[name="purchasedBudget"]').on('input', function()
    {
      var value = $(this).val().replace(/[^0-9.]/g, '');
      var parts = value.split('.');

      if (parts.length > 2)
      {
          value = parts[0] + '.' + parts.slice(1).join('');
      }

      if (parts[1] && parts[1].length > 2)
      {
          value = `${parts[0]}.${parts[1].slice(0, 2)}`;
      }

      $(this).val(value)
    })
})

$(document).on('click', 'td.child .addItem', function()
{
    $(this).closest('tr').after(itemRow.replace(/KEY/g, childKey));
    $(this).closest('tr').next().find('.form-date').datetimepicker(
    {
        language:  config.clientLang,
        weekStart: 1,
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        minView: 2,
        forceParse: 0,
        format: 'yyyy-mm-dd'
    });
    childKey++;
});

$(document).on('click', 'td.child .delItem', function()
{
    if($(this).parents('.table-child').find('tbody tr').size() > 1)
    {
        $(this).closest('tr').remove();
    }
})
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
