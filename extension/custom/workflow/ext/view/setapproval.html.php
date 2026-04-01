<?php include $app->getExtensionRoot() . 'max/workflow/view/header.html.php';?>
<?php include $app->getExtensionRoot() . 'max/common/view/picker.html.php';?>
<?php include $app->getExtensionRoot() . 'max/workflow/view/coverconfirm.html.php';?>
<?php js::set('cover', $lang->workflow->cover);?>
<?php js::set('module', $flow->module);?>
<?php js::set('approvalCount', count($approvalFlows));?>
<style>
.input-row {display:flex; gap:5px; align-items:center;}
.input-row > div {flex: 1;}
.input-row > .operator {flex: 0.5;}
.input-row > .logicOperater {flex: 0.5;}
.input-row + .input-row{margin-top:5px;}
</style>
<div class='space space-sm'></div>
<div class='main-row'>
  <div class='side-col'>
    <?php include $app->getExtensionRoot() . 'max/workflow/view/side.html.php';?>
  </div>
  <div class='main-col'>
    <div class='panel'>
      <div class='panel-heading'>
        <strong><?php echo $lang->workflow->setApproval;?></strong>
      </div>
      <div class='panel-body'>
        <form id='setForm' method='post' action='<?php echo inlink('setApproval', "module=$flow->module");?>'>
          <div>
            <div>
              <table class='table table-form' id='relationTable' style="width:<?php echo $lang->workflowrelation->tableWidth;?>px">
                <tbody>
                  <tr>
                    <th class='w-60px'><?php echo $this->lang->workflow->status;?></th>
                    <td class='w-300px'><?php echo html::radio('approval', $lang->workflowapproval->approvalList, $flow->approval);?></td>
                    <td></td>
                  </tr>
                  <?php if(!empty($approvalFlows)):?>
                  <tr class='approval-select hide'>
                    <th><?php echo $this->lang->workflowapproval->approvalFlow;?></th>
                    <td colspan='2'>
                      <table class='table table-bordered' id='approvalTable'>
                        <tr>
                          <th class='text-center'><?php echo $lang->workflowcondition->condition;?></th>
                          <th class='text-center' colspan='2'><?php echo $lang->workflowapproval->approvalFlow;?></th>
                        </tr>

                        <?php if(!empty($approvalList)):?>
                        <?php $fieldKey = 1;?>
                        <?php foreach($approvalList as $approval):?>
                        <?php $conditions = json_decode($approval->condition); ?>

                        <tr data-key='<?php echo $approval->id;?>'>
                          <td>
                            <?php if(!empty($conditions)):?>
                            <?php foreach($conditions->fields as $key => $condition):?>

                            <div class='input-row' data-key='<?php echo $fieldKey;?>'>
                              <?php if($condition->logicalOperator):?>
                              <div><?php echo html::select("logicalOperator[{$approval->id}][$fieldKey]", $lang->workflowcondition->logicalOperatorList, $condition->logicalOperator, "class='form-control'");?></div>
                              <?php endif;?>
                              <div><?php echo html::select("field[{$approval->id}][$fieldKey]", $fields, $condition->field, "class='form-control chosen'");?></div>
                              <div class='operator'><?php echo html::select("operator[{$approval->id}][$fieldKey]", $lang->workflowcondition->operatorList, $condition->operator, "class='form-control'");?></div>
                              <div id='paramTD'><?php echo html::input("param[{$approval->id}][$fieldKey]", $condition->param, "id='param1' class='form-control' autocomplete='off'");?></div>
                              <?php echo html::a('javascript:void(0);', "<i class='icon-plus icon-large'></i>", '', "class='addCondition'");?>
                              <?php if($condition->logicalOperator):?>
                              <a href="javascript:;" class="delCondition"><i class="icon-close"></i></a>
                              <?php endif;?>
                            </div>

                            <?php $fieldKey++;?>
                            <?php endforeach;?>
                            <?php else:?>

                            <div class='input-row' data-key='1'>
                              <div><?php echo html::select("field[{$approval->id}][$fieldKey]", $fields, '', "class='form-control chosen'");?></div>
                              <div class='operator'><?php echo html::select("operator[{{$approval->id}}][$fieldKey]", $lang->workflowcondition->operatorList, '', "class='form-control'");?></div>
                              <div id='paramTD'><?php echo html::input("param[{$approval->id}][$fieldKey]", '', "id='param1' class='form-control' autocomplete='off'");?></div>
                              <?php echo html::a('javascript:void(0);', "<i class='icon-plus icon-large'></i>", '', "class='addCondition'");?>
                            </div>

                            <?php $fieldKey++;?>
                            <?php endif;?>
                          </td>
                          <td><?php echo html::select("approvalFlow[{$approval->id}]", array('') + $approvalFlows, $approval->flow, "class='form-control chosen'");?></td>
                          <td><?php echo html::a('javascript:void(0);', "<i class='icon-plus icon-large'></i>",  '', "class='addApproval'");?></td>
                        </tr>

                        <?php $approvalKey = $approval->id;?>
                        <?php endforeach;?>
                        <?php else:?>

                        <tr data-key='1'>
                          <td>
                            <div class='input-row' data-key='1'>
                              <div><?php echo html::select("field[1][1]", $fields, '', "class='form-control chosen'");?></div>
                              <div class='operator'><?php echo html::select("operator[1][1]", $lang->workflowcondition->operatorList, '', "class='form-control'");?></div>
                              <div id='paramTD'><?php echo html::input("param[1][1]", '', "id='param1' class='form-control' autocomplete='off'");?></div>
                              <?php echo html::a('javascript:void(0);', "<i class='icon-plus icon-large'></i>", '', "class='addCondition'");?>
                            </div>
                          </td>
                          <td><?php echo html::select('approvalFlow[1]', array('') + $approvalFlows, $approvalFlow, "class='form-control chosen'");?></td>
                          <td><?php echo html::a('javascript:void(0);', "<i class='icon-plus icon-large'></i>",  '', "class='addApproval'");?></td>
                        </tr>

                        <?php endif;?>
                      </table>
                    </td>
                  </tr>
                  <?php endif;?>

                  <tr class='submit-box'>
                    <th></th>
                    <td class='form-actions'><?php echo html::submitButton();?></td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <?php if(empty($approvalFlows)):?>
          <div class='alert alert-warning approval-select hide'>
          <?php
            echo $this->lang->workflowapproval->noApproval;
            if(commonModel::hasPriv('approvalflow', 'browse'))
            {
                echo $this->lang->workflowapproval->createTips[0];
                echo baseHTML::a(helper::createLink('approvalflow', 'browse', 'type=workflow'), $this->lang->workflowapproval->createApproval, "target='_blank' class='btn btn-default margin-left'");
            }
            else
            {
                echo $this->lang->workflowapproval->createTips[1];
            }
          ?>
          </div>
          <?php endif;?>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
