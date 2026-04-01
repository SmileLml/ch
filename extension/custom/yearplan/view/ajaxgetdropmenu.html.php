<?php js::set('poolID', $poolID);?>
<?php js::set('module', $module);?>
<?php js::set('method', $method);?>
<style>
#navTabs {position: sticky; top: 0; background: #fff; z-index: 950;}
#navTabs>li {padding: 0px 10px; display: inline-block}
#navTabs>li>span {display: inline-block;}
#navTabs>li>a {margin: 0!important; padding: 8px 0px; display: inline-block}

#tabContent {margin-top: 5px; z-index: 900; max-width: 220px}
.yearplanTree ul {list-style: none; margin: 0}
.yearplanTree .yearplans>ul {padding-left: 7px;}
.yearplanTree .yearplans>ul>li>div {display: flex; flex-flow: row nowrap; justify-content: flex-start; align-items: center;}
.yearplanTree .yearplans>ul>li label {background: rgba(255,255,255,0.5); line-height: unset; color: #838a9d; border: 1px solid #d8d8d8; border-radius: 2px; padding: 1px 4px;}
.yearplanTree li a i.icon {font-size: 15px !important;}
.yearplanTree li a i.icon:before {min-width: 16px !important;}
.yearplanTree li .label {position: unset; margin-bottom: 0;}
.yearplanTree li>a, div.hide-in-search>a {display: block; padding: 2px 10px 2px 5px; overflow: hidden; line-height: 20px; text-overflow: ellipsis; white-space: nowrap; border-radius: 4px;}
.yearplanTree .tree li>.list-toggle {line-height: 24px;}
.yearplanTree .tree li.has-list.open:before {content: unset;}

#swapper li>div.hide-in-search>a:focus, #swapper li>div.hide-in-search>a:hover {color: #838a9d; cursor: default;}
#swapper li > a {margin-top: 4px; margin-bottom: 4px;}
#swapper li {padding-top: 0; padding-bottom: 0;}
#swapper .tree li>.list-toggle {top: -1px;}

#closed {width: 90px; height: 25px; line-height: 25px; background-color: #ddd; color: #3c495c; text-align: center; margin-left: 15px; border-radius: 2px;}
#gray-line {width:230px; height: 1px; margin-left: 10px; margin-bottom:2px; background-color: #ddd;}
</style>
<?php
$yearplanCounts = array();
$yearplanNames  = array();
$link         = $this->createLink('yearplandemand', 'browse', "poolID=%s");
$tabActive    = '';
$myYearplans    = 0;
$others       = 0;
$dones        = 0;

$yearplanCounts['myYearplan'] = 0;
$yearplanCounts['others']    = 0;
$yearplanCounts['closed']    = 0;
foreach($yearplans as $index => $yearplan)
{
    if($yearplan->status != 'closed' and $yearplan->owner   == $this->app->user->account)  $yearplanCounts['myYearplan'] ++;
    if($yearplan->status != 'closed' and !($yearplan->owner == $this->app->user->account)) $yearplanCounts['others'] ++;
    if($yearplan->status == 'closed') $yearplanCounts['closed'] ++;
    $yearplanNames[] = $yearplan->name;
}

$yearplansPinYin = common::convert2Pinyin($yearplanNames);

$myYearplansHtml     = '<ul class="tree tree-angles" data-ride="tree">';
$normalYearplansHtml = '<ul class="tree tree-angles" data-ride="tree">';
$closedYearplansHtml = '<ul class="tree tree-angles" data-ride="tree">';

foreach($yearplans as $index => $yearplan)
{
    $selected    = $yearplan->id == $poolID ? 'selected' : '';
    $yearplanName = $yearplan->name;

    if($yearplan->status != 'closed' and $yearplan->owner == $this->app->user->account)
    {
        $myYearplansHtml .= '<li>' . html::a(sprintf($link, $yearplan->id), $yearplanName, '', "class='$selected clickable' title='{$yearplan->name}' data-key='" . zget($yearplansPinYin, $yearplan->name, '') . "'") . '</li>';

        if($selected == 'selected') $tabActive = 'myYearplan';

        $myYearplans ++;
    }
    else if($yearplan->status != 'closed' and !($yearplan->owner == $this->app->user->account))
    {
        $normalYearplansHtml .= '<li>' . html::a(sprintf($link, $yearplan->id), $yearplanName, '', "class='$selected clickable' title='{$yearplan->name}' data-key='" . zget($yearplansPinYin, $yearplan->name, '') . "'") . '</li>';

        if($selected == 'selected') $tabActive = 'other';

        $others ++;
    }
    else if($yearplan->status == 'closed')
    {
        $closedYearplansHtml .= '<li>' . html::a(sprintf($link, $yearplan->id), $yearplanName, '', "class='$selected clickable' title='$yearplan->name' data-key='" . zget($yearplansPinYin, $yearplan->name, '') . "'") . '</li>';

        if($selected == 'selected') $tabActive = 'closed';
    }
}
$myYearplansHtml     .= '</ul>';
$normalYearplansHtml .= '</ul>';
$closedYearplansHtml .= '</ul>';
?>

<div class="table-row">
  <div class="table-col col-left">
    <div class='list-group'>
      <?php $tabActive = ($myYearplans and ($tabActive == 'closed' or $tabActive == 'myYearplan')) ? 'myYearplan' : 'other';?>
      <?php if($myYearplans): ?>
      <ul class="nav nav-tabs  nav-tabs-primary" id="navTabs">
        <li class="<?php if($tabActive == 'myYearplan') echo 'active';?>"><?php echo html::a('#myYearplan', $lang->yearplan->myDemand, '', "data-toggle='tab' class='not-list-item not-clear-menu'");?><span class="label label-light label-badge"><?php echo $myYearplans;?></span><li>
        <li class="<?php if($tabActive == 'other') echo 'active';?>"><?php echo html::a('#other', $lang->yearplan->other, '', "data-toggle='tab' class='not-list-item not-clear-menu'")?><span class="label label-light label-badge"><?php echo $others;?></span><li>
      </ul>
      <?php endif;?>
      <div class="tab-content yearplanTree" id="tabContent">
        <div class="tab-pane yearplans <?php if($tabActive == 'myYearplan') echo 'active';?>" id="myYearplan">
          <?php echo $myYearplansHtml;?>
        </div>
        <div class="tab-pane yearplans <?php if($tabActive == 'other') echo 'active';?>" id="other">
          <?php echo $normalYearplansHtml;?>
        </div>
      </div>
    </div>
    <div class="col-footer">
      <?php //echo html::a(helper::createLink('yearplan', 'browse', 'programID=0&browseType=all'), '<i class="icon icon-cards-view muted"></i> ' . $lang->yearplan->all, '', 'class="not-list-item"'); ?>
      <a class='pull-right toggle-right-col not-list-item'><?php echo $lang->yearplan->doneDemands?><i class='icon icon-angle-right'></i></a>
    </div>
  </div>
  <div id="gray-line" hidden></div>
  <div id="closed" hidden><?php echo $lang->yearplan->closedYearplan?></div>
  <div class="table-col col-right yearplanTree">
   <div class='list-group yearplans'><?php echo $closedYearplansHtml;?></div>
  </div>
</div>
<script>scrollToSelected();</script>
<script>
$(function()
{
    $('.nav-tabs li span').hide();
    $('.nav-tabs li.active').find('span').show();

    $('.nav-tabs>li a').click(function()
    {
        if($('#swapper input[type="search"]').val() == '')
        {
            $(this).siblings().show();
            $(this).parent().siblings('li').find('span').hide();
        }
    })

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
        if($('.list-group.yearplans').height() == 0)
        {
            $('#closed').attr("hidden", true);
            $('#gray-line').attr("hidden", true);
        }
    });
})
</script>
