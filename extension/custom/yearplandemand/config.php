<?php
$config->yearplandemand = new stdclass();
$config->yearplandemand->create      = new stdclass();
$config->yearplandemand->edit        = new stdclass();
$config->yearplandemand->integration = new stdclass();
$config->yearplandemand->confirm     = new stdclass();

$config->yearplandemand->create->requiredFields      = 'name';
$config->yearplandemand->edit->requiredFields        = $config->yearplandemand->create->requiredFields;
$config->yearplandemand->integration->requiredFields = $config->yearplandemand->create->requiredFields;
$config->yearplandemand->confirm->requiredFields     = 'confirmResult';

$config->yearplandemand->editor = new stdclass();
$config->yearplandemand->editor->create      = array('id' => 'desc', 'tools' => 'simpleTools');
$config->yearplandemand->editor->edit        = array('id' => 'desc', 'tools' => 'simpleTools');
$config->yearplandemand->editor->view        = array('id' => 'desc', 'tools' => 'simpleTools');
$config->yearplandemand->editor->integration = array('id' => 'desc', 'tools' => 'simpleTools');
$config->yearplandemand->editor->confirm     = array('id' => 'confirmComment', 'tools' => 'simpleTools');
$config->yearplandemand->editor->export      = array('id' => 'desc', 'tools' => 'simpleTools');

$config->yearplandemand->list = new stdclass();
$config->yearplandemand->list->exportFields = 'id, name, desc, level, approvalDate, planConfirmDate, goliveDate, itPlanInto, itPM, businessArchitect, category, businessManager, initDept, dept, isPurchased, purchasedContents, files, mergeTo, mergeSources';

$config->yearplandemand->templateFields = "name,desc,level,approvalDate,planConfirmDate,goliveDate,itPlanInto,itPM,businessArchitect,category,businessManager,initDept,dept,isPurchased,purchasedContents";

$config->yearplandemand->sysLangFields = 'level,category,isPurchased';

$config->yearplandemand->userFields = 'itPM,businessArchitect,businessManager';

$config->yearplandemand->listFields = "level,itPM,businessArchitect,businessManager,category,initDept,dept,isPurchased";

/* Search. */
global $lang;
$config->yearplandemand->search['module'] = 'yearplandemand';
$config->yearplandemand->search['fields']['id']                = $lang->yearplandemand->id;
$config->yearplandemand->search['fields']['name']              = $lang->yearplandemand->name;
$config->yearplandemand->search['fields']['status']            = $lang->yearplandemand->status;
$config->yearplandemand->search['fields']['level']             = $lang->yearplandemand->level;
$config->yearplandemand->search['fields']['category']          = $lang->yearplandemand->category;
$config->yearplandemand->search['fields']['initDept']          = $lang->yearplandemand->initDept;
$config->yearplandemand->search['fields']['dept']              = $lang->yearplandemand->dept;
$config->yearplandemand->search['fields']['approvalDate']      = $lang->yearplandemand->approvalDate;
$config->yearplandemand->search['fields']['planConfirmDate']   = $lang->yearplandemand->planConfirmDate;
$config->yearplandemand->search['fields']['goliveDate']        = $lang->yearplandemand->goliveDate;
$config->yearplandemand->search['fields']['itPlanInto']        = $lang->yearplandemand->itPlanInto;
$config->yearplandemand->search['fields']['itPM']              = $lang->yearplandemand->itPM;
$config->yearplandemand->search['fields']['businessArchitect'] = $lang->yearplandemand->businessArchitect;
$config->yearplandemand->search['fields']['businessManager']   = $lang->yearplandemand->businessManager;
$config->yearplandemand->search['fields']['isPurchased']       = $lang->yearplandemand->isPurchased;
$config->yearplandemand->search['fields']['purchasedContents'] = $lang->yearplandemand->purchasedContents;

$config->yearplandemand->search['params']['id']                = array('operator' => '=', 'control' => 'input', 'values' => '');
$config->yearplandemand->search['params']['name']              = array('operator' => '=', 'control' => 'input', 'values' => '');
$config->yearplandemand->search['params']['status']            = array('operator' => '=', 'control' => 'select', 'values' => $lang->yearplandemand->statusList);
$config->yearplandemand->search['params']['level']             = array('operator' => '=', 'control' => 'select', 'values' => $lang->yearplandemand->levelList);
$config->yearplandemand->search['params']['category']          = array('operator' => '=', 'control' => 'select', 'values' => $lang->yearplandemand->categoryList);
$config->yearplandemand->search['params']['initDept']          = array('operator' => '=', 'control' => 'select', 'values' => '');
$config->yearplandemand->search['params']['dept']              = array('operator' => 'include', 'control' => 'select', 'values' => '');
$config->yearplandemand->search['params']['approvalDate']      = array('operator' => '=', 'control' => 'input', 'values' => '', 'class' => 'date');
$config->yearplandemand->search['params']['planConfirmDate']   = array('operator' => '=', 'control' => 'input', 'values' => '', 'class' => 'date');
$config->yearplandemand->search['params']['goliveDate']        = array('operator' => '=', 'control' => 'input', 'values' => '', 'class' => 'date');
$config->yearplandemand->search['params']['itPlanInto']        = array('operator' => '=', 'control' => 'input', 'values' => '');
$config->yearplandemand->search['params']['itPM']              = array('operator' => '=', 'control' => 'select', 'values' => 'users');
$config->yearplandemand->search['params']['businessArchitect'] = array('operator' => '=', 'control' => 'select', 'values' => '');
$config->yearplandemand->search['params']['businessManager']   = array('operator' => 'include', 'control' => 'select', 'values' => 'users');
$config->yearplandemand->search['params']['isPurchased']       = array('operator' => '=', 'control' => 'select', 'values' => $lang->yearplandemand->isPurchasedList);
$config->yearplandemand->search['params']['purchasedContents'] = array('operator' => 'include', 'control' => 'input', 'values' => $lang->yearplandemand->isPurchasedList);

