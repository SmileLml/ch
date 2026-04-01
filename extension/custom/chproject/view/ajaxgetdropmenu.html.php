<style>
#navTabs {position: sticky; top: 0; background: #fff; z-index: 950;}
#navTabs > li {padding: 0px 10px; display: inline-block}
#navTabs > li > span {display: inline-block;}
#navTabs > li > a {margin: 0!important; padding: 8px 0px; display: inline-block}

#tabContent {margin-top: 5px; z-index: 900; max-width: 220px}
.projectTree ul {list-style: none; margin: 0}
.projectTree .projects > ul {padding-left: 7px;}
.projectTree .projects > ul > li > div {display: flex; flex-flow: row nowrap; justify-content: flex-start; align-items: center;}
.projectTree .projects > ul > li label {background: rgba(255,255,255,0.5); line-height: unset; color: #838a9d; border: 1px solid #d8d8d8; border-radius: 2px; padding: 1px 4px;}
.projectTree li a i.icon {font-size: 15px !important;}
.projectTree li a i.icon:before {min-width: 16px !important;}
.projectTree li .label {position: unset; margin-bottom: 0;}
.projectTree li > a, div.hide-in-search>a {display: block; padding: 2px 10px 2px 5px; overflow: hidden; line-height: 20px; text-overflow: ellipsis; white-space: nowrap; border-radius: 4px;}
.projectTree .tree li > .list-toggle {line-height: 24px;}
.projectTree .tree li.has-list.open:before {content: unset;}
.tree.noProgram li {padding-left: 0;}

#swapper li > div.hide-in-search>a:focus, #swapper li > div.hide-in-search>a:hover {color: #838a9d; cursor: default;}
#swapper li > a {margin-top: 4px; margin-bottom: 4px;}
#swapper li {padding-top: 0; padding-bottom: 0;}
#swapper .tree li > .list-toggle {top: -1px;}

#dropMenu div#closed {width: 90px; height: 25px; line-height: 25px; background-color: #ddd; color: #3c495c; text-align: center; margin-left: 15px; border-radius: 2px;}
#gray-line {width:230px; height: 1px; margin-left: 10px; margin-bottom:2px; background-color: #ddd;}
</style>
<?php
$projectCounts = array('unclosed' => 0, 'other' => 0);
$projectNames  = array_column($projects, 'name');
$tabActive     = '';

foreach($projects as $project)
{
    $isClosed = $project->status == 'closed';
    if(!$isClosed) $projectOptions['unclosed'][$project->id] = $project;
    if($isClosed)  $projectOptions['other'][$project->id]    = $project;
}

$treeHtml = '<ul class="tree tree-angles" data-ride="tree">%s</ul>';

$projectHtml = [];
$projectHtml['unclosed'] = '';
$projectHtml['other']  = '';

foreach($projectOptions as $type => $options)
{
    foreach($options as $project)
    {
        $projectCounts[$type]++;
        $selected = $project->id == $projectID ? 'selected' : '';
        $icon     = '<i class="icon icon-sprint"></i> ';

        $projectName = $icon . $project->name;

        $li = '<li>' . html::a(sprintf($link, $project->id), $projectName, '', "class='$selected clickable' title='{$project->name}' data-key='" . zget($projectsPinYin, $project->name, '') . "'") . '</li>';
        $projectHtml[$type] .= $li;
    }
}
?>

<div class="table-row">
  <div class="table-col col-left">
    <div class='list-group'>
      <?php $tabActive = isset($projectOptions['unclosed'][$projectID]) ? 'unclosed' : 'other';?>
      <?php if($projectHtml['unclosed'] or $projectHtml['other']):?>
      <ul class="nav nav-tabs  nav-tabs-primary" id="navTabs">
        <li class="<?php if($tabActive == 'unclosed') echo 'active';?>">
          <?php $count = isset($projectOptions['unclosed']) ? count($projectOptions['unclosed']) : 0;?>
          <?php echo html::a('#unclosed', $lang->chproject->unclosed . " <span class='label label-light label-badge'>{$count}</span>", '', "data-toggle='tab' class='not-list-item not-clear-menu'")?>
        </li>
        <li class="<?php if($tabActive == 'other') echo 'active';?>">
          <?php $count = isset($projectOptions['other']) ? count($projectOptions['other']) : 0;?>
          <?php echo html::a('#other', $lang->chproject->other . " <span class='label label-light label-badge'>{$count}</span>", '', "data-toggle='tab' class='not-list-item not-clear-menu'")?>
        </li>
      </ul>
      <?php endif;?>
      <div class="tab-content projectTree" id="tabContent">
        <div class="tab-pane projects <?php if($tabActive == 'unclosed') echo 'active';?>" id="unclosed">
          <?php printf($treeHtml, $projectHtml['unclosed']);?>
        </div>
        <div class="tab-pane projects <?php if($tabActive == 'other') echo 'active';?>" id="other">
          <?php printf($treeHtml, $projectHtml['other']);?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
scrollToSelected();
$('#swapper [data-ride="tree"]').tree('expand');

$('#swapper #dropMenu .search-box').on('onSearchChange', function(event, value)
{
    if(value != '')
    {
        $('div.hide-in-search').siblings('i').addClass('hide-in-search');
        $('.nav-tabs li span').hide();
    }
    else
    {
        $('div.hide-in-search').siblings('i').removeClass('hide-in-search');
        $('li.has-list div.hide-in-search').removeClass('hidden');
        $('.nav-tabs li.active').find('span').show();
    }
    if($('.form-control.search-input').val().length > 0)
    {
        $('#closed').attr("hidden", false);
        $('#gray-line').attr("hidden", false);
    }
    else
    {
        $('#closed').attr("hidden", true);
        $('#gray-line').attr("hidden", true);
    }
});

$('#swapper #dropMenu').on('onSearchComplete', function(event, value)
{
    if($('.list-group.projects').height() == 0)
    {
        $('#closed').attr("hidden", true);
        $('#gray-line').attr("hidden", true);
    }
});
</script>
