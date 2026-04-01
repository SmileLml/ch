<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div>
  <div class='main-col'>
    <style>
    .col-sm-3{padding-bottom: 5px;}
    </style>
    <div class='cell'>
      <div class='with-padding'>
        <div class="table-row" id='conditions'>
          <div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->projectName;?></span>
                <?php echo html::select('projectapproval', $projectapprovalList, $projectapproval, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->deferredType;?></span>
                <?php echo html::select('deferredType[]', $lang->monitoring->deferredTypeList, $deferredType, "class='form-control chosen' multiple");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->projectpri;?></span>
                <?php echo html::select('projectpri', $workflowFieldOptions['priList'], $projectpri, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->responsibleDept;?></span>
                <?php echo html::select('responsibleDept', $depts, $responsibleDept, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->businessPM;?></span>
                <?php echo html::select('businessPM', $users, $businessPM, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->itPM;?></span>
                <?php echo html::select('itPM', $users, $itPM, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->businessDept;?></span>
                <?php echo html::select('businessDept', $depts, $businessDept, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->productManager;?></span>
                <?php echo html::select('productManager', $users, $productManager, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class='input-group input-group-sm'>
                <span class='input-group-addon'><?php echo $lang->monitoring->itDevM;?></span>
                <?php echo html::select('itDevM', $users, $itDevM, "class='form-control chosen'");?>
              </div>
            </div>
            <div class='col-sm-3'>
              <div class="input-group w-100">
                <?php echo html::submitButton($lang->searchAB, '', 'btn btn-primary btn-block btn-query');?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php if(empty($projectApprovals)):?>
    <div class="cell">
      <div class="table-empty-tip">
        <p><span class="text-muted"><?php echo $lang->error->noData;?></span></p>
      </div>
    </div>
    <?php else:?>
    <div class='cell'>
      <div class='panel'>
        <div class="panel-heading">
          <div class="panel-title"><?php echo $title;?></div>
          <div class='panel-actions pull-right'>
          <?php
          $link  = common::hasPriv('monitoring', 'export') ? $this->createLink('monitoring', 'export', "params={$params}") : '#';
          echo html::a($link, $lang->export, '', 'class="btn btn-primary btn-block btn-query export"');
          ?>
          </div>
        </div>
        <form class='main-table' method='post' data-ride="table">
          <div class="table-responsive">
            <table class='table table-bordered table-fixed'>
              <thead>
                <tr class='colhead text-left'>
                  <th class="w-100px"><?php echo $lang->monitoring->projectNumber;?></th>
                  <th class="w-200px"><?php echo $lang->monitoring->projectName;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->projectpri;?></th>
                  <th class="w-200px"><?php echo $lang->monitoring->responsibleDept;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->businessPM;?></th>
                  <th class="w-200px"><?php echo $lang->monitoring->businessDept;?></th>
                  <th class="w-150px"><?php echo $lang->monitoring->itPM;?></th>
                  <th class="w-150px"><?php echo $lang->monitoring->productManager;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->itDevM;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->beginDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->projectReviewDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->businessID;?></th>
                  <th class="w-200px"><?php echo $lang->monitoring->businessTitle;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->businessStatus;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->PRDdate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->PRDconfirmDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->PRDwarning;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->goLiveDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->goLiveConfirmDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->goLiveWarning;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->acceptanceDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->acceptanceConfirmDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->acceptanceWarning;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->terminationDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->terminationConfirmDate;?></th>
                  <th class="w-120px"><?php echo $lang->monitoring->terminationWarning;?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($projectApprovals as $projectApproval):?>
                <tr class="text-left">
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $projectApproval->projectNumber;?>'><?php echo $projectApproval->projectNumber;?></td>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $projectApproval->name;?>'><?php echo $projectApproval->name;?></td>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo zget($workflowFieldOptions['priList'], $projectApproval->pri, '');?>'><?php echo zget($workflowFieldOptions['priList'], $projectApproval->pri, '');?></td>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo zget($depts, $projectApproval->responsibleDept);?>'><?php echo zget($depts, $projectApproval->responsibleDept);?></td>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo zget($users, $projectApproval->businessPM);?>'><?php echo zget($users, $projectApproval->businessPM);?></td>

                  <?php
                  $itPMList         = '';
                  $businessDeptList = '';
                  if(!empty($projectApproval->accountList) && isset($projectApproval->accountList['itPM']))
                  {
                      foreach($projectApproval->accountList['itPM'] as $itPM)
                      {
                          $itPMList .= ',' . zget($users, $itPM);

                          if(isset($userDepts[$itPM]) && !empty($userDepts[$itPM])) $businessDeptList .= '、' . zget($depts, $userDepts[$itPM]);
                      }
                  }
                  $itPMList         = trim($itPMList, ',');
                  $businessDeptList = trim($businessDeptList, '、');
                  ?>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $businessDeptList; ?>'><?php echo $businessDeptList;?></td>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $itPMList; ?>'><?php echo $itPMList;?></td>

                  <?php
                  $productManagerList = '';
                  if(!empty($projectApproval->accountList) && isset($projectApproval->accountList['productManager']))
                  {
                      foreach($projectApproval->accountList['productManager'] as $productManager)
                      {
                          $productManagerList .= ',' . zget($users, $productManager);
                      }
                  }
                  $productManagerList = trim($productManagerList, ',');
                  ?>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $productManagerList; ?>'><?php echo $productManagerList;?></td>

                  <?php
                  $itDevMList = '';
                  if(!empty($projectApproval->accountList) && isset($projectApproval->accountList['itDevM']))
                  {
                      foreach($projectApproval->accountList['itDevM'] as $itDevM)
                      {
                          $itDevMList .= ',' . zget($users, $itDevM);
                      }
                  }
                  $itDevMList = trim($itDevMList, ',');
                  ?>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $itDevMList; ?>'><?php echo $itDevMList;?></td>
                  <?php $begin = helper::isZeroDate($projectApproval->begin) ? '' : $projectApproval->begin;?>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $begin;?>'><?php echo $begin;?></td>
                  <?php $projectReviewDate = helper::isZeroDate($projectApproval->projectReviewDate) ? '' : $projectApproval->projectReviewDate;?>
                  <td rowspan="<?php echo $projectApproval->projectRowspan;?>" title='<?php echo $projectReviewDate;?>'><?php echo $projectReviewDate;?></td>

                    <?php if(!empty($projectApproval->businessList)):?>
                    <?php $businessTR = '';?>
                    <?php foreach($projectApproval->businessList as $business):?>
                    <?php echo $businessTR;?>
                    <td style="padding-left: 5px;"><?php echo $business->id;?></td>
                    <td><?php echo $business->name;?></td>
                    <td title="<?php echo zget($workflowFieldOptions['businessStatusList'], $business->status, '');?>"><?php echo zget($workflowFieldOptions['businessStatusList'], $business->status, '');?></td>

                    <?php
                    $PRDdate        = helper::isZeroDate($business->PRDdate)        ? '' : $business->PRDdate;
                    $PRDconfirmDate = helper::isZeroDate($business->PRDconfirmDate) ? '' : $business->PRDconfirmDate;
                    $goLiveDate     = helper::isZeroDate($business->goLiveDate)     ? '' : $business->goLiveDate;
                    $acceptanceDate = helper::isZeroDate($business->acceptanceDate) ? '' : $business->acceptanceDate;
                    $closeDate      = helper::isZeroDate($business->closeDate)      ? '' : $business->closeDate;
                    ?>
                    <td title='<?php echo $PRDdate;?>'><?php echo $PRDdate;?></td>
                    <td title='<?php echo $PRDconfirmDate;?>'><?php echo $PRDconfirmDate;?></td>
                    <td><?php echo $this->monitoring->getOverdueWarning($business->PRDWarning);?></td>
                    <td title='<?php echo $goLiveDate;?>'><?php echo $goLiveDate;?></td>
                    <td><?php echo helper::isZeroDate($business->goLiveConfirmDate) ? '' : $business->goLiveConfirmDate;?></td>
                    <td><?php echo $this->monitoring->getOverdueWarning($business->goLiveWarning);?></td>
                    <td title='<?php echo $acceptanceDate;?>'><?php echo $acceptanceDate;?></td>
                    <td title='<?php echo $closeDate;?>'><?php echo $closeDate;?></td>
                    <td><?php echo $this->monitoring->getOverdueWarning($business->acceptanceWarning);?></td>

                      <?php if(empty($businessTR)):?>
                        <td rowspan="<?php echo $projectApproval->projectRowspan;?>"><?php echo helper::isZeroDate($projectApproval->terminationDate) ? '' : $projectApproval->terminationDate;?></td>
                        <?php $reviewDate = ($projectApproval->status == 'finished' && !helper::isZeroDate($projectApproval->reviewDate)) ? $projectApproval->reviewDate : '';?>
                        <td rowspan="<?php echo $projectApproval->projectRowspan;?>"><?php echo $reviewDate;?></td>
                        <td rowspan="<?php echo $projectApproval->projectRowspan;?>"><?php echo $this->monitoring->getOverdueWarning($projectApproval->terminationWarning);?></td>
                      <?php $businessTR = '<tr>'; endif;?>
                    </tr>
                    <?php endforeach;?>
                    <?php else:?>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>
                  <?php endif;?>
                <?php endforeach;?>
              </tbody>
            </table>
          </div>
          <div class='table-footer'>
            <?php echo $pager->show('right', 'pagerjs');?>
          </div>
        </form>
      </div>
    </div>
    <?php endif;?>
  </div>
</div>
<script>
$(function()
{
    var url = '<?php echo inlink('browse', "params={params}");?>';
    $('button.btn-query').on('click', function()
    {
        var filters = $('#conditions .form-control');
        var filterParams = [];
        filters.each(function(index, elem)
        {
            var name  = $(elem).attr('name');
            var value = $(elem).val();
            if(name.startsWith('date')) value = btoa(value);
            if(Array.isArray(value)) value = value.filter(function(v) {return !!v;}).join(',');
            filterParams.push(value);
        });

        filterParams = filterParams.join('|');

        url = url.replace('{params}', encodeURI(filterParams));
        window.location.href = url;
    });
});
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
