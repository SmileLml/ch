<?php
/**
 * The create view file of flow module of ZDOO.
 *
 * @copyright   Copyright 2009-2016 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     商业软件，非开源软件
 * @author      Gang Liu <liugang@cnezsoft.com>
 * @package     flow
 * @version     $Id$
 * @link        http://www.zdoo.com
 */
?>
<?php include 'header.html.php';?>
<?php include '../../common/view/picker.html.php';?>
<?php if(!empty($this->config->flow->editor->create)) include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<?php js::set('moduleName', $flow->module);?>
<?php js::set('action', $action->action);?>
<?php
if($this->app->rawModule == 'projectapproval')
{
    $descOptions = [];
    $projectRoleList = $childFields['sub_projectmembers']['projectRole']->options;
    if($projectRoleList)
    {
            foreach(array_filter(array_keys($projectRoleList)) as $projectRole) $descOptions[$projectRole] = $this->config->$projectRole;
    }

    $projectUnitPrice = isset($this->config->projectUnitPrice) ? $this->config->projectUnitPrice : 1000;
    $projectCosts     = $this->flow->ajaxGetProjectCost();

    js::set('projectUnitPrice',   $projectUnitPrice);
    js::set('projectCosts',       $projectCosts);
    js::set('projectDescOptions', $descOptions);
    js::set('richtextDefault',    $this->lang->projectapproval->richtextDefault);
}
?>

