$(function()
{
    $('#needNotReview').on('change', function()
    {
        $('#reviewer').text('').attr('disabled', $(this).is(':checked') ? 'disabled' : null).trigger('chosen:updated');
        if($(this).is(':checked'))
        {
            $('#reviewerBox').removeClass('required');
        }
        else
        {
            $('#reviewerBox').addClass('required');
        }
        loadAssignedTo();

        getStatus('create', "product=" + $('#product').val() + ",execution=" + 0 + ",needNotReview=" + ($(this).prop('checked') ? 1 : 0));
    });
    $('#needNotReview').change();

    $('#submit').on('click', function()
    {
        var storyStatus = !$('#reviewer').val() || $('#needNotReview').is(':checked') ? 'active' : 'reviewing';
        $('<input />').attr('type', 'hidden').attr('name', 'status').attr('value', storyStatus).appendTo('#dataform');
        $('#dataform').submit();
    });

    if($('#reviewer').val()) loadAssignedTo();

    // init pri selector
    $('#pri').on('change', function()
    {
        var $select = $(this);
        var $selector = $select.closest('.pri-selector');
        var value = $select.val();
        $selector.find('.pri-text').html('<span class="label-pri label-pri-' + value + '" title="' + value + '">' + value + '</span>');
    });

    $('#source').on('change', function()
    {
        if(storyType == 'demand') return false;

        var source = $(this).val();
        if($.inArray(source, feedbackSource) != -1)
        {
            $('#feedbackBox').removeClass('hidden');
            $('#reviewerBox').attr('colspan', $('#assignedToBox').hasClass('hidden') ? 2 : 1);
            $('#assignedToBox').attr('colspan', 1);
        }
        else
        {
            $('#feedbackBox').addClass('hidden');
            $('#reviewerBox').attr('colspan', $('#assignedToBox').hasClass('hidden') ? 4 : 2);
            $('#assignedToBox').attr('colspan', 2);
        }
    });

    $('#customField').click(function()
    {
        hiddenRequireFields();
    });

    /* Implement a custom form without feeling refresh. */
    $('#formSettingForm .btn-primary').click(function()
    {
        saveCustomFields('createFields');

        setTimeout(function()
        {
            var showFieldList = showFields + ',';
            if(showFieldList.indexOf(',source,') >= 0)
            {
                $('#source').trigger("change");
            }
            else
            {
                $('#feedbackBox').addClass('hidden');
                $('#reviewerBox').attr('colspan', $('#assignedToBox').hasClass('hidden') ? 4 : 2);
                $('#assignedToBox').attr('colspan', 2);
            }
        }, 100);

        return false;
    });
});

/**
 * Load assignedTo.
 *
 * @access public
 * @return void
 */
function loadAssignedTo()
{
    var assignees = $('#reviewer').val();
    var link      = createLink('story', 'ajaxGetAssignedTo', 'type=create&storyID=0&assignees=' + assignees);
    $.post(link, function(data)
    {
        $('#assignedTo').replaceWith(data);
        $('#assignedToBox .picker').remove();
        $('#assignedTo').picker();
    });

    var colspan = $('#assignedToBox').attr('colspan');
    if($('#needNotReview').is(':checked'))
    {
        $('#assignedToBox').removeClass('hidden');
        $('#reviewerBox').attr('colspan', colspan);
    }
    else
    {
        $('#assignedToBox').addClass('hidden');
        $('#reviewerBox').attr('colspan', colspan * 2);
    }
}

function refreshPlan()
{
    loadProductPlans($('#product').val(), $('#branch').val());
}

/**
 * Set lane.
 *
 * @param  int $regionID
 * @access public
 * @return void
 */
function setLane(regionID)
{
    laneLink = createLink('kanban', 'ajaxGetLanes', 'regionID=' + regionID + '&type=story&field=lane');
    $.get(laneLink, function(lane)
    {
        if(!lane) lane = "<select id='lane' name='lane' class='form-control'></select>";
        $('#lane').replaceWith(lane);
        $('#lane' + "_chosen").remove();
        $('#lane').next('.picker').remove();
        $('#lane').chosen();
    });
}

function loadProduct(productID)
{
    if(typeof parentStory != 'undefined' && parentStory)
    {
        confirmLoadProduct = confirm(moveChildrenTips);
        if(!confirmLoadProduct)
        {
            $('#product').val(oldProductID);
            $('#product').trigger("chosen:updated");
            return false;
        }
    }

    if(typeof hasSR != 'undefined' && hasSR)
    {
        confirmLoadProduct = confirm(moveSRTips);//Set hasSR variable in pro and biz.
        if(!confirmLoadProduct)
        {
            $('#product').val(oldProductID);
            $('#product').trigger("chosen:updated");
            return false;
        }
    }

    oldProductID = $('#product').val();
    loadProductBranches(productID);
    loadProductReviewers(productID);
}

