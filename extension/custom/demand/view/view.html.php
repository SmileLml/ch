<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php if(!isonlybody()):?>
    <?php $gobackLink = $this->session->demandViewBackUrl ? $this->session->demandViewBackUrl : inlink('browse', "poolID=$demand->pool");?>
    <?php echo html::a($gobackLink, '<i class="icon icon-back icon-sm"></i> ' . $lang->goback, '', "class='btn btn-secondary'");?>
    <div class="divider"></div>
    <?php endif;?>
    <div class="page-title">
      <span class="label label-id"><?php echo $demand->id?></span>
      <span class="text" title='<?php echo $demand->name;?>'><?php echo $demand->name;?></span>
    </div>
  </div>
</div>
<div id="mainContent" class="main-row">
  <div class="main-col col-8">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demand->businessDesc;?></div>
        <div class="detail-content article-content">
          <?php echo !empty($demand->businessDesc) ? $demand->businessDesc : "<div class='text-center text-muted'>" . $lang->noData . '</div>';?>
        </div>
      </div>
    </div>
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demand->desc;?></div>
        <div class="detail-content article-content">
          <?php echo !empty($demand->desc) ? $demand->desc : "<div class='text-center text-muted'>" . $lang->noData . '</div>';?>
        </div>
      </div>
      <?php echo $this->fetch('file', 'printFiles', array('files' => $demand->files, 'fieldset' => 'true', 'object' => $demand));?>
      <?php $actionFormLink = $this->createLink('action', 'comment', "objectType=demand&objectID=$demand->id");?>
    </div>
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demand->businessObjective;?></div>
        <div class="detail-content article-content">
          <?php echo !empty($demand->businessObjective) ? $demand->businessObjective : "<div class='text-center text-muted'>" . $lang->noData . '</div>';?>
        </div>
      </div>
    </div>
    <?php if($demand->URS):?>
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demand->URS;?></div>
        <div class="detail-content main-table">
           <table class='table'>
             <thead>
               <tr>
                 <th class='w-60px'><?php echo $lang->story->id;?></th>
                 <th class='w-60px'><?php echo $lang->story->pri;?></th>
                 <th class='w-100px'><?php echo $lang->story->type;?></th>
                 <th><?php echo $lang->URCommon . $lang->demand->nameAB;?></th>
                 <th class='w-80px'><?php echo $lang->story->statusAB;?></th>
                 <th class='w-80px'><?php echo $lang->story->stageAB;?></th>
               </tr>
             </thead>
             <tbody>
             <?php foreach($demand->URS as $story):?>
               <tr>
                 <td><?php echo $story->id;?></th>
                 <td><?php echo zget($lang->story->priList, $story->pri);?></th>
                 <td><?php echo $lang->URCommon;?></th>
                 <td><?php echo html::a(helper::createLink('story', 'view', "id={$story->id}"), $story->title);?></th>
                 <td><?php echo zget($lang->story->statusList, $story->status);?></th>
                 <td></th>
               </tr>
               <?php if(!empty($story->track)):?>
               <?php foreach($story->track as $track):?>
               <tr>
                 <td><?php echo $track->id;?></th>
                 <td><?php echo zget($lang->story->priList, $track->pri);?></th>
                 <td><?php echo $lang->SRCommon;?></th>
                 <td><?php echo html::a(helper::createLink('story', 'view', "id={$track->id}"), $track->title);?></th>
                 <td><?php echo zget($lang->story->statusList, $track->status);?></th>
                 <td><?php echo zget($lang->story->stageList, $track->stage);?></th>
               </tr>
             <?php endforeach;?>
             <?php endif;?>
             <?php endforeach;?>
             </tbody>
           </table>
        </div>
      </div>
    </div>
    <?php endif;?>

    <?php if($demand->SRS):?>
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demand->SRS;?></div>
        <div class="detail-content main-table">
           <table class='table'>
             <thead>
               <tr>
                 <th class='w-60px'><?php echo $lang->story->id;?></th>
                 <th class='w-60px'><?php echo $lang->story->pri;?></th>
                 <th class='w-100px'><?php echo $lang->story->type;?></th>
                 <th><?php echo $lang->story->title;?></th>
                 <th class='w-80px'><?php echo $lang->story->statusAB;?></th>
                 <th class='w-80px'><?php echo $lang->story->stageAB;?></th>
               </tr>
             </thead>
             <tbody>
             <?php foreach($demand->SRS as $story):?>
               <tr>
                 <td><?php echo $story->id;?></th>
                 <td><?php echo zget($lang->story->priList, $story->pri);?></th>
                 <td><?php echo $lang->SRCommon;?></th>
                 <td><?php echo html::a(helper::createLink('story', 'view', "id={$story->id}"), $story->title);?></th>
                 <td><?php echo zget($lang->story->statusList, $story->status);?></th>
                 <td><?php echo zget($lang->story->stageList, $story->stage);?></th>
               </tr>
             <?php endforeach;?>
             </tbody>
           </table>
        </div>
      </div>
    </div>
    <?php endif;?>
    <div class="cell"><?php include $app->getModuleRoot() . 'common/view/action.html.php';?></div>
    <div class='main-actions'>
      <div class="btn-toolbar">
        <?php common::printBack($gobackLink);?>
        <div class='divider'></div>
        <?php
          common::printIcon('demand', 'submit', "demandID=$demand->id", $demand, 'button', 'start', '', 'iframe', true);
          common::printIcon('demand', 'review', "demandID=$demand->id", $demand, 'button', 'glasses', '', 'iframe', true);

          if($demand->status == 'active' and common::hasPriv('demand', 'tostory'))
          {
              if($config->URAndSR)
              {
                  echo html::a('#toStory', '<i class="icon-demand-subdivide icon-split"></i>', '', "title='{$this->lang->demand->tostory}' data-toggle='modal' class='btn' data-id='$demand->id' onclick='getRequirementID(this)'");
              }
              else
              {
                  echo html::a(helper::createLink('demand', 'tostory', "demandID={$demand->id}&type=story"), '<i class="icon-demand-subdivide icon-split"></i>', '', "title='{$this->lang->demand->tostory}' class='btn'");
              }
          }

          common::printIcon('demand', 'edit', "demandID=$demand->id", $demand, 'button');
          common::printIcon('demand', 'close', "demandID=$demand->id", $demand, 'button', 'off', '', 'iframe', true);
          common::printIcon('demand', 'delete', "demandID=$demand->id", $demand, 'button', 'trash', 'hiddenwin');