<div class='panel'>
  <div class='panel-heading'>
    <strong><?php echo str_replace('-', '', $title);?></strong>
  </div>
  <div class='panel-body'>
    <form id='ajaxForm' method='post' enctype='multipart/form-data' action='<?php echo $actionURL;?>'>
      <table class='table table-form'>
        <?php $hasChildFields = false;?>
        <?php $hasPrevField   = false;?>
        <?php $cols           = 0;?>
        <?php $maxCols        = $action->columns;?>
        <?php $colspan        = "colspan=" . ($maxCols - 1);?>

        <?php foreach($fields as $field):?>
        <?php
        if(!$field->show) continue;

        $width = ($field->width && $field->width != 'auto' ? $field->width . 'px' : 'auto');
        $value = $field->defaultValue;
        if($field->field == $prevField)
        {
            $hasPrevField = true;
            $value        = $prevDataID;
        }
        ?>

        <?php
        if($cols >= ($maxCols - 1))
        {
            echo '</tr>';
            $cols = 0;

            $attr     = 'class="field-row"';
            $relation = zget($relations, $field->field, '');
            if($relation && strpos(",$relation->actions,", ',many2one,') === false)
            {
                $prevDataID = isset($data->{$field->field}) ? $data->{$field->field} : 0;
                $attr       = "class='prevTR field-row' data-prev='{$relation->prev}' data-next='{$relation->next}' data-action='$action->action' data-field='{$relation->field}' data-dataID='$prevDataID'";
            }

            echo "<tr $attr>";
        }

        $cols += $field->colspan + $field->titleColspan;
        ?>
        <?php /* Print files. */ ?>
        <?php if($field->control == 'file'):?>
        <tr class='field-row'>
          <th class='w-100px'><?php echo $field->name;?></th>
          <td>
            <?php $fileField  = "files{$field->field}";?>
            <?php $labelsName = "labels{$field->field}";?>
            <?php echo $this->fetch('file', 'buildForm', "fileCount=1&percent=0.9&filesName={$fileField}&labelsName={$labelsName}");?>
          </td>
          <td></td>
        </tr>

        <?php /* Print mailto. */ ?>
        <?php elseif($field->field == 'mailto'):?>
        <tr class='field-row'>
          <th class='w-100px'><?php echo $field->name;?></th>
          <td>
            <div class='input-group'>
              <?php echo html::select('mailto[]', $users, $value, "class='form-control picker-select' data-placeholder='{$lang->chooseUserToMail}' multiple");?>
              <?php echo $this->fetch('my', 'buildContactLists');?>
            </div>
          </td>
          <td class='text-important'><?php echo $lang->flow->tips->notice;?></td>
        </tr>

        <?php /* Print sub tables. */ ?>
        <?php elseif(isset($childFields[$field->field])):?>
        <?php $hasChildFields = true;?>
        <tr class='field-row'>
          <th><?php echo $field->name;?></th>
          <td colspan='2' class='child'>
            <table class='table table-form table-child' data-child='<?php echo $field->field;?>' id='<?php echo $field->field;?>' style='width: <?php echo $width;?>'>
              <?php /* Add a empty row of sub table. */ ?>
              <?php if($field->field == 'sub_projectprocess'):?>
              <?php
              $process       = $childFields[$field->field]['detail'];
              $processName   = $childFields[$field->field]['name'];
              $processFields = $childFields[$field->field];
              ?>
              <tr>
              <?php foreach($childFields[$field->field] as $childField):?>
              <?php if(!$childField->show) continue;?>
              <?php if($childField->control == 'file') continue;?>
              <?php if($childField->field == 'detail') continue;?>
              <?php if($childField->field == 'name') continue;?>
              <th class='text-left' style='border:none;'> <?php echo $childField->name;?> </th>
              <?php endforeach;?>
              </tr>
              <tr>
              <?php foreach($childFields[$field->field] as $childField):?>
              <?php if(!$childField->show) continue;?>
              <?php if($childField->control == 'file') continue;?>
              <?php if($childField->field == 'detail') continue;?>
              <?php if($childField->field == 'name') continue;?>
              <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>
              <td style='width: <?php echo $childWidth;?>'>
                <?php $element = "children[$field->field][$childField->field][1]";?>
                <?php echo $this->flow->buildControl($childField, $childField->defaultValue, $element, $childModule);?>
              </td>
              <?php endforeach;?>
              </tr>
              <tr>
              <th class='text-left' style='border:none;' colspan='6'> <?php echo $processName->name;?> </th>
              </tr>
              <tr >
              <td colspan='6'>
                <?php $element = "children[$field->field][$processName->field][1]";?>
                <?php echo $this->flow->buildControl($processName, $processName->defaultValue, $element, $childModule);?>
              </td>
              </tr>
              <tr>
              <th class='text-left' style='border:none;' colspan='6'> <?php echo $process->name;?> </th>
              </tr>
              <tr >
              <td colspan='6'>
                <?php $element = "children[$field->field][$process->field][1]";?>
                <?php echo $this->flow->buildControl($process, $process->defaultValue, $element, $childModule);?>
              </td>
              <td class='w-180px'>
                <?php echo html::hidden("children[$field->field][id][1]");?>
                <a href='javascript:;' class='btn btn-default addProcess'><i class='icon-plus'></i></a>
                <a href='javascript:;' class='btn btn-default delProcess'><i class='icon-close'></i></a>
              </td>
              </tr>
              <tr id="processRowHr[<?php echo 1;?>]" style='border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;'></tr>
              <?php elseif($field->field == 'sub_projectbusiness'):?>
              <?php
              $business = $childFields[$field->field]['business'];
              $businessFields = $childFields[$field->field];
              ?>
              <tr>
                <th class='text-left' style='border:none;' colspan="6"> <?php echo $business->name;?> </th>
              </tr>
              <tr>
                <td colspan="6">
                <?php $element = "children[$field->field][$business->field][1]";?>
                <?php echo $this->flow->buildControl($business, $business->defaultValue, $element, $childModule);?>
                </td>
              </tr>
              <tr>
              <?php foreach($childFields[$field->field] as $childField):?>
              <?php if(!$childField->show) continue;?>
              <?php if($childField->control == 'file') continue;?>
              <?php if($childField->field == 'business') continue;?>
              <th class='text-left' style='border:none;'> <?php echo $childField->name;?> </th>
              <?php endforeach;?>
              </tr>
              <tr>
              <?php foreach($childFields[$field->field] as $childField):?>
              <?php if(!$childField->show) continue;?>
              <?php if($childField->field == 'business') continue;?>
              <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>

              <?php $childValue = $childField->defaultValue;?>
              <td style='width: <?php echo $childWidth;?>'>
                <?php
                if($field->readonly or $childField->readonly)
                {
                    if($childField->control == 'multi-select' or $childField->control == 'checkbox')
                    {
                        $childValues = explode(',', $childValue);
                        foreach($childValues as $childV)
                        {
                            if(in_array($childV, $this->config->flow->variables)) $childV = $this->loadModel('workflowhook')->getParamRealValue($childV);

                            echo zget($childField->options, $childV, '') . ' ';
                        }
                    }
                    else
                    {
                        echo zget($childField->options, $childValue);
                    }

                    echo html::hidden("children[$field->field][$childField->field][1]", $childValue);
                }
                else
                {
                    $element = "children[$field->field][$childField->field][1]";

                    echo $this->flow->buildControl($childField, $childValue, $element, $field->field);
                }
                ?>
              </td>
              <?php endforeach;?>
              <td class='w-100px'>
                <?php echo html::hidden("children[{$field->field}][id][1]");?>
                <a href='javascript:;' class='btn btn-default addBusiness'><i class='icon-plus'></i></a>
                <a href='javascript:;' class='btn btn-default delBusiness'><i class='icon-close'></i></a>
              </td>
              </tr>
              <tr id='businessRowHr[1]' style='border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;'></tr>
              <?php else:?>
              <thead>
                <tr>
                <?php foreach($childFields[$field->field] as $childField):?>
                <?php if(!$childField->show) continue;?>
                <?php if($childField->control == 'file') continue;?>
                <th style='border:none;'> <?php echo $childField->name;?> </th>
                <?php endforeach;?>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <?php foreach($childFields[$field->field] as $childField):?>
                  <?php if(!$childField->show) continue;?>
                  <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>

                  <?php $childValue = $childField->defaultValue;?>
                  <td style='width: <?php echo $childWidth;?>'>
                    <?php
                    if($field->readonly or $childField->readonly)
                    {
                        if($childField->control == 'multi-select' or $childField->control == 'checkbox')
                        {
                            $childValues = explode(',', $childValue);
                            foreach($childValues as $childV)
                            {
                                if(in_array($childV, $this->config->flow->variables)) $childV = $this->loadModel('workflowhook')->getParamRealValue($childV);

                                echo zget($childField->options, $childV, '') . ' ';
                            }
                        }
                        else
                        {
                            echo zget($childField->options, $childValue);
                        }

                        echo html::hidden("children[$field->field][$childField->field][1]", $childValue);
                    }
                    else
                    {
                        $element = "children[$field->field][$childField->field][1]";

                        echo $this->flow->buildControl($childField, $childValue, $element, $field->field);
                    }
                    ?>
                  </td>
                  <?php endforeach;?>
                  <td class='w-100px'>
                    <?php echo html::hidden("children[{$field->field}][id][1]");?>
                    <a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a>
                    <a href='javascript:;' class='btn btn-default delItem'><i class='icon-close'></i></a>
                  </td>
                </tr>
              </tbody>
              <?php endif;?>
            </table>
          </td>
        </tr>
        <?php elseif($field->field == 'productPlan'):?>
        <?php echo $this->fetch('component', 'productPlan')?>
        <?php /* Print other fields. */ ?>
        <?php else:?>
          <?php $titleWidth = ($field->titleWidth && $field->titleWidth != 'auto' ? $field->titleWidth . 'px' : '150px');?>
          <th <?php if($field->titleColspan > 1) echo "colspan={$field->titleColspan}";?> style='width: <?php echo $titleWidth;?>'><?php echo $field->name;?></th>
          <td <?php if($field->colspan > 1)      echo "colspan={$field->colspan}";?>>
            <div style='width: <?php echo $width;?>'>
              <?php
              if($field->readonly)
              {
                  if($field->control == 'multi-select' or $field->control == 'checkbox')
                  {
                      $values = explode(',', $value);
                      foreach($values as $v)
                      {
                          if(in_array($v, $this->config->flow->variables)) $v = $this->loadModel('workflowhook')->getParamRealValue($v);

                          echo zget($field->options, $v, '') . ' ';
                      }
                  }
                  else
                  {
                      if(in_array($value, $this->config->flow->variables)) $value = $this->loadModel('workflowhook', 'flow')->getParamRealValue($value, 'control');
                      echo zget($field->options, $value);
                  }

                  echo html::hidden($field->field, $value);
              }
              else
              {
                  echo $this->flow->buildControl($field, $value);
              }
              ?>
            </div>
          </td>
        <?php endif;?>
        <?php endforeach;?>

        <tr>
          <th></th>
          <td <?php echo $colspan;?> class='form-actions  text-center'>
            <?php if($prevField && !$hasPrevField) echo html::hidden($prevField, is_array($prevDataID) ? implode(',', $prevDataID) : $prevDataID);?>
            <?php echo baseHTML::submitButton();?>
            <?php echo html::backButton();?>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<?php js::set('childKey', 2);?>
