$('nav>ul>li').eq(0).removeClass('active');
$('nav>ul>li').last().addClass('active');
let projectapprovalLink = '';
if(requestType == 'GET')       projectapprovalLink = createLink('projectapproval', 'view', 't=' + projectapproval.name + '&dataID=' + projectapproval.id);
if(requestType == 'PATH_INFO') projectapprovalLink = createLink('projectapproval', 'view', 't=' + projectapproval.id);

$('nav>ul>li').eq(0).find('a').attr('href', projectapprovalLink);

let projectbusinessLink = '';
if(requestType == 'GET')       projectbusinessLink = createLink('projectapproval', 'business', 'dataID=' + projectapproval.id);
if(requestType == 'PATH_INFO') projectbusinessLink = createLink('projectapproval', 'business', 't=' + projectapproval.id);
$('nav>ul>li').eq(1).find('a').attr('href', projectbusinessLink);

if(requestType == 'GET')       finishReportLink = createLink('projectapproval', 'finishReport', 'dataID=' + projectapproval.id);
if(requestType == 'PATH_INFO') finishReportLink = createLink('projectapproval', 'finishReport', 't=' + projectapproval.id);
$('nav>ul>li').eq(2).find('a').attr('href', finishReportLink);

$('#exportPDF').click(function(){
    const element = document.getElementById('tabsNav');
    html2pdf().from(element).set({
        margin: 1,
        filename: projectapproval.name + '-' + finishReportTitle + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 }, 
        jsPDF: {
            orientation: 'portrait',
            unit: 'mm',
            format: [297, 420],
            putOnlyUsedFonts: true,
            orientation: 'portrait'
          }
    }).save();
})