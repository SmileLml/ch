<script>
$(function()
{
    $('#contactListMenu').attr("onchange", "setMailto('mailto', this.value)");
})

function setMailto(mailto, contactListID)
{
    var oldUsers = $('#' + mailto).val() ? $('#' + mailto).val() : '';

    link = createLink('user', 'ajaxGetContactUsers', 'listID=' + contactListID + '&dropdownName=' + mailto+ '&oldUsers=' + oldUsers);

    $.get(link, function(users)
    {
        $('#' + mailto).data('zui.picker').destroy();
        $('#' + mailto).replaceWith(users);
        $('#' + mailto).picker();
    });
}
</script>
<?php if($this->app->rawModule == 'business'):?>
<script>
var goback     = '<?php echo $lang->goback;?>'
var demandList = '<?php echo $this->session->demandList;?>'
$('#ajaxForm').find('a:contains("' + goback + '")').attr('href', demandList);
</script>
<?php endif;?>

<?php if($this->app->rawModule == 'projectapproval' and $this->app->rawMethod == 'create'):?>
<?php $accountTitle = $childFields['sub_projectmembers']['account']->name;?>
<?php $descOptions  = array('foundingMember' => $this->config->foundingMember, 'businessPM' => $this->config->businessPM, 'itPM' => $this->config->itPM, 'productManager' => $this->config->productManager);?>
<?php $projectRoleList = array_slice($childFields['sub_projectmembers']['projectRole']->options, 0, 5, true);?>
<?php js::set('projectRoleList', json_encode($projectRoleList, JSON_UNESCAPED_UNICODE));?>
<?php js::set('projectDescription', $childFields['sub_projectmembers']['description']->name);?>
<?php js::set('projectAccount', $accountTitle);?>
<?php js::set('projectAccountElement', html::select('projectAccountElement', $childFields['sub_projectmembers']['account']->options, '', "class='form-control picker-select' placeholder={$accountTitle}"));?>
<?php js::set('projectDesc', json_encode($descOptions, JSON_UNESCAPED_UNICODE));?>
<script>
var projectRoleList       = $.parseJSON(projectRoleList);
var projectDescList       = $.parseJSON(projectDesc);
var projectMemberInfoList = '';
var projectMemberIndex    = 1;
$.each(projectRoleList, function(role, title)
{
    if(!role) return true;

    var projectDesc = projectDescList[role];
    if(!projectDesc || projectDesc == 'null') projectDesc = '';

    var addItem = '';
    if(projectMemberIndex == 4)
    {
        addItem = '<td class="w-100px"><input type="hidden" name="children[sub_projectmembers][id][' + projectMemberIndex + ']" id="childrensub_projectmembersid' + projectMemberIndex + '" value=""/><a href="javascript:;" class="btn btn-default addItem"><i class="icon-plus"></i></a></td>';
    }

    var projectAccountSelectElement = projectAccountElement.replace("projectAccountElement", "children[sub_projectmembers][account][" + projectMemberIndex + "]");
    projectMemberInfoList += '<tr>'
                           + '<td style="width: 200px">' + title + '<input type="hidden" name="children[sub_projectmembers][projectRole][' + projectMemberIndex + ']" value="' + role + '"/><input type="hidden" name="children[sub_projectmembers][id][' + projectMemberIndex + ']" value=""/></td>'
                           + '<td style="width: 200px">' + projectAccountSelectElement + '</td>'
                           + '<td style="width: 800px"><input type="text" name="children[sub_projectmembers][description][' + projectMemberIndex + ']" value="' + projectDesc + '" placeholder="' + projectDescription + '" class="form-control" /></td>'
                           + addItem
                           + '</tr>';
    projectMemberIndex++;

    childKey = projectMemberIndex;
});

$('#childrensub_projectmembersprojectRole1').parent().parent().before(projectMemberInfoList);
$('#childrensub_projectmembersprojectRole1').parent().parent().remove();
</script>
<?php endif;?>