<?php if($formulaScript) echo $formulaScript;?>
<?php if($linkageScript) echo $linkageScript;?>

<?php /* The table below is used to generate dom when click plus button. */?>
<?php if($hasChildFields)
{
    $itemRows = array();
    foreach($childFields as $childModule => $moduleFields)
    {
        $itemRow = '<tr class="field-row">';
        foreach($moduleFields as $childField)
        {
            if(!$childField->show) continue;
            $element = "children[$childModule][$childField->field][KEY]";
            $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');
            $itemRow .= "<td style='width: {$childWidth}'>";
            $itemRow .= $this->flow->buildControl($childField, $childField->defaultValue, $element, $childModule);
            $itemRow .= '</td>';
        }
        $itemRow .= "<td class='w-100px'>";
        $itemRow .= html::hidden("children[$childModule][id][KEY]");
        $itemRow .= "<a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a> ";
        $itemRow .= "<a href='javascript:;' class='btn btn-default delItem'><i class='icon-close'></i></a>";
        $itemRow .= '</td>';
        $itemRow .= '</tr>';

        $itemRows[$childModule] = $itemRow;
    }

    $processRow  = '<tr>';
    foreach($processFields as $processField)
    {
      if(!$processField->show) continue;
      if($processField->control == 'file') continue;
      if($processField->field == 'name') continue;
      if($processField->field == 'detail') continue;
      $processRow .= "<th class='text-left' style='border:none;'> {$processField->name} </th>";
    }
    $processRow .= '</tr>';
    $processRow .= '<tr>';
    foreach($processFields as $processField)
    {
      if(!$processField->show) continue;
      if($processField->control == 'file') continue;
      if($processField->field == 'detail') continue;
      if($processField->field == 'name') continue;
      $element = "children[sub_projectprocess][$processField->field][KEY]";
      $childWidth = ($processField->width && $processField->width != 'auto' ? $processField->width . 'px' : 'auto');
      $processRow .= "<td style='width: {$childWidth}'>";
      if($readonly or $processField->readonly)
      {
          $processRow .= html::input("$element", '', "class='form-control' readonly");
      }
      else
      {
          $processRow .= $this->flow->buildControl($processField, $processField->defaultValue, $element, 'sub_projectprocess');
      }
      $processRow .= '</td>';
    }
    $processRow .= '</tr>';
    $processRow .= '<tr>';
    $processRow .= "<th class='text-left' style='border:none;' colspan='6'> {$processName->name} </th>";
    $processRow .= '</tr>';
    $processRow .= '<tr>';
    $processRow .= "<td colspan='6'>";
    $processRow .= $this->flow->buildControl($processName, $processName->defaultValue, "children[sub_projectprocess][name][KEY]", 'sub_projectprocess');
    $processRow .= "</td>";
    $processRow .= "</tr>";
    $processRow .= '<tr>';
    $processRow .= "<th class='text-left' style='border:none;' colspan='6'> {$process->name} </th>";
    $processRow .= '</tr>';
    $processRow .= '<tr>';
    $processRow .= "<td colspan='6'>";
    $processRow .= $this->flow->buildControl($process, $process->defaultValue, "children[sub_projectprocess][detail][KEY]", 'sub_projectprocess');
    $processRow .= "</td>";
    $processRow .= "<td class='w-100px'>";
    $processRow .= html::hidden("children[sub_projectprocess][id][KEY]");
    $processRow .= "<a href='javascript:;' class='btn btn-default addProcess'><i class='icon-plus'></i></a> ";
    $processRow .= "<a href='javascript:;' class='btn btn-default delProcess'><i class='icon-close'></i></a>";
    $processRow .= "</td>";
    $processRow .= '</tr>';
    $processRow .= '<tr id="processRowHr[KEY]" style="border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;"></tr>';

    $businessRow  = '<tr>';
    $businessRow .= "<th style='border:none;text-align:left;' colspan='6'> {$business->name} </th>";
    $businessRow .= '</tr>';
    $businessRow .= '<tr>';
    $businessRow .= "<td colspan='6'>";
    $businessRow .= $this->flow->buildControl($business, $business->defaultValue, "children[sub_projectbusiness][business][KEY]", 'sub_projectbusiness');
    $businessRow .= '</tr>';
    $businessRow .= '<tr>';
    foreach($businessFields as $businessField)
    {
      if(!$businessField->show) continue;
      if($businessField->control == 'file') continue;
      if($businessField->field == 'business') continue;
      $businessRow .= "<th class='text-left' style='border:none;'> {$businessField->name} </th>";
    }
    $businessRow .= '</tr>';
    $businessRow .= '<tr>';
    foreach($businessFields as $businessField)
    {
      if(!$businessField->show) continue;
      if($businessField->control == 'file') continue;
      if($businessField->field == 'business') continue;
      $element = "children[sub_projectbusiness][$businessField->field][KEY]";
      $childWidth = ($businessField->width && $businessField->width != 'auto' ? $businessField->width . 'px' : 'auto');
      $businessRow .= "<td style='width: {$childWidth}'>";
      if($readonly or $businessField->readonly)
      {
          $businessRow .= html::input("$element", '', "class='form-control' readonly");
      }
      else
      {
          $businessRow .= $this->flow->buildControl($businessField, $businessField->defaultValue, $element, 'sub_projectbusiness');
      }
      $businessRow .= '</td>';
    }
    $businessRow .= "<td class='w-100px'>";
    $businessRow .= html::hidden("children[sub_projectbusiness][id][KEY]");
    $businessRow .= "<a href='javascript:;' class='btn btn-default addBusiness'><i class='icon-plus'></i></a> ";
    $businessRow .= "<a href='javascript:;' class='btn btn-default delBusiness'><i class='icon-close'></i></a>";
    $businessRow .= '</td>';
    $businessRow .= '</tr>';
    $businessRow .= '<tr id="businessRowHr[KEY]" style="border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;"></tr>';
    js::set('itemRows', $itemRows);
    js::set('businessRow', $businessRow);
    js::set('processRow', $processRow);
}
?>

