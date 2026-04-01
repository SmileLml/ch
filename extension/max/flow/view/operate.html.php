<?php
/**
 * The operate view file of flow module of ZDOO.
 *
 * @copyright   Copyright 2009-2016 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     商业软件，非开源软件
 * @author      Gang Liu <liugang@cnezsoft.com>
 * @package     flow
 * @version     $Id$
 * @link        http://www.zdoo.com
 */
?>
<?php
include 'header.html.php';
include '../../common/view/picker.html.php';
$isModal      = $action->open == 'modal';
$colspan      = $isModal ? '' : "colspan='2'";
$editorModule = $action->action == 'edit' ? 'edit' : 'operate';
if(!empty($this->config->flow->editor->$editorModule)) include $app->getModuleRoot() . 'common/view/kindeditor.html.php';
js::set('moduleName', $flow->module);
js::set('action', $action->action);
js::set('dataID', $data->id);
?>
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
    js::set('projectRoleList',    $childFields['sub_projectmembers']['projectRole']->options);
    js::set('richtextDefault',    $this->lang->projectapproval->richtextDefault);
}
?>
<?php if(!$isModal):?>
<div class='panel'>
  <div class='panel-heading'>
    <strong><?php echo str_replace('-', '', $title);?></strong>
  </div>
  <div class='panel-body'>
<?php else:?>
<div id='mainContent' class='main-content'>
  <div class='main-header'>
    <h2><?php echo $title;?></h2>
  </div>
