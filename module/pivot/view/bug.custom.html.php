<style>
#conditions {display: flex;}
#conditions .condition-options {margin-left: 16px;}
.w-256 {width: 256px;}
.w-512 {width: 512px;}
.w-768 {width: 768px;}
.w-100 {width: 100px;}
.required:before {
    position: absolute;
    top: 11px;
    left: 3px;
    display: inline-block;
    font-size: 16px;
    color: #ea644a;
    content: '*';
}
.filter-content {
    margin-top: 8px;
    display: flex;
    gap :8px;
    flex-wrap: wrap;
}
</style>

<div class='cell'>
  <div class='panel'>
    <div class="panel-heading">
      <div class="panel-title">
        <div id='conditions'>
          <div><?php echo $lang->pivot->customList[$method];?></div>
          <div class='condition-options'>
            <span style="font-weight: normal;"><?php echo $lang->pivot->customDimension;?>:</span>
            <?php foreach($lang->pivot->story->dimensions as $key => $label): ?>
            <label class="radio-inline">
              <?php $link = inlink('preview', "dimension=$dimension&group=custom&module=pivot&method=$method&params=&customDimension=$key");?>
              <input type="radio" name="dimension" onclick="setDimension('<?php echo $link;?>')" value="<?php echo $key;?>" <?php echo $key === $customDimension ? 'checked' : ''; ?>/><?php echo $label;?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class='filter-content'>
        <?php foreach($lang->pivot->bugCustom->filters as $key => $label): ?>
        <?php
        $filterConfig = $this->config->pivot->bugCustom->filters[$key];
        $controlType = $filterConfig['type'];
        $class    = isset($filterConfig['class']) ? $filterConfig['class'] : 'w-256';
        $multiple = $filterConfig['multiple'] ? 'multiple' : '';
        $required = $filterConfig['required'] ? 'required' : '';
        $default  = $newParams[$key];
        $options  = $filterConfig['options'];
        $options  = $this->pivot->getCustomOptions($options);
        if(empty($multiple)) $options = array_filter($options);

        if($key === 'type') $default = array_filter(array_keys($options), function($value) {return $value !== 'manage' && $value !== 'affair';});

        $show = $filterConfig['show'];
        if(!in_array($customDimension, $show)) continue;
        ?>
        <div class="input-group <?php echo $class;?>">
          <label class="input-group-addon <?php echo $required;?>"><?php echo $label;?></label>
          <?php
          if($controlType === 'select')
          {
              echo html::select($key, $options, $default, "class='form-control picker-select' $multiple");
          }
          if($controlType === 'dateRange')
          {
              $begin = $default['begin'];
              $end   = $default['end'];

              echo html::input("{$key}[begin]", $begin, "class='form-control form-date begin'");
              echo "<span class='input-group-addon fix-border borderBox' style='border-radius: 0px;'>{$lang->pivot->colon}</span>";
              echo html::input("{$key}[end]", $end, "class='form-control form-date end'");
          }
          ?>
        </div>
        <?php endforeach; ?>
        <div class="input-group w-100">
        <?php echo html::submitButton($lang->pivot->query, 'onclick="setAllFilterAndReload()"', 'btn btn-primary btn-block');?>
        </div>
      </div>

    </div>
  </div>
</div>
<div class="cell">
  <div class="table-empty-tip hidden">
    <p><span class="text-muted"><?php echo $lang->error->noData;?></span></p>
  </div>
  <div class="reportData">
    <table class="table table-condensed table-striped table-bordered table-fixed datatable" style="width: auto; min-width: 100%" data-fixed-left-width="400">
      <thead>
        <tr>
          <?php foreach($lang->pivot->bugCustom->cols[$customDimension] as $field => $label): ?>
          <th data-flex="false" rowspan="1" colspan="1" data-width="auto" data-field="<?php echo $field;?>" data-align="center" class="text-center"><?php echo $label; ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($data as $row): ?>
        <tr class="text-center">
          <?php foreach(array_keys($lang->pivot->bugCustom->cols[$customDimension]) as $field): ?>
          <td rowspan="1"><?php echo isset($row->$field) ? ($field == 'newEffCloseBugRate' ? number_format($row->$field, 2) . '%' : $row->$field) : ($field == 'newEffCloseBugRate' ? '0.00%' : '0'); ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <?php $pager->show('right', 'pagerjs');?>
  </div>
</div>

<script>
function setDimension(url)
{
    window.location.href = url;
}

function setAllFilterAndReload()
{
    const method          = '<?php echo $method;?>';
    const dimension       = '<?php echo $dimension;?>';
    const customDimension = '<?php echo $customDimension;?>';
    let params = '';
    if(customDimension == 'team')
    {
        let team    = $('#team').data('zui.picker').getValue();
        let bugType = $('#bugType').data('zui.picker').getValue();
        let begin   = window.btoa($('#datebegin').val());
        let end     = window.btoa($('#dateend').val());
        if(team)    team = team.filter(item => item).join();
        if(bugType) bugType = bugType.filter(item => item).join();

        let filterParams = [];
        filterParams.push(team);
        filterParams.push(bugType);
        filterParams.push(begin);
        filterParams.push(end);
        params = encodeURI(filterParams.join('|'));
    }
    else if(customDimension == 'sprint')
    {
        let team            = $('#team').data('zui.picker').getValue();
        let executionName   = $('#executionName').data('zui.picker').getValue();
        let executionStatus = $('#executionStatus').data('zui.picker').getValue();
        let bugType         = $('#bugType').data('zui.picker').getValue();
        if(team)            team = team.filter(item => item).join();
        if(executionName)   executionName = executionName.filter(item => item).join();
        if(executionStatus) executionStatus = executionStatus.filter(item => item).join();
        if(bugType)         bugType = bugType.filter(item => item).join();

        let filterParams = [];
        filterParams.push(team);
        filterParams.push(executionName);
        filterParams.push(executionStatus);
        filterParams.push(bugType);
        params = encodeURI(filterParams.join('|'));
    }
    else if(customDimension == 'project')
    {
        let program  = $('#program').data('zui.picker').getValue();
        let project  = $('#project').data('zui.picker').getValue();
        let projectStatus  = $('#projectStatus').data('zui.picker').getValue();
        let proposalType  = $('#proposalType').data('zui.picker').getValue();
        let bugType = $('#bugType').data('zui.picker').getValue();
        let begin   = window.btoa($('#datebegin').val());
        let end     = window.btoa($('#dateend').val());

        if(program)    program = program.filter(item => item).join();
        if(project)    project = project.filter(item => item).join();
        if(projectStatus)    projectStatus = projectStatus.filter(item => item).join();
        if(proposalType)    proposalType = proposalType.filter(item => item).join();
        if(bugType)    bugType = bugType.filter(item => item).join();

        let filterParams = [];
        filterParams.push(program);
        filterParams.push(project);
        filterParams.push(projectStatus);
        filterParams.push(proposalType);
        filterParams.push(bugType);
        filterParams.push(begin);
        filterParams.push(end);
        params = encodeURI(filterParams.join('|'));
    }

    let vars = 'dimension=' + dimension + '&group=custom&module=pivot&method=' + method + '&params=' + params + '&customDimension=' + customDimension;
    let link = $.createLink('pivot', 'preview', vars);
    window.location.href = link;
}

</script>