<?php if($this->app->rawModule == 'projectapproval'):?>
<script>
$(function()
{
  $.each(richtextDefault, function(index, value){
    $('textarea[name="' + index + '"]').prev('div').children('div').eq(1).children('div').last().text(value);
  })
})
</script>
<?php endif;?>

<script>
  $(document).on('click', 'td.child .addProcess', function()
{
    var child = $(this).parents('table').data('child');

    // 1. 查找当前元素最近的父级 <tr> 元素
    var currentTr = $(this).closest('tr');
    // 2. 在当前 <tr> 元素之后查找目标 id 的 <tr> 元素
    var targetTr = currentTr.nextAll('tr').filter(function() {
        return this.id.indexOf('processRowHr') !== -1;
    }).first();

    targetTr.after(processRow.replace(/KEY/g, childKey));

    $(this).closest('tr').nextAll().find('.picker-select').picker();
    childKey++;
});
$(document).on('click', 'td.child .delProcess', function()
{
    if($(this).parents('.table-child').find('tbody>tr').size() > 7)
    {
        $(this).closest('tr').prevAll('tr').slice(0, 5).remove();
        $(this).closest('tr').next('tr').remove();
        $(this).closest('tr').remove();
    }
    else
    {
        $(this).closest('tr').find('input,select,textarea').val('');
        $(this).closest('tr').find('.chosen-controled').trigger('chosen:updated');
        $(this).closest('tr').find('.picker-selection-remove').click();
        $(this).closest('tr').prevAll('tr').slice(0, 6).find('input,select,textarea').val('');
        $(this).closest('tr').prevAll('tr').slice(0, 6).find('.chosen-controled').trigger('chosen:updated');
        $(this).closest('tr').prevAll('tr').slice(0, 6).find('.picker-selection-remove').click();
    }
})
$(document).on('click', 'td.child .addBusiness', function()
{
    var child = $(this).parents('table').data('child');

    // 1. 查找当前元素最近的父级 <tr> 元素
    var currentTr = $(this).closest('tr');
    // 2. 在当前 <tr> 元素之后查找目标 id 的 <tr> 元素
    var targetTr = currentTr.nextAll('tr').filter(function() {
        return this.id.indexOf('businessRowHr') !== -1;
    }).first();

    targetTr.after(businessRow.replace(/KEY/g, childKey));

    $(this).closest('tr').nextAll().find('.picker-select').picker();
    $(this).closest('tr').nextAll().find('.form-date').datetimepicker(
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
    $(this).closest('tr').nextAll().find('.form-datetime').datetimepicker(
    {
        language:  config.clientLang,
        weekStart: 1,
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        forceParse: 0,
        showMeridian: 1,
        format: 'yyyy-mm-dd hh:ii'
    });

    childKey++;
});

$(document).on('click', 'td.child .delBusiness', function()
{
    if($(this).parents('.table-child').find('tbody>tr').size() > 5)
    {
        $(this).closest('tr').prevAll('tr').slice(0, 3).remove();
        $(this).closest('tr').next('tr').remove();
        $(this).closest('tr').remove();
    }
    else
    {
        $(this).closest('tr').find('input,select,textarea').val('');
        $(this).closest('tr').find('.chosen-controled').trigger('chosen:updated');
        $(this).closest('tr').find('.picker-selection-remove').click();
        $(this).closest('tr').prevAll('tr').slice(0, 3).find('input,select,textarea').val('');
        $(this).closest('tr').prevAll('tr').slice(0, 3).find('.chosen-controled').trigger('chosen:updated');
        $(this).closest('tr').prevAll('tr').slice(0, 3).find('.picker-selection-remove').click();
    }
})

$(document).on('click', 'td.child .addItem', function()
{
    var child = $(this).parents('table').data('child');
    $(this).closest('tr').after(itemRows[child].replace(/KEY/g, childKey));
    initSelect($(this).closest('tr').next().find('.picker-select'));
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
    $(this).closest('tr').next().find('.form-datetime').datetimepicker(
    {
        language:  config.clientLang,
        weekStart: 1,
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        forceParse: 0,
        showMeridian: 1,
        format: 'yyyy-mm-dd hh:ii'
    });

    childKey++;
});

$(document).on('click', 'td.child .delItem', function()
{
    if($(this).parents('.table-child').find('tbody tr').size() > 1)
    {
        $(this).closest('tr').remove();
    }
    else
    {
        $(this).closest('tr').find('input,select,textarea').val('');
    }
})
</script>
<script>
<?php helper::import('../js/search.js');?>
</script>
<?php include 'footer.html.php';?>
