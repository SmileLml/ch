<?php js::set('isFinished', $data->status == 'finished')?>
<?php js::set('methodAction', $action->action);?>
<script>
//从工作流js设置中拿出的内容
$(document).ready(function()
{
    $('tr:has(#future)').hide();
    $('#version').closest('tr').hide();

    $('#budget').closest('tr').append('<td><div class="checkbox-primary"><input type="checkbox" id="pending" name="pending" value="1"><label for="pending1">待定</label></div></td>');

    $('#pending').change(function()
    {
        if($(this).is(':checked'))
        {
            $('#budget').val('').attr('disabled', 'disabled');
        }
        else
        {
            $('#budget').removeAttr('disabled');
        }
    });

    $('#program').change(function()
    {
        var parentProgram = $(this).val();
        if(parentProgram != 0)
        {
            $('#aclprogram').parent().show();
        }
        else
        {
            $('#aclprogram').parent().hide();
        }
    });

    var parentProgram = $(this).val();
    if(parentProgram != 0)
    {
        $('#aclprogram').parent().show();
    }
    else
    {
        $('#aclprogram').parent().hide();
    }

    $('#begin, #end').change(function()
    {
        beginDate = $('#begin').val();
        endDate   = $('#end').val();

        var begin = new Date(beginDate.replace(/-/g,"/"));
        var end   = new Date(endDate.replace(/-/g,"/"));
        var time  = end.getTime() - begin.getTime();
        var days  = parseInt(time / (1000 * 60 * 60 * 24)) + 1;

        $('#days').val(days);
    });

    if($('#future1').is(':checked'))
    {
        $('#pending').prop('checked', true);
        $('#budget').val('').prop('disabled', true);
    }

    if($('#hasProduct2').is(':checked'))
    {
        $('.productsBox').parent().hide();
    }

    $('input[name="hasProduct"]').change(function()
    {
        var selectedValue = $('input[name="hasProduct"]:checked').val();
        if(selectedValue === "1")
        {
            $('.productsBox').parent().show();
        }
        else
        {
            $('.productsBox').parent().hide();
        }
    });
    if($('select[name="process"]').val() == 'Y')
    {
        $('#sub_projectprocess').closest('tr').css('display', 'table-row');
    }
    else
    {
        $('#sub_projectprocess').closest('tr').css('display', 'none');
    }
    $('select[name="process"]').change(function(){
        if($(this).val() == 'Y')
        {
            $('#sub_projectprocess').closest('tr').css('display', 'table-row');
        }
        else
        {
            $('#sub_projectprocess').closest('tr').css('display', 'none');
        }
    })

    initDemand();
    $(document).on('change', '[id^="childrensub_projectbusinessbusiness"]', function()
    {
        handleDemandChange(this);
    });

    $(document).on('change', '[id^="childrensub_projectcostcostType"]', function()
    {
        costChange(this);
    });

    $(document).on('change', '[name*=sub_projectcost]', calculateBudget);

    $(document).on('change', '[id^="childrensub_projectmembersprojectRole"]', function()
    {
        projectRoleChange(this);
    });

    $(document).on('change', '[id^="childrensub_projectmembersaccount"]', function()
    {
        projectMembersChange(this);
    });
    if(!isFinished)
    {
        $('nav>ul>li').eq(2).remove()
    }
    if(methodAction != 'view')
    {
        $('nav>ul>li').eq(1).remove()
    }
});

