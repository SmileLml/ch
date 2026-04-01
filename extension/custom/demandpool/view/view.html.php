<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php if(!isonlybody()):?>
    <?php echo html::a(inlink('browse'), '<i class="icon icon-back icon-sm"></i> ' . $lang->goback, '', "class='btn btn-secondary'");?>
    <div class="divider"></div>
    <?php endif;?>
    <div class="page-title">
      <span class="label label-id"><?php echo $demandpool->id?></span>
      <span class="text" title='<?php echo $demandpool->name;?>'><?php echo $demandpool->name;?></span>
    </div>
  </div>
</div>
<div id="mainContent" class="main-row">
  <div class="main-col col-8">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demandpool->desc;?></div>
        <div class="detail-content article-content">
          <?php echo !empty($demandpool->desc) ? $demandpool->desc : "<div class='text-center text-muted'>" . $lang->noData . '</div>';?>
        </div>
      </div>
      <?php echo $this->fetch('file', 'printFiles', array('files' => $demandpool->files, 'fieldset' => 'true', 'object' => $demandpool));?>
      <?php $actionFormLink = $this->createLink('action', 'comment', "objectType=demandpool&objectID=$demandpool->id");?>
    </div>
    <div class="cell"><?php include $app->getModuleRoot() . 'common/view/action.html.php';?></div>
    <div class='main-actions'>
      <div class="btn-toolbar">
        <?php common::printBack(inlink('browse'));?>
        <div class='divider'></div>
        <?php
          common::printIcon('demandpool', 'edit', "demandpoolID=$demandpool->id", $demandpool, 'button');
          common::printIcon('demandpool', 'close', "demandpoolID=$demandpool->id", $demandpool, 'button', 'off', '', 'iframe', true);
          common::printIcon('demandpool', 'delete', "demandpoolID=$demandpool->id", $demandpool, 'button', 'trash', 'hiddenwin');
        ?>
      </div>
    </div>
  </div>
  <div class="side-col col-4">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demandpool->basicInfo;?></div>
        <div class='detail-content'>
          <table class='table table-data'>
            <tbody>
              <tr>
                <th class='w-100px'><?php echo $lang->demandpool->owner;?></th>
                <td><?php echo zget($users, $demandpool->owner, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demandpool->dept;?></th>
                <td><?php echo zget($depts, $demandpool->dept, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demandpool->participant;?></th>
                <td><?php foreach(explode(',', $demandpool->participant) as $user) echo zget($users, $user, '') . ' '; ?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demandpool->createdBy;?></th>
                <td><?php echo zget($users, $demandpool->createdBy, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demandpool->createdDate;?></th>
                <td><?php echo $demandpool->createdDate;?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
