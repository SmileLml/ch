<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datepicker.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2>
        <span class='label label-id'><?php echo $yearplanDemand->id;?></span>
        <?php echo '<span title="' . $yearplanDemand->name . '">' . $yearplanDemand->name . '</span>';?>
        <small><?php echo $lang->arrow . $title;?></small>
      </h2>
    </div>
    <form class="load-indicator main-form form-ajax" method='post' enctype='multipart/form-data' id='dataform'>
      <table class='table table-form'>
        <tr>
          <th class='w-90px'><?php echo $lang->yearplandemand->confirmComment;?></th>
          <td>
            <?php echo html::textarea('confirmComment', '', "class='form-control' rows='5'");?>
          </td>
        </tr>
        <tr>
          <td colspan='2' class='text-center form-actions'>
            <?php echo html::submitButton($lang->yearplandemand->confirm);?>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>