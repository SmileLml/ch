$('#toRelationButton').on('click', function()
{
    var executionID = $('#execution').val();

    var link = createLink('execution', 'relation', 'executionID=' + executionID + '&chprojectID=' + projectID) + '#app=chteam';
    window.location = link;
});