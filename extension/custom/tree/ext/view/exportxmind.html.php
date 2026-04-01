<?php include $app->getModuleRoot() . 'common/view/header.lite.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='main-header'>
    <h2><?php echo $lang->tree->exportXmind;?></h2>
  </div>
  <form method='post' target='hiddenwin' onsubmit='setDownloading();' style='padding: 0px 5% 20px;'>
    <table class='w-p100 table table-form'>
      <tr>
        <th class="w-120px"><?php echo $lang->tree->fileNameTips;?></th>
        <td class="required">
          <input type="text" name="fileName" id="fileName" value="<?php echo $fileName;?>" class="form-control" autocomplete="off">
        </td>
        <td class="w-140px hidden">
          <?php echo html::select('fileType', $lang->tree->fileTypeList, 'new', 'class="form-control chosen"')?>
        </td>
        <td class="w-100px">
          <?php echo html::submitButton();?>
        </td>
      </tr>
      <tr>
        <th class="w-120px" style='vertical-align: top;'><?php echo $lang->tree->guide;?></th>
        <td colspan='3'>
          <p>通过导出Xmind文件，可以让您以脑图的方式创建模块数据。</p>
          <p>可以通过Xmind脑图创建模块的对象有：需求、Bug、用例、用例库、反馈、工单、主机。</p>
          <p>导出的Xmind节点以：<b>AAA[bbb:ccc]</b> 的格式展示，其中AAA为模块标题，bbb为模块类型，ccc为模块ID。</p>
          <p>对象类型：需求(story)、Bug(bug)、用例(case)、用例库(caselib)、反馈(feedback)、工单(ticket)、主机(host)。</p>
          <p>如果导出已有数据，则模块ID会默认存在，可修改模块名称；如需新增模块节点，要将模块ID设置为0。</p>
        </td>
      </tr>
      <tr>
        <th class="w-120px" style='vertical-align: top;'><?php echo $lang->tree->sampleGraph;?></th>
        <td colspan="3">
          <p><img src="<?php echo $config->webRoot . 'static/images/xminddemo.png';?>" alt="demo"></p>
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
