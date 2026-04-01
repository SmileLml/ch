<?php include $app->getModuleRoot() . 'common/view/header.lite.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='main-header'>
    <h2><?php echo $lang->programplan->exportXmind;?></h2>
  </div>
  <form method='post' target='hiddenwin' onsubmit='setDownloading();' style='padding: 0px 5% 20px;'>
    <table class='w-p100 table table-form'>
      <tr>
        <th class="w-120px"><?php echo $lang->programplan->fileNameTips;?></th>
        <td class="required" colspan="3">
          <input type="text" name="fileName" id="fileName" value="<?php echo $fileName;?>" class="form-control" autocomplete="off">
        </td>
      </tr>
      <tr>
        <th class="w-120px"><?php echo $lang->programplan->exportRange;?></th>
        <td>
          <?php echo html::select('range', $lang->programplan->xmindRange, 'stage', 'class="form-control chosen"')?>
        </td>
        <th class="w-120px hidden"><?php echo $lang->programplan->exportVersion;?></th>
        <td class="hidden">
          <?php echo html::select('fileType', $lang->programplan->xmindVersion, 'new', 'class="form-control chosen"')?>
        </td>
      </tr>
      <tr>
        <td class="text-center" colspan="4">
          <?php echo html::submitButton();?>
        </td>
      </tr>
    </table>
  </form>
</div>
<script>
function setDownloading()
{
    if(navigator.userAgent.toLowerCase().indexOf("opera") > -1) return true;   // Opera don't support, omit it.
    
    $.cookie('downloading', 0);
    time = setInterval("closeWindow()", 300);
    return true;
}

function closeWindow()
{
    if($.cookie('downloading') == 1)
    {
        parent.$.closeModal();
        $.cookie('downloading', null);
        clearInterval(time);
    }
}
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.lite.html.php';?>
