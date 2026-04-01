<?php include $app->getExtensionRoot() . 'max/flow/view/header.html.php';?>
<?php include $app->getExtensionRoot() . 'max/common/view/picker.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<style>
.checkbox-primary {display: inline-block; line-height: 20px;}
#ganttView .gantt_layout_cell {min-width: 560px!important;}
.review-result {white-space: nowrap;}
.datetimepicker {width:200px;}
.panel-heading strong{font-size:15px;}
.tooltip { max-width: none;white-space: normal;}
</style>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    
  </div>
</div>
<div id='mainContent' class='main-content'>
  <div class='main-header'>
    <h2><?php echo $title;?></h2>
  </div>
  <?php $childKey       = 1;?>
  <form id='operateForm' method='post' enctype='multipart/form-data' action='<?php echo $actionURL;?>' class='not-watch' style="height: 91%;overflow: hidden;">
    <div id='reviewRow' class='main-row fade split-row in' style="overflow: auto;height: calc(100% - 30px);display:block;">
      <div class='side-col' data-min-width='550' style='width:1000px;'>
        <div class='cell scrollbar-hover'>
          <div class="panel panel-block">
            <div class="panel-heading">
              <strong><?php echo $this->lang->projectapproval->finishInfo;?></strong>
            </div>
            <div class="panel-body scroll">
              <table class="table table-hover table-fixed">
                <tr>
                  <th><?php echo $fields['finishApplicant']->name;?></th>
                  <td><?php echo zget($fields['finishApplicant']->options, $data->finishApplicant);?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['finishApplicationDate']->name;?></th>
                  <td><?php echo $data->finishApplicationDate?></td>
                </tr>
              </table>
            </div>
          </div>
          <div class="panel panel-block">
            <div class="panel-heading">
              <strong><?php echo $fields['summary']->name;?></strong>
            </div>
            <div class="panel-body scroll">
              <?php echo $data->summary;?>
            </div>
          </div>
          <div class="panel panel-block">
            <div class="panel-heading">
              <strong><?php echo $this->lang->basicInfo;?></strong>
            </div>
            <div class="panel-body scroll">
              <table class="table table-hover table-fixed">
                <tr>
                  <th><?php echo $fields['name']->name;?></th>
                  <td><?php echo $data->name;?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['pri']->name;?></th>
                  <td><?php echo zget($fields['pri']->options, $data->pri);?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['responsibleDept']->name;?></th>
                  <td><?php echo zget($fields['responsibleDept']->options, $data->responsibleDept);?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['projectNumber']->name;?></th>
                  <td><?php echo $data->projectNumber;?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['class']->name;?></th>
                  <td><?php echo zget($fields['class']->options, $data->class);?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['businessPM']->name;?></th>
                  <td><?php echo zget($fields['businessPM']->options, $data->businessPM);?></td>
                </tr>
              </table>
            </div>
          </div>
          <div class="panel panel-block">
            <div class="panel-heading">
              <strong><?php echo $this->lang->projectapproval->progressSituation;?></strong>
            </div>
            <div class="panel-body scroll">
              <table class="table table-hover table-fixed">
                <tr>
                  <th><?php echo $this->lang->projectapproval->progress;?></th>
                  <td><?php echo html::ring($project->progress);?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['begin']->name;?></th>
                  <td><?php echo $data->begin;?></td>
                </tr>
                <tr>
                  <th><?php echo $fields['end']->name;?></th>
                  <td><?php echo $data->end;?></td>
                </tr>
                <tr>
                  <th>
                    <?php echo $this->lang->projectapproval->progressDeviation;?>
                    <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->progressDeviationTips;?>'><i class='icon-help'></i></a >
                  </th>
                  <td><?php echo $progressDeviation;?></td>
                </tr>
                <tr>
                  <th>
                    <?php echo $this->lang->projectapproval->practicalBegin;?>
                    <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->practicalBeginTips;?>'><i class='icon-help'></i></a >
                  </th>
                  <td><?php echo $practicalBegin;?></td>
                </tr>
                <tr>
                  <th>
                    <?php echo $this->lang->projectapproval->practicalEnd;?>
                    <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->practicalEndTips;?>'><i class='icon-help'></i></a >
                  </th>
                  <td><?php echo $practicalEnd;?></td>
                </tr>
              </table>
            </div>
          </div>
          <?php
          $children = array();
          foreach($fields as $field)
          {
            if(isset($childFields[$field->field]))
            {
              $children[$field->field] = $field->name;
              continue;
            }
          }
          ?>
          <?php foreach($children as $child => $childName):?>
          <?php if(empty($childDatas[$child])) continue;?>
          <div class='panel panel-block'>
            <div class='panel-heading'><strong><?php echo $childName;?></strong></div>
            <div class='panel-body scroll'>
              <table class='table table-hover table-fixed'>
                <thead>
                  <tr>
                    <?php foreach($childFields[$child] as $childField):?>
                    <?php if(!$childField->show) continue;?>
                    <?php $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');?>
                    <th style='width: <?php echo $childWidth;?>'><?php echo $childField->name;?></th>
                    <?php endforeach;?>
                  </tr>
                </thead>
                <?php foreach($childDatas[$child] as $childData):?>
                <tr>
                  <?php foreach($childFields[$child] as $childField):?>
                  <?php if(!$childField->show) continue;?>
                  <?php
                  if(strpos(',date,datetime,', ",$childField->control,") !== false)
                  {
                      $childValue = formatTime($childData->{$childField->field});
                  }
                  else
                  {
                      if(is_array($childData->{$childField->field}))
                      {
                          $childValues = array();
                          foreach($childData->{$childField->field} as $value)
                          {
                              if(!empty($value)) $childValues[] = zget($childField->options, $value);
                          }
                          $childValue = implode(',', $childValues);
                      }
                      else
                      {
                          $childValue = zget($childField->options, $childData->{$childField->field});
                      }
                  }
                  ?>
                  <?php if($child == 'sub_projectvalue' && ($childField->field == 'completeDesc' || $childField->field == 'remark')):?>
                  <td data-toggle="modal" data-target="#projectvalueTemplate" data-title="<?php echo $childField->name;?>" data-body="<?php echo $childValue;?>"><?php echo $childValue;?></td>
                  <?php else:?>
                  <td title="<?php echo $childValue;?>"><?php echo $childValue;?></td>
                  <?php endif;?>
                  <?php endforeach;?>
                </tr>
                <?php endforeach;?>
              </table>
            </div>
          </div>
          <?php endforeach;?>
          <div class="panel panel-block">
            <div class="panel-heading">
              <strong><?php echo $this->lang->projectapproval->qualitySituation;?></strong>
            </div>
            <div class="panel-body scroll">
              <table class="table table-hover table-fixed">
                <tr>
                  <th><?php echo $this->lang->projectapproval->allBugNum;?></th>
                  <td><?php echo $allBugNum;?></td>
                </tr>
                <tr>
                  <th><?php echo $this->lang->projectapproval->onlineBugNum;?></th>
                  <td><?php echo $onlineBugNum;?></td>
                </tr>
                <tr>
                  <th>
                    <?php echo $this->lang->projectapproval->onlineBugprogress;?>
                    <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->onlineBugprogressTips;?>'><i class='icon-help'></i></a >
                  </th>
                  <td><?php echo $onlineBugprogress;?></td>
                </tr>
                <tr>
                  <th><?php echo $this->lang->projectapproval->unsolvedBugNum;?></th>
                  <td><?php echo $unsolvedBugNum;?></td>
                </tr>
              </table>
            </div>
          </div>
          <div class="panel panel-block">
            <div class="panel-heading">
              <strong><?php echo $this->lang->projectapproval->changeNumStatistics;?></strong>
            </div>
            <div class="panel-body scroll">
              <table class="table table-hover table-fixed">
                <tr>
                  <th><?php echo $this->lang->projectapproval->allChangeNum;?></th>
                  <td><?php echo $allChangeNum;?></td>
                </tr>
                <?php foreach($fields['changeType']->options as $changeTypeKey => $changeType):?>
                <?php if(empty($changeType)) continue;?>
                <tr>
                  <th><?php echo $changeType . $this->lang->projectapproval->changeNum;?></th>
                  <td><?php echo $changeTypeNum[$changeTypeKey];?></td>
                </tr>
                <?php endforeach;?>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class='col-spliter' id="splitLine"></div>
      <div class='main-col' data-min-width='600' id="issueList" style="width:600px">
      <?php $class = empty($reviewcl) ? '' : 'review-footer';?>
      <div class='cell <?php echo $class;?>'>
        <table class='table table-form'>
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
          <?php if(!$field->show || $field->field == 'sub_projectcost' ||  $field->field == 'sub_projectvalue') continue;?>
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
              <?php $fileField  = "{$field->field}files";?>
              <?php $labelsName = "labels{$field->field}";?>
              <?php echo $this->fetch('file', 'printFiles', array('files' => $data->$fileField, 'fieldset' => 'false', 'object' => null, 'method' => 'view', 'showDelete' => false, 'showEdit' => false));?>
              
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
                <?php if($field->field == 'sub_projectbusiness'):?>
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
                <tr>
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
                  <td class='w-100px'>
                    <?php echo html::hidden("children[$childModule][id][$childKey]", $childData->id);?>
                    <a href='javascript:;' class='btn btn-default addBusiness'><i class='icon-plus'></i></a>
                    <a href='javascript:;' class='btn btn-default delBusiness'><i class='icon-close'></i></a>
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
                  <a href='javascript:;' class='btn btn-default addBusiness'><i class='icon-plus'></i></a>
                  <a href='javascript:;' class='btn btn-default delBusiness'><i class='icon-close'></i></a>
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
            <td colspan='3' class='form-actions text-center'>
            <?php echo baseHTML::submitButton();?>
            </td>
          </tr>
        </table>
      </div>
      </div>
    </div>
  </form>
