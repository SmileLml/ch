<?php

use DeepCopy\f001\A;

 include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php if(!isonlybody()):?>
    <?php $gobackLink = inlink('browse', "yearplanID=$yearplanDemand->parent");?>
    <?php echo html::a($gobackLink, '<i class="icon icon-back icon-sm"></i> ' . $lang->goback, '', "class='btn btn-secondary'");?>
    <div class="divider"></div>
    <?php endif;?>
    <div class="page-title">
      <span class="label label-id"><?php echo $yearplanDemand->id?></span>
      <span class="text" title='<?php echo $yearplanDemand->name;?>'><?php echo $yearplanDemand->name;?></span>
    </div>
  </div>
</div>
<div id="mainContent" class="main-row">
  <div class="main-col col-8">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->yearplandemand->desc;?></div>
        <div class="detail-content article-content">
          <?php echo !empty($yearplanDemand->desc) ? $yearplanDemand->desc : "<div class='text-center text-muted'>" . $lang->noData . '</div>';?>
        </div>
      </div>
    </div>
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->yearplandemand->milestone;?></div>
        <div class="detail-content article-content">
          <?php if(!empty($yearplanmilestones)):?>
          <table class='table table-hover table-fixed'>
            <thead>
              <tr>
                <?php foreach($this->lang->yearplandemand->milestoneFields as $milestoneField):?>
                  <td><?php echo $milestoneField;?></td>
                <?php endforeach;?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($yearplanmilestones as $yearplanmilestone):?>
                <tr>
                  <td><?php echo $yearplanmilestone->batch;?></td>
                  <td><?php echo $yearplanmilestone->name;?></td>
                  <td><?php echo formatTime($yearplanmilestone->planConfirmDate);?></td>
                  <td><?php echo formatTime($yearplanmilestone->goliveDate);?></td>
                </tr>
              <?php endforeach;?>
            </tbody>
          </table>
          <?php else: ?>
          <div class='text-center text-muted'>
          <?php echo $lang->noData;?>
          </div>
          <?php endif;?>
        </div>
      </div>
    </div>
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->yearplandemand->purchasedContents;?></div>
        <div class="detail-content article-content">
          <?php echo !empty($yearplanDemand->purchasedContents) ? $yearplanDemand->purchasedContents : "<div class='text-center text-muted'>" . $lang->noData . '</div>';?>
        </div>
      </div>
      <?php echo $this->fetch('file', 'printFiles', array('files' => $yearplanDemand->files, 'fieldset' => 'true', 'object' => $yearplanDemand));?>
    </div>
    <?php $this->printExtendFields($yearplanDemand, 'div', "position=left&inForm=0&inCell=1");?>
    <div class="cell"><?php include $app->getModuleRoot() . 'common/view/action.html.php';?></div>
    <div class='main-actions'>
      <div class="btn-toolbar">
        <?php common::printBack($gobackLink);?>
        <div class='divider'></div>
        <?php
        common::printIcon('yearplandemand', 'edit', "yearplandemandID=$yearplanDemand->id", $yearplanDemand, 'list');
        common::printIcon('yearplandemand', 'submit', "yearplandemandID=$yearplanDemand->id&yearplanId=$yearplanDemand->parent", $yearplanDemand, 'list', 'confirm', 'hiddenwin');
        common::printIcon('yearplandemand', 'confirm', "yearplandemandID=$yearplanDemand->id&yearplanId=$yearplanDemand->parent", $yearplanDemand, 'list', 'ok');
        common::printIcon('yearplandemand', 'cancel', "yearplandemandID=$yearplanDemand->id&yearplanId=$yearplanDemand->parent", $yearplanDemand, 'list', 'cancel ');
        common::printIcon('yearplandemand', 'restore', "yearplandemandID=$yearplanDemand->id&yearplanId=$yearplanDemand->parent", $yearplanDemand, 'list', 'magic ');
        common::printIcon('yearplandemand', 'delete', "yearplandemandID=$yearplanDemand->id&yearplanId=$yearplanDemand->parent", $yearplanDemand, 'list', 'trash', 'hiddenwin');
        common::printIcon('yearplandemand', 'sendback', "yearplandemandID=$yearplanDemand->id&yearplanId=$yearplanDemand->parent", $yearplanDemand, 'list', 'back');
        ?>
      </div>
    </div>
  </div>
  <div class="side-col col-4">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->yearplandemand->basicInfo;?></div>
        <div class='detail-content'>
          <table class='table table-data'>
            <tbody>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->level;?></th>
                <td><?php echo zget($lang->yearplandemand->levelList, $yearplanDemand->level, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->status;?></th>
                <td><?php echo zget($lang->yearplandemand->statusList, $yearplanDemand->status, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->category;?></th>
                <td><?php echo zget($lang->yearplandemand->categoryList, $yearplanDemand->category, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->initDept;?></th>
                <td><?php echo zget($depts, $yearplanDemand->initDept, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->dept;?></th>
                <td>
                  <?php
                  foreach(explode(',', $yearplanDemand->dept) as $dept)
                  {
                    echo zget($depts, $dept, '') . ' &nbsp;';
                  }
                  ?>
                </td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->approvalDate;?></th>
                <td><?php echo formatTime($yearplanDemand->approvalDate);?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->planConfirmDate;?></th>
                <td><?php echo formatTime($yearplanDemand->planConfirmDate);?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->goliveDate;?></th>
                <td><?php echo formatTime($yearplanDemand->goliveDate);?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->itPlanInto;?></th>
                <td><?php echo $yearplanDemand->itPlanInto;?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->itPM;?></th>
                <td><?php echo zget($users, $yearplanDemand->itPM, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->businessArchitect;?></th>
                <td><?php echo zget($users, $yearplanDemand->businessArchitect, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->businessManager;?></th>
                <td>
                  <?php
                  foreach(explode(',', $yearplanDemand->businessManager) as $user)
                  {
                    echo zget($users, $user, '') . ' &nbsp;';
                  }
                  ?>
                </td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->isPurchased;?></th>
                <td><?php echo zget($lang->yearplandemand->isPurchasedList, $yearplanDemand->isPurchased, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->mergeTo;?></th>
                <td>
                <?php 
                if(!empty($yearplanDemand->mergeTo))
                {
                  $yearplandemandLink = helper::createLink('yearplandemand', 'view', "yearplandemandID=$yearplanDemand->mergeTo");
                  echo html::a($yearplandemandLink, zget($yearplanDemands, $yearplanDemand->mergeTo, ''));
                }
                ?>
                </td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->mergeSources;?></th>
                <td>
                  <?php
                  foreach(explode(',', $yearplanDemand->mergeSources) as $mergeSource)
                  {
                    $yearplandemandLink = helper::createLink('yearplandemand', 'view', "yearplandemandID=$mergeSource");
                    echo empty($mergeSource) ? '' : html::a($yearplandemandLink, zget($yearplanDemands, $mergeSource, '')) . ' &nbsp;';
                  }
                  ?>
                </td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->createdBy;?></th>
                <td><?php echo zget($users, $yearplanDemand->createdBy, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplandemand->createdDate;?></th>
                <td><?php echo formatTime($yearplanDemand->createdDate);?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php $this->printExtendFields($yearplanDemand, 'div', "position=right&inForm=0&inCell=1");?>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>