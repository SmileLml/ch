<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <div class='btn-group'>
      <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjects, $intanceProjectID) : $lang->chproject->allProject;?>
      <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
      <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
        <?php
          $class = '';
          if($intanceProjectID == 0) $class = 'class="active"';
          echo "<li $class>" . html::a($this->createLink('chproject', 'gantt', "projectID=$projectID&intanceProjectID=0&type=$ganttType&orderBy=$orderBy"), $lang->chproject->allProject) . "</li>";
          foreach($intanceProjects as $key => $intanceProjectName)
          {
              $class = $intanceProjectID == $key ? 'class="active"' : '';
              echo "<li $class>" . html::a($this->createLink('chproject', 'gantt', "projectID=$projectID&intanceProjectID=$key&type=$ganttType&orderBy=$orderBy"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
          }
        ?>
      </ul>
    </div>
    <?php if(isset($ganttType)):?>
    <?php echo html::a('javascript:updateCriticalPath()', $lang->execution->gantt->showCriticalPath, '', "class='btn btn-link' id='criticalPath'");?>
    <?php echo html::a('###', "<i class='icon icon-fullscreen'></i> " . $lang->execution->gantt->fullScreen, '', "class='btn btn-link' id='fullScreenBtn'");?>
    <div class='btn btn-link' id='ganttPris'>
      <strong><?php echo $lang->task->pri . " : "?></strong>
      <?php foreach($lang->execution->gantt->progressColor as $pri => $color):?>
      <?php if($pri <= 4):?>
      <span style="background:<?php echo $color?>"><?php echo $pri;?></span> &nbsp;
      <?php endif;?>
      <?php endforeach;?>
    </div>
    <?php else:?>
    <?php echo html::a($this->createLink('execution', 'gantt', "executionID=$executionID"), "<i class='icon icon-back icon-sm'></i> " . $lang->goback, '', "class='btn btn-secondary'");?>
    <?php endif;?>
  </div>
  <div class="btn-toolbar pull-right">
    <?php if(isset($ganttType)):?>
    <?php if(common::hasPriv('execution', 'ganttsetting')) echo html::a($this->createLink('execution', 'ganttsetting', "executionID=$executionID", '', true) . '#app=chteam', "<i class='icon icon-cog-outline muted'></i> " . $lang->execution->ganttSetting, '', "class='btn btn-link iframe' data-width='45%'");?>
    <?php if((!empty($this->config->CRProject) or $execution->status != 'closed') and common::hasPriv('execution', 'relation')) echo html::a('#checkChproject', "<i class='icon icon-list-alt muted'></i><span class='text'>{$lang->execution->maintainRelation}</span>", '', "data-app='qa' data-toggle='modal' class='btn btn-link'");?>
    
    <?php endif;?>
    <?php if($this->app->rawMethod != 'relation' and $this->app->rawMethod != 'maintainrelation'):?>
    <div class="btn-group">
      <button class="btn btn-link" data-toggle="dropdown"><i class="icon icon-export muted"></i> <span class="text"><?php echo $lang->export ?></span> <span class="caret"></span></button>
      <ul class="dropdown-menu" id='exportActionMenu'>
        <li><a href='javascript:exportGantt()'><?php echo $lang->execution->gantt->exportImg;?></a></li>
        <li><a href='javascript:exportGantt("pdf")'><?php echo $lang->execution->gantt->exportPDF;?></a></li>
      </ul>
    </div>
    <?php else:?>
    <?php
      if(common::hasPriv('execution', 'maintainRelation')) echo html::a($this->createLink('execution', 'maintainRelation', "executionID=$executionID"), "<i class='icon icon-plus'></i> " . $lang->execution->gantt->editRelationOfTasks, '', "class='btn btn-secondary'");
    ?>
    <?php endif;?>
    <?php
    $checkObject = new stdclass();
    $checkObject->execution = $executionID;
    $misc = common::hasPriv('task', 'create', $checkObject) ? "class='btn btn-primary iframe' data-width='1200px'" : "class='btn btn-primary disabled'";
    $link = common::hasPriv('task', 'create', $checkObject) ?  $this->createLink('task', 'create', "execution=$executionID&storyID=0&moduleID=0&taskID=0&todoID=0&extra=&bugID=0&projectID=$projectID", '', true) : '#';
    echo html::a($link, "<i class='icon icon-plus'></i> " . $lang->task->create, '', $misc);
    ?>
  </div>
</div>
<div class="modal fade" id="checkChproject">
  <div class="modal-dialog mw-500px select-project-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><?php echo $lang->bug->selectProjects;?></h4>
      </div>
      <div class="modal-body">
        <table class='table table-form'>
          <tr>
            <th><?php echo $lang->chproject->project;?></th>
            <td><?php echo html::select('execution', $executions, $executionID, "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <td colspan='2' class='text-center'>
              <?php echo html::commonButton($lang->confirm, "id='toRelationButton'", 'btn btn-primary btn-wide');?>
              <?php echo html::commonButton($lang->cancel,  "id='cancelButton' data-dismiss='modal'", 'btn btn-default btn-wide');?>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
