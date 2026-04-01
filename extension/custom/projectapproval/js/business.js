let projectbusinessLink = '';
if(requestType == 'GET')       projectbusinessLink = createLink('projectapproval', 'business', 'dataID=' + projectapproval.id);
if(requestType == 'PATH_INFO') projectbusinessLink = createLink('projectapproval', 'business', 't=' + projectapproval.id);
$('nav>ul>li').eq(1).find('a').attr('href', projectbusinessLink);
$('nav>ul>li').eq(0).removeClass('active');

let projectapprovalLink = '';
if(requestType == 'GET')       projectapprovalLink = createLink('projectapproval', 'view', 't=' + projectapproval.name + '&dataID=' + projectapproval.id);
if(requestType == 'PATH_INFO') projectapprovalLink = createLink('projectapproval', 'view', 't=' + projectapproval.id);

if(requestType == 'GET')       finishReportLink = createLink('projectapproval', 'finishReport', 'dataID=' + projectapproval.id);
if(requestType == 'PATH_INFO') finishReportLink = createLink('projectapproval', 'finishReport', 't=' + projectapproval.id);
$('nav>ul>li').eq(2).find('a').attr('href', finishReportLink);

$('nav>ul>li').eq(0).find('a').attr('href', projectapprovalLink);

if(projectapproval.status != 'finished')
{
    $('nav>ul>li').eq(2).remove();
}
