$(function(){
    $('.export').click(function()
    {
        let fileID = $('#file').val();
        let importUrl = createLink('workflow', 'import', 'id=' + fileID);
        
        window.location.href = importUrl;
    })
})