$(document).ready(function()
{
    // 保存原始的 jQuery ajax 方法
    var originalAjax = $.ajax;
    
    // 重写 jQuery ajax 方法
    $.ajax = function(settings) 
    {
        // 保存原始的 complete 回调
        var originalComplete = settings.complete;
        
        // 添加我们自己的 complete 处理
        settings.complete = function(xhr, textStatus) 
        {
            var $operateForm = $('#operateForm');
            if($operateForm.length && settings.type && settings.type.toLowerCase() === 'post') 
            {
                // 只隐藏当前可能显示的日期选择器，而不是所有的
                $operateForm.find('.form-date, .date').each(function() 
                {
                    var $datepicker = $(this).data('datetimepicker');
                    if($datepicker && $datepicker.picker && $datepicker.picker.is(':visible')) $datepicker.hide();
                });
            }
            
            // 调用原始的 complete 回调（如果存在）
            if(originalComplete) originalComplete.apply(this, arguments);
        };
        
        // 调用原始的 ajax 方法
        return originalAjax.apply(this, arguments);
    };
    
    $.setAjaxForm('#operateForm');

    $('#dataID').change(function()
    {
        location.href = createLink(window.moduleName, window.action, 'dataID=' + $(this).val());
    });

    $('.prevTR select').change(function()
    {
        loadPrevData($(this).parents('tr'), $(this).val());
    });

    $('.prevTR').each(function()
    {
        loadPrevData($(this));
    });
})
