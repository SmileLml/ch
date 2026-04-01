$('#toRelationButton').on('click', function()
{
    var productID = $('#product').val();

    var link = createLink('story', 'batchCreate', 'productID=' + productID + '&branch=all&moduleID=0&storyID=0&project=' + projectID + '&plan=0&storyType=requirement&extra=&businessID=' + businessID) + '#app=project';
    window.location = link;
});