/**
 * Load branch.
 *
 * @access public
 * @return void
 */
function loadBranch()
{
    var branch    = $('#branch').val();
    var productID = $('#product').val();
    if(typeof(branch) == 'undefined') branch = 0;
    if(typeof(productID) == 'undefined' && config.currentMethod == 'edit') productID = oldProductID;

    loadProductModules(productID, branch);
    loadProductPlans(productID, branch);
}

/**
 * Load branches when change product.
 *
 * @param  int   $productID
 * @access public
 * @return void
 */
function loadProductBranches(productID)
{
    var param = 'all';
    param = 'active';
    $('#branch').remove();
    $('#branch_chosen').remove();
    $.get(createLink('branch', 'ajaxGetBranches', "productID=" + productID + "&oldBranch=0&param=" + param + "&projectID=0"), function(data)
    {
        var $product = $('#product');
        var $inputGroup = $product.closest('.input-group');
        $inputGroup.find('.input-group-addon').toggleClass('hidden', !data);
        if(data)
        {
            $inputGroup.append(data);
            $('#branch').css('width', config.currentMethod == 'create' ? '120px' : '65px').chosen();
        }
        $inputGroup.fixInputGroup();

        loadProductModules(productID, $('#branch').val());
        loadProductPlans(productID, $('#branch').val());
    })
}

/**
 * Load modules when change product.
 *
 * @param  int    $productID
 * @param  int    $branch
 * @access public
 * @return void
 */
function loadProductModules(productID, branch)
{
    if(typeof(branch) == 'undefined') branch = $('#branch').val();
    if(!branch) branch = 0;

    var currentModule = 0;

    var moduleLink = createLink('tree', 'ajaxGetOptionMenu', 'productID=' + productID + '&viewtype=story&branch=' + branch + '&rootModuleID=0&returnType=html&fieldID=&needManage=true&extra=&currentModuleID=' + currentModule);
    var $moduleIDBox = $('#moduleIdBox');
    $moduleIDBox.load(moduleLink, function()
    {
        $moduleIDBox.find('#module').chosen();
        if(typeof(storyModule) == 'string' && config.currentMethod != 'edit') $moduleIDBox.prepend("<span class='input-group-addon'>" + storyModule + "</span>");
        $moduleIDBox.fixInputGroup();
    });
}

/**
 * Load plans when change product.
 *
 * @param  int    $productID
 * @param  int    $branch
 * @access public
 * @return void
 */
function loadProductPlans(productID, branch)
{
    if(typeof(branch) == 'undefined') branch = 0;
    if(!branch) branch = 0;
    var expired = config.currentMethod == 'create' ? 'unexpired' : '';
    planLink = createLink('product', 'ajaxGetPlans', 'productID=' + productID + '&branch=' + branch + '&planID=' + $('#plan').val() + '&fieldID=&needCreate=true&expired='+ expired +'&param=skipParent,' + config.currentMethod);
    var $planIdBox = $('#planIdBox');
    $planIdBox.load(planLink, function()
    {
        $planIdBox.find('#plan').chosen();
        $planIdBox.fixInputGroup();
    });
}

/**
 * Load reviewers when change product.
 *
 * @param  int    $productID
 * @access public
 * @return void
 */
function loadProductReviewers(productID)
{
    var reviewerLink  = createLink('product', 'ajaxGetReviewers', 'productID=' + productID + '&storyID=0');
    var needNotReview = $('#needNotReview').attr('checked');
    $.get(reviewerLink, function(data)
    {
        if(data)
        {
            var $reviewer = $('#reviewer');
            var chosen = $reviewer.data('chosen');
            if(chosen)
            {
                chosen.destroy();
            }
            else
            {
                var picker = $reviewer.data('zui.picker');
                if(picker) picker.destroy();
            }
            $reviewer.replaceWith(data);
            $reviewer = $('#reviewer');
            if($reviewer.data('pickertype')) $reviewer.picker({chosenMode: true});
            else $reviewer.chosen();
            if(needNotReview == 'checked') $('#reviewer').attr('disabled', 'disabled').trigger('chosen:updated');
        }
    });
}

function getStatus(method, params)
{
    $.get(createLink('story', 'ajaxGetStatus', "method=" + method + '&params=' + params), function(status)
    {
        $('form #status').val(status).change();
    });
}
