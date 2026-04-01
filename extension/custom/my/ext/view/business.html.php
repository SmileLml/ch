<?php
/**
 * The issue view file of my module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     my
 * @version     $Id
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php js::set('mode', $mode);?>
<?php js::set('total', $pager->recTotal);?>
<?php js::set('rawMethod', $app->rawMethod);?>
<style>
.c-id {width: 50px;}
.c-type, .c-owner, .c-severity {width: 80px;}
.c-createdDate{width: 100px;}
.c-assignedTo {padding-left: 29px !important;}
.c-actions-8 {width: 240px !important;}
</style>
<div id="mainMenu" class="clearfix">
  <div class="btn-toolbar pull-left">
    <?php $recTotalLabel = " <span class='label label-light label-badge'>{$pager->recTotal}</span>"; ?>
    <?php
    foreach($lang->my->featureBar[$app->rawMethod]['business'] as $param => $name)
    {
        echo html::a(inlink($app->rawMethod, "mode=$mode&type=$param"),   "<span class='text'>{$name}</span>"   . ($type == $param ? $recTotalLabel : ''), '', "class='btn btn-link" . ($type == $param   ? ' btn-active-text' : '') . "'");
    }
    ?>
  </div>
  <a class="btn btn-link querybox-toggle" id='bysearchTab'><i class="icon icon-search muted"></i> <?php echo $lang->my->byQuery;?></a>
</div>
<div id="mainContent" class="main-table table-bug" data-ride="table">
  <?php $dataModule = 'workBusiness';?>
  <div class="cell<?php if($type == 'bySearch') echo ' show';?>" id="queryBox" data-module=<?php echo $dataModule;?>></div>
  <?php if(empty($businessList)):?>
  <div class="table-empty-tip">
    <p><span class="text-muted"><?php echo $lang->task->noTask;?></span></p>
  </div>
  <?php else:?>
  <table class="table has-sort-head table-fixed" id='businessList'>
  <?php $vars = "mode=$mode&type=$type&orderBy=%s&recTotal=$recTotal&recPerPage=$recPerPage&pageID=$pageID"; ?>
    <thead>
      <tr>
        <?php $index = 1;?>
        <?php foreach($fields as $field):?>
        <?php if(!$field->show) continue;?>
        <?php $width = ($field->width && $field->width != 'auto' ? $field->width . 'px' : 'auto');?>
        <?php if($field->field == 'estimate') $width = '100px';?>
        <?php if($field->field == 'developmentBudget') $width = '100px';?>
        <th class="text-<?php echo $field->position;?>" style="width:<?php echo $field->field == 'actions' ? '100px' : $width;?>">
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
    <?php foreach($businessList as $data):?>
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
            $output = baseHTML::a(helper::createLink($flow->module, 'view', "dataID={$data->id}"), $data->name, 'target="_blank"');
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
      $link = helper::createLink('business', 'close', "dataID={$data->id}");
      echo html::a('javascript:void(0);', $this->lang->projectapproval->close, '', 'data-url=' . $link) . '&nbsp;&nbsp;';
      ?>
      </td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  <?php endif;?>
  <thead>
</div>
<div class="table-footer">
<?php $pager->show('right', 'pagerjs');?>
</div>
<?php js::set('listName', 'businessList')?>
<script>
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
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>