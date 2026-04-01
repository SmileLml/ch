$(function()
{
    $(document).on('keydown', "[name^='name']", function()
    {
        $(this).parents('tr').find(':input[name^="estimate"]').val(8);
    })
});

function switchExecution(id)
{
    var link = createLink('task', 'batchCreate', 'executionID=' + id);
    location.href = link;
}
