<?php
/**
 * The browse view file of testreport module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Yidong Wang <yidong@cnezsoft.com>
 * @package     testreport
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php if($config->global->flow == 'full'):?>
<div id='mainMenu' class='clearfix'>
  <div class='pull-left btn-toolbar'>
    <div class='btn-group'>
      <?php $viewName = $intanceProjectID != 0 ? zget($intanceProjectPairs, $intanceProjectID) : $lang->chproject->allProject;?>
      <a href='javascript:;' class='btn btn-link btn-limit text-ellipsis' data-toggle='dropdown' style="max-width: 120px;"><span class='text' title='<?php echo $viewName;?>'><?php echo $viewName;?></span> <span class='caret'></span></a>
      <ul class='dropdown-menu' style='max-height:240px; max-width: 300px; overflow-y:auto'>
        <?php
            $class = '';
            if($intanceProjectID == 0) $class = 'class="active"';
            echo "<li $class>" . html::a($this->createLink('chproject', 'testreport', "projectID=$projectID&chprojectID=0&extra=$extra&orderBy=$orderBy&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID"), $lang->chproject->allProject) . "</li>";
            foreach($intanceProjectPairs as $key => $intanceProjectName)
            {
                $class = $intanceProjectID == $key ? 'class="active"' : '';
                echo "<li $class>" . html::a($this->createLink('chproject', 'testreport', "projectID=$projectID&chprojectID=$key&extra=$extra&orderBy=$orderBy&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID"), $intanceProjectName, '', "title='{$intanceProjectName}' class='text-ellipsis'") . "</li>";
            }
        ?>
      </ul>
    </div>
    <span class='btn btn-link btn-active-text'>
      <span class='text'><?php echo $lang->testreport->browse;?></span>
      <span class="label label-light label-badge"><?php echo $pager->recTotal;?></span>
    </span>
  </div>

</div>
<?php endif;?>

<div id='mainContent' class='main-table'>
  <?php if(empty($reports)):?>
  <div class="table-empty-tip">
    <p><span class="text-muted"><?php echo $lang->testreport->noReport;?></span></p>
  </div>
  <?php else:?>
  <table class='table has-sort-head table-fixed' id='reportList'>
    <?php $vars = "projectID=$projectID&intanceProjectID=$intanceProjectID&extra=$extra&orderBy=%s&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}";?>
    <thead>
      <tr class='text-center'>
        <th class='c-id'><?php common::printOrderLink('id', $orderBy, $vars, $lang->idAB);?></th>
        <th class='text-left'><?php common::printOrderLink('title', $orderBy, $vars, $lang->testreport->title);?></th>
        <th class='c-user'><?php common::printOrderLink('product', $orderBy, $vars, $lang->testreport->product);?></th>
        <th class='c-user'><?php common::printOrderLink('project', $orderBy, $vars, $lang->testreport->project);?></th>
        <th class='c-user'><?php common::printOrderLink('createdBy', $orderBy, $vars, $lang->openedByAB);?></th>
        <th class='c-full-date'><?php common::printOrderLink('createdDate', $orderBy, $vars, $lang->testreport->createdDate);?></th>
        <?php if($intanceProject->multiple):?>
        <th class='c-object text-left'><?php common::printOrderLink('project', $orderBy, $vars, $lang->testreport->execution);?></th>
        <?php endif;?>
        <th class='c-object text-left'><?php echo $lang->testreport->testtask;?></th>
        <th class='c-actions-2'><?php echo $lang->actions;?></th>
      </tr>
    </thead>
    <tbody class='text-center'>
      <?php foreach($reports as $report):?>
      <tr>
        <?php $viewLink = helper::createLink('testreport', 'view', "reportID=$report->id&tab=basic&reTotal=0&recPerPage=100&pageID=1&chprojectID=$projectID#app=chteam");?>
        <td><?php echo html::a($viewLink, sprintf('%03d', $report->id), '', "data-app='{$app->tab}'");?></td>
        <td class='c-name'><?php echo html::a($viewLink, $report->title, '', "data-app='{$app->tab}' title='{$report->title}'")?></td>
        <td title="<?php echo $report->productName;?>"><?php echo $report->productName;?></td>
        <td title="<?php echo $report->projectName;?>"><?php echo $report->projectName;?></td>
        <td><?php echo zget($users, $report->createdBy);?></td>
        <td><?php echo substr($report->createdDate, 2);?></td>
        <?php if($intanceProject->multiple):?>
        <?php $execution = zget($executions, $report->execution, '');?>
        <?php $executionName = ($report->execution and zget($execution, 'multiple', '')) ? '#' . $report->execution . zget($execution, 'name', '') : '';?>
        <td class='text-left' title='<?php echo $executionName?>'><?php echo $executionName;?></td>
        <?php endif;?>
        <?php
        $taskName = '';
        foreach(explode(',', $report->tasks) as $taskID) $taskName .= '#' . $taskID . $tasks[$taskID] . ' ';
        ?>
        <td class='text-left' title='<?php echo $taskName?>'><?php echo $taskName;?></td>
        <td class='c-actions'>
          <?php
          if(common::canBeChanged('report', $report))
          {
            common::printIcon('testreport', 'edit', "id=$report->id&begin=&end=&chprojectID=$projectID#app=chteam", '', 'list');
            common::printIcon('testreport', 'delete', "id=$report->id&confirm=no&project=$projectID", '', 'list', 'trash', 'hiddenwin');
          }
          ?>
        </td>
      </tr>
      <?php endforeach;?>
    </tbody>
  </table>
  <div class='table-footer'><?php $pager->show('right', 'pagerjs');?></div>
  <?php endif;?>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>