$config->yearplandemand->datatable = new stdclass();
$config->yearplandemand->datatable->defaultField = array('id', 'name', 'level', 'status', 'initDept', 'itPlanInto', 'businessManager', 'approvalDate', 'planConfirmDate', 'goliveDate', 'actions');

$config->yearplandemand->datatable->fieldList['id']['title']    = 'id';
$config->yearplandemand->datatable->fieldList['id']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['id']['width']    = '70';
$config->yearplandemand->datatable->fieldList['id']['checkbox'] = true;
$config->yearplandemand->datatable->fieldList['id']['required'] = 'yes';

$config->yearplandemand->datatable->fieldList['name']['title']    = 'name';
$config->yearplandemand->datatable->fieldList['name']['type']     = 'html';
$config->yearplandemand->datatable->fieldList['name']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['name']['width']    = '70';
$config->yearplandemand->datatable->fieldList['name']['required'] = 'yes';

$config->yearplandemand->datatable->fieldList['status']['title']    = 'status';
$config->yearplandemand->datatable->fieldList['status']['type']     = 'html';
$config->yearplandemand->datatable->fieldList['status']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['status']['width']    = '70';
$config->yearplandemand->datatable->fieldList['status']['control']  = 'select';
$config->yearplandemand->datatable->fieldList['status']['required'] = 'no';

$config->yearplandemand->datatable->fieldList['level']['title']    = 'level';
$config->yearplandemand->datatable->fieldList['level']['type']     = 'html';
$config->yearplandemand->datatable->fieldList['level']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['level']['width']    = '70';
$config->yearplandemand->datatable->fieldList['level']['required'] = 'no';
$config->yearplandemand->datatable->fieldList['level']['control']  = 'select';

$config->yearplandemand->datatable->fieldList['category']['title']    = 'category';
$config->yearplandemand->datatable->fieldList['category']['type']     = 'html';
$config->yearplandemand->datatable->fieldList['category']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['category']['width']    = '70';
$config->yearplandemand->datatable->fieldList['category']['required'] = 'no';
$config->yearplandemand->datatable->fieldList['category']['control']  = 'select';

$config->yearplandemand->datatable->fieldList['initDept']['title']      = 'initDept';
$config->yearplandemand->datatable->fieldList['initDept']['fixed']      = 'left';
$config->yearplandemand->datatable->fieldList['initDept']['width']      = '70';
$config->yearplandemand->datatable->fieldList['initDept']['required']   = 'no';
$config->yearplandemand->datatable->fieldList['initDept']['control']    = 'select';
$config->yearplandemand->datatable->fieldList['initDept']['dataSource'] = array('module' => 'dept', 'method' =>'getOptionMenuByGrade', 'params' => '0&3');

$config->yearplandemand->datatable->fieldList['dept']['title']      = 'dept';
$config->yearplandemand->datatable->fieldList['dept']['fixed']      = 'left';
$config->yearplandemand->datatable->fieldList['dept']['width']      = '70';
$config->yearplandemand->datatable->fieldList['dept']['required']   = 'no';
$config->yearplandemand->datatable->fieldList['dept']['control']    = 'multiple';
$config->yearplandemand->datatable->fieldList['dept']['dataSource'] = array('module' => 'dept', 'method' =>'getOptionMenuByGrade', 'params' => '0&3');

$config->yearplandemand->datatable->fieldList['approvalDate']['title']    = 'approvalDate';
$config->yearplandemand->datatable->fieldList['approvalDate']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['approvalDate']['width']    = '70';
$config->yearplandemand->datatable->fieldList['approvalDate']['required'] = 'no';

$config->yearplandemand->datatable->fieldList['planConfirmDate']['title']    = 'planConfirmDate';
$config->yearplandemand->datatable->fieldList['planConfirmDate']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['planConfirmDate']['width']    = '70';
$config->yearplandemand->datatable->fieldList['planConfirmDate']['required'] = 'no';

