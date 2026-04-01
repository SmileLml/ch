<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php js::set('projectapproval', $projectapproval);?>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-right">
    <div class='btn-group dropdown'>
    <?php
    if($this->app->user->admin || $projectapproval->businessPM == $this->app->user->account) common::printLink('projectapproval', 'linkBusiness', "projectapprovalID=$projectapprovalID", "<i class='icon icon-plus'></i> {$lang->linkBusiness}", '', "class='btn btn-primary' id='linkBusiness'", '', true);
    ?>
    </div>
  </div>
</div>
<div id="mainContent" class="main-row fade">
  <div class="main-col">
    <?php if(empty($dataList)):?>
    <div class="table-empty-tip">
      <p><span class="text-muted"><?php echo $lang->noData;?></span></p>
    </div>
    <?php else:?>
    <form class='main-table' method='post' id='businessForm'>
      <div class="table-responsive">
        <table class='table has-sort-head' id="<?php echo $flow->module;?>Table">
          <thead>
            <tr class='text-center'>
              <?php $vars = "projectID=$projectID&orderBy=%s&recTotal=$pager->recTotal&recPerPage=$pager->recPerPage&pageID=$pager->pageID"; ?>
              <?php $index = 1;?>
              <?php foreach($fields as $field):?>
              <?php if(!$field->show) continue;?>
              <?php $width = ($field->width && $field->width != 'auto' ? $field->width . 'px' : 'auto');?>
              <th class="text-<?php echo $field->position;?>" style="width:<?php echo $width;?>">
                <?php if($index == 1 && $batchActions && $dataList):?>
                <div class='checkbox-primary check-all' title='<?php echo $this->lang->selectAll;?>'><label></label></div>
                <?php endif;?>
                <?php
                if($field->field == 'desc' || $field->field == 'asc' || $field->field == 'actions' || $field->field == 'estimate')
                {
                    echo $field->name;
                }
                else
                {
                    commonModel::printOrderLink($field->field, $orderBy, $vars, $field->name, 'project', 'business');
                }
                ?>
              </th>
              <?php $index++;?>
              <?php endforeach;?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($dataList as $data):?>
            <tr>
              <?php $index = 1;?>
              <?php foreach($fields as $field):?>
              <?php if(!$field->show || $field->field == 'actions') continue;?>
              <?php
              $output = '';
              if(is_array($data->{$field->field}))
              {
                  foreach($data->{$field->field} as $value) $output .= zget($field->options, $value) . ' ';
              }
              else
              {
                  if($field->field == 'name')
                  {
                      if(commonModel::hasPriv($flow->module, 'view'))
                      {
                          $output = baseHTML::a(helper::createLink($flow->module, 'view', "dataID={$data->id}"), $data->name);
                      }
                      else
                      {
                          $output = $data->name;
                      }
                  }
                  else
                  {
                      $output = zget($field->options, $data->{$field->field});
                  }

                  if($field->control == 'datetime') $output = formatTime($output, 'Y-m-d H:i');
              }
              ?>
              <td class="text-<?php echo $field->position;?>" title='<?php echo strip_tags(str_replace("</p>", "\n", str_replace(array("\n", "\r"), "", $output)));?>'>
                <?php if($index == 1 && $batchActions):?>
                <div class='checkbox-primary'><input type='checkbox' name='dataIDList[]' value='<?php echo $data->id;?>' id='dataIDList<?php echo $data->id;?>'>
                  <label for='dataIDList<?php echo $data->id;?>'></label>
                </div>
                <?php endif;?>
                <?php echo $output;?>
              </td>
              <?php $index++;?>
              <?php endforeach;?>
              <td class="actions nowrap">
                <?php
                if($data->status == 'beOnline' || $data->status == 'PRDReviewing')
                {
                  $tempProjectapproval = $this->dao->select('*')->from('zt_flow_projectapproval')->where('id')->eq($data->project)->fetch();
                  $tempProjectDept     = $this->loadModel('dept')->getAllChildId($tempProjectapproval->responsibleDept);
                  $infoLeqader         = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoLeqader);
                  $infoAttache         = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoAttache);

                  $isProjectLeader     = false;
                  $projectDepts        = $this->dao->select('*')->from('zt_dept')->where('id')->in($tempProjectDept)->fetchAll();
                  foreach($projectDepts as $dept) if(strpos($dept->leaders, $this->app->user->account) !== false) $isProjectLeader = true;

                  if($data->status == 'PRDReviewing' && ($this->app->user->admin || ((in_array($this->app->user->dept, $tempProjectDept) || $isProjectLeader) && (isset($infoLeqader[$this->app->user->account]) || isset($infoAttache[$this->app->user->account])) && commonModel::hasPriv('business', 'prdreview')))) echo html::a('javascript:void(0);', $this->lang->projectapproval->prdsubmit, '', "class='isConfirm' title='{$this->lang->projectapproval->prdsubmit}' data-url=" . $this->createLink('business', 'prdreview', "dataID={$data->id}"));
                  if($data->status == 'beOnline')
                  {
                    $isBusinessLeader = false;
                    $tempBusinessDept = $this->loadModel('dept')->getAllChildId($data->createdDept);
                    $businessDepts    = $this->dao->select('*')->from('zt_dept')->where('id')->in($tempBusinessDept)->fetchAll();
                    foreach($businessDepts as $dept) if(strpos($dept->leaders, $this->app->user->account) !== false) $isBusinessLeader = true;

                    $projectBusinessPM = $this->dao->select('account')->from('zt_flow_projectmembers')->where('parent')->eq($data->project)->andWhere('projectRole')->eq('businessPM')->fetchPairs('account');
                    if($this->app->user->admin || (commonModel::hasPriv('business', 'close') && ($isProjectLeader || ($isBusinessLeader && (isset($infoLeqader[$this->app->user->account]) || isset($infoAttache[$this->app->user->account]))) || (isset($projectBusinessPM[$this->app->user->account]) || $data->businessPM == $this->app->user->account))))
                    {
                      $link = helper::createLink('business', 'close', "dataID={$data->id}");
                      echo html::a('javascript:void(0);', $this->lang->projectapproval->close, '', 'data-url=' . $link) . '&nbsp;&nbsp;';
                    }
                  }
                }
                ;?>
              </td>
            </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <?php $vars = "projectID=$projectID&orderBy=%s&recTotal=$pager->recTotal&recPerPage=$pager->recPerPage&pageID=$pager->pageID"; ?>
      <div class="table-footer">
        <?php $pager->show('right', 'pagerjs');?>
      </div>
    </form>
    <?php endif;?>
  </div>
