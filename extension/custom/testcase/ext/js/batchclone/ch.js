$(function()
{
    fromCases.forEach(function(value, index){
        var currentIndex = index + 1;
        loadProductBranches($('#product' + currentIndex).val(), currentIndex, true);
    })
})

function loadProductBranches(productID, index, isCheckOld = false)
{
    
    $('#branch' + index).remove();
    var fromIndex  = index -1;
    var oldBranch  = isCheckOld ? fromCases[fromIndex].branch : 0;
    var browseType = 'active';
    var param      = "productID=" + productID + "&oldBranch=" + oldBranch  + "&browseType=active&projectID=0&index=" + index;

    $.get(createLink('testcase', 'ajaxGetBranches', param), function(data)
    {
        console.log(index)
        if(data)
        {
            $('#product' + index).closest('.input-group').append(data);
            $('#branch' + index).css('width', '95px');
        }
        loadProductModules(productID, oldBranch, index, isCheckOld);
        loadProductProjects(productID, oldBranch, index, isCheckOld);
    })
}

function loadProductProjects(productID, branch, index, isCheckOld = false)
{
    if(typeof(branch) == 'undefined') branch = $('#branch' + index).val();
    if(!branch) branch = 0;
    var fromIndex   = index -1;
    var projectID   = isCheckOld ? fromCases[fromIndex].project : 0;
    var projectLink = createLink('testcase', 'ajaxGetProductProjects', 'productID=' + productID + '&branch=' + branch + '&projectID=' + projectID + '&number=' + index);
    $('#project' + index).parent('td').load(projectLink, function()
    {
        $("#project" + index).chosen();
    });
    loadExecutions(productID, projectID, index, isCheckOld);
}

function loadExecutions(productID, projectID, index, isCheckOld = false)
{
    if(typeof(branch) == 'undefined') branch = $('#branch' + index).val();
    if(!branch) branch = 0;
    var fromIndex     = index -1;
    var executionID   = isCheckOld ? fromCases[fromIndex].execution : 0;
    var executionLink = createLink('testcase', 'ajaxGetProductExecutions', 'productID=' + productID + '&branch=' + branch + '&projectID=' + projectID + '&executionID=' + executionID + '&number=' + index);
    $('#execution' + index).parent('td').load(executionLink, function()
    {
        $("#execution" + index).chosen();
        var objectID = projectID;
        var moduleID = $('#modules' + index).val();
        objectID = (executionID == 0) ? objectID : executionID;
        loadStories(productID, moduleID, index, objectID, isCheckOld);
    });
}

function setStories(productID, executionID, index, projectID)
{
    var objectID = projectID;
    var moduleID = $('#modules' + index).val();
    objectID = (executionID == 0) ? objectID : executionID;
    loadStories(productID, moduleID, index, objectID);
}

function loadProductModules(productID, branch, index, isCheckOld = false)
{
    
    if(typeof(branch) == 'undefined') branch = $('#branch' + index).val();
    if(!branch) branch = 0;
    var fromIndex  = index -1;
    var moduleID   = isCheckOld ? fromCases[fromIndex].module : 0;
    var moduleLink = createLink('tree', 'ajaxGetOptionMenu', 'productID=' + productID + '&viewtype=case&branch=' + branch + '&rootModuleID=0&returnType=html&fieldID=' + index + '&needManage=false&extra=nodeleted&currentModuleID=' + moduleID);
    $('#modules' + index).parent('td').load(moduleLink, function()
    {
        $("#modules" + index).attr('onchange', "onModuleChanged("+ productID + ", this.value, " + index + ")").chosen();
    });
    var objectID = (fromCases[fromIndex].project == 0) ? 0 : fromCases[fromIndex].project;
    objectID = (fromCases[fromIndex].execution == 0) ? objectID : fromCases[fromIndex].execution;
    loadStories(productID, moduleID, index, objectID, isCheckOld);
    setScenes(productID, moduleID, index, objectID, isCheckOld);
}

function onModuleChanged(productID, moduleID, index, isCheckOld = false)
{
    var objectID = $('#project' + index).val();
    objectID = ($('#execution' + index).val() == 0) ? objectID : $('#execution' + index).val();
    loadStories(productID, moduleID, index, objectID, isCheckOld);
    setScenes(productID, moduleID, index, objectID, isCheckOld);
}

function setScenes(productID, moduleID, index, objectID, isCheckOld)
{
    var fromIndex = index -1;
    var sceneID   = isCheckOld ? fromCases[fromIndex].scene : 0;
    var branchID  = $('branch' + index).val();
    if(!branchID) branchID = 0;
    link = createLink('testcase', 'ajaxGetScenes', 'productID=' + productID + '&branch=' + branch + '&moduleID=' + moduleID + '&element=scene&sceneID=' + sceneID + '&number=' + index);
    $.get(link, function(scenes){
        if(!scenes) scenes = '<select id="scene' + index + '" name="scene[' + index + ']" class="form-control"></select>';
        $('#scene' + index).replaceWith(scenes);
        $('#scene' + index + "_chosen").remove();
        $('#scene' + index).next('.picker').remove();
        $('#scene' + index).attr('name', 'scene[' + index + ']');
        $('#scene' + index).chosen();
    })
}

function loadStories(productID, module, index, objectID, isCheckOld = false)
{
    var fromIndex = index -1;
    var storyID   = isCheckOld ? fromCases[fromIndex].story : 0;
    var branchID  = $('branch' + index).val();
    if(!branchID) branchID = 0;
    var storyLink = createLink('testcase', 'ajaxGetProductStories', 'productID=' + productID + '&branch=' + branchID + '&moduleID=' + module + '&storyID=' + storyID + '&onlyOption=false&status=noclosed&limit=0&type=full&hasParent=1&objectID=' + objectID + '&number=' + index);
    $('#story' + index).parent('td').load(storyLink, function()
    {
        $("#story" + index).chosen();
    });
}