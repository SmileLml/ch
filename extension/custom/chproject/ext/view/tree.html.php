<?php
/**
 * The execution tree view file of execution module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Hao Sun <sunhao@cnezsoft.com>
 * @package     execution
 * @version     $Id: tree.html.php 4894 2013-06-25 01:28:39Z wyd621@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <div class='btn-group'>
      <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjects, $intanceProjectID) : $lang->chproject->allProject;?>
      <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
      <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
        <?php
          $class = '';
          if($intanceProjectID == 0) $class = 'class="active"';
          echo "<li $class>" . html::a($this->createLink('chproject', 'tree', "projectID=$projectID&intanceProjectID=0&type=$ganttType&orderBy=$orderBy"), $lang->chproject->allProject) . "</li>";
          foreach($intanceProjects as $key => $intanceProjectName)
          {
              $class = $intanceProjectID == $key ? 'class="active"' : '';
              echo "<li $class>" . html::a($this->createLink('chproject', 'tree', "projectID=$projectID&intanceProjectID=$key"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
          }
        ?>
      </ul>
    </div>
    <?php
    foreach($lang->execution->treeLevel as $name => $btnLevel)
    {
        if(empty($tree) && ($name == 'root' or $name == 'all')) continue;
        $icon = '';
        if($name == 'root') $icon = ' <i class="icon-fold-all"></i>';
        if($name == 'all')  $icon = ' <i class="icon-unfold-all"></i>';
        echo html::a('javascript:;', "<span class='text'>$btnLevel$icon</span>", '', "class='btn btn-link btn-tree-view' data-type='{$name}'");
    }
    ?>
  </div>
  <div class="btn-toolbar pull-right">
    
    <?php
    $misc = "class='btn btn-link iframe" . (common::hasPriv('task', 'export', $execution) ? '' : ' disabled') . "' data-width='700'";
    $link = common::hasPriv('task', 'export') ? $this->createLink('task', 'export', "execution=$projectID&orderBy=$orderBy&type=tree") : '#';
    echo html::a($link, "<i class='icon icon-export muted'></i> <span class='text'>{$lang->export}</span>", '', $misc);

    $checkObject = new stdclass();
    $checkObject->execution = $executionID;
    $misc = common::hasPriv('task', 'create', $checkObject) ? "class='btn btn-primary'" : "class='btn btn-primary disabled'";
    $link = common::hasPriv('task', 'create', $checkObject) ?  $this->createLink('task', 'create', "execution=$executionID&storyID=&moduleID=&taskID=0&todoID=0&extra=&bugID=0&projectID=$projectID") . '#app=chteam' : '#';
    echo html::a($link, "<i class='icon icon-plus'></i> " . $lang->task->create, '', $misc);
    ?>
  </div>
</div>

<div id="mainContent" class="main-row hide-side">
  <?php if(empty($tree)):?>
  <div class="table-empty-tip">
    <p>
      <span class="text-muted"><?php echo $lang->task->noTask;?></span>
      <?php if(common::hasPriv('task', 'create', $checkObject)):?>
      <?php echo html::a($this->createLink('task', 'create', "execution=$executionID&storyID=&moduleID=&taskID=0&todoID=0&extra=&bugID=0&projectID=$projectID") . '#app=chteam', "<i class='icon icon-plus'></i> " . $lang->task->create, '', "class='btn btn-info'");?>
      <?php endif;?>
    </p>
  </div>
  <?php else:?>
  <div class="main-col">
    <div class="cell">
      <ul class="tree" id="taskTree">
        <?php echo $tree;?>
      </ul>
    </div>
  </div>
  <div class="side-col">
    <div class="cell">
      <div id="itemContent" class="load-indicator loading"></div>
    </div>
  </div>
  <?php endif;?>
</div>
<?php js::set('type', $level);?>
<?php js::set('collapse', false);?>
<script>
$(function()
{
    $('[data-type=<?php echo $level;?>]').addClass('btn-active-text');
})
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
