$(document).ready(function()
{
    setTimeout(function() {
        initDemand();

        $('[name*="costUnit"]').each(function()
        {
            let costUnit  = $(this);
            costUnit.addClass('form-control');
            costUnit.attr('type', 'text');
            costUnit.prop('readonly', true);

            html = $(this).clone();
            html.addClass('form-control').attr('type', 'text').prop('readonly', true);
            $(this).closest('td').html(html);

            let costUnitKey = costUnit.attr('id').replace('childrensub_projectcostcostUnit', '');

            let costDesc = $('#childrensub_projectcostcostDesc' + costUnitKey);

            costDesc.addClass('form-control');
            costDesc.attr('type', 'text');
            costDesc.prop('readonly', true);

            costDescHtml = costDesc.clone();
            costDescHtml.addClass('form-control').attr('type', 'text').prop('readonly', true);
            costDesc.closest('td').html(costDescHtml);
        });
    }, 300);

    $(document).on('change', '[id^="childrensub_projectbusinessbusiness"]', function()
    {
        handleDemandChange(this);
    });

    $(document).on('change', '[id^="childrensub_projectcostcostType"]', function()
    {
        costChange(this);
    });

    $(document).on('change', '[name*=sub_projectcost]', calculateBudget);
});

function initDemand(dom)
{
    let developmentBudgets;
    let outsourcingBudgets;
    let headBusinesses;

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
        headBusinesses =  $('#sub_projectbusiness').find('[id^="childrensub_projectbusinessheadBusiness"]')
        headBusinesses.each(function()
        {
            html = $(this).clone();
            html.addClass('form-control').attr('type', 'text').prop('readonly', true);
            $(this).closest('td').html(html);
        })

        $(dom).closest('tr').find('[id^="childrensub_projectbusinessheadBusiness"]').closest('td').find('.chosen-container').empty().append('<input class="form-control headBusiness" class="headBusiness" value=""  readonly />');
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessheadBusiness"]').closest('td').find('.picker').empty().append('<input class="form-control headBusiness" class="headBusiness" value=""  readonly />');
    }
    else
    {
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessdevelopmentBudget"]').prop('readonly', true);
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessoutsourcingBudget"]').prop('readonly', true);
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

        $(dom).closest('tr').find('[id^="childrensub_projectbusinessdevelopmentBudget"]').val(developmentBudget);
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessoutsourcingBudget"]').val(outsourcingBudget);
        $(dom).closest('tr').find('[id^="childrensub_projectbusinessheadBusiness"]').val(headBusinessUser);
    });
}

function clearInfo(dom)
{
    $(dom).closest('tr').find('[id^="childrensub_projectbusinessdevelopmentBudget"]').val('');
    $(dom).closest('tr').find('[id^="childrensub_projectbusinessoutsourcingBudget"]').val('');
    $(dom).closest('tr').find('[id^="childrensub_projectbusinessheadBusiness"]').val('');
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
    let budget = 0;

    $('[name*="costBudget"]').each(function()
    {
        let costBudgetID  = $(this).attr('id');
        let costBudgetKey = costBudgetID.replace('childrensub_projectcostcostBudget', '');
        let costType      = $('#childrensub_projectcostcostType' + costBudgetKey).val();

        let costUnit  = projectCosts[costType].costUnit;
        let costPrice = projectCosts[costType].costPrice;

        let value = $(this).val().trim();
        if(value && !isNaN(value))
        {
            let cost = parseFloat(value);

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
    if(!isNaN(budget))
    {
        $('#totalCost').val(budget);
    }
}
