<script>
var from    = '<?php echo $from;?>';
var newLink = '<?php echo $this->session->teamTaskList;?>';

if(from == 'chproject')
{
    $('a.btn-secondary').attr('href', newLink);
    $('.form-actions a').attr('href', newLink);
}
</script>