$field         = html::select("field[APPROVALKEY][KEY]", $fields, '', "class='form-control chosen'");
$operator      = html::select("operator[APPROVALKEY][KEY]", $lang->workflowcondition->operatorList, '', "class='form-control'");
$logicOperater = html::select('logicalOperator[APPROVALKEY][KEY]', $lang->workflowcondition->logicalOperatorList, '', "class='form-control'");
$itemRow = <<<EOT
  <div class='input-row' data-key='KEY'>
    <div class='logicOperater'>{$logicOperater}</div>
    <div>{$field}</div>
    <div class='operator'>{$operator}</div>
    <div id='paramTD'><input type="text" value= "" name="param[APPROVALKEY][KEY]" id="paramKEY" class="form-control" autocomplete="off"></div>
    <a href="javascript:;" class="addCondition"><i class="icon-plus icon-large"></i></a>
    <a href="javascript:;" class="delCondition"><i class="icon-close icon-large"></i></a>
  </div>
EOT;
js::set('conditionKey', isset($fieldKey) ? $fieldKey : 2);
js::set('approvalKey', isset($approvalKey) ? $approvalKey + 1 : 1);
js::set('itemRow', $itemRow);
?>
<script>
var dateOptions =
{
    language:  config.clientLang,
    weekStart: 1,
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    startView: 2,
    minView: 2,
    forceParse: 0,
    format: 'yyyy-mm-dd'
};
var datetimeOptions =
{
    language:  config.clientLang,
    weekStart: 1,
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    startView: 2,
    forceParse: 0,
    showMeridian: 1,
    format: 'yyyy-mm-dd hh:ii'
};

