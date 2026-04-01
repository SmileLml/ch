<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<style>
.table td{white-space:nowrap;text-overflow:ellipsis;overflow:hidden;position:unset !important;border-bottom-color:#ddd !important;}
.requirement{background: #fff}
.main-table tbody>tr:hover { background-color: #fff; }
.main-table tbody>tr:nth-child(odd):hover { background-color: #f5f5f5; }
.fix-table-copy-wrapper {overflow: unset !important;}
.table tr > th .dropdown > a.dropdown-toggle {display: flex; align-items: center;}
.table tr > th .dropdown > a.dropdown-toggle .product-name {overflow: hidden;}
.main-table {margin-bottom: 20px}
.table td:not(#demandTrack-search td) {text-align: left; padding: 2px 8px !important;}
</style>
<div id="mainMenu" class="clearfix">
   <a class="btn btn-link querybox-toggle" id='bysearchTab'><i class="icon icon-search muted"></i> <?php echo $lang->searchAB;?></a>
</div>
<div id="mainContent" class="main-row fade">
  <div class="main-col">
    <div class="cell<?php if($browseType == 'bysearch') echo ' show';?>" id="queryBox" data-module='demandTrack'></div>
    <?php if(false and empty($tracks)):?>
    <div class="table-empty-tip">
      <p>
        <span class="text-muted"><?php echo $lang->noData;?></span>
      </p>
    </div>
    <?php else:?>
    <div class='main-table' data-ride="table">
      <div class="table-responsive" style="overflow: auto;">
        <table class='table table-bordered' id="trackList">
          <thead>
            <tr class='text-left'>
              <th class='w-200px'><?php echo $lang->demand->originalDemand;?></th>
              <th class='w-200px'><?php echo $lang->business->common;?></th>
              <th class='w-200px'><?php echo $lang->demand->projectName;?></th>
              <th class='w-200px'><?php echo $lang->story->requirement;?></th>
              <th class='w-200px'><?php echo $lang->story->common;?></th>
              <th class='w-200px'><?php echo $lang->story->product;?></th>
              <th class='w-200px'><?php echo $lang->story->project;?></th>
              <th class='w-200px'><?php echo $lang->story->execution;?></th>
              <th class='w-200px'><?php echo $lang->story->tasks;?></th>
              <?php if($config->edition == 'max'):?>
              <th class='w-200px'><?php echo $lang->story->design;?></th>
              <?php endif;?>
              <th class='w-200px'><?php echo $lang->story->case;?></th>
              <th class='w-200px'><?php echo $lang->story->build;?></th>
              <th class='w-200px'><?php echo $lang->story->testtask;?></th>
              <th class='w-200px'><?php echo $lang->story->testreport;?></th>
              <th class='w-200px'><?php echo $lang->story->release;?></th>
              <?php if($config->edition == 'max'):?>
              <th class='w-200px'><?php echo $lang->story->repoCommit;?></th>
              <?php endif;?>
              <th class='w-200px'><?php echo $lang->story->bug;?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($tracks as $demandID => $demand):?>
            <?php
            $rowspan = count($demand->stories);
            foreach($demand->requirements as $requirement) $rowspan += count($requirement->stories) ? count($requirement->stories) : 1;
            ?>
            <tr>
              <td <?php if($rowspan != 0) echo "rowspan=" . $rowspan;?>>
                <?php echo html::a(inlink('view', "demandID=$demandID"), $demand->name);?>
              </td>
              <td <?php if($rowspan != 0) echo "rowspan=" . $rowspan;?>>
                  <?php echo ($demand->businessID) ? html::a($this->createLink('business', 'view', "id=$demand->businessID"), $demand->businessName) : ''?>
              </td>
              <td <?php if($rowspan != 0) echo "rowspan=" . $rowspan;?>>
                    <?php if($demand->project || $demand->projectApproval) echo ($demand->project && $demand->projectName) ? html::a($this->createLink('projectapproval', 'view', "id=$demand->project"), $demand->projectName) : '';?>
              </td>
              <?php if(!empty($demand->requirements)):?>
              <?php $i = 0;?>
              <?php foreach($demand->requirements as $requirement):?>
              <?php if($i != 0) echo '<tr>';?>
              <td rowspan=<?php echo count($requirement->stories) ? count($requirement->stories) : 1;?>><?php echo html::a($this->createLink('story', 'view', "requirementID=$requirement->id"), $requirement->title);?></td>

              <?php if($requirement->stories):?>
              <?php $j = 0;?>
              <?php foreach($requirement->stories as $story):?>
              <?php if($j != 0) echo '<tr>';?>
              <td><?php echo html::a($this->createLink('story', 'view', "storyID=$story->id"), $story->title);?></td>
              <td><?php echo html::a($this->createLink('product', 'view', "id=$story->product"), zget($products, $story->product, $story->product));?></td>
              <td>
                <?php foreach($story->projects as $id => $project):?>
                <?php echo html::a($this->createLink('project', 'view', "id=$id"), $project->name, '', "title='$project->name'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <td>
                <?php foreach($story->executions as $id => $execution):?>
                <?php echo html::a($this->createLink('execution', 'view', "id=$id"), $execution->name, '', "title='$execution->name'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <td>
                <?php foreach($story->tasks as $taskID => $task):?>
                <?php echo html::a($this->createLink('task', 'view', "taskID=$taskID"), $task->name, '', "title='$task->name'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <?php if($config->edition == 'max'):?>
              <td>
                <?php foreach($story->designs as $designID => $design):?>
                <?php echo html::a($this->createLink('design', 'view', "designID=$designID"), $design->name, '', "title='$design->name'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <?php endif;?>
              <td>
                <?php foreach($story->cases as $caseID => $case):?>
                <?php echo html::a($this->createLink('testcase', 'view', "caseID=$caseID"), $case->title, '', "title='$case->title'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <td>
                <?php foreach($story->builds as $buildID => $buildName):?>
                <?php echo html::a($this->createLink('build', 'view', "buildID=$buildID"), $buildName, '', "title='$buildName'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <td>
                <?php foreach($story->testtasks as $taskID => $testtask):?>
                <?php echo html::a($this->createLink('testtask', 'cases', "taskID=$taskID"), $testtask->name, '', "title='$testtask->name'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <td>
                <?php foreach($story->testreports as $reportID => $reportName):?>
                <?php echo html::a($this->createLink('testreport', 'view', "reportID=$reportID"), $reportName, '', "title='$reportName'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <td>
                <?php foreach($story->releases as $releaseID => $release):?>
                <?php echo html::a($this->createLink('release', 'view', "releaseID=$releaseID"), $release->name, '', "title='$release->name'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <?php if($config->edition == 'max'):?>
              <td>
                <?php foreach($story->revisions as $revision => $repoComment):?>
                <?php
                echo html::a($this->createLink('design', 'revision', "repoID=$revision"), '#'. $revision . '-' . $repoComment, '', "data-app='devops'") . '<br/>';
                ?>
                <?php endforeach;?>
              </td>
              <?php endif;?>
              <td>
                <?php foreach($story->bugs as $bugID => $bug):?>
                <?php echo html::a($this->createLink('bug', 'view', "bugID=$bugID"), $bug->title, '', "title='$bug->title'") . '<br/>';?>
                <?php endforeach;?>
              </td>
              <?php if($j != 0) echo '</tr>';?>
              <?php $j++;?>
              <?php endforeach;?>
              <?php else:?>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <?php endif;?>

              <?php if($i != 0) echo '</tr>';?>
              <?php $i++;?>
              <?php endforeach;?>
              <?php endif;?>

              <?php if(!empty($demand->stories)):?>
              <?php foreach($demand->stories as $story):?>
              <?php if(!empty($demand->requirements)) echo '<tr>';?>
                <td></td>
                <td><?php echo html::a($this->createLink('story', 'view', "storyID=$story->id"), $story->title);?></td>
                <td><?php echo html::a($this->createLink('product', 'view', "id=$story->product"), zget($products, $story->product, $story->product));?></td>
                <td>
                  <?php foreach($story->projects as $id => $project):?>
                  <?php echo html::a($this->createLink('project', 'view', "id=$id"), $project->name, '', "title='$project->name'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <td>
                  <?php foreach($story->executions as $id => $execution):?>
                  <?php echo html::a($this->createLink('execution', 'view', "id=$id"), $execution->name, '', "title='$execution->name'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <td>
                  <?php foreach($story->tasks as $taskID => $task):?>
                  <?php echo html::a($this->createLink('task', 'view', "taskID=$taskID"), $task->name, '', "title='$task->name'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <?php if($config->edition == 'max'):?>
                <td>
                  <?php foreach($story->designs as $designID => $design):?>
                  <?php echo html::a($this->createLink('design', 'view', "designID=$designID"), $design->name, '', "title='$design->name'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <?php endif;?>
                <td>
                  <?php foreach($story->cases as $caseID => $case):?>
                  <?php echo html::a($this->createLink('testcase', 'view', "caseID=$caseID"), $case->title, '', "title='$case->title'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <td>
                  <?php foreach($story->builds as $buildID => $buildName):?>
                  <?php echo html::a($this->createLink('build', 'view', "buildID=$buildID"), $buildName, '', "title='$buildName'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <td>
                  <?php foreach($story->testtasks as $taskID => $testtask):?>
                  <?php echo html::a($this->createLink('testtask', 'cases', "taskID=$taskID"), $testtask->name, '', "title='$testtask->name'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <td>
                  <?php foreach($story->testreports as $reportID => $reportName):?>
                  <?php echo html::a($this->createLink('testreport', 'view', "reportID=$reportID"), $reportName, '', "title='$reportName'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <td>
                  <?php foreach($story->releases as $releaseID => $release):?>
                  <?php echo html::a($this->createLink('release', 'view', "releaseID=$releaseID"), $release->name, '', "title='$release->name'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
                <?php if($config->edition == 'max'):?>
                <td>
                  <?php foreach($story->revisions as $revision => $repoComment):?>
                  <?php
                  echo html::a($this->createLink('design', 'revision', "repoID=$revision"), '#'. $revision . '-' . $repoComment, '', "data-app='devops'") . '<br/>';
                  ?>
                  <?php endforeach;?>
                </td>
                <?php endif;?>
                <td>
                  <?php foreach($story->bugs as $bugID => $bug):?>
                  <?php echo html::a($this->createLink('bug', 'view', "bugID=$bugID"), $bug->title, '', "title='$bug->title'") . '<br/>';?>
                  <?php endforeach;?>
                </td>
              <?php if(!empty($demand->requirements)) echo '</tr>';?>
              <?php endforeach;?>
              <?php endif;?>

              <?php if(empty($demand->stories) and empty($demand->requirements)):?>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <?php endif;?>

            </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <div class='table-footer'><?php $pager->show('right', 'pagerjs');?></div>
    </div>
    <?php endif;?>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
