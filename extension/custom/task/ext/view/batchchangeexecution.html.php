<?php
/**
 * The complete file of task module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Jia Fu <fujia@cnezsoft.com>
 * @package     task
 * @version     $Id: complete.html.php 935 2010-07-06 07:49:24Z jajacn@126.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id='mainContent' class='main-content' style="height: 260px;">
  <div class='center-block'>
    <div class='main-header'>
      <h2>
        <?php echo $lang->task->changeProject;?>
      </h2>
    </div>
    <form method='post' target='hiddenwin'>
      <input type='hidden' name='taskIdList' value='<?php echo $taskIdList;?>' />
      <table class='table table-form'>
        <tr>
          <th class='w-80px'><?php echo $lang->task->project;?></th>
          <td class='required'><?php echo html::select('project', $projects, '', "class='form-control picker-select' data-drop-direction='bottom' onchange='loadExecutionsByProject(this.value)'");?></td><td></td>
        </tr>
        <tr>
          <th class='w-80px'><?php echo $lang->task->execution;?></th>
          <td class='required'><?php echo html::select('execution', [], '', "class='form-control picker-select' data-drop-direction='bottom'");?></td><td></td>
        </tr>
        <tr>
        </tr>
        <tr>
          <td colspan='3' class='text-center form-actions'>
            <?php echo html::submitButton();?>
            <?php echo html::linkButton($lang->goback, $this->session->taskList);?>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<script>
function loadExecutionsByProject(projectID)
{
    var link = createLink('project', 'ajaxGetExecutions', 'projectID=' + projectID);
    
    $.get(link, function(data)
    {
        $('#execution').replaceWith(data);
        $('#execution').next('.picker').remove();
        $('#execution').picker();
    });
}
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>