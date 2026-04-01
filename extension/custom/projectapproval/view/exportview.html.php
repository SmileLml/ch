<?php
/**
 * The export view file of file module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Congzhi Chen <congzhi@cnezsoft.com>
 * @package     file
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.lite.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/chosen.html.php';?>
<?php $this->app->loadLang('file');?>
<main id="main">
  <div class="container">
    <div id="mainContent" class='main-content load-indicator'>
      <div class='main-header'>
        <h2><?php echo $lang->export;?></h2>
      </div>
      <form class='main-form' method='post' target='hiddenwin'>
        <table class="table table-form" id='exportTable'>
          <tbody>
            <tr>
              <th class='c-name'><?php echo $lang->file->fileName;?></th>
              <td class="c-fileName"><?php echo html::input('fileName', isset($fileName) ? $fileName : '', "class='form-control' autofocus placeholder='{$lang->file->untitled}'");?></td>
              <td></td>
            </tr>
            <tr>
              <th><?php echo $lang->file->extension;?></th>
              <td><?php echo html::select('fileType', $lang->exportFileTypeList, '', 'class="form-control chosen" data-drop_direction="down"');?></td>
            </tr>
            <tr>
              <td class='text-center' colspan='2'>
                <?php echo html::submitButton($lang->export, "onclick='setDownloading();'", 'btn btn-primary');?>
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
  </div>
</main>

<script>
function setDownloading()
{
    if(navigator.userAgent.toLowerCase().indexOf("opera") > -1) return true;   // Opera don't support, omit it.

    var $fileName = $('#fileName');
    if($fileName.val() === '') $fileName.val('<?php echo $lang->file->untitled;?>');

    $.cookie('downloading', 0);
    time = setInterval("closeWindow()", 300);
    $('#mainContent').addClass('loading');
    return true;
}

function closeWindow()
{
    if($.cookie('downloading') == 1)
    {
        $('#mainContent').removeClass('loading');
        parent.$.closeModal();
        $.cookie('downloading', null);
        clearInterval(time);
    }
}
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.lite.html.php';?>
