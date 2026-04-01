<script>
$(document).ready(function()
{
   if($('#project').val() == '') $('#itPM').closest('tr').hide();

   var isTrans = $('input[name="isTrans"]:checked').val();
   if(isTrans == 'yes')
   {
      $('#reviewResultnoReview').parent().show();
   }
   else
   {
       $('#reviewResultnoReview').parent().hide();
   }
   $('input[name="isTrans"]').change(function()
   {
       var isTrans = $('input[name="isTrans"]:checked').val();
       if(isTrans == 'yes')
       {
           $('#reviewResultnoReview').parent().show();
           $('#reviewResultnoReview').prop('checked', true);
       }
       else
       {
           $('#reviewResultnoReview').parent().hide();
           $('#reviewResultpass').prop('checked', true);
       }
   });
   $('input[name="reviewResult"]').change(function()
   {
        var reviewResult  = $('input[name="reviewResult"]:checked').val();
        var stakeholderTr = $('textarea[name="reviewOpinion"]').closest('tr').nextAll();
        var stakeholderTr = stakeholderTr.filter(':lt(' + (stakeholderTr.length - 1) + ')');
        if(reviewResult == 'pass')
        {
            stakeholderTr.show();
        }
        else
        {
            stakeholderTr.hide();
        }
   });

   if(typeof isCreatedDeptLeader !== "undefined")
   {
        if(isCreatedDeptLeader === false)
        {
            $('#businessPM').attr('readonly', true);
            $('#businessPM').closest('td').css('pointer-events', 'none');
            $('#businessPM').next('div').children('div').css('background-color', 'rgb(245, 245, 245)');
            $('#businessPM').next('div').find('div>div>span').eq(1).remove();
        }
        else
        {
            $('#businessPM').closest('td').toggleClass('required', true);
        }
   }
   
   $('#operateForm tr:eq(-2)').after(stakeholdersHtml);
});
</script>
