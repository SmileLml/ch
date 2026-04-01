<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php if(!isonlybody()):?>
    <?php echo html::a(inlink('browse'), '<i class="icon icon-back icon-sm"></i> ' . $lang->goback, '', "class='btn btn-secondary'");?>
    <div class="divider"></div>
    <?php endif;?>
    <div class="page-title">
      <span class="label label-id"><?php echo $yearplan->id?></span>
      <span class="text" title='<?php echo $yearplan->name;?>'><?php echo $yearplan->name;?></span>
    </div>
  </div>
</div>
<div id="mainContent" class="main-row">
  <div class="main-col col-8">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->yearplan->desc;?></div>
        <div class="detail-content article-content">
          <?php echo !empty($yearplan->desc) ? $yearplan->desc : "<div class='text-center text-muted'>" . $lang->noData . '</div>';?>
        </div>
      </div>
      <?php echo $this->fetch('file', 'printFiles', array('files' => $yearplan->files, 'fieldset' => 'true', 'object' => $yearplan));?>
      <?php $actionFormLink = $this->createLink('action', 'comment', "objectType=yearplan&objectID=$yearplan->id");?>
    </div>
    <div class="cell"><?php include $app->getModuleRoot() . 'common/view/action.html.php';?></div>
    <div class='main-actions'>
      <div class="btn-toolbar">
        <?php common::printBack(inlink('browse'));?>
        <div class='divider'></div>
        <?php
          common::printIcon('yearplan', 'edit', "yearplanID=$yearplan->id", $yearplan, 'button');
          common::printIcon('yearplan', 'close', "yearplanID=$yearplan->id", $yearplan, 'button', 'off', '', 'iframe', true);
          common::printIcon('yearplan', 'delete', "yearplanID=$yearplan->id", $yearplan, 'button', 'trash', 'hiddenwin');
        ?>
      </div>
    </div>
  </div>
  <div class="side-col col-4">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->yearplan->basicInfo;?></div>
        <div class='detail-content'>
          <table class='table table-data'>
            <tbody>
              <tr>
                <th class='w-100px'><?php echo $lang->yearplan->owner;?></th>
                <td><?php echo zget($users, $yearplan->owner, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->yearplan->participant;?></th>
                <td><?php foreach(explode(',', $yearplan->participant) as $user) echo zget($users, $user, '') . ' '; ?></td>
              </tr>
              <tr>
                <th><?php echo $lang->yearplan->createdBy;?></th>
                <td><?php echo zget($users, $yearplan->createdBy, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->yearplan->createdDate;?></th>
                <td><?php echo $yearplan->createdDate;?></td>
              </tr>
              <?php $this->printExtendFields($yearplan, 'table', "position=left&inForm=0");?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
