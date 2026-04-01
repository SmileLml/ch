<?php
/**
 * The edit view of caselib module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     caselib
 * @version     $Id: edit.html.php 4728 2013-05-03 06:14:34Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<style>
#contactListMenu_chosen {width: 100px !important;}
#contactListMenu + .chosen-container {min-width: 100px;}
td > <?php echo "#" . $dropdownName;?> + .chosen-container .chosen-choices {border-radius: 2px 2px 0 0;}
td > <?php echo "#" . $dropdownName;?> + .chosen-container + #contactListMenu + .chosen-container > .chosen-single {border-radius: 0 0 2px 2px; border-top-width: 0; padding-top: 6px;}
#contactListMenu + .chosen-container.chosen-container-active > .chosen-single {border-top-width: 1px !important; padding-top: 5px !important;}
</style>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2><?php echo $lang->caselib->edit;?></h2>
    </div>
    <form class='load-indicator main-form form-ajax' method='post' target='hiddenwin' id='dataform'>
      <table class='table table-form'>
        <tr>
          <th><?php echo $lang->caselib->name;?></th>
          <td><?php echo html::input('name', $lib->name, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->caselib->desc;?></th>
          <td><?php echo html::textarea('desc', htmlSpecialString($lib->desc), "rows=10 class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->caselib->acl;?></th>
          <td colspan='2'><?php echo nl2br(html::radio('acl', $lang->caselib->aclList, $lib->acl, 'onchange=displayWhitelist(this.value)', 'block'));?></td>
        </tr>
        <tr class="whitelistth">
          <th><?php echo $lang->caselib->whitelist;?></th>
          <td colspan='2'>
            <div class="input-group">
            <?php echo html::select('whitelist[]', $users, $lib->whitelist, "class='form-control chosen' multiple");?>
            <?php echo html::select('contactListMenu', $chteams, '', "class='form-control chosen' onchange=\"setWhitelist('whitelist', this.value)\"");?>
            </div>
          </td>
        </tr>
        <?php $this->printExtendFields($lib, 'table');?>
        <tr>
          <td class='text-center form-actions' colspan='2'>
            <?php echo html::submitButton();?>
            <?php echo html::backButton();?>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<script>

$(function() {
  acl = $('input[name="acl"]:checked').val();
  displayWhitelist(acl)
});
function displayWhitelist(acl)
{
  if(acl == 'private')
  {
    $('.whitelistth').css('display', 'table-row')
  }
  else
  {
    $('.whitelistth').css('display', 'none')
  }
}
function setWhitelist(mailto, chteamID)
{
    var oldUsers = $('#' + mailto).val() ? $('#' + mailto).val() : '';
    link = createLink('chteam', 'ajaxGetMembers', 'listID=' + chteamID + '&dropdownName=' + mailto + '&oldUsers=' + oldUsers);
    $.get(link, function(users)
    {
        var picker = $('#' + mailto).data('zui.picker');
        if(picker) picker.destroy();

        $('#' + mailto).replaceWith(users);
        $('#' + mailto + '_chosen').remove();
        $('#' + mailto).siblings('.picker').remove();

        if($("[data-pickertype='remote']").length == 0 && $('.picker-select').length == 0)
        {
            $('#' + mailto).chosen();
        }
        else
        {
            $('#' + mailto + "[data-pickertype!='remote']").picker({chosenMode: true});
            $("[data-pickertype='remote']").each(function()
            {
                var pickerremote = $(this).attr('data-pickerremote');
                $(this).picker({chosenMode: true, remote: pickerremote});
            });
        }
    });
}
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
