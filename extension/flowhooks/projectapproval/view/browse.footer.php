<?php js::set('disableAfterClickList', $lang->projectapproval->disableAfterClickList);?>
<style>
#flowReport {display: none;}
</style>
<script>
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