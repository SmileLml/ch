<?php
/**
 * The bug view file of execution module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     execution
 * @version     $Id: bug.html.php 4894 2013-06-25 01:28:39Z wyd621@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datatable.fix.html.php';?>
<style>
#subHeader #dropMenu .col-left .list-group {margin-bottom: 0px; padding-top: 10px;}
#subHeader #dropMenu .col-left {padding-bottom: 0px;}
#currentBranch + #dropMenu .col-left {padding-bottom: 30px;}
</style>
<div id="mainMenu" class="clearfix main-row fade in">
  <div id="sidebarHeader">
    <div class="title" title="<?php echo $moduleName;?>">
     <?php
     echo $moduleName;
     if(!empty($moduleID))
     {
         $removeLink = $this->createLink('chproject', 'bug', "projectID={$projectID}&intanceProjectID={$intanceProjectID}&productID={$productID}&branch={$branchID}&orderBy=$orderBy&build=$buildID&type=$type&param=0&recTotal=$pager->recTotal&recPerPage=$pager->recPerPage");
         echo html::a($removeLink, "<i class='icon icon-sm icon-close'></i>", '', "class='text-muted' data-app='{$this->app->tab}'");
     }
     ?>
    </div>
  </div>
  <div class="btn-toolbar pull-left">
    <div class='btn-group'>
      <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjects, $intanceProjectID) : $lang->chproject->allProject;?>
      <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
      <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
        <?php
          $class = '';
          if($intanceProjectID == 0) $class = 'class="active"';
          echo "<li $class>" . html::a($this->createLink('chproject', 'bug', "projectID=$projectID&intanceProjectID=0&productID=$productID&branch=$branchID&orderBy=$orderBy&build=$buildID&type=$type&param=0"), $lang->chproject->allProject) . "</li>";
          foreach($intanceProjects as $key => $intanceProjectName)
          {
              $class = $intanceProjectID == $key ? 'class="active"' : '';
              echo "<li $class>" . html::a($this->createLink('chproject', 'bug', "projectID=$projectID&intanceProjectID=$key&productID=$productID&branch=$branchID&orderBy=$orderBy&build=$buildID&type=$type&param=0"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
          }
        ?>
      </ul>
    </div>
    <?php common::sortFeatureMenu();?>
    <?php foreach($lang->execution->featureBar['bug'] as $featureType => $label):?>
    <?php $active = $type == $featureType ? 'btn-active-text' : '';?>
    <?php $label  = "<span class='text'>$label</span>";?>
    <?php if($type == $featureType):?>
    <?php $label .= " <span class='label label-light label-badge'>{$pager->recTotal}</span>";?>
    <?php $label .= $build ? " <span class='label label-danger'>Build:{$build->name}</span>" : '';?>
    <?php endif;?>
    <?php $module = $type != 'bysearch' ? "&param=$param" : '';?>
    <?php echo html::a(inlink('bug', "projectID=$projectID&intanceProjectID={$intanceProjectID}&productID={$productID}&branch={$branchID}&orderBy=status,id_desc&build=$buildID&type={$featureType}$module"), $label, '', "class='btn btn-link $active' id='{$featureType}Tab'");?>
    <?php endforeach;?>
    <a class="btn btn-link querybox-toggle" id="bysearchTab"><i class="icon icon-search muted"></i> <?php echo $lang->bug->search;?></a>
  </div>
  <div class="btn-toolbar pull-right">
    <?php common::printLink('bug', 'export', "productID=0&orderBy=$orderBy&browseType=&executionID=0&chprojectID=$projectID", "<i class='icon icon-export muted'> </i> " . $lang->bug->export, '', "class='btn btn-link export'");?>
    <?php common::printLink('bug', 'create', "productID=$defaultProduct&branch=0&extras=&chprojectID=$projectID", "<i class='icon icon-plus'></i> " . $lang->bug->create, '', "class='btn btn-primary' data-app='chteam'");?>
  </div>
</div>
<?php if($this->app->getViewType() == 'xhtml'):?>
<div id="xx-title">
  <strong>
  <?php echo ($this->project->getById($project->project)->name . ' / ' . $this->project->getByID($project->id)->name) ?>
  </strong>
</div>
<?php endif;?>
<div id="mainContent" class='main-row split-row fade'>
  <div class="side-col" id='sidebar' data-min-width='235'>
    <div class="sidebar-toggle"><i class="icon icon-angle-left"></i></div>
    <div class='cell'>
      <?php if(!$moduleTree):?>
      <hr class="space">
      <div class="text-muted" style='text-align:center'><?php echo $lang->bug->noModule;?></div>
      <hr class="space">
      <?php else:?>
      <?php echo $moduleTree;?>
      <?php endif;?>
    </div>
  </div>
  <div class='main-col' data-min-width='400'>
    <div class="cell <?php if($type == 'bysearch') echo 'show';?>" id="queryBox" data-module='chprojectBug'></div>
    <?php if(empty($bugs)):?>
    <div class="table-empty-tip">
      <p>
        <span class="text-muted"><?php echo $lang->bug->noBug;?></span>
        <?php if(common::canModify('execution', $project) and common::hasPriv('bug', 'create')):?>
        <?php echo html::a($this->createLink('bug', 'create', "productID=$defaultProduct&branch=0&extras=&chprojectID=$projectID"), "<i class='icon icon-plus'></i> " . $lang->bug->create, '', "class='btn btn-info' data-app='chteam'");?>
        <?php endif;?>
      </p>
    </div>
    <?php else:?>
    <?php
    $datatableId  = $this->moduleName . ucfirst($this->methodName);
    $useDatatable = (isset($config->datatable->$datatableId->mode) and $config->datatable->$datatableId->mode == 'datatable');
    ?>
    <?php if($this->app->getViewType() == 'xhtml'):?>
    <form class='main-table' method='post' id='executionBugForm'>
    <?php else:?>
    <form class='main-table' method='post' id='executionBugForm' <?php if(!$useDatatable) echo "data-ride='table'";?>>
    <?php endif;?>
      <div class="table-header fixed-right">
        <nav class="btn-toolbar pull-right setting"></nav>
      </div>
      <?php
      $vars = "projectID=$projectID&intanceProjectID={$intanceProjectID}&productID={$productID}&branch={$branchID}&orderBy=%s&build=$buildID&type=$type&param=$param&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}";

      $useDatatable ? include $app->getModuleRoot() . 'common/view/datatable.html.php' : include $app->getModuleRoot() . 'common/view/tablesorter.html.php';

      $setting = $this->datatable->getSetting('chproject');
      $widths  = $this->datatable->setFixedFieldWidth($setting);
      $columns = 0;

      $canBatchAssignTo = common::hasPriv('bug', 'batchAssignTo');
      ?>
      <?php if(!$useDatatable) echo '<div class="table-responsive">';?>
      <table class='table has-sort-head<?php if($useDatatable) echo ' datatable';?>' id='bugList' data-fixed-left-width='<?php echo $widths['leftWidth']?>' data-fixed-right-width='<?php echo $widths['rightWidth']?>'>
        <thead>
          <tr>
            <?php if($this->app->getViewType() == 'xhtml'):?>
            <?php
            foreach($setting as $value)
            {
                if($value->id == 'title' || $value->id == 'id' || $value->id == 'pri' || $value->id == 'status')
                {
                    $this->datatable->printHead($value, $orderBy, $vars, $canBatchAssignTo);
                    $columns ++;
                }
            }
            ?>
            <?php else:?>
            <?php
            foreach($setting as $value)
            {
                if(!$project->hasProduct and $project->model != 'scrum' and $value->id == 'plan') continue;
                if(!$project->hasProduct and $value->id == 'branch') continue;
                if($value->show)
                {
                    if(common::checkNotCN() and $value->id == 'severity')  $value->name = $lang->bug->severity;
                    if(common::checkNotCN() and $value->id == 'pri')       $value->name = $lang->bug->pri;
                    if(common::checkNotCN() and $value->id == 'confirmed') $value->name = $lang->bug->confirmed;
                    $this->datatable->printHead($value, $orderBy, $vars, $canBatchAssignTo);
                    $columns ++;
                }
            }
            ?>
            <?php endif;?>
          </tr>
        </thead>
        <tbody>
        <?php foreach($bugs as $bug):?>
        <tr data-id='<?php echo $bug->id?>'>
          <?php if($this->app->getViewType() == 'xhtml'):?>
          <?php
            foreach($setting as $value)
            {
                if($value->id == 'title' || $value->id == 'id' || $value->id == 'pri' || $value->id == 'status')
                {
                  $this->bug->printCell($value, $bug, $users, $builds, $branchOption, $modulePairs, array($project), $plans, $stories, $tasks, $useDatatable ? 'datatable' : 'table', $projectPairs, $products);
                }
            }?>
          <?php else:?>
          <?php foreach($setting as $value)
          {
              if(!$project->hasProduct and $project->model != 'scrum' and $value->id == 'plan') continue;
              if(!$project->hasProduct and $value->id == 'branch') continue;
              $this->bug->printCell($value, $bug, $users, $builds, $branchOption, $modulePairs, $executions, $plans, $stories, $tasks, $useDatatable ? 'datatable' : 'table', $projectPairs, $products);
          }
          ?>
          <?php endif;?>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      <?php if(!$useDatatable) echo '</div>';?>
      <div class='table-footer'>
        <?php if($canBatchAssignTo):?>
        <div class="checkbox-primary check-all"><label><?php echo $lang->selectAll?></label></div>
        <div class="table-actions btn-toolbar">
          <div class="btn-group dropup">
            <button data-toggle="dropdown" type="button" class="btn" id="mulAssigned"><?php echo $lang->bug->assignedTo?> <span class="caret"></span></button>
            <?php
            $withSearch = count($memberPairs) > 10;
            $actionLink = $this->createLink('bug', 'batchAssignTo', "projectID=$projectID&type=chproject");
            echo html::select('assignedTo', $memberPairs, '', 'class="hidden"');

            if($withSearch)
            {
                echo "<div class='dropdown-menu search-list search-box-sink' data-ride='searchList'>";
                echo '<div class="input-control search-box has-icon-left has-icon-right search-example">';
                echo '<input id="userSearchBox" type="search" class="form-control search-input" autocomplete="off" />';
                echo '<label for="userSearchBox" class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label>';
                echo '<a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a>';
                echo '</div>';
                $membersPinYin = common::convert2Pinyin($memberPairs);
            }
            else
            {
                echo "<div class='dropdown-menu search-list'>";
            }
            echo '<div class="list-group">';
            foreach($memberPairs as $key => $value)
            {
                if(empty($key)) continue;
                $searchKey = $withSearch ? ('data-key="' . zget($membersPinYin, $value, '') . " @$key\"") : "data-key='@$key'";
                echo html::a("javascript:$(\".table-actions #assignedTo\").val(\"$key\");setFormAction(\"$actionLink\")", $value, '', $searchKey);
            }
            echo "</div>";
            echo "</div>";
            ?>
          </div>
        </div>
        <?php endif;?>
        <div class="table-statistic"><?php echo $summary;?></div>
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>
<?php js::set('replaceID', 'bugList');?>
<?php js::set('browseType', $type);?>
<?php js::set('param', $param);?>
<script>
<?php if(!empty($useDatatable)):?>
$(function(){$('#executionBugForm').table();})
<?php endif;?>
</script>
<?php include $app->getModuleRoot(). 'common/view/footer.html.php';?>
