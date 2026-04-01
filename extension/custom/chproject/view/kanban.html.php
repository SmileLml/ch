<?php
/**
 * The task kanban view file of execution module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2012 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @author      Wang Yidong, Zhu Jinyong
 * @package     execution
 * @version     $Id: taskkanban.html.php $
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kanban.html.php';?>
<?php if($groupBy == 'story' and $browseType == 'task'):?>
<style>
.kanban-cols {left: 0px !important;}
</style>
<?php endif;?>
<div id='mainMenu' class='clearfix'>
  <div class='btn-toolbar pull-left'>
    <?php if($features['qa']):?>
    <div class="input-control space c-type">
      <?php echo html::select('type', $lang->kanban->type, $browseType, 'class="form-control chosen" data-max_drop_width="215"');?>
    </div>
    <?php endif;?>
    <?php if(!$browseType):?>
    <div class="input-control space c-group">
      <?php echo html::select('group',  $lang->kanban->group->$browseType, $groupBy, 'class="form-control chosen" data-max_drop_width="215"');?>
    </div>
    <?php endif;?>
  </div>
  <div class='input-group pull-left not-fix-input-group' id='kanbanScaleControl'>
    <span class='input-group-btn'>
      <button class='btn btn-icon' type='button' data-type='-'><i class='icon icon-minuse-solid-circle text-muted'></i></button>
    </span>
    <span class='input-group-addon'>
      <span id='kanbanScaleSize'>1</span><?php echo $lang->execution->kanbanCardsUnit; ?>
    </span>
    <span class='input-group-btn'>
      <button class='btn btn-icon' type='button' data-type='+'><i class='icon icon-plus-solid-circle text-muted'></i></button>
    </span>
  </div>
  <div class='btn-group'>
    <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjectPairs, $intanceProjectID) : $lang->chproject->allProject;?>
    <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
    <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
      <?php
        $class = '';

        if($intanceProjectID == 0) $class = 'class="active"';
        echo "<li $class>" . html::a($this->createLink('chproject', 'kanban', "projectID=$projectID&browseType=$browseType&orderBy=$orderBy&groupBy=$groupBy&intanceProjectID=0"), $lang->chproject->allProject) . "</li>";
        foreach($intanceProjectPairs as $key => $intanceProjectName)
        {
            $class = $intanceProjectID == $key ? 'class="active"' : '';
            echo "<li $class>" . html::a($this->createLink('chproject', 'kanban', "projectID=$projectID&browseType=$browseType&orderBy=$orderBy&groupBy=$groupBy&intanceProjectID=$key", ""), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
        }
      ?>
    </ul>
  </div>
  <div class='btn-toolbar pull-right'>
    <div class="input-group" id="taskKanbanSearch">
      <div class="input-control search-box" id="searchBox">
      <input type="text" name="taskKanbanSearchInput" id="taskKanbanSearchInput" value="" class="form-control" oninput="searchCards(this.value)" placeholder="<?php echo $lang->execution->pleaseInput;?>" autocomplete="off">
      </div>
    </div>
    <?php
    echo html::a('javascript:toggleSearchBox()', "<i class='icon-search muted'></i> " . $lang->searchAB, '', "class='btn btn-link querybox-toggle'");
    $link = $this->createLink('task', 'export', "execution=$projectID&orderBy=$orderBy&type=unclosed");
    if(common::hasPriv('task', 'export')) echo html::a($link, "<i class='icon-export muted'></i> " . $lang->export, '', "class='btn btn-link iframe export' data-width='700'");
    ?>
    <?php if($canBeChanged):?>
    <?php
    $width = common::checkNotCN() ? '850px' : '700px';
    echo "<div class='btn-group menu-actions'>";
    echo html::a('javascript:;', "<i class='icon icon-ellipsis-v'></i>", '', "data-toggle='dropdown' class='btn btn-link'");
    echo "<ul class='dropdown-menu pull-right'>";
    if(common::hasPriv('execution', 'setKanban'))   echo '<li>' . html::a(helper::createLink('execution', 'setKanban', "projectID=$projectID", '', true), '<i class="icon icon-cog-outline"></i>' . $lang->execution->setKanban, '', "class='iframe btn btn-link text-left' data-width='$width'") . '</li>';
    if(common::hasPriv('execution', 'printKanban')) echo '<li>' . html::a($this->createLink('execution', 'printKanban', "executionID=$projectID"), "<i class='icon icon-printer muted'></i>" . $lang->execution->printKanban, '', "class='iframe btn btn-link' id='printKanban' title='{$lang->execution->printKanban}' data-width='500'") . '</li>';
    echo '<li>' .html::a('javascript:fullScreen()', "<i class='icon icon-fullscreen muted'></i>" . $lang->execution->fullScreen, '', "class='btn btn-link' title='{$lang->execution->fullScreen}' data-width='500'") . '</li>';
    echo '</ul></div>';
    ?>
    <?php
    $checkObject = new stdclass();
    $checkObject->execution = $defaultExecution->id;

    $canCreateTask  = common::hasPriv('task', 'create', $checkObject);
    $canCreateBug   = ($productID and common::hasPriv('bug', 'create'));
    $canCreateStory = ($productID and common::hasPriv('story', 'create'));
    $hasStoryButton = $canCreateStory;
    $hasTaskButton  = $canCreateTask;
    $hasBugButton   = $canCreateBug;
    ?>
    <?php if($canCreateTask or $canCreateBug or $canCreateStory):?>
    <div class='dropdown' id='createDropdown'>
      <button class='btn btn-primary' type='button' data-toggle='dropdown'><i class='icon icon-plus'></i> <?php echo $this->lang->create;?> <span class='caret'></span></button>
      <ul class='dropdown-menu pull-right'>
        <?php $showDivider = false;?>
        <?php if($features['story'] and $hasStoryButton):?>
        <?php if($canCreateStory) echo '<li>' . html::a(helper::createLink('story', 'create', "productID=$productID&branch=0&moduleID=0&story=0&execution=0&bugID=0&planID=0&todoID=0&extra=&storyType=story&chproject={$project->id}", '', true), $lang->execution->createStory, '', "class='iframe' data-width='80%'") . '</li>';?>
        <?php $showDivider = true;?>
        <?php endif;?>
        <?php if($features['qa']):?>
        <?php if($showDivider) echo '<li class="divider"></li>';?>
        <?php if($canCreateBug) echo '<li>' . html::a(helper::createLink('bug', 'create', "productID=$productID&moduleID=0&extras=&chprojectID={$project->id}", '', true), $lang->bug->create, '', "class='iframe'") . '</li>';?>
        <?php endif;?>
        <?php if($showDivider) echo '<li class="divider"></li>';?>
        <?php if($canCreateTask) echo '<li>' . html::a(helper::createLink('task', 'create', "executionID=0&storyID=0&moduleID=0&taskID=0&todoID=0&extra=&bugID=0&chprojectID={$project->id}", '', true), $lang->task->create, '', "class='iframe' data-width='80%'") . '</li>';?>
      </ul>
    </div>
    <?php endif;?>
    <?php else:?>
    <?php $canCreateTask = $canCreateBug = $canCreateStory = false;?>
    <?php endif;?>
  </div>
</div>

<div class='panel' id='kanbanContainer'>
  <div class='panel-body'>
    <div id='kanbans'></div>
  </div>
  <div class='table-empty-tip hidden' id='emptyBox'>
    <p><span class="text-muted"><?php echo $lang->kanbancard->empty;?></span></p>
  </div>
</div>

<div class="modal fade" id="linkStoryByPlan">
  <div class="modal-dialog mw-500px">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="icon icon-close"></i></button>
        <h4 class="modal-title"><?php echo $lang->execution->linkStoryByPlan;?></h4><?php echo '(' . $lang->execution->linkStoryByPlanTips . ')';?>
      </div>
      <div class="modal-body">
        <div class='input-group'>
          <?php echo html::select('plan', $allPlans, '', "class='form-control chosen' id='plan'");?>
          <span class='input-group-btn'><?php echo html::commonButton($lang->execution->linkStory, "id='toStoryButton'", 'btn btn-primary');?></span>
        </div>
      </div>
    </div>
  </div>
</div>
<?php js::set('executionID', $projectID);?>
<?php js::set('productID', $productID);?>
<?php js::set('kanbanGroup', $kanbanGroup);?>
<?php js::set('kanbanList', array_keys($kanbanGroup));?>
<?php js::set('browseType', $browseType);?>
<?php js::set('groupBy', $groupBy);?>
<?php js::set('productNum', $productNum);?>
<?php js::set('searchValue', '');?>
<?php js::set('chprojectID', $project->id);?>
<?php
js::set('priv',
    array(
        'canEditName'         => common::hasPriv('kanban', 'setColumn'),
        'canSetWIP'           => common::hasPriv('kanban', 'setWIP'),
        'canSetLane'          => common::hasPriv('kanban', 'setLane'),
        'canSortCards'        => common::hasPriv('kanban', 'cardsSort'),
        'canCreateTask'       => $canCreateTask,
        'canBatchCreateTask'  => $canBatchCreateTask,
        'canImportBug'        => $canImportBug,
        'canCreateBug'        => $canCreateBug,
        'canBatchCreateBug'   => $canBatchCreateBug,
        'canCreateStory'      => $canCreateStory,
        'canBatchCreateStory' => $canBatchCreateStory,
        'canLinkStory'        => $canLinkStory,
        'canLinkStoryByPlan'  => $canLinkStoryByPlan,
        'canAssignTask'       => common::hasPriv('task', 'assignto'),
        'canAssignStory'      => common::hasPriv('story', 'assignto'),
        'canFinishTask'       => common::hasPriv('task', 'finish'),
        'canPauseTask'        => common::hasPriv('task', 'pause'),
        'canCancelTask'       => common::hasPriv('task', 'cancel'),
        'canCloseTask'        => common::hasPriv('task', 'close'),
        'canActivateTask'     => common::hasPriv('task', 'activate'),
        'canStartTask'        => common::hasPriv('task', 'start'),
        'canAssignBug'        => common::hasPriv('bug', 'assignto'),
        'canConfirmBug'       => common::hasPriv('bug', 'confirmBug'),
        'canActivateBug'      => common::hasPriv('bug', 'activate'),
        'canCloseStory'       => common::hasPriv('story', 'close')
    )
);
?>
<?php js::set('executionLang', $lang->execution);?>
<?php js::set('storyLang', $lang->story);?>
<?php js::set('taskLang', $lang->task);?>
<?php js::set('bugLang', $lang->bug);?>
<?php js::set('editName', $lang->execution->editName);?>
<?php js::set('setWIP', $lang->execution->setWIP);?>
<?php js::set('sortColumn', $lang->execution->sortColumn);?>
<?php js::set('kanbanLang', $lang->kanban);?>
<?php js::set('deadlineLang', $lang->task->deadlineAB);?>
<?php js::set('estStartedLang', $lang->task->estStarted);?>
<?php js::set('noAssigned', $lang->task->noAssigned);?>
<?php js::set('userList', $userList);?>
<?php js::set('entertime', time());?>
<?php js::set('fluidBoard', $project->fluidBoard);?>
<?php js::set('minColWidth', $project->fluidBoard == '0' ? $project->colWidth : $project->minColWidth);?>
<?php js::set('maxColWidth',$project->fluidBoard == '0' ? $project->colWidth : $project->maxColWidth);?>
<?php js::set('displayCards', $project->displayCards);?>
<?php js::set('needLinkProducts', $lang->execution->needLinkProducts);?>
<?php js::set('hourUnit', $config->hourUnit);?>
<?php js::set('orderBy', $storyOrder);?>
<?php js::set('defaultMinColWidth', $this->config->minColWidth);?>
<?php js::set('defaultMaxColWidth', $this->config->maxColWidth);?>
<?php js::set('teamWords', $lang->execution->teamWords);?>
<?php js::set('canImportBug', $features['qa']);?>
<?php js::set('canBeChanged', $canBeChanged);?>

<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
