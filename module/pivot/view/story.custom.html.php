<?php
$title = $lang->pivot->customList[$method];
$customDimension = empty($customDimension) ? 'team' : $customDimension;
?>
<style>
#conditions {display: flex;}
#conditions .condition-options {margin-left: 16px;}
.w-256 {width: 256px;}
.w-512 {width: 520px;}
.w-784 {width: 784px;}
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
</style>

<div class='cell'>
  <div class='panel'>
    <div class="panel-heading">
      <div class="panel-title">
        <div id='conditions'>
          <div><?php echo $title;?></div>
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

      <div class="filter-content" style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
        <?php foreach($lang->pivot->story->filters as $key => $label): ?>
        <?php
        $filterConfig = $this->config->pivot->story->filters[$key];
        $controlType = $filterConfig['type'];
        $class    = isset($filterConfig['class']) ? $filterConfig['class'] : 'w-256';
        $multiple = $filterConfig['multiple'] ? 'multiple' : '';
        $required = $filterConfig['required'] ? 'required' : '';
        $default  = $filterConfig['default'];
        $options  = $filterConfig['options'];
        $options  = $this->pivot->getCustomOptions($options);
        if(empty($multiple)) $options = array_filter($options);

        if($key === 'type') $default = array_filter(array_keys($options), function($value) {return $value !== 'manage' && $value !== 'affair';});
        if($key === 'program')
        {
            $default = array();
            $pattern = '/春航(\d{4})年项目集/';
            foreach($options as $optionKey => $value)
            {
                if(preg_match($pattern, $value, $matches)) $default[] = $optionKey;
            }
        }

        $show = $filterConfig['show'];
        if(!in_array($customDimension, $show)) continue;
        ?>
        <div class="input-group <?php echo $class;?>">
          <label class="input-group-addon <?php echo $required;?>"><?php echo $label;?></label>
          <?php
          if($controlType === 'select')
          {
              if(isset($params[$key])) $default = $params[$key];
              echo html::select($key, $options, $default, "class='form-control picker-select' $multiple");
          }
          if($controlType === 'dateRange')
          {
              list($begin, $end) = $default;
              if(isset($params['begin'])) $begin = $params['begin'];
              if(isset($params['end'])) $end = $params['end'];
              $begin = date('Y-m-d', strtotime($begin));
              $end   = date('Y-m-d', strtotime($end));
              echo html::input("{$key}[begin]", $begin, "class='form-control form-date begin'");
              echo "<span class='input-group-addon fix-border borderBox' style='border-radius: 0px;'>{$lang->pivot->colon}</span>";
              echo html::input("{$key}[end]", $end, "class='form-control form-date end'");
          }
          ?>
        </div>
        <?php endforeach; ?>
        <div class="input-group w-100">
        <?php echo html::submitButton($lang->pivot->query, '', 'btn btn-primary btn-block btn-query');?>
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
          <?php foreach($config->pivot->story->cols[$customDimension] as $field): ?>
          <th data-flex="false" rowspan="1" colspan="1" data-width="auto" data-field="<?php echo $field;?>" data-align="center" class="text-center">
          <?php
          $cols          = $lang->pivot->story->cols;
          $dimensionLang = $lang->pivot->story->dimensions[$customDimension];
          echo in_array($field, array('storyEstimate', 'consumed', 'productivity')) ? sprintf($cols[$field], $dimensionLang) : $cols[$field]; ?>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($data as $row): ?>
        <tr class="text-center">
          <?php foreach($config->pivot->story->cols[$customDimension] as $field): ?>
          <td rowspan="1">
          <?php
          $value = '0';
          if(isset($row->$field)) $value = $row->$field;
          if(in_array($field, $this->config->pivot->story->percentCols))  $value = number_format($value * 100, 2, '.', '') . '%';
          elseif(in_array($field, $this->config->pivot->story->rateCols)) $value = number_format($value, 2, '.', '');
          echo $value;
          ?>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <?php
    $pager->show('right', 'pagerjs');
    ?>
  </div>
</div>
<script>
function setDimension(url)
{
  window.location.href = url;
}

$(function()
{
    var url = '<?php echo inlink('preview', "dimension=$dimension&group=custom&module=pivot&method=$method&params={params}&customDimension=$customDimension");?>';
    $('button.btn-query').on('click', function()
    {
        var filters = $('.filter-content .form-control');
        var filterParams = [];
        filters.each(function(index, elem)
        {
            var name = $(elem).attr('name');
            var value = $(elem).val();
            if(name.startsWith('date')) value = btoa(value);
            if(Array.isArray(value)) value = value.filter(function(v) {return !!v;}).join(',');
            filterParams.push(value);
        });

        filterParams = filterParams.join('|');

        url = url.replace('{params}', encodeURI(filterParams));
        setDimension(url);
    });
});
</script>
