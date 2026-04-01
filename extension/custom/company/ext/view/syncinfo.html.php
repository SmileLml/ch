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
<style>
.modal-dialog{width:600px;}
</style>
<div id="mainContent" class='main-content'>
    <form class='main-form ' method='post' id="dataform" action='<?php echo $this->createLink('company', 'syncInfo');?>'>
        <table class="table table-form">
          <tbody>
            <tr>
              <th class='c-name'><?php echo $lang->company->begin;?></th>
              <td><?php echo html::input('begin', date("Y-m-d", strtotime("-1 day")), "class='form-control form-date'");?></td>
            </tr>
            <tr>
              <th class='c-name'><?php echo $lang->company->end;?></th>
              <td><?php echo html::input('end', date('Y-m-d'), "class='form-control form-date'");?></td>
            </tr>
            <tr>
                <td class='text-center' colspan='2'>
                <?php echo html::submitButton($lang->company->syncInfo, "", 'btn btn-primary');?>
              </td>
            </tr>
          </tbody>
        </table>
      </form>
</div>
<script>
$('#triggerModal .close').click(function(){
    window.location.reload();
});
$(document).ready(function()
{
    $dataform = $('#dataform');
    $dataform.submit(function()
    {
        $dataform.find('button[type=submit]').prop('disabled', true);
    });
});
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.lite.html.php';?>
