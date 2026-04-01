<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php if(isset($suhosinInfo)):?>
<div class='alert alert-info'><?php echo $suhosinInfo?></div>
<?php elseif(empty($maxImport) and $allCount > $this->config->file->maxImport):?>
<div id="mainContent" class="main-content">
  <div class="main-header">
    <h2><?php echo $lang->demand->import;?></h2>
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
    <h2><?php echo $lang->demand->import;?></h2>
  </div>
  <form class='main-form' target='hiddenwin' method='post' style='overflow-x:auto'>
    <table class='table table-form' id='showData'>
      <thead>
        <tr class='text-center'>
          <th class='c-id text-center'><?php echo $lang->idAB;?></th>
          <th class='w-100px'><?php echo $lang->demand->pri;?></th>
          <th class='w-150px'><?php echo $lang->demand->project;?></th>
          <th class='w-150px'><?php echo $lang->demand->dept;?></th>
          <th class='w-150px required'><?php echo $lang->demand->name;?></th>
          <th class='w-150px required'><?php echo $lang->demand->businessDesc;?></th>
          <th class='w-200px'><?php echo $lang->demand->desc;?></th>
          <th class='w-150px required'><?php echo $lang->demand->businessObjective;?></th>
          <th class='w-120px'><?php echo $lang->demand->assignedTo;?></th>
          <th class='w-120px'><?php echo $lang->demand->reviewer;?></th>
          <th class='w-140px'><?php echo $lang->demand->deadline;?></th>
          <?php
            $extendFields = $this->demand->getFlowExtendFields();
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
        <?php foreach($demandData as $key => $demand):?>
        <tr class='text-top'>
          <td>
            <?php
            if(!empty($demand->id))
            {
                echo $demand->id . html::hidden("id[$key]", $demand->id);
                $insert = false;
            }
            else
            {
                echo $addID++ . " <sub style='vertical-align:sub;color:gray'>{$lang->demand->new}</sub>";
            }
            ?>
          </td>
          <td><?php echo html::select("pri[$key]", $lang->demand->priList, !empty($demand->pri) ? $demand->pri : ((!empty($demand->id) and isset($demands[$demand->id])) ? $demands[$demand->id]->pri : ''), "class='form-control chosen'")?></td>
          <td><?php echo html::select("project[$key]", $projects, $demand->project, 'class="form-control chosen"');?></td>
          <td><?php echo html::select("dept[$key][]", $depts, $demand->dept, 'class="form-control chosen" multiple');?></td>
          <td><?php echo html::input("name[$key]", htmlspecialchars($demand->name, ENT_QUOTES), "class='form-control'")?></td>
          <td><?php echo html::textarea("businessDesc[$key]", $demand->businessDesc, "class='form-control' cols='35' rows='1'")?></td>
          <td><?php echo html::textarea("desc[$key]", $demand->desc, "class='form-control' cols='35' rows='1'")?></td>
          <td><?php echo html::textarea("businessObjective[$key]", $demand->businessObjective, "class='form-control' cols='35' rows='1'")?></td>
          <td><?php echo html::select("assignedTo[$key]", $users, !empty($demand->assignedTo) ? $demand->assignedTo: ((!empty($demand->id) and isset($demands[$demand->id])) ? $demands[$demand->id]->assignedTo : ''), "class='form-control chosen'")?></td>
          <td><?php echo html::select("reviewer[$key]", $users, !empty($demand->reviewer) ? $demand->reviewer : ((!empty($demand->id) and isset($demands[$demand->id])) ? $demands[$demand->id]->reviewer : ''), "class='form-control chosen'")?></td>
          <td><?php echo html::input("deadline[$key]", !empty($demand->deadline) ? $demand->deadline : ((!empty($demand->id) and isset($demands[$demand->id])) ? $demands[$demand->id]->deadline : ''), "class='form-control form-date' autocomplete='off'")?></td>
          <?php
            $this->loadModel('flow');
            foreach($extendFields as $extendField) echo "<td" . (($extendField->control == 'select' or $extendField->control == 'multi-select') ? " style='overflow:visible'" : '') . ">" . $this->flow->getFieldControl($extendField, $demand, $extendField->field . "[$key]") . "</td>";
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
            echo html::linkButton($lang->goback, $this->createLink('demand', 'browse', 'demandID=' . $poolID), 'self', '', 'btn btn-wide');
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
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