</div>

<div class="modal fade" id="projectvalueTemplate">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only"><?php echo $lang->close; ?></span></button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
      </div>
    </div>
  </div>
</div>

<style>
.review-footer{margin-top: 10px; height: 260px;}
.review-footer table th{vertical-align: middle}
.reviewcl td{padding: 4px 10px !important;}
</style>
<?php
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
js::set('itemRows', $itemRows);
?>
<?php js::set('childKey', $childKey);?>
<?php js::set('stopSubmit', $lang->review->stopSubmit);?>
<script>

$(document).ready(function() {
    $('#projectvalueTemplate').on('show.zui.modal', function (event) {
        var button = $(event.relatedTarget);
        var title = button.data('title');
        var body = button.data('body');
        var modal = $(this);
        modal.find('.modal-title').text(title);
        modal.find('.modal-body').text(body);
    });
});

$(function()
{
    $('textarea[name*="sub_projectreviewdetails"]').attr('rows', 1);

    $.setAjaxForm('#operateForm');

    $('#dataID').change(function()
    {
        location.href = createLink(window.moduleName, window.action, 'dataID=' + $(this).val());
    });

    $('.prevTR select').change(function()
    {
        loadPrevData($(this).parents('tr'), $(this).val());
    });

    $('.prevTR').each(function()
    {
        loadPrevData($(this));
    });

    var isTrans = $('input[name="isTrans"]:checked').val();
    if(isTrans == 'yes')
    {
        $('#reviewResultnoReview').parent().show();
    }
    else
    {
        $('#reviewResultnoReview').parent().hide();
    }
    $('input[name="isTrans"]').change(function()
    {
        var isTrans = $('input[name="isTrans"]:checked').val();
        if(isTrans == 'yes')
        {
            $('#reviewResultnoReview').parent().show();
            $('#reviewResultnoReview').prop('checked', true);
        }
        else
        {
            $('#reviewResultnoReview').parent().hide();
            $('#reviewResultpass').prop('checked', true);
        }
    });

    var mainHeight = $(window).height() - $('#footer').outerHeight() - $('#header').outerHeight() - 350;
    var sideHeight = mainHeight + 275;
    $('.main-col #reviewcl').css('height', mainHeight);
    $('.side-col .cell').css('height', sideHeight);
    $('table.reviewcl .issueResult, table.reviewcl').change(function()
    {
        var result = $(this).val();
        if(result == 1)
        {
            $(this).closest('tr').find('.opinion').attr('readonly', true);
        }
        else
        {
            $(this).closest('tr').find('.opinion').attr('readonly', false);
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
        $('textarea[name*="sub_projectreviewdetails"]').attr('rows', 1);
        childKey++;
    });

    $(document).on('click', 'td.child .delItem', function()
    {
        if($(this).parents('.table-child').find('tbody>tr').size() > 1)
        {
            let id = $(this).closest('tr').find('td:first select').attr('id');
            let key = id.replace('childrensub_projectmembersprojectRole', '');
            let projectRole = $('#childrensub_projectmembersprojectRole' + key).val();
            if(projectRole != undefined && projectRole)
            {
              $(this).closest('tr').find('td:first select').val('')
              getApprovalReviewer(projectRole);
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
})

function loadPrevData($selector, dataID, element)
{
    if(typeof dataID  === 'undefined') dataID  = 0;
    if(typeof element === 'undefined') element = 'tr';

    var prev   = $selector.data('prev');
    var next   = $selector.data('next');
    var action = $selector.data('action');
    var field  = $selector.data('field');
    if(dataID == 0) dataID = $selector.data('dataid');

    $('.prevData.' + prev).remove();

    /* Must use flow as module name here because the function ajaxGetPrevData is not a action of a flow. */
    var link = createLink('flow', 'ajaxGetPrevData', 'prev=' + prev + '&next=' + next + '&action=' + action + '&dataID=' + dataID + '&element=' + element);
    $.get(link, function(prevData)
    {
        if(!prevData) return false;

        $selector.after(prevData);
    });
}
</script>
<?php if((!empty($doc->contentType) and $doc->contentType == 'markdown') or (!empty($template->type) and $template->type == 'markdown')):?>
<?php css::import($jsRoot . "markdown/simplemde.min.css");?>
<?php js::import($jsRoot . 'markdown/simplemde.min.js'); ?>
<script>
$(function()
{
    var simplemde = new SimpleMDE({element: $("#markdownContent")[0],toolbar:false, status: false});
    simplemde.value($('#markdownContent').html());
    simplemde.togglePreview();

    $('#content .CodeMirror .editor-preview a').attr('target', '_blank');
})
</script>
<?php endif;?>
<?php include $app->getExtensionRoot() . 'max/flow/view/footer.html.php';?>
