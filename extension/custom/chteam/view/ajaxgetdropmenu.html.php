<style>
#navTabs {position: sticky; top: 0; background: #fff; z-index: 950;}
#navTabs > li {padding: 0px 10px; display: inline-block}
#navTabs > li > span {display: inline-block;}
#navTabs > li > a {margin: 0!important; padding: 8px 0px; display: inline-block}

#tabContent {margin-top: 5px; z-index: 900; max-width: 220px}
.teamTree ul {list-style: none; margin: 0}
.teamTree .teams > ul {padding-left: 7px;}
.teamTree .teams > ul > li > div {display: flex; flex-flow: row nowrap; justify-content: flex-start; align-items: center;}
.teamTree .teams > ul > li label {background: rgba(255,255,255,0.5); line-height: unset; color: #838a9d; border: 1px solid #d8d8d8; border-radius: 2px; padding: 1px 4px;}
.teamTree li a i.icon {font-size: 15px !important;}
.teamTree li a i.icon:before {min-width: 16px !important;}
.teamTree li .label {position: unset; margin-bottom: 0;}
.teamTree li > a, div.hide-in-search>a {display: block; padding: 2px 10px 2px 5px; overflow: hidden; line-height: 20px; text-overflow: ellipsis; white-space: nowrap; border-radius: 4px;}
.teamTree .tree li > .list-toggle {line-height: 24px;}
.teamTree .tree li.has-list.open:before {content: unset;}
.tree.noProgram li {padding-left: 0;}

#swapper li > div.hide-in-search>a:focus, #swapper li > div.hide-in-search>a:hover {color: #838a9d; cursor: default;}
#swapper li > a {margin-top: 4px; margin-bottom: 4px;}
#swapper li {padding-top: 0; padding-bottom: 0;}
#swapper .tree li > .list-toggle {top: -1px;}

#dropMenu div#closed {width: 90px; height: 25px; line-height: 25px; background-color: #ddd; color: #3c495c; text-align: center; margin-left: 15px; border-radius: 2px;}
#gray-line {width:230px; height: 1px; margin-left: 10px; margin-bottom:2px; background-color: #ddd;}
</style>
<?php
$teamCounts = array('myTeam' => 0, 'other' => 0);
$teamNames  = array_column($teams, 'name');
$tabActive  = '';

$teamsPinYin = common::convert2Pinyin($teamNames);

foreach($teams as $team)
{
    $isMyTeam = strpos(",{$team->leader},{$team->members},", ",{$this->app->user->account},") !== false;
    if($isMyTeam)  $teamOptions['myTeam'][$team->id] = $team;
    if(!$isMyTeam) $teamOptions['other'][$team->id]  = $team;
}

$treeHtml = '<ul class="tree tree-angles" data-ride="tree">%s</ul>';

$teamHtml = [];
$teamHtml['myTeam'] = '';
$teamHtml['other']  = '';

foreach($teamOptions as $type => $options)
{
    foreach($options as $team)
    {
        $teamCounts[$type]++;
        $selected = $team->id == $teamID ? 'selected' : '';
        $icon     = '<i class="icon icon-group"></i> ';

        $teamName = $icon . $team->name;

        $li = '<li>' . html::a(sprintf($link, $team->id), $teamName, '', "class='$selected clickable' title='{$team->name}' data-key='" . zget($teamsPinYin, $team->name, '') . "'") . '</li>';
        $teamHtml[$type] .= $li;
    }
}
?>

<div class="table-row">
  <div class="table-col col-left">
    <div class='list-group'>
      <?php $tabActive = isset($teamOptions['myTeam'][$teamID]) ? 'myTeam' : 'other';?>
      <?php if($teamHtml['myTeam'] or $teamHtml['other']):?>
      <ul class="nav nav-tabs  nav-tabs-primary" id="navTabs">
        <li class="<?php if($tabActive == 'myTeam') echo 'active';?>">
          <?php $count = isset($teamOptions['myTeam']) ? count($teamOptions['myTeam']) : 0;?>
          <?php echo html::a('#myTeam', $lang->chteam->myTeam . " <span class='label label-light label-badge'>{$count}</span>", '', "data-toggle='tab' class='not-list-item not-clear-menu'")?>
        </li>
        <li class="<?php if($tabActive == 'other') echo 'active';?>">
          <?php $count = isset($teamOptions['other']) ? count($teamOptions['other']) : 0;?>
          <?php echo html::a('#other', $lang->chteam->other . " <span class='label label-light label-badge'>{$count}</span>", '', "data-toggle='tab' class='not-list-item not-clear-menu'")?>
        </li>
      </ul>
      <?php endif;?>
      <div class="tab-content teamTree" id="tabContent">
        <div class="tab-pane teams <?php if($tabActive == 'myTeam') echo 'active';?>" id="myTeam">
          <?php printf($treeHtml, $teamHtml['myTeam']);?>
        </div>
        <div class="tab-pane teams <?php if($tabActive == 'other') echo 'active';?>" id="other">
          <?php printf($treeHtml, $teamHtml['other']);?>
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
    if($('.list-group.teams').height() == 0)
    {
        $('#closed').attr("hidden", true);
        $('#gray-line').attr("hidden", true);
    }
});
</script>
