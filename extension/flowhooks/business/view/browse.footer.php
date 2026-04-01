<?php js::set('disableAfterClickList', $lang->business->disableAfterClickList);?>
<style>
#flowReport {display: none;}
#menuActions > a {display: none;}

body {padding-bottom: 20px;}
</style>
<script>
$('a.reloadPage:contains("<?php echo $this->lang->close;?>")').each(function(){
    $(this).attr('data-url', $(this).attr('href'));
    $(this).attr('href', 'javascript:void(0);');
})
$('a.reloadPage:contains("<?php echo $this->lang->close;?>")').on('click', function(e)
{

    var url = $(this).attr('data-url');
    var confirmed = confirm('<?php echo $this->lang->business->confirmClose;?>');

    if (confirmed)
    {
        window.location.href = url;
    }
})
$(document).ready(function() {
    $.each(disableAfterClickList, function(key,value){
        $('.actions a:contains("' + value + '")').click(function(event) {
            var link = $(this);
            event.preventDefault();

            link.off('click');
            link.css({
            'color': 'gray',
            'pointer-events': 'none'
            });
        });
    })
});
</script>
