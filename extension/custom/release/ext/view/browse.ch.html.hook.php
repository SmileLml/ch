<script>
var releaseLink = 'https://gaiatest.9cair.com/threeinone/tioupdateList?notRedmineFlag=true';
//在class为btn-toolbar pull-right的div中插入一个超链接
$('#mainMenu .pull-right').append('<a class="btn btn-primary" href="'+releaseLink+'" target="_blank">' + '<?php echo $lang->release->common?>' + '</a>');
</script>