function initDemand(dom)
{
    if(!dom)
    {
        developmentBudgets = $('#sub_projectbusiness').find('[id^="childrensub_projectbusinessdevelopmentBudget"]')
        developmentBudgets.each(function()
        {
            html = $(this).clone();
            html.addClass('form-control').attr('type', 'text').prop('readonly', true);
            $(this).closest('td').html(html);
        });

        outsourcingBudgets = $('#sub_projectbusiness').find('[id^="childrensub_projectbusinessoutsourcingBudget"]');
        outsourcingBudgets.each(function()
        {
            html = $(this).clone();
            html.addClass('form-control').attr('type', 'text').prop('readonly', true);
            $(this).closest('td').html(html);
        });
        headBusinesses =  $('#sub_projectbusiness').find('[id^="childrensub_projectbusinessheadBusiness"]');
        headBusinesses.each(function()
        {
            html = $(this).clone();
            html.addClass('form-control').attr('type', 'text').prop('readonly', true);
            $(this).closest('td').html(html);
        });
    }
    else
    {
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessdevelopmentBudget"]').prop('readonly', true);;
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessoutsourcingBudget"]').prop('readonly', true);;
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessheadBusiness"]').prop('readonly', true);;
    }
}

function handleDemandChange(dom)
{
    initDemand(dom);

    let demandID = $(dom).val();

    if(!demandID)
    {
        clearInfo(dom);
        return;
    }

    $url = createLink('project', 'ajaxGetBusiness', 'id=' + demandID);
    $.get($url, function(data)
    {
        data = JSON.parse(data)
        if(!data.business) return;

        let business          = data.business;
        let developmentBudget = business.developmentBudget;
        let headBusinessUser  = business.headBusinessUser;
        let outsourcingBudget = business.outsourcingBudget;

        $(dom).closest('tr').nextAll('tr').slice(0, 2).find('[id^="childrensub_projectbusinessdevelopmentBudget"]').val(developmentBudget);
        $(dom).closest('tr').nextAll('tr').slice(0, 2).find('[id^="childrensub_projectbusinessoutsourcingBudget"]').val(outsourcingBudget);
        $(dom).closest('tr').nextAll('tr').slice(0, 2).find('[id^="childrensub_projectbusinessheadBusiness"]').val(headBusinessUser);
        $(dom).closest('tr').nextAll('tr').slice(0, 2).find('[id^="childrenbusinessID"]').val(demandID);

        let createdDeptList  = $('#relevantDept').data('zui.picker').getValue();
        let businessUnitList = $('#businessUnit').data('zui.picker').getValue();

        createdDeptList.push(business.createdDept);
        businessUnitList.push(business.businessUnit);
        $('#relevantDept').data('zui.picker').setValue(createdDeptList);
        $('#businessUnit').data('zui.picker').setValue(businessUnitList);

    });
}

function clearInfo(dom)
{
    //还有别的地方用到这个方法吗
    $(dom).closest('tr').nextAll('tr').slice(0, 2).find('[id^="childrensub_projectbusinessdevelopmentBudget"]').val('');
    $(dom).closest('tr').nextAll('tr').slice(0, 2).find('[id^="childrensub_projectbusinessoutsourcingBudget"]').val('');
    $(dom).closest('tr').nextAll('tr').slice(0, 2).find('[id^="childrensub_projectbusinessheadBusiness"]').val('');
}

function costChange(dom)
{
    let costType = $(dom).val();
    let id       = $(dom).attr('id');
    let key      = id.replace('childrensub_projectcostcostType', '');
    let node     = $(dom);

    if(!costType) return;

    let costUnit  = projectCosts[costType].costUnit;
    let costDesc  = projectCosts[costType].costDesc;
    let costPrice = projectCosts[costType].costPrice;

    let costUnltID = '#childrensub_projectcostcostUnit' + key;
    $(costUnltID).val(costUnit);
    $(costUnltID).addClass('form-control');
    $(costUnltID).attr('type', 'text');
    $(costUnltID).prop('readonly', true);

    let costDescID = '#childrensub_projectcostcostDesc' + key;
    $(costDescID).val(costDesc);
    $(costDescID).addClass('form-control');
    $(costDescID).attr('type', 'text');
    $(costDescID).prop('readonly', true);
}