<?php endif;?>
    <form id='operateForm' method='post' enctype='multipart/form-data' action='<?php echo $actionURL;?>' class='not-watch'>
      <table class='table table-form'>
        <?php $hasChildFields = false;?>
        <?php $childKey       = 1;?>
        <?php $cols           = 0;?>
        <?php $maxCols        = $action->columns;?>
        <?php $colspan        = "colspan=" . ($maxCols - 1);?>
        <?php if(!empty($config->openedApproval) && $flow->approval == 'enabled' && strpos($action->action, 'approvalsubmit') !== false):?>
        <tr>
          <th class='w-100px'><?php echo $lang->{$flow->module}->reviewers;?></th>
          <?php $reviewCols = $flow->module == 'projectapproval' ? $maxCols : ($maxCols - 1); ?>
          <td id='reviewerBox' <?php echo "colspan=$reviewCols"?>></td>
        </tr>
        <?php endif;?>

        <?php if($canTransTo):?>
        <tr class='field-row'>
          <th class='w-100px'><?php echo $lang->isTrans;?></th>
          <td><?php echo html::radio('isTrans', $lang->isTransList, 'no')?></td>
        </tr>
        <tr class='field-row hidden'>
          <th class='w-100px'><?php echo $lang->transTo;?></th>
          <td class='required'><?php echo html::select('transTo[]', $transToUsers, '', "class='form-control picker-select' multiple");?></td>
        </tr>
        <script>
            $('input[name="isTrans"]').change(function() {
                if ($(this).val() === 'yes')
                {
                    $('#transTo').closest('tr').removeClass('hidden');
                }
                else
                {
                    $('#transTo').closest('tr').addClass('hidden');
                }
            });
        </script>
        <?php endif;?>
        <tr>
        <?php foreach($fields as $field):?>
        <?php if(!$field->show) continue;?>
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
        <?php $readonly = $field->readonly;?>
        <?php $width    = ($field->width && $field->width != 'auto' ? $field->width . 'px' : 'auto');?>

        <?php $value = ($field->defaultValue or $field->defaultValue === 0) ? $field->defaultValue : zget($data, $field->field, '');?>

        <?php /* Print files. */ ?>
        <?php if($field->control == 'file'):?>
        <tr class='field-row'>
          <th class='w-100px'><?php echo $field->name;?></th>
          <td>
            <?php $fileField  = "files{$field->field}";?>
            <?php $labelsName = "labels{$field->field}";?>
            <?php if($readonly) echo $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false'));?>
            <?php if(!$readonly) echo $this->fetch('file', 'buildForm', "fileCount=1&percent=0.9&filesName={$fileField}&labelsName={$labelsName}");?>
          </td>
          <?php if(!$isModal):?>
          <td></td>
          <?php endif;?>
        </tr>

        <?php /* Print mailto. */ ?>
        <?php elseif($field->field == 'mailto'):?>
        <?php if($field->buildin) $users = $this->loadModel('user')->getDeptPairs('pofirst|nodeleted|noclosed');?>
        <?php if($field->buildin) $users = $this->loadModel('user')->getDeptPairs('pofirst|nodeleted|noclosed');?>
        <tr class='field-row'>
          <th class='w-100px'><?php echo $field->name;?></th>
          <td>
            <div class='input-group'>
              <?php echo html::select('mailto[]', $users, $value, "class='form-control picker-select' data-placeholder='{$lang->chooseUserToMail}' multiple");?>
              <?php echo $this->fetch('my', 'buildContactLists');?>
            </div>
          </td>
          <?php if(!$isModal):?>
          <td class='text-important'><?php echo $lang->flow->tips->notice;?></td>
          <?php endif;?>
        </tr>

        <?php /* Print sub tables. */ ?>
        <?php elseif(isset($childFields[$field->field])):?>
        <?php $hasChildFields = true;?>
        <?php $childModule    = $field->field;?>
        <tr class='field-row'>
          <th><?php echo $field->name;?></th>
          <td colspan='2' class='child'>
            <table class='table table-form table-child' data-child='<?php echo $field->field;?>' id='<?php echo $field->field;?>' style='width: <?php echo $width;?>'>
              <?php $datas = isset($childDatas[$field->field]) ? $childDatas[$field->field] : array();?>
              <?php if($field->field == 'sub_projectprocess'):?>
              <?php
              $process       = $childFields[$field->field]['detail'];
              $processName   = $childFields[$field->field]['name'];
              $processFields = $childFields[$field->field];
              ?>
              <?php if($datas):?>
              <?php foreach($datas as $childData):?>
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

              <?php $childValue = ($childField->defaultValue or $childField->defaultValue === 0) ? $childField->defaultValue : zget($childData, $childField->field, '');?>
                <td style='width: <?php echo $childWidth;?>'>
                  <?php
                  if($readonly or $childField->readonly)
                  {
                      if($childField->control == 'multi-select' or $childField->control == 'checkbox')
                      {
                          $childValues = explode(',', $childValue);
                          foreach($childValues as $childV)
                          {
                              if(in_array($childV, $this->config->flow->variables)) $childV = $this->loadModel('workflowhook')->getParamRealValue($childV);

                              echo zget($field->options, $childV, '') . ' ';
                          }
                      }
                      else
                      {
                          echo zget($childField->options, $childValue);
                      }

                      echo html::hidden("children[$childModule][$childField->field][$childKey]", $childValue);
                  }
                  else
                  {
                      $element = "children[$childModule][$childField->field][$childKey]";

                      echo $this->flow->buildControl($childField, $childValue, $element, $field->field);
                  }
                  ?>
                </td>
                <?php endforeach;?>
              </tr>
               <tr colspan='6'>
              <th class='text-left' style='border:none;'> <?php echo $processName->name;?> </th>
              </tr>
              <tr colspan='6'>
              <?php $childValue = ($processName->defaultValue or $processName->defaultValue === 0) ? $processName->defaultValue : zget($childData, $processName->field, '');?>
              <td colspan='6'>
                <?php

                  $element = "children[$childModule][$processName->field][$childKey]";

                  echo $this->flow->buildControl($processName, $childValue, $element, $field->field);

                ?>
              </td>
              </tr>
              <tr colspan='6'>
              <th class='text-left' style='border:none;'> <?php echo $process->name;?> </th>
              </tr>
              <tr colspan='6'>
              <?php $childValue = ($process->defaultValue or $process->defaultValue === 0) ? $process->defaultValue : zget($childData, $process->field, '');?>
              <td colspan='6'>
                <?php
                if($readonly or $process->readonly)
                {
                    if($process->control == 'multi-select' or $process->control == 'checkbox')
                    {
                        $childValues = explode(',', $childValue);
                        foreach($childValues as $childV)
                        {
                            if(in_array($childV, $this->config->flow->variables)) $childV = $this->loadModel('workflowhook')->getParamRealValue($childV);

                            echo zget($field->options, $childV, '') . ' ';
                        }
                    }
                    else
                    {
                        echo zget($process->options, $childValue);
                    }

                    echo html::hidden("children[$childModule][$process->field][$childKey]", $childValue);
                }
                else
                {
                    $element = "children[$childModule][$process->field][$childKey]";

                    echo $this->flow->buildControl($process, $childValue, $element, $field->field);
                }
                ?>
              </td>
              <?php if(!$readonly):?>
                <td class='w-180px'>
                  <?php echo html::hidden("children[$childModule][id][$childKey]", $childData->id);?>
                  <a href='javascript:;' class='btn btn-default addProcess'><i class='icon-plus'></i></a>
                  <a href='javascript:;' class='btn btn-default delProcess'><i class='icon-close'></i></a>
                </td>
              <?php endif;?>
              </tr>
              <tr id="processRowHr[<?php echo $childKey;?>]" style='border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;'></tr>
              <?php $childKey++;?>
              <?php endforeach;?>
              <?php endif;?>
              <?php /* Add a empty row of sub table. */ ?>
              <?php if(!$readonly && empty($datas)):?>
              <tr>
              <?php foreach($childFields[$field->field] as $childField):?>
              <?php if(!$childField->show) continue;?>
              <?php if($childField->control == 'file') continue;?>
              <?php if($childField->field == 'name') continue;?>
              <?php if($childField->field == 'detail') continue;?>
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
                <?php $element = "children[$childModule][$childField->field][$childKey]";?>
                <?php echo $this->flow->buildControl($childField, $childField->defaultValue, $element, $childModule);?>
              </td>
              <?php endforeach;?>
              </tr>
              <tr>
              <th class='text-left' style='border:none;' colspan='6'> <?php echo $processName->name;?> </th>
              </tr>
              <tr >
              <td colspan='6'>
                <?php $element = "children[$childModule][$processName->field][$childKey]";?>
                <?php echo $this->flow->buildControl($processName, $processName->defaultValue, $element, $childModule);?>
              </td>
              </tr>
              <tr>
              <th class='text-left' style='border:none;' colspan='6'> <?php echo $process->name;?> </th>
              </tr>
              <tr >
              <td colspan='6'>
                <?php $element = "children[$childModule][$process->field][$childKey]";?>
                <?php echo $this->flow->buildControl($process, $process->defaultValue, $element, $childModule);?>
              </td>
              <td class='w-180px'>
                <?php echo html::hidden("children[$childModule][id][$childKey]");?>
                <a href='javascript:;' class='btn btn-default addProcess'><i class='icon-plus'></i></a>
                <a href='javascript:;' class='btn btn-default delProcess'><i class='icon-close'></i></a>
              </td>
              </tr>
              <tr id="processRowHr[<?php echo $childKey;?>]" style='border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;'></tr>
              <?php $childKey++;?>
              <?php endif;?>
              <?php elseif($field->field == 'sub_projectbusiness'):?>
              <?php
              $business       = $childFields[$field->field]['business'];
              $businessFields = $childFields[$field->field];
              ?>
              <?php if($datas):?>
              <?php foreach($datas as $childData):?>
              <tr colspan='6'>
              <th class='text-left' style='border:none;'> <?php echo $business->name;?> </th>
              </tr>
              <tr colspan='6'>
              <?php $childValue = ($business->defaultValue or $business->defaultValue === 0) ? $business->defaultValue : zget($childData, $business->field, '');?>
              <td colspan='6'>
                <?php
                if($readonly or $business->readonly)
                {
                    if($business->control == 'multi-select' or $business->control == 'checkbox')
                    {
                        $childValues = explode(',', $childValue);
                        foreach($childValues as $childV)
                        {
                            if(in_array($childV, $this->config->flow->variables)) $childV = $this->loadModel('workflowhook')->getParamRealValue($childV);

                            echo zget($field->options, $childV, '') . ' ';
                        }
                    }
                    else
                    {
                        echo zget($business->options, $childValue);
                    }

                    echo html::hidden("children[$childModule][$business->field][$childKey]", $childValue);
                }
                else
                {
                    $element = "children[$childModule][$business->field][$childKey]";

                    echo $this->flow->buildControl($business, $childValue, $element, $field->field);
                }
                ?>
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
              <tr data-businessStatus = '<?php echo $childData->status;?>'>
              <?php foreach($childFields[$field->field] as $childField):?>
              <?php if(!$childField->show) continue;?>
              <?php if($childField->control == 'file') continue;?>
              <?php if($childField->field == 'business') continue;?>
              <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>

              <?php $childValue = ($childField->defaultValue or $childField->defaultValue === 0) ? $childField->defaultValue : zget($childData, $childField->field, '');?>
                <td style='width: <?php echo $childWidth;?>'>
                  <?php
                  if($readonly or $childField->readonly)
                  {
                      if($childField->control == 'multi-select' or $childField->control == 'checkbox')
                      {
                          $childValues = explode(',', $childValue);
                          foreach($childValues as $childV)
                          {
                              if(in_array($childV, $this->config->flow->variables)) $childV = $this->loadModel('workflowhook')->getParamRealValue($childV);

                              echo zget($field->options, $childV, '') . ' ';
                          }
                      }
                      else
                      {
                          echo zget($childField->options, $childValue);
                      }

                      echo html::hidden("children[$childModule][$childField->field][$childKey]", $childValue);
                  }
                  else
                  {
                      $element = "children[$childModule][$childField->field][$childKey]";

                      echo $this->flow->buildControl($childField, $childValue, $element, $field->field);
                  }
                  ?>
                </td>
                <?php endforeach;?>
                <?php if(!$readonly):?>
                <td class='w-180px'>
                  <?php echo html::hidden("children[$childModule][id][$childKey]", $childData->id);?>
                  <?php echo html::hidden("childrenbusinessID[$childKey]", $childData->business);?>
                  <?php if($action->module == 'projectapproval' && $action->action != 'approvalsubmit4'):?>
                  <a href='javascript:;' class='btn btn-default addBusiness'><i class='icon-plus'></i></a>
                  <?php endif;?>
                  <?php if($action->module == 'projectapproval' && $action->action != 'approvalsubmit3'):?>
                  <a href='javascript:;' class='btn btn-default delBusiness'><i class='icon-close'></i></a>
                  <?php endif;?>
                  <?php if($action->module == 'projectapproval' && ($action->action == 'approvalsubmit3' || $action->action == 'approvalsubmit4')):?>
                  <?php if($childData->status != 'closed' && $childData->status != 'cancelled' && $childData->status != 'beOnline'):?>
                  <a href='javascript:;' class='btn btn-default' onclick="editBusiness(<?php echo $childKey; ?>)"><i class='icon-edit'></i></a>
                  <?php endif;?>
                  <a href='javascript:;' class='btn btn-default' onclick="viewBusiness(<?php echo $childKey; ?>)"><i class='icon-eye'></i></a>
                  <?php endif;?>
                </td>
                <?php endif;?>
              </tr>
              <tr id='businessRowHr[<?php echo $childKey;?>]' style='border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;'></tr>
              <?php $childKey++;?>
              <?php endforeach;?>
              <?php endif;?>
              <?php /* Add a empty row of sub table. */ ?>
              <?php if(!$readonly && empty($datas)):?>
              <tr>
              <th class='text-left' style='border:none;' colspan='6'> <?php echo $business->name;?> </th>
              </tr>
              <tr >
              <td colspan='6'>
                <?php $element = "children[$childModule][$business->field][$childKey]";?>
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
              <?php if($childField->control == 'file') continue;?>
              <?php if($childField->field == 'business') continue;?>
              <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>
              <td style='width: <?php echo $childWidth;?>'>
                <?php $element = "children[$childModule][$childField->field][$childKey]";?>
                <?php echo $this->flow->buildControl($childField, $childField->defaultValue, $element, $childModule);?>
              </td>
              <?php endforeach;?>
              <td class='w-100px'>
                <?php echo html::hidden("children[$childModule][id][$childKey]");?>
                <?php echo html::hidden("childrenbusinessID[$childKey]");?>
                <a href='javascript:;' class='btn btn-default addBusiness'><i class='icon-plus'></i></a>
                <a href='javascript:;' class='btn btn-default delBusiness'><i class='icon-close'></i></a>
                <?php if($action->module == 'projectapproval' && ($action->action == 'approvalsubmit3' || $action->action == 'approvalsubmit4')):?>
                <a href='javascript:;' class='btn btn-default' onclick="editBusiness(<?php echo $childKey;?>)"><i class='icon-edit'></i></a>
                <a href='javascript:;' class='btn btn-default' onclick="viewBusiness(<?php echo $childKey;?>)"><i class='icon-eye'></i></a>
                <?php endif;?>
              </td>
              </tr>
              <tr id="businessRowHr[<?php echo $childKey;?>]" style='border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;'></tr>
              <?php $childKey++;?>
              <?php endif;?>
              <?php else:?>
              <?php if($datas):?>
              <thead>
                <tr>
                <?php foreach($childFields[$field->field] as $childField):?>
                <?php if(!$childField->show) continue;?>
                <?php if($childField->control == 'file') continue;?>
                <th style='border:none;'> <?php echo $childField->name;?> </th>
                <?php endforeach;?>
                </tr>
              </thead>
              <?php endif;?>

              <?php foreach($datas as $childData):?>
             </tbody>
              <tr>
                <?php foreach($childFields[$field->field] as $childField):?>
                <?php if(!$childField->show) continue;?>
                <?php if($childField->control == 'file') continue;?>
                <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>

                <?php $childValue = ($childField->defaultValue or $childField->defaultValue === 0) ? $childField->defaultValue : zget($childData, $childField->field, '');?>
                <td style='width: <?php echo $childWidth;?>'>
                  <?php
                  if($readonly or $childField->readonly)
                  {
                      if($childField->control == 'multi-select' or $childField->control == 'checkbox')
                      {
                          $childValues = explode(',', $childValue);
                          foreach($childValues as $childV)
                          {
                              if(in_array($childV, $this->config->flow->variables)) $childV = $this->loadModel('workflowhook')->getParamRealValue($childV);

                              echo zget($field->options, $childV, '') . ' ';
                          }
                      }
                      else
                      {
                          echo zget($childField->options, $childValue);
                      }

                      echo html::hidden("children[$childModule][$childField->field][$childKey]", $childValue);
                  }
                  else
                  {
                      $element = "children[$childModule][$childField->field][$childKey]";

                      echo $this->flow->buildControl($childField, $childValue, $element, $field->field);
                  }
                  ?>
                </td>
                <?php endforeach;?>
                <?php if(!$readonly):?>
                <td class='w-100px'>
                  <?php echo html::hidden("children[$childModule][id][$childKey]", $childData->id);?>
                  <a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a>
                  <a href='javascript:;' class='btn btn-default delItem'><i class='icon-close'></i></a>
                </td>
                <?php endif;?>
              </tr>
              <?php $childKey++;?>
              <?php endforeach;?>
              </tbody>

              <?php /* Add a empty row of sub table. */ ?>
              <?php if(!$readonly && empty($datas)):?>
              <thead>
                <tr>
                <?php foreach($childFields[$field->field] as $childField):?>
                <?php if(!$childField->show) continue;?>
                <?php if($childField->control == 'file') continue;?>
                <th style='border:none;'> <?php echo $childField->name;?> </th>
                <?php endforeach;?>
                </tr>
              </thead>
              <tr class='field-row'>
                <?php foreach($childFields[$field->field] as $childField):?>
                <?php if(!$childField->show) continue;?>
                <?php if($childField->control == 'file') continue;?>
                <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>
                <td style='width: <?php echo $childWidth;?>'>
                  <?php $element = "children[$childModule][$childField->field][$childKey]";?>
                  <?php echo $this->flow->buildControl($childField, $childField->defaultValue, $element, $childModule);?>
                </td>
                <?php endforeach;?>
                <td class='w-100px'>
                  <?php echo html::hidden("children[$childModule][id][$childKey]");?>
                  <a href='javascript:;' class='btn btn-default addItem'><i class='icon-plus'></i></a>
                  <a href='javascript:;' class='btn btn-default delItem'><i class='icon-close'></i></a>
                </td>
              </tr>
              <?php $childKey++;?>
              <?php endif;?>
              <?php endif;?>
            </table>
          </td>
        </tr>
        <?php elseif($field->field == 'productPlan'):?>
        <?php echo $this->fetch('component', 'productPlan', ['products' => [], 'plans' => [], 'defaultProduct' => $value])?>
        <?php /* Print other fields. */ ?>
        <?php else:?>
          <?php $titleWidth = ($field->titleWidth && $field->titleWidth != 'auto' ? $field->titleWidth . 'px' : '150px');?>
          <th <?php if($field->titleColspan > 1) echo "colspan={$field->titleColspan}";?> style='width: <?php echo $titleWidth;?>'><?php echo $field->name;?></th>
          <td <?php if($field->colspan > 1)      echo "colspan={$field->colspan}";?>>
            <div style='width: <?php echo $width;?>'>
              <?php
              if($readonly)
              {
                  if($field->control == 'multi-select' or $field->control == 'checkbox')
                  {
                      if(!is_array($value)) $value = explode(',', $value);
                      foreach($value as $v)
                      {
                          if(in_array($v, $this->config->flow->variables)) $v = $this->loadModel('workflowhook')->getParamRealValue($v);

                          echo zget($field->options, $v, '') . ' ';
                      }
                      $value = join(',', $value);
                  }
                  else
                  {
                      echo zget($field->options, $value);
                  }

                  echo html::hidden($field->field, $field->control == 'richtext' ? str_replace("'", '&#039;', htmlspecialchars($value)) : $value);
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
          <td <?php echo $colspan;?> class='form-actions text-center'>
            <?php echo baseHTML::submitButton();?>
            <?php if(!$isModal) echo html::backButton();?>
            <?php echo html::hidden('referer', $referer);?>
          </td>
        </tr>
      </table>
    </form>
<?php if(!$isModal):?>
  </div>
<?php endif;?>
</div>

<?php
/* The table below is used to generate dom when click plus button. */
if($hasChildFields)
{
    $itemRows = array();
    foreach($childFields as $childModule => $moduleFields)
    {
        $itemRow = '<tr>';
        foreach($moduleFields as $childField)
        {
            if(!$childField->show) continue;
            if($childField->control == 'file') continue;
            $element = "children[$childModule][$childField->field][KEY]";
            $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');
            $itemRow .= "<td style='width: {$childWidth}'>";
            if($readonly or $childField->readonly)
            {
                $itemRow .= html::input("$element", '', "class='form-control' readonly");
            }
            else
            {
                $itemRow .= $this->flow->buildControl($childField, $childField->defaultValue, $element, $childModule);
            }
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
      if($processField->field == 'detail') continue;
      if($processField->field == 'name') continue;
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
    $businessRow .= "<th class='text-left' style='border:none;' colspan='6'> {$business->name} </th>";
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
    $businessRow .= html::hidden("childrenbusinessID[KEY]");
    $businessRow .= "<a href='javascript:;' class='btn btn-default addBusiness'><i class='icon-plus'></i></a> ";
    $businessRow .= "<a href='javascript:;' class='btn btn-default delBusiness'><i class='icon-close'></i></a>";

    if($action->module == 'projectapproval' && ($action->action == 'approvalsubmit3' || $action->action == 'approvalsubmit4'))
    {
        $businessRow .= "<a href='javascript:;' class='btn btn-default' onclick='editBusiness(KEY)'><i class='icon-edit'></i></a>";
        $businessRow .= "<a href='javascript:;' class='btn btn-default' onclick='viewBusiness(KEY)'><i class='icon-eye'></i></a>";
    }

    $businessRow .= '</td>';
    $businessRow .= '</tr>';
    $businessRow .= '<tr id="businessRowHr[KEY]" style="border-bottom: 1px dashed rgba(0, 0, 0, 0.3); height: 20px;"></tr>';
    js::set('itemRows', $itemRows);
    js::set('businessRow', $businessRow);
    js::set('processRow', $processRow);
}
?>

<?php js::set('childKey', $childKey);?>
<?php if($formulaScript) echo $formulaScript;?>
<?php if($linkageScript) echo $linkageScript;?>
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

function editBusiness(key)
{
    var businessID = $('#childrenbusinessID' + key).val();
    var projectID  = dataID;
    var link       = createLink('business', 'projectchange', 'dataID=' + businessID + '|' + projectID, '', true);

    new $.zui.ModalTrigger(
    {
        type: 'iframe',
        url: link,
        width: 900,
    }).show();
}

function viewBusiness(key)
{
    var businessID = $('#childrenbusinessID' + key).val();
    var link       = createLink('business', 'view', 'dataID=' + businessID, '', true);

    window.open(link, '_blank');
}

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
        $(this).closest('tr').prevAll('tr').slice(0, 4).find('input,select,textarea').val('');
        $(this).closest('tr').prevAll('tr').slice(0, 4).find('.chosen-controled').trigger('chosen:updated');
        $(this).closest('tr').prevAll('tr').slice(0, 4).find('.picker-selection-remove').click();
    }

    link = createLink('business', 'deletedDraftBusiness', 'projectID=' + dataID + '&businessID=' + $(this).closest('tr').find('input[id*="childrenbusinessID"]').val());
    $.ajax({
        url: link,
        type: 'GET',
        async: true,
        dataType: 'json'
    });
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
    if($(this).parents('.table-child').find('tbody>tr').size() > 1)
    {
        let id = $(this).closest('tr').find('td:first select').attr('id');
        if (id && id.startsWith('childrensub_projectmembersprojectRole'))
        {
            let key = id.replace('childrensub_projectmembersprojectRole', '');
            let projectRole = $('#childrensub_projectmembersprojectRole' + key).val();
            if(projectRole != undefined && projectRole)
            {
                $(this).closest('tr').find('td:first select').val('')
                getApprovalReviewer(projectRole);
            }
        }
        $(this).closest('tr').remove();
    }
    else
    {
        $(this).closest('tr').find('input,select,textarea').val('');
        $(this).closest('tr').find('.chosen-controled').trigger('chosen:updated');
        $(this).closest('tr').find('.picker-selection-remove').click();
    }
})

$(function()
{
    window.sayHello = function()
    {
        parent.$.apps.open(parent.createLink('business', 'browse'), 'business', true)
        //parent.location.href = parent.createLink('business', 'browse');
    };
})

var link = createLink('flow', 'ajaxGetNodes', 'object=' + moduleName + '&id=' + dataID + '&action=' + action);
$('#reviewerBox').load(link, function(){$(this).find('select').picker()});
</script>
<script>
<?php helper::import('../js/search.js');?>
</script>
<?php if($action->module == 'projectapproval' && ($action->action == 'approvalsubmit3' || $action->action == 'approvalsubmit4')):?>
<style>
#triggerModal .modal-dialog {top: 5% !important;}
#triggerModal .modal-body   {height: 90% !important;}
</style>
<?php endif;?>
<?php include 'footer.html.php';?>