$(document).on('click', '#approvalTable .addCondition', function()
{
    let newRow;
    newRow = window.itemRow.replace(/APPROVALKEY/g, $(this).closest('tr').data('key'));
    newRow = newRow.replace(/KEY/g, window.conditionKey);

    $(this).closest('.input-row').after(newRow);
    $(this).closest('.input-row').next().find('[name*=field]').chosen();

    window.conditionKey++;
});

$(document).on('click', '#approvalTable .delCondition', function()
{
    $(this).closest('.input-row').remove();
})

$(document).on('change', '#approvalTable [name*=field]', function()
{
    var $div        = $(this).closest('.input-row');
    var approvalKey = $div.closest('tr').data('key');
    var key         = $div.data('key');
    var name        = window.btoa(encodeURI('param[' + approvalKey + '][' + key + ']'));
    var value       = $div.find('#paramTD [name*=param]').val();

    value = window.btoa(encodeURI(value));

    var link        = createLink('workflowfield', 'ajaxGetFieldControl', 'module=' + window.moduleName + '&field=' + $(this).val() + '&value=' + value + '&elementName=' + name);
    $div.find('#paramTD').load(link, function()
    {
        $div.find('select.chosen').chosen();
        $div.find('.form-date').datetimepicker(dateOptions);
        $div.find('.form-datetime').datetimepicker(datetimeOptions);

        initSelect($div.find('#paramTD .picker-select'));
    });
});

$('#approvalTable [name*=field]').change();

$(document).on('click', '#approvalTable .addApproval', function()
{
    var approvalKey = ++window.approvalKey;

    var $tr = $(this).closest('tr');
    var $clone = $tr.clone();
    $clone.find('.chosen-container').remove();
    $clone.find('.picker').remove();
    $clone.find('.chosen').chosen().val('').trigger('chosen:updated');
    $clone.find('.form-date').datetimepicker(dateOptions);
    $clone.find('.form-datetime').datetimepicker(datetimeOptions);
    $clone.find('.input-row').each(function()
    {
        var key = window.conditionKey++;

        $(this).attr('data-key', key);
        $(this).find('[name*=field]').attr('name', 'field[' + approvalKey + '][' + key + ']');
        $(this).find('[name*=operator]').attr('name', 'operator[' + approvalKey + '][' + key + ']');
        $(this).find('[name*=param]').attr('name', 'param[' + approvalKey + '][' + key + ']');
        $(this).find('[name*=logicalOperator]').attr('name', 'logicalOperator[' + approvalKey + '][' + key + ']');
    });

    initSelect($clone.find('.picker-select'));

    $clone.find('[name*=approvalFlow]').attr('name', 'approvalFlow[' + approvalKey + ']');
    $clone.data('key', approvalKey);

    if($clone.find('.delApproval').length == 0) $clone.find('td:last').append('<a href="javascript:;" class="delApproval"><i class="icon-close icon-large"></i></a>');

    $tr.after($clone);
});

$(document).on('click', '#approvalTable .delApproval', function()
{
    $(this).closest('tr').remove();
});
</script>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