</div>
<?php js::set('requestType', $config->requestType);?>
<script>
var batchAddModalTrigger = new $.zui.ModalTrigger(
{
    width: '95%',
    type: 'iframe',
    waittime: 8000,
});
$('#linkBusiness').click(function(e)
{
    batchAddModalTrigger.show({url: $(this).attr('href'), showHeader:false});
    return false;
})
$('.actions a:contains("<?php echo $this->lang->projectapproval->close;?>")').on('click', function(e)
{
  var url = $(this).attr('data-url');
  var confirmed = confirm('<?php echo $this->lang->business->confirmClose;?>');

  if (confirmed)
  {
    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
          location.reload();
        }
    });
  }
})
// $('.action a:contains("<?php echo $this->lang->cancel;?>")').on('click', function(e)
// {
//     var url = $(this).attr('data-url');
//     var confirmed = confirm('<?php echo $this->lang->business->confirmCancel;?>');

//     if (confirmed)
//     {
//         window.location.href = url;
//     }
// })
$('.isConfirm').on('click', function(e)
{
  var url   = $(this).attr('data-url');
  var title = $(this).attr('title');
  var confirmed = confirm(`<?php echo $this->lang->projectapproval->confirmMessage;?>`);

  if (confirmed)
  {
    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
          location.reload();
        }
    });
  }
})
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
