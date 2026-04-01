<?php
/**
 * The finish report file of flow module of ZDOO.
 *
 * @copyright   Copyright 2009-2016 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     商业软件，非开源软件
 * @author      Gang Liu <liugang@cnezsoft.com>
 * @package     flow
 * @version     $Id$
 * @link        http://www.zdoo.com
 */
?>
<?php include $app->getExtensionRoot() . 'max/flow/view/header.html.php';?>
<?php include $app->getExtensionRoot() . 'max/common/view/picker.html.php';?>
<script src="https://cdn.bootcdn.net/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.js"></script>
<style>
.tooltip { max-width: none;white-space: normal;}
</style>
<?php js::set('requestType', $config->requestType);?>
<?php js::set('projectapproval', $projectapproval);?>
<?php js::set('finishReportTitle', $title);?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-right">
    <div class='btn-group dropdown'>
    <button id="exportPDF" type="button" class="btn btn-primary"><?php echo $lang->projectapproval->exportPDF;?></button>
    </div>
  </div>
</div>
<div class='tabs' id='tabsNav'>
  <div class='tab-content'>
    <div class='main-row'>
      <div class='main-col col-9'>
        <div class="panel panel-block">
          <div class="panel-heading">
            <strong><?php echo $this->lang->basicInfo;?></strong>
          </div>
          <div class="panel-body scroll">
            <table class="table table-hover table-fixed">
              <tr>
                <th><?php echo $fields['name']->name;?></th>
                <td><?php echo $data->name;?></td>
                <th><?php echo $fields['projectNumber']->name;?></th>
                <td><?php echo $data->projectNumber;?></td>
              </tr>
              <tr>
                <th><?php echo $fields['pri']->name;?></th>
                <td><?php echo zget($fields['pri']->options, $data->pri);?></td>
                <th><?php echo $fields['class']->name;?></th>
                <td><?php echo zget($fields['class']->options, $data->class);?></td>
              </tr>
              <tr>
                <th><?php echo $fields['responsibleDept']->name;?></th>
                <td><?php echo zget($fields['responsibleDept']->options, $data->responsibleDept);?></td>
                <th><?php echo $fields['businessPM']->name;?></th>
                <td><?php echo zget($fields['businessPM']->options, $data->businessPM);?></td>
              </tr>
              <tr>
                <th><?php echo $fields['finishReviewDate']->name;?></th>
                <td><?php echo $data->finishReviewDate;?></td>
                <th><?php echo $fields['finishReviewLocation']->name;?></th>
                <td><?php echo $data->finishReviewLocation;?></td>
              </tr>
              <tr>
                <th><?php echo $fields['finishParticipant']->name;?></th>
                <td>
                  <?php
                  foreach($data->finishParticipant as $finishParticipant) echo zget($fields['finishParticipant']->options, $finishParticipant) . '  ';
                  ?>
                </td>
                <th><?php echo $fields['finishAbsentee']->name;?></th>
                <td>
                  <?php
                  foreach($data->finishAbsentee as $finishAbsentee) echo zget($fields['finishAbsentee']->options, $finishAbsentee) . '  ';
                  ?>
                </td>
              </tr>
              <tr>
                <th><?php echo $fields['finishRecorder']->name;?></th>
                <td>
                  <?php
                  foreach($data->finishRecorder as $finishRecorder) echo zget($fields['finishRecorder']->options, $finishRecorder) . '  ';
                  ?>
                </td>
                <th></th>
                <td></td>
              </tr>
            </table>
          </div>
        </div>
        <div class="panel panel-block">
          <div class="panel-heading">
            <strong><?php echo $this->lang->projectapproval->finishFiles;?></strong>
          </div>
          <div class="panel-body scroll">
            
            <?php echo $this->fetch('file', 'printFiles', array('files' => $data->finishFilesfiles, 'fieldset' => 'false', 'object' => null, 'method' => 'view', 'showDelete' => false, 'showEdit' => false));?>
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
                <th>
                <?php echo $this->lang->projectapproval->progressDeviation;?>
                <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->progressDeviationTips;?>'><i class='icon-help'></i></a >
                </th>
                <td><?php echo $progressDeviation;?></td>
              </tr>
              <tr>
                <th><?php echo $fields['begin']->name;?></th>
                <td><?php echo $data->begin;?></td>
                <th>
                  <?php echo $this->lang->projectapproval->practicalBegin;?>
                  <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->practicalBeginTips;?>'><i class='icon-help'></i></a >
                </th>
                <td><?php echo $practicalBegin;?></td>
              </tr>
              <tr>
                <th><?php echo $fields['end']->name;?></th>
                <td><?php echo $data->end;?></td>
                <th>
                  <?php echo $this->lang->projectapproval->practicalEnd;?>
                  <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->practicalEndTips;?>'><i class='icon-help'></i></a >
                </th>
                <td><?php echo $practicalEnd;?></td>
              </tr>
            </table>
          </div>
        </div>
        <div class='panel panel-block'>
          <?php $child = 'sub_projectcost';?>
          <div class='panel-heading'><strong><?php echo $fields['sub_projectcost']->name;?></strong></div>
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
                  <td title='<?php echo $childValue;?>'><?php echo $childValue;?></td>
                  <?php endforeach;?>
                </tr>
                <?php endforeach;?>
              </table>
            </div>
          </div>

        <div class="panel panel-block">
          <div class="panel-heading">
            <strong><?php echo $this->lang->projectapproval->qualitySituation;?></strong>
          </div>
          <div class="panel-body scroll">
            <table class="table table-hover table-fixed">
              <tr>
                <th><?php echo $this->lang->projectapproval->allBugNum;?></th>
                <td><?php echo $allBugNum;?></td>
                <th>
                  <?php echo $this->lang->projectapproval->onlineBugprogress;?>
                  <a data-toggle='tooltip' data-placement='auto' title='<?php echo $this->lang->projectapproval->onlineBugprogressTips;?>'><i class='icon-help'></i></a >
                </th>
                <td><?php echo $onlineBugprogress;?></td>
              </tr>
              <tr>
                <th><?php echo $this->lang->projectapproval->onlineBugNum;?></th>
                <td><?php echo $onlineBugNum;?></td>
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
        <div class='panel panel-block'>
          <?php $child = 'sub_projectvalue';?>
          <div class='panel-heading'><strong><?php echo $fields['sub_projectvalue']->name;?></strong></div>
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
                  <?php if($childField->field == 'completeDesc' || $childField->field == 'remark'):?>
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
          <div class="panel panel-block">
          <div class="panel-heading">
            <strong><?php echo $this->lang->projectapproval->reviewConclusion;?></strong>
          </div>
          <div class="panel-body scroll">
            <table class="table table-hover table-fixed">
              <thead>
                <tr>
                  <th><?php echo $fields['reviewers']->name;?></th>
                  <th><?php echo $this->lang->user->dept;?></th>
                  <th><?php echo $fields['reviewResult']->name;?></th>
                  <th><?php echo $fields['reviewDate']->name;?></th>
                  <th><?php echo $this->lang->projectapproval->reviewOpinion;?></th>
                <tr>
              </thead>
              <tbody>
                <?php foreach($reviewDetails as $reviewDetail):?>
                <tr>
                  <td><?php echo $reviewDetail->reviewer;?></td>
                  <td><?php echo $reviewDetail->reviewDept;?></td>
                  <td><?php echo $reviewDetail->reviewResult;?></td>
                  <td><?php echo $reviewDetail->reviewDate;?></td>
                  <td><?php echo $reviewDetail->remark;?></td>
                </tr>
                <?php endforeach;?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
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
</script>
<?php include $app->getExtensionRoot() . 'max/flow/view/footer.html.php';?>