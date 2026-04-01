<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datepicker.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainContent" class="main-content fade">
  <div class="center-block">
    <div class="main-header">
      <h2><?php echo $lang->demand->create;?></h2>
    </div>
    <form class="load-indicator main-form form-ajax" method='post' enctype='multipart/form-data' id='dataform'>
      <table class="table table-form">
        <tbody>
          <tr>
            <th><?php echo $lang->demand->deadline;?></th>
            <td >
              <div class='input-group'>
                <!-- <span class='input-group-addon'><?php echo $lang->demand->deadline;?></span> -->
                <?php echo html::input('deadline', '', "class='form-control form-date'");?>
              </div>
            </td>
            <td></td>
          </tr>
          <tr>
            <!--
            <td>
              <div class='input-group'>
                <span class='input-group-addon'><?php echo $lang->demand->reviewer;?></span>
                <?php echo html::select('reviewer', $users, '', "class='form-control chosen'");?>
              </div>
            </td>
            -->
          </tr>
          <tr>
            <th><?php echo $lang->demand->name;?></th>
            <td>
              <div class='table-row'>
                <div class='table-col input-size'>
                  <?php echo html::input('name', '', "class='form-control'");?>
                </div>
              </div>
            <td>
              <div class='input-group'>
                <span class='input-group-addon'><?php echo $lang->demand->dept;?></span>
                <?php echo html::select('dept[]', $depts, '', "class='form-control picker-select' multiple");?>
              </div>
            </td>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->demand->project;?></th>
            <td colspan='2'><?php echo html::select('project', $allProjects, '', "class='form-control chosen'");?></td>
          </tr>
          <?php $this->printExtendFields('', 'table', 'columns=2');?>
          <tr>
            <th><?php echo $lang->demand->businessDesc;?></th>
            <td colspan='2'><?php echo html::textarea('businessDesc', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->demand->desc;?></th>
            <td colspan='2'><?php echo html::textarea('desc', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->demand->businessObjective;?></th>
            <td colspan='2'><?php echo html::textarea('businessObjective', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->demand->mailto;?></th>
            <td colspan='2'>
              <div class="input-group">
                <?php echo html::select('mailto[]', $users, '', "class='form-control picker-select' data-placeholder='{$lang->chooseUsersToMail}' multiple");?>
                <?php echo $this->fetch('my', 'buildContactLists');?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->files;?></th>
            <td colspan='2'><?php echo $this->fetch('file', 'buildform', 'fileCount=1&percent=0.85');?></td>
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
