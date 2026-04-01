<script>
$('#projectReviewDate').css({opacity: 0.8, 'pointer-events': 'none'});
$(document).ready(function()
{
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
});
</script>
