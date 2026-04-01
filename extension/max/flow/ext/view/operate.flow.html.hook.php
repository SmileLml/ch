<script>
function setMailto(mailto, contactListID)
{
    var oldUsers = $('#' + mailto).val() ? $('#' + mailto).val() : '';

    link = createLink('user', 'ajaxGetContactUsers', 'listID=' + contactListID + '&dropdownName=' + mailto + '&oldUsers=' + oldUsers);

    $.get(link, function(users)
    {
        $('#' + mailto).data('zui.picker').destroy();
        $('#' + mailto).replaceWith(users);
        $('#' + mailto).picker();
    });
}
</script>
<?php if($this->app->rawModule == 'projectapproval' and ($this->app->rawMethod == 'edit' or strpos($this->app->rawMethod, 'approvalsubmit') !== false)):?>
<script>
var processedValues = [];
var projectmembersprojectRole = $('#sub_projectmembers').find('[id^="childrensub_projectmembersprojectRole"]');
var defaultProjectRole = ['foundingMember', 'businessPM', 'itPM', 'productManager'];
projectmembersprojectRole.each(function()
{
    var value = $(this).val();
    if ($.inArray(value, defaultProjectRole) === -1) return true;

    if (processedValues.indexOf(value) === -1)
    {
        let id    = $(this).attr('id');
        let name  = $(this).attr('name');
        let title = projectRoleList[value];
        let key   = id.replace('childrensub_projectmembersprojectRole', '');

        var projectMemberInfoList = title + '<input type="hidden" name="' + name + '" value="' + value + '" id="childrensub_projectmembersprojectRole' + key + '"/>';

        $(this).parent().html(projectMemberInfoList);
        $('#childrensub_projectmembersid' + key).parent().find('.delItem').remove();

        processedValues.push(value); // 将当前值添加到已处理列表中
    }
});
</script>
<?php endif;?>
