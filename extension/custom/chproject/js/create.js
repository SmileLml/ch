$(function()
{
    $('#begin').on('change', function()
    {
       $("#end").val('');
       $("#days").val('');
       $("input:radio[name='delta']").attr("checked",false);
    });
    $('#end').on('change', function()
    {
        $("input:radio[name='delta']").attr("checked", false);
    })

    if(typeof(currentPlanID) == 'undefined')
    {
        $('#productsBox select[id^="products"]').each(function()
        {
            var branchID = 0;
            if($(this).closest('.input-group').find('select[id^="branch"]').size() > 0)
            {
                var branchID = $(this).closest('.input-group').find('select[id^="branch"]').val();
            }
            loadPlans($(this), branchID);
        });
    }

    /* Assign value to the manage products by the different request type.*/
    var $product = $('#products0');
    if(productID)
    {
        $product.val(productID);
        $product.trigger("chosen:updated");
    }

    var adjustMainCol = function()
    {
        $('.main-form .col-main').css('width', Math.max(250, Math.floor(($('#tplBoxWrapper').parent('td').outerWidth() - 50)/3) + 10));
    };
    adjustMainCol();
    $(window).on('resize', adjustMainCol);

    $('#teams_chosen').click(function()
    {
        $('#teams_chosen ul li').each(function(index)
        {
            if(index == 0)
            {
                var projectName = subString($(this).text(), 56);
                $(this).text(projectName);
                $(this).append(' <label class="label">' + projectCommon + '</label>');
            }
            else
            {
                $(this).prepend('&nbsp;&nbsp;&nbsp;');
            }
        })
    })

    $('#teams').change(function()
    {
        var objectID = $(this).val();
        $.get(createLink('execution', 'ajaxGetTeamMembers', 'objectID=' + objectID), function(data)
        {
            $('#teamMembers').parent().html(data);
            $('#teamMembers').picker({chosenMode: true});
        });
    })

    if(isStage)
    {
        $('#attribute').change(function()
        {
            var attribute = $(this).val();
            hidePlanBox(attribute);
        })

        $('#attribute').change();
    }

    var acl = $("[name^='acl']:checked").val();
    setWhite(acl);

    $('#submit').click(function()
    {
        var products      = new Array();
        var existedBranch = false;

        /* Determine whether the products of the same branch are linked. */
        $("#productsBox select[name^='products']").each(function()
        {
            var productID = $(this).val();
            if(typeof(products[productID]) == 'undefined') products[productID] = new Array();
            if(multiBranchProducts[productID])
            {
                var branchID = $(this).closest('.input-group').find("select[id^=branch]").val();
                if(products[productID][branchID])
                {
                    existedBranch = true;
                }
                else
                {
                    products[productID][branchID] = branchID;
                }
                if(existedBranch) return false;
            }
        });

        if(existedBranch)
        {
            bootbox.alert(errorSameBranches);
            return false;
        }
    });

    /* Init for copy execution. */
    $("select[id^=branch]").each(disableSelectedBranch);
    disableSelectedProduct();

    /* Check the all products and branches control when uncheck the product. */
    $(document).on('change', "select[id^='products']", function()
    {
        if($(this).val() == 0)
        {
            $("select[id^='branch']").each(disableSelectedBranch);

            disableSelectedProduct();
        }
    });

    $(document).on('change', "select[id^='branch']", disableSelectedBranch);

    if($('.disabledBranch').length > 0)
    {
        $('.disabledBranch div[id^="branch"]').addClass('chosen-disabled');
    }

    $('[data-toggle="popover"]').popover();
});

function showLifeTimeTips()
{
    var lifetime = $('#lifetime option:selected').val();
    if(lifetime == 'ops')
    {
        $('#lifeTimeTips').show();
    }
    else
    {
        $('#lifeTimeTips').hide();
    }
}

/**
 * Refresh page.
 *
 * @param  object $projectID
 * @access public
 * @return void
 */
function setType(type)
{
    location.href = createLink('execution', 'create', 'projectID=' + projectID + '&executionID=0&copyExecutionID=&planID=0&confirm=no&productID=0&extra=type=' + type);
}

/**
 * Cut a string of letters and characters with the same length.
 *
 * @param  string $title
 * @param  int    $stringLength
 * @access public
 * @return string
 */
function subString(title, stringLength)
{
    if(title.replace(/[\u4e00-\u9fa5]/g, "**").length > stringLength)
    {
        var length = 0;
        for(var i = 0; i < title.length; i ++)
        {
            length += title.charCodeAt(i) > 255 ? 2 : 1;
            if(length > stringLength)
            {
                title = title.substring(0, i) + '...';
                break;
            }
        }
    }

    return title;
}