function calculateBudget()
{
    let budget           = 0;
    let itPlanIntoBudget = 0;

    $('[name*="costBudget"]').each(function()
    {
        let costBudgetID  = $(this).attr('id');
        let costBudgetKey = costBudgetID.replace('childrensub_projectcostcostBudget', '');
        let costType      = $('#childrensub_projectcostcostType' + costBudgetKey).val();

        if(!costType) return;

        if(costType == 'businessInto') return;

        let costUnit  = projectCosts[costType].costUnit;
        let costPrice = projectCosts[costType].costPrice;

        let value = $(this).val().trim();
        if(value && !isNaN(value))
        {
            let cost = parseFloat(value);

            if(methodAction == 'approvalsubmit3' && costType == 'itPlanInto')
            {
                itPlanIntoBudget += cost;
                $.cookie('projectCostBudget', itPlanIntoBudget, {expires: 1, path:config.webRoot});
            }

            if(costUnit == '元')
            {
                budget += cost;
            }
            else
            {
                budget += cost * costPrice;
            }
        }
    });
    if(!isNaN(budget)){
        $('#totalCost').val(budget);
    }
}

function projectRoleChange(dom)
{
    let projectRole = $(dom).val();
    let id          = $(dom).attr('id');
    let key         = id.replace('childrensub_projectmembersprojectRole', '');

    if(!projectRole) return;

    let projectDesc   = projectDescOptions[projectRole];
    let projectDescID = '#childrensub_projectmembersdescription' + key;
    $(projectDescID).val(projectDesc);

    getApprovalReviewer(projectRole);
}

function projectMembersChange(dom)
{
    let id  = $(dom).attr('id');
    let key = id.replace('childrensub_projectmembersaccount', '');
    let projectRole = $('#childrensub_projectmembersprojectRole' + key).val();

    if(!projectRole) return;

    getApprovalReviewer(projectRole);
}

function getApprovalReviewer(projectRole)
{
    $('select[id*=approval_reviewer]').each(function()
    {
        var nextInput = $(this).prevAll('input').first();

        let id = $(nextInput).attr('id');
        if(!id) return;
        let types = id.split(',');
        let type = types[0].replace('projectapproval', '');

        // 要检查的类名数组
        const classNamesToCheck = ['businessManager', 'businessArchitect', 'PMO', 'itPM', 'businessPM', 'foundingMember', 'productManager'];
        var users = '';
        var field = '';
        var _this = this;
        var users = '';

        types.forEach(function(item)
        {
            let type = item.replace('projectapproval', '');
            if(classNamesToCheck.includes(type))
            {
                var myPicker = $(_this).data('zui.picker');
                if(myPicker != null) myPicker.destroy();

                field = _this.id;
                if(type == 'businessManager') users += ',' + $('#businessPM').val();
                if(type != 'businessManager')
                {
                    var selector1 = 'select[name*="children[sub_projectmembers][projectRole]"]';
                    var selector2 = 'input[name*="children[sub_projectmembers][projectRole]"]';

                    var usersFromSelect = processElements(selector1, type);
                    var usersFromInput  = processElements(selector2, type);
                    users += ',' + [usersFromSelect, usersFromInput].filter(Boolean).join(',');
                }
            }
        })

        if(users != '')
        {
            var link = createLink('user', 'ajaxGetProjectMembers', 'accounts=' + users + '&field=' + field + '&type=' + type);
            $.get(link, function(data)
            {
                $('#' + field).replaceWith(data);
                $('#' + field).picker();
            });
        }
    });
}

function processElements(selector, type)
{
    var users    = '';
    var elements = $(selector);

    elements.each(function()
    {
        var element = $(this);

        if(element.val() === type)
        {
            let name    = element.attr('name');
            var matches = name.match(/\d+/); // 提取所有数字

            if(matches)
            {
                var number = matches[0];

                if(number >= 0)
                {
                    // 追加用户值
                    var userValue = $('#childrensub_projectmembersaccount' + number).val();
                    if(userValue)
                    {
                        if(users) users += ','; // 在添加第一个用户之前添加逗号
                        users += userValue;
                    }
                }
            }
        }
    });

    return users;
}

$('#businessPM').change(function()
{
    getApprovalReviewer($(this).val());
});
</script>
