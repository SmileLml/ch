<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php if(isset($suhosinInfo)):?>
<div class='alert alert-info'><?php echo $suhosinInfo?></div>
<?php elseif(empty($maxImport) and $allCount > $this->config->file->maxImport):?>
<div id="mainContent" class="main-content">
  <div class="main-header">
    <h2><?php echo $title;?></h2>
  </div>
  <p><?php echo sprintf($lang->file->importSummary, $allCount, html::input('maxImport', $config->file->maxImport, "style='width:50px'"), ceil($allCount / $config->file->maxImport));?></p>
  <p><?php echo html::commonButton($lang->import, "id='import'", 'btn btn-primary');?></p>
</div>
<script>
$(function()
{
    $('#maxImport').keyup(function()
    {
        if(parseInt($('#maxImport').val())) $('#times').html(Math.ceil(parseInt($('#allCount').html()) / parseInt($('#maxImport').val())));
    });
    $('#import').click(function(){location.href = createLink('demand', 'showImport', "pageID=1&maxImport=" + $('#maxImport').val())})
});
</script>
<?php else:?>
<div id="mainContent" class="main-content">
  <div class="main-header clearfix">
    <h2><?php echo $lang->yearplandemand->import;?></h2>
  </div>
  <form class='main-form' target='hiddenwin' method='post' style='overflow-x:auto'>
    <table class='table table-form' id='showData'>
      <thead>
        <tr class='text-center'>
          <th class='w-150px'><?php echo $lang->yearplandemand->name;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->level;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->category;?></th>
          <th style="width: 270px;"><?php echo $lang->yearplandemand->initDept;?></th>
          <th style="width: 270px;"><?php echo $lang->yearplandemand->dept;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->approvalDate;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->planConfirmDate;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->goliveDate;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->itPlanInto;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->itPM;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->businessArchitect;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->businessManager;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->isPurchased;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->purchasedContents;?></th>
          <th class='w-150px'><?php echo $lang->yearplandemand->desc;?></th>
          <?php
          $extendFields = $this->yearplandemand->getFlowExtendFields();
          foreach($extendFields as $extendField)
          {
              $required = strpos(",$extendField->rules,", ',1,') !== false ? 'required' : '';
              echo "<th class='w-200px $required'>{$extendField->name}</th>";
          }
          ?>
        </tr>
      </thead>
      <tbody>
      <?php
      $insert = true;
      $addID  = 1;  
      ?>
      <?php foreach($yearplandemandData as $key => $yearplandemand):?>
        <tr class='text-top'>
          <td>
          <?php echo html::input("name[$key]", htmlspecialchars($yearplandemand->name, ENT_QUOTES), "class='form-control'")?>
          </td>
          <td>
          <?php echo html::select("level[$key]", $lang->yearplandemand->levelList, $yearplandemand->level, 'class="form-control chosen"');?>
          </td>
          <td>
          <?php echo html::select("category[$key]", $lang->yearplandemand->categoryList, $yearplandemand->category, "class='form-control chosen'");?>
          </td>
          <td>
          <?php echo html::select("initDept[$key]", $depts, $yearplandemand->initDept, "class='form-control chosen'");?>
          </td>
          <td>
          <?php echo html::select("dept[$key][]", $depts, $yearplandemand->dept, "class='form-control picker-select' multiple");?>
          </td>
          <td>
          <?php echo html::input("approvalDate[$key]", formatTime($yearplandemand->approvalDate), "class='form-control form-date'");?>
          </td>
          <td>
          <?php echo html::input("planConfirmDate[$key]", formatTime($yearplandemand->planConfirmDate), "class='form-control form-date'");?>
          </td>
          <td>
          <?php echo html::input("goliveDate[$key]", formatTime($yearplandemand->goliveDate), "class='form-control form-date'");?>
          </td>
          <td>
          <?php echo html::input("itPlanInto[$key]", $yearplandemand->itPlanInto, "class='form-control'");?>
          </td>
          <td>
          <?php echo html::select("itPM[$key]", $users, $yearplandemand->itPM, "class='form-control chosen'");?>
          </td>
          <td>
          <?php echo html::select("businessArchitect[$key]", $users, $yearplandemand->businessArchitect, "class='form-control chosen'");?>
          </td>
          <td>
          <?php echo html::select("businessManager[$key][]", $users, $yearplandemand->businessManager, "class='form-control picker-select' multiple");?>
          </td>
          <td>
          <?php echo html::select("isPurchased[$key]", $lang->yearplandemand->isPurchasedList, $yearplandemand->isPurchased, "class='form-control chosen'");?>
          </td>
          <td>
          <?php echo html::textarea("purchasedContents[$key]", $yearplandemand->purchasedContents, "class='form-control'");?>
          </td>
          <td>
          <?php echo html::textarea("desc[$key]", $yearplandemand->desc, "class='form-control'");?>
          </td>
          <?php
          $this->loadModel('flow');
          foreach($extendFields as $extendField) echo "<td" . (($extendField->control == 'select' or $extendField->control == 'multi-select') ? " style='overflow:visible'" : '') . ">" . $this->flow->getFieldControl($extendField, $yearplandemand, $extendField->field . "[$key]") . "</td>";
          ?>
        </tr>
      <?php endforeach;?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan='9' class='text-center form-actions'>
            <?php
            $submitText = $isEndPage ? $this->lang->save : $this->lang->file->saveAndNext;
            if(!$insert and $dataInsert === '')
            {
                echo "<button type='button' data-toggle='modal' data-target='#importNoticeModal' class='btn btn-primary btn-wide'>{$submitText}</button>";
            }
            else
            {
                echo html::submitButton($submitText);
                if($dataInsert !== '') echo html::hidden('insert', $dataInsert);
            }
            echo html::hidden('isEndPage', $isEndPage ? 1 : 0);
            echo html::hidden('pagerID', $pagerID);
            echo html::linkButton($lang->goback, $this->createLink('yearplandemand', 'browse', 'yearplanID=' . $yearplanID), 'self', '', 'btn btn-wide');
            echo sprintf($lang->file->importPager, $allCount, $pagerID, $allPager);
            ?>
          </td>
        </tr>
      </tfoot>
    </table>
    <?php if(!$insert and $dataInsert === '') include $app->getModuleRoot() . 'common/view/noticeimport.html.php';?>
  </form>
</div>
<?php endif;?>
<script>
$(function(){
  $('input[name^="itPlanInto["]').on('input', function()
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

    $('input[name^="itQuotedPrice["]').on('input', function()
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
    $('input[name^="planBudget["]').on('input', function()
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
    $('input[name^="purchasedBudget["]').on('input', function()
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
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>