$config->yearplandemand->datatable->fieldList['goliveDate']['title']    = 'goliveDate';
$config->yearplandemand->datatable->fieldList['goliveDate']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['goliveDate']['width']    = '70';
$config->yearplandemand->datatable->fieldList['goliveDate']['required'] = 'no';

$config->yearplandemand->datatable->fieldList['itPlanInto']['title']    = 'itPlanIntoSimplify';
$config->yearplandemand->datatable->fieldList['itPlanInto']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['itPlanInto']['width']    = '70';
$config->yearplandemand->datatable->fieldList['itPlanInto']['required'] = 'no';

$config->yearplandemand->datatable->fieldList['itPM']['title']      = 'itPM';
$config->yearplandemand->datatable->fieldList['itPM']['fixed']      = 'left';
$config->yearplandemand->datatable->fieldList['itPM']['width']      = '70';
$config->yearplandemand->datatable->fieldList['itPM']['required']   = 'no';
$config->yearplandemand->datatable->fieldList['itPM']['control']    = 'select';
$config->yearplandemand->datatable->fieldList['itPM']['dataSource'] = array('module' => 'user', 'method' =>'getPairs', 'params' => 'noclosed|noletter');

$config->yearplandemand->datatable->fieldList['businessArchitect']['title']      = 'businessArchitect';
$config->yearplandemand->datatable->fieldList['businessArchitect']['fixed']      = 'left';
$config->yearplandemand->datatable->fieldList['businessArchitect']['width']      = '70';
$config->yearplandemand->datatable->fieldList['businessArchitect']['required']   = 'no';
$config->yearplandemand->datatable->fieldList['businessArchitect']['control']    = 'select';
$config->yearplandemand->datatable->fieldList['businessArchitect']['dataSource'] = array('module' => 'user', 'method' =>'getPairs', 'params' => 'noclosed|noletter');

$config->yearplandemand->datatable->fieldList['businessManager']['title']      = 'businessManager';
$config->yearplandemand->datatable->fieldList['businessManager']['fixed']      = 'left';
$config->yearplandemand->datatable->fieldList['businessManager']['width']      = '70';
$config->yearplandemand->datatable->fieldList['businessManager']['required']   = 'no';
$config->yearplandemand->datatable->fieldList['businessManager']['control']    = 'select';
$config->yearplandemand->datatable->fieldList['businessManager']['dataSource'] = array('module' => 'user', 'method' =>'getPairs', 'params' => 'noclosed|noletter');

$config->yearplandemand->datatable->fieldList['isPurchased']['title']    = 'isPurchased';
$config->yearplandemand->datatable->fieldList['isPurchased']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['isPurchased']['width']    = '70';
$config->yearplandemand->datatable->fieldList['isPurchased']['required'] = 'no';
$config->yearplandemand->datatable->fieldList['isPurchased']['control']  = 'select';

$config->yearplandemand->datatable->fieldList['createdBy']['title']    = 'createdBy';
$config->yearplandemand->datatable->fieldList['createdBy']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['createdBy']['width']    = '70';
$config->yearplandemand->datatable->fieldList['createdBy']['required'] = 'no';

$config->yearplandemand->datatable->fieldList['createdDate']['title']    = 'createdDate';
$config->yearplandemand->datatable->fieldList['createdDate']['fixed']    = 'left';
$config->yearplandemand->datatable->fieldList['createdDate']['width']    = '70';
$config->yearplandemand->datatable->fieldList['createdDate']['required'] = 'no';

$config->yearplandemand->datatable->fieldList['desc']['title']   = 'desc';
$config->yearplandemand->datatable->fieldList['desc']['control'] = 'textarea';
$config->yearplandemand->datatable->fieldList['desc']['display'] = false;

$config->yearplandemand->datatable->fieldList['mergeSources']['title']   = 'mergeSources';
$config->yearplandemand->datatable->fieldList['mergeSources']['control'] = 'multiple';
$config->yearplandemand->datatable->fieldList['mergeSources']['dataSource'] = array('module' => 'yearplandemand', 'method' =>'getMergeObject', 'params' => '');
$config->yearplandemand->datatable->fieldList['mergeSources']['display'] = false;

$config->yearplandemand->datatable->fieldList['mergeTo']['title']   = 'mergeTo';
$config->yearplandemand->datatable->fieldList['mergeTo']['control'] = 'select';
$config->yearplandemand->datatable->fieldList['mergeTo']['dataSource'] = array('module' => 'yearplandemand', 'method' =>'getMergeObject', 'params' => '');
$config->yearplandemand->datatable->fieldList['mergeTo']['display'] = false;

$config->yearplandemand->datatable->fieldList['actions']['title']    = 'actions';
$config->yearplandemand->datatable->fieldList['actions']['fixed']    = 'right';
$config->yearplandemand->datatable->fieldList['actions']['width']    = '180';
$config->yearplandemand->datatable->fieldList['actions']['required'] = 'yes';