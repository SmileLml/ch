$(function()
{
    $('#chteamList tbody tr').each(function()
    {
        var $content = $(this).find('td.content');
        var content  = $content.find('div').html();
        if(content.indexOf('<br') >= 0 || content.indexOf('<img') >= 0)
        {
            $content.append("<a href='###' class='more'><i class='icon icon-angle-right rotate-down'></i></a>");
        }
    });
})

$(document).on('click', 'td.content .more', function(e)
{
    var $toggle = $(this);
    if($toggle.hasClass('open'))
    {
        $toggle.removeClass('open');
        $toggle.closest('.content').find('div').css('height', '25px');
        $toggle.css('padding-top', 0);
        $toggle.find('i').addClass('rotate-down');
    }
    else
    {
        $toggle.addClass('open');
        $toggle.closest('.content').find('div').css('height', 'auto');
        $toggle.css('padding-top', ($toggle.closest('.content').find('div').height() - $toggle.height()) / 2);
        $toggle.find('i').removeClass('rotate-down');
    }
});