?>
      </div>
    </div>
  </div>
  <div class="side-col col-4">
    <div class="cell">
      <div class="detail">
        <div class="detail-title"><?php echo $lang->demand->basicInfo;?></div>
        <div class='detail-content'>
          <table class='table table-data'>
            <tbody>
              <tr>
                <th class='w-100px'><?php echo $lang->demand->project;?></th>
                <td><?php echo zget($projects, $demand->project, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->demand->dept;?></th>
                <td><?php
                foreach(explode(',', $demand->dept) as $dept)
                {
                  echo zget($depts, $dept, '') . ' &nbsp;';
                }
                ?></td>
              </tr>
              <?php $this->printExtendFields($demand, 'table', 'inForm=0');?>
              <tr>
                <th class='w-100px'><?php echo $lang->demand->demandSource;?></th>
                <td><?php echo zget($depts, $demandSource, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demand->reviewer;?></th>
                <td><?php echo zget($users, $demand->reviewer, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->demand->stage;?></th>
                <td><?php echo zget($lang->demand->stageList, $demand->stage, '');?></td>
              </tr>
              <tr>
                <th class='w-100px'><?php echo $lang->demand->status;?></th>
                <td><?php echo zget($lang->demand->statusList, $demand->status, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demand->reviewer;?></th>
                <td><?php echo zget($users, $demand->reviewer, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demand->business;?></th>
                <td><?php echo $demand->business ? html::a(helper::createLink('business', 'view', 'dataID=' . $demand->business->id), $demand->business->name) : '';?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demand->mailto;?></th>
                <td><?php foreach(explode(',', $demand->mailto) as $user) echo zget($users, $user, '') . ' '; ?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demand->createdBy;?></th>
                <td><?php echo zget($users, $demand->createdBy, '');?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demand->deadline;?></th>
                <td><?php echo formatTime($demand->deadline);?></td>
              </tr>
              <tr>
                <th><?php echo $lang->demand->createdDate;?></th>
                <td><?php echo formatTime($demand->createdDate);?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="toStory">
  <div class="modal-dialog mw-500px">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <h4 class="modal-title"><?php echo $lang->demand->chooseType;?></h4>
      </div>
      <div class="modal-body">
        <table class='table table-form'>
          <tr>
            <th><?php echo $lang->demand->storyType;?></th>
            <td><?php echo html::radio('totype', $lang->demand->storyTypeList, 'story');?></td>
            <td class='text-center'>
              <?php echo html::commonButton($lang->demand->next, "id='toStoryBtn'", 'btn btn-primary');?>
              <?php echo html::hidden('demand', '');?>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
$('#toStoryBtn').on('click', function()
{
    var demandID = $('#demand').val();
    var type = $("input[name*='totype']:checked").val();
    var link = createLink('demand', 'tostory', 'demandID=' + demandID + '&type=' + type);
    location.href = link;
})

function getRequirementID(obj)
{
    var demandID = $(obj).attr("data-id");
    $('#demand').val(demandID);
}
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
