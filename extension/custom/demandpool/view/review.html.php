<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datepicker.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainContent" class="main-content fade">
  <div class="center-block">
    <div class="main-header">
      <h2><?php echo $lang->demandpool->review;?></h2>
    </div>
    <form class="load-indicator main-form form-ajax" method='post' enctype='multipart/form-data' id='dataform'>
      <table class="table table-form">
        <tbody>
          <tr>
            <th class='w-100px'><?php echo $lang->demandpool->result;?></th>
            <td><?php echo html::select('status', $lang->demandpool->resultList, '', "class='form-control'");?></td>
            <td></td>
          </tr>
          <tr>
            <th><?php echo $lang->demandpool->assignedTo;?></th>
            <td><?php echo html::select('assignedTo', $users, $demandpool->assignedTo, "class='form-control chosen'");?></td>
            <td></td>
          </tr>
          <tr>
            <th><?php echo $lang->demandpool->mailto;?></th>
            <td colspan='3'>
              <div class="input-group">
                <?php echo html::select('mailto[]', $users, $demandpool->mailto, "class='form-control chosen' data-placeholder='{$lang->chooseUsersToMail}' multiple");?>            
                <?php echo $this->fetch('my', 'buildContactLists');?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->comment;?></th>
            <td colspan='3'><?php echo html::textarea('comment', '', "rows='6' class='form-control'");?></td>
          </tr>
          <tr>
            <td class='form-actions text-center' colspan='3'><?php echo html::submitButton();?></td>
          </tr>
        </tbody>
      </table>
    </form>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
