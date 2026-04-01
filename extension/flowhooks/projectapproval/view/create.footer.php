<script>
$(document).ready(function()
{
    let costUnltID = '#childrensub_projectcostcostUnit1';
    $(costUnltID).addClass('form-control');
    $(costUnltID).attr('type', 'text');
    $(costUnltID).prop('readonly', true);

    let costDescID = '#childrensub_projectcostcostDesc1';
    $(costDescID).addClass('form-control');
    $(costDescID).attr('type', 'text');
    $(costDescID).prop('readonly', true);
});
</script>
