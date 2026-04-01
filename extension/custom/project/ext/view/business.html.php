<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<style>
.table tbody>tr>td
{
  overflow: hidden;
  white-space: nowrap;
}
</style>
<div id="mainContent" class="main-row fade">
  <div class="main-col">
    <?php if(empty($dataList)):?>
    <div class="table-empty-tip">
      <p><span class="text-muted"><?php echo $lang->noData;?></span></p>
    </div>
    <?php else:?>
    <form class='main-table' method='post' id='businessForm'>
      <div class="table-responsive">
        <table class='table has-sort-head table-fixed' id="<?php echo $flow->module;?>Table">
          <thead>
            <tr class='text-center'>
              <?php $vars = "projectID=$projectID&orderBy=%s&recTotal=$pager->recTotal&recPerPage=$pager->recPerPage&pageID=$pager->pageID"; ?>
              <?php $index = 1;?>
              <?php foreach($fields as $field):?>
              <?php if(!$field->show) continue;?>
              <?php $width = ($field->width && $field->width != 'auto' ? $field->width . 'px' : 'auto');?>
              <?php if($field->field == 'estimate') $width = '100px';?>
              <?php if($field->field == 'developmentBudget') $width = '100px';?>
              <th class="text-<?php echo $field->position;?>" style="width:<?php echo $field->field == 'actions' ? '100px' : $width;?>">
                <?php if($index == 1 && $batchActions && $dataList):?>
                <div class='checkbox-primary check-all' title='<?php echo $this->lang->selectAll;?>'><label></label></div>
                <?php endif;?>
                <?php
                if($field->field == 'desc' || $field->field == 'asc' || $field->field == 'actions' || $field->field == 'requirement' || $field->field == 'estimate' || $field->field == 'developmentBudget')
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
          <tbody class="c-actions">
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
                $projectItPM = $this->dao->select('account')->from('zt_flow_projectmembers')->where('parent')->eq($data->project)->andWhere('projectRole')->eq('itPM')->fetchPairs('account');
                $projectProductManager = $this->dao->select('account')->from('zt_flow_projectmembers')->where('parent')->eq($data->project)->andWhere('projectRole')->eq('productManager')->fetchPairs('account');
                if(commonModel::hasPriv('project', 'splitEpic') && ($data->status == 'approvedProject' || $data->status == 'portionPRD')) echo html::a($this->createLink('project', 'selectProduct', "projectID={$projectID}&businessID={$data->id}"), "<i class='icon-story-batchCreate icon-split'></i>", '', "class='btn' data-app='project' data-toggle='modal' title='{$this->lang->project->splitEpic}'");
                $requirements = $this->dao->select('status')->from('zt_story')->where('business')->eq($data->id)->andWhere('deleted')->eq(0)->fetchAll();
                if(count($requirements) != 0 && $data->status == 'portionPRD' && ($this->app->user->admin || (commonModel::hasPriv('business', 'prdsubmit') && ($projectItPM[$this->app->user->account] || $projectProductManager[$this->app->user->account]))))
                {
                  $hasPRDSubmit = true;
                  foreach($requirements as $requirement)
                  {
                    if(!in_array($requirement->status, array('active', 'devInProgress', 'beOnline', 'closed'))) $hasPRDSubmit = false;
                  }

                  if($hasPRDSubmit) echo html::a('javascript:void(0);', "<i class='icon-story-submitReview icon-confirm'></i>", '', "class='btn isConfirm' title='{$this->lang->project->prdsubmit}' data-url=" . $this->createLink('business', 'prdsubmit', "dataID={$data->id}"));
                }
                if($data->status == 'PRDReviewing' && ($this->app->user->admin || (commonModel::hasPriv('business', 'prdcancel') && ($projectItPM[$this->app->user->account] || $projectProductManager[$this->app->user->account])))) echo html::a('javascript:void(0);', "<i class='icon-story-recall icon-undo'></i>", '', "class='btn isConfirm' title='{$this->lang->project->prdcancel}' data-url=" . $this->createLink('business', 'prdcancel', "dataID={$data->id}"));
                ?>
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
<script>
$('.isConfirm').on('click', function(e)
{
  var url   = $(this).attr('data-url');
  var title = $(this).attr('title');
  var confirmed = confirm(`<?php echo $this->lang->project->confirmMessage;?>`);

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
