<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datepicker.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainContent" class="main-content fade">
  <div class="center-block">
    <div class="main-header">
      <h2><?php echo $lang->yearplan->create;?></h2>
    </div>
    <form class="load-indicator main-form form-ajax" method='post' enctype='multipart/form-data' id='dataform'>
      <table class="table table-form">
        <tbody>
          <tr>
            <th class='w-140px'><?php echo $lang->yearplan->owner;?></th>
            <td><?php echo html::select('owner', $users, '', "class='form-control chosen'");?></td>
            <td>
              
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->yearplan->name;?></th>
            <td colspan='2'><?php echo html::input('name', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->yearplan->desc;?></th>
            <td colspan='2'><?php echo html::textarea('desc', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->yearplan->participant;?></th>
            <td colspan='2'>
              <div class="input-group">
                <?php echo html::select('participant[]', $users, '', "class='form-control chosen' multiple");?>
                <?php echo $this->fetch('my', 'buildContactLists', 'dropdownName=participant');?>
              </div>
            </td>
          </tr>
          <?php $this->printExtendFields('', 'table', 'columns=2');?>
          <tr>
            <th><?php echo $lang->files;?></th>
            <td colspan='2'><?php echo $this->fetch('file', 'buildform', 'fileCount=1&percent=0.85');?></td>
          </tr>
          <tr>
            <th><?php echo $lang->yearplan->acl;?></th>
            <td colspan='2'><?php echo nl2br(html::radio('acl', $lang->yearplan->aclList, 'open', '', 'block'));?></td>
          </tr>
          <tr>
            <td class='form-actions text-center' colspan='3'><?php echo html::submitButton() . html::backButton();?></td>
          </tr>
        </tbody>
      </table>
    </form>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
