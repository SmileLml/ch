<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php if(isset($suhosinInfo)):?>
<div class='alert alert-info'><?php echo $suhosinInfo?></div>
<?php elseif(empty($maxImport) and $allCount > $this->config->file->maxImport):?>
<div id="mainContent" class="main-content">
  <div class="main-header">
    <h2><?php echo $lang->demandpool->import;?></h2>
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
    $('#import').click(function(){location.href = createLink('demandpool', 'showImport', "pageID=1&maxImport=" + $('#maxImport').val())})
});
</script>
<?php else:?>
<div id="mainContent" class="main-content">
  <div class="main-header clearfix">
    <h2><?php echo $lang->demandpool->import;?></h2>
  </div>
  <form class='main-form' target='hiddenwin' method='post' style='overflow-x:auto'>
    <table class='table table-form' id='showData'>
      <thead>
        <tr>
          <th class='w-60px'><?php echo $lang->demandpool->id?></th>
          <th class='w-300px'><?php echo $lang->demandpool->name?></th>
          <th class='w-200px'><?php echo $lang->demandpool->product;?></th>
          <th class='w-120px'><?php echo $lang->demandpool->dept;?></th>
          <th class='w-140px'><?php echo $lang->demandpool->assignedTo;?></th>
          <th class='w-140px'><?php echo $lang->demandpool->deadline;?></th>
          <th class='w-120px'><?php echo $lang->demandpool->pri;?></th>
          <th class='w-200px'><?php echo $lang->demandpool->background?></th>
          <th class='w-200px'><?php echo $lang->demandpool->desc?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $insert = true;
        $addID  = 1;
        ?>
        <?php foreach($demandpoolData as $key => $demandpool):?>
        <tr class='text-top'>
          <td>
            <?php
            if(!empty($demandpool->id))
            {
                echo $demandpool->id . html::hidden("id[$key]", $demandpool->id);
                $insert = false;
            }
            else
            {
                echo $addID++ . " <sub style='vertical-align:sub;color:gray'>{$lang->demandpool->new}</sub>";
            }
            ?>
          </td>
          <td><?php echo html::input("name[$key]", htmlspecialchars($demandpool->name, ENT_QUOTES), "class='form-control'")?></td>
          <td><?php echo html::select("product[$key]", $lang->demandpool->productList, !empty($demandpool->product) ? $demandpool->product : ((!empty($demandpool->id) and isset($demandpools[$demandpool->id])) ? $demandpools[$demandpool->id]->product : ''), "class='form-control chosen'")?></td>
          <td><?php echo html::select("dept[$key]", $depts, !empty($demandpool->dept) ? $demandpool->dept : ((!empty($demandpool->id) and isset($demandpools[$demandpool->id])) ? $demandpools[$demandpool->id]->dept : ''), "class='form-control chosen'")?></td>
          <td><?php echo html::select("assignedTo[$key]", $users, !empty($demandpool->assignedTo) ? $demandpool->assignedTo: ((!empty($demandpool->id) and isset($demandpools[$demandpool->id])) ? $demandpools[$demandpool->id]->assignedTo : ''), "class='form-control chosen'")?></td>
          <td><?php echo html::input("deadline[$key]", !empty($demandpool->deadline) ? $demandpool->deadline : ((!empty($demandpool->id) and isset($demandpools[$demandpool->id])) ? $demandpools[$demandpool->id]->deadline : ''), "class='form-control form-date' autocomplete='off'")?></td>
          <td><?php echo html::select("pri[$key]", $lang->story->priList, !empty($demandpool->pri) ? $demandpool->pri : ((!empty($demandpool->id) and isset($demandpools[$demandpool->id])) ? $demandpools[$demandpool->id]->pri : ''), "class='form-control chosen'")?></td>
          <td><?php echo html::textarea("background[$key]", $demandpool->background, "class='form-control' cols='35' rows='1'")?></td>
          <td><?php echo html::textarea("desc[$key]", $demandpool->desc, "class='form-control' cols='35' rows='1'")?></td>
        </tr>
        <?php endforeach;?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan='16' class='text-center form-actions'>
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
            echo ' &nbsp; ' . html::backButton();
            echo "<button type='submit' id='saveDraft' class='btn btn-wide btn-secondary' data-loading='稍候...'>{$lang->demandpool->saveDraft}</button>";
            echo ' &nbsp; ' . sprintf($lang->file->importPager, $allCount, $pagerID, $allPager);
            ?>
          </td>
        </tr>
      </tfoot>
    </table>
    <?php if(!$insert and $dataInsert === '') include '../../common/view/noticeimport.html.php';?>
  </form>
</div>
<?php endif;?>
<script>
$(function()
{
    $.fixedTableHead('#showData');
});

$(function()
{
    $('#saveDraft').click(function()
    {
        $(this).after("<input type='hidden' name='draft' value=1 />");
    })
})
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
