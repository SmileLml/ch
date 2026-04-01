<?php
$config->pivot = new stdclass();
$config->pivot->widthInput = 128;
$config->pivot->widthDate  = 248;
$config->pivot->recPerPage = 50;
$config->pivot->recPerPageList = array(1,5,10,15,20,25,30,35,40,45,50,100,200,500,1000,2000);

$config->pivot->fileType =  array('xlsx' => 'xlsx', 'xls' => 'xls', 'html' => 'html', 'mht' => 'mht');

$config->pivot->create = new stdclass();
$config->pivot->create->requiredFields = 'type,group';

$config->pivot->edit = new stdclass();
$config->pivot->edit->requiredFields = 'type,group';

$config->pivot->design = new stdclass();
$config->pivot->design->requiredFields = 'group';

$config->pivot->multiColumn = array('cluBarX' => 'yaxis', 'cluBarY' => 'yaxis', 'radar' => 'yaxis', 'line' => 'yaxis', 'stackedBar' => 'yaxis', 'stackedBarY' => 'yaxis');

$config->pivot->checkForm = array();
$config->pivot->checkForm['line']        = array('cantequal' => 'xaxis,yaxis');
$config->pivot->checkForm['cluBarX']     = array('cantequal' => 'xaxis,yaxis');
$config->pivot->checkForm['cluBarY']     = array('cantequal' => 'xaxis,yaxis');
$config->pivot->checkForm['radar']       = array('cantequal' => 'xaxis,yaxis');
$config->pivot->checkForm['stackedBar']  = array('cantequal' => 'xaxis,yaxis');
$config->pivot->checkForm['stackedBarY'] = array('cantequal' => 'xaxis,yaxis');
global $lang;
$config->pivot->settings = array();
$config->pivot->settings['cluBarX'] = array();
$config->pivot->settings['cluBarX']['xaxis']   = array();
$config->pivot->settings['cluBarX']['xaxis'][] = array('field' => 'xaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 4);

$config->pivot->settings['cluBarX']['yaxis']   = array();
$config->pivot->settings['cluBarX']['yaxis'][] = array('field' => 'yaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);
$config->pivot->settings['cluBarX']['yaxis'][] = array('field' => 'agg', 'type' => 'select', 'options' => 'aggList', 'required' => false, 'placeholder' => $lang->pivot->aggType, 'col' => 2);

$config->pivot->settings['cluBarY'] = array();
$config->pivot->settings['cluBarY']['xaxis']   = array();
$config->pivot->settings['cluBarY']['xaxis'][] = array('field' => 'xaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 4);

$config->pivot->settings['cluBarY']['yaxis']   = array();
$config->pivot->settings['cluBarY']['yaxis'][] = array('field' => 'yaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);
$config->pivot->settings['cluBarY']['yaxis'][] = array('field' => 'agg', 'type' => 'select', 'options' => 'aggList', 'required' => false, 'placeholder' => $lang->pivot->aggType, 'col' => 2);

$config->pivot->settings['stackedBarY'] = array();
$config->pivot->settings['stackedBarY']['xaxis']   = array();
$config->pivot->settings['stackedBarY']['xaxis'][] = array('field' => 'xaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 4);

$config->pivot->settings['stackedBarY']['yaxis']   = array();
$config->pivot->settings['stackedBarY']['yaxis'][] = array('field' => 'yaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);
$config->pivot->settings['stackedBarY']['yaxis'][] = array('field' => 'agg', 'type' => 'select', 'options' => 'aggList', 'required' => false, 'placeholder' => $lang->pivot->aggType, 'col' => 2);

$config->pivot->settings['line'] = array();
$config->pivot->settings['line']['xaxis']   = array();
$config->pivot->settings['line']['xaxis'][] = array('field' => 'xaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 4);

$config->pivot->settings['line']['yaxis']   = array();
$config->pivot->settings['line']['yaxis'][] = array('field' => 'yaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);
$config->pivot->settings['line']['yaxis'][] = array('field' => 'agg', 'type' => 'select', 'options' => 'aggList', 'required' => false, 'placeholder' => $lang->pivot->aggType, 'col' => 2);

$config->pivot->settings['pie'] = array();
$config->pivot->settings['pie']['group']   = array();
$config->pivot->settings['pie']['group'][] = array('field' => 'group', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);

$config->pivot->settings['pie']['metric']   = array();
$config->pivot->settings['pie']['metric'][] = array('field' => 'metric',  'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);

$config->pivot->settings['pie']['stat']   = array();
$config->pivot->settings['pie']['stat'][] = array('field' => 'agg', 'type' => 'select', 'options' => 'aggList', 'required' => false, 'placeholder' => $lang->pivot->aggType, 'col' => 2);

$config->pivot->settings['radar'] = array();
$config->pivot->settings['radar']['xaxis']   = array();
$config->pivot->settings['radar']['xaxis'][] = array('field' => 'xaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 4);

$config->pivot->settings['radar']['yaxis']   = array();
$config->pivot->settings['radar']['yaxis'][] = array('field' => 'yaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);
$config->pivot->settings['radar']['yaxis'][] = array('field' => 'agg', 'type' => 'select', 'options' => 'aggList', 'required' => false, 'placeholder' => $lang->pivot->aggType, 'col' => 2);

$config->pivot->settings['stackedBar'] = array();
$config->pivot->settings['stackedBar']['xaxis']   = array();
$config->pivot->settings['stackedBar']['xaxis'][] = array('field' => 'xaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 4);

$config->pivot->settings['stackedBar']['yaxis']   = array();
$config->pivot->settings['stackedBar']['yaxis'][] = array('field' => 'yaxis', 'type' => 'select', 'options' => 'field', 'required' => true, 'placeholder' => $lang->pivot->chooseField, 'col' => 2);
$config->pivot->settings['stackedBar']['yaxis'][] = array('field' => 'agg', 'type' => 'select', 'options' => 'aggList', 'required' => false, 'placeholder' => $lang->pivot->aggType, 'col' => 2);

$config->pivot->settings['testingReport'] = array('field' => array('type' => 'td'));

$config->pivot->transTypes = array();
$config->pivot->transTypes['int']      = 'number';
$config->pivot->transTypes['float']    = 'number';
$config->pivot->transTypes['double']   = 'number';
$config->pivot->transTypes['datetime'] = 'date';
$config->pivot->transTypes['date']     = 'date';

$config->pivot->story = new stdclass();
$config->pivot->story->filters = array();
$config->pivot->story->filters['team']          = array('class' => 'w-512', 'show' => array('team', 'sprint'),            'type' => 'select',    'required' => false, 'default' => array(),         'options' => 'team',          'multiple' => true);
$config->pivot->story->filters['sprint']        = array('class' => 'w-512', 'show' => array('sprint'),                    'type' => 'select',    'required' => false, 'default' => array(),         'options' => 'sprint',        'multiple' => true);
$config->pivot->story->filters['sprintStatus']  = array('show' => array('sprint'),                                        'type' => 'select',    'required' => false, 'default' => array('closed'), 'options' => 'executionStatus',  'multiple' => true);
$config->pivot->story->filters['program']       = array('class' => 'w-512', 'show' => array('project'),                   'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'program',       'multiple' => true);
$config->pivot->story->filters['project']       = array('class' => 'w-512', 'show' => array('project'),                   'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'project',       'multiple' => true);
$config->pivot->story->filters['projectStatus'] = array('show' => array('project'),                                       'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'projectStatus', 'multiple' => true);
$config->pivot->story->filters['projectedType'] = array('show' => array('project'),                                       'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'projectedType', 'multiple' => true);
$config->pivot->story->filters['stage']         = array('show' => array('team', 'sprint', 'project'),                     'type' => 'select',    'required' => true,  'default' => 'status_closed',  'options' => 'storyStage',    'multiple' => false);
$config->pivot->story->filters['date']          = array('class' => 'w-512', 'show' => array('team', 'sprint', 'project'), 'type' => 'dateRange', 'required' => true,  'default' => array('-2 weeks', 'today'));
$config->pivot->story->filters['type']          = array('class' => 'w-784', 'show' => array('team', 'sprint', 'project'), 'type' => 'select',    'required' => true,  'default' => array(),         'options' => 'storyType',     'multiple' => true);

$config->pivot->story->cols = array();
$config->pivot->story->cols['team']    = array('team', 'storyCount', 'storyEstimate', 'consumed', 'productivity', 'finishRate', 'participants', 'load', 'planRate', 'actualRate');
$config->pivot->story->cols['sprint']  = array('team', 'execution', 'storyCount', 'storyEstimate', 'consumed', 'productivity', 'finishRate', 'participants', 'load', 'planRate', 'actualRate');
$config->pivot->story->cols['project'] = array('program', 'project', 'storyCount', 'storyEstimate', 'consumed', 'productivity', 'finishRate');

$config->pivot->story->rateCols    = array('storyEstimate', 'consumed', 'productivity', 'finishRate', 'load', 'planRate', 'actualRate');
$config->pivot->story->percentCols = array('productivity', 'finishRate');

$config->pivot->bugCustom = new stdclass();
$config->pivot->bugCustom->filters = array();
$config->pivot->bugCustom->filters['team']            = array('class' => 'w-512', 'show' => array('team', 'sprint'),            'type' => 'select',    'required' => false, 'default' => array(),   'options' => 'team',        'multiple' => true);
$config->pivot->bugCustom->filters['executionName']   = array('class' => 'w-512', 'show' => array('sprint'), 'type' => 'select',    'required' => false, 'default' => array(),   'options' => 'sprint',     'multiple' => true);
$config->pivot->bugCustom->filters['executionStatus'] = array('class' => 'w-512', 'show' => array('sprint'), 'type' => 'select',    'required' => false, 'default' => array(),   'options' => 'executionStatus',     'multiple' => true);
$config->pivot->bugCustom->filters['bugType']         = array('class' => 'w-512', 'show' => array('team', 'sprint', 'project'), 'type' => 'select',    'required' => false, 'default' => array(),   'options' => 'bugType',     'multiple' => true);
$config->pivot->bugCustom->filters['date']            = array('class' => 'w-512', 'show' => array('team', 'project'), 'type' => 'dateRange', 'required' => true,  'default' => array('-2 weeks', 'today'));
$config->pivot->bugCustom->filters['program']         = array('class' => 'w-512', 'show' => array('project'), 'type' => 'select', 'required' => false,  'default' => array(), 'options' => 'program', 'multiple' => true);
$config->pivot->bugCustom->filters['project']         = array('class' => 'w-512', 'show' => array('project'), 'type' => 'select', 'required' => false,  'default' => array(), 'options' => 'project', 'multiple' => true);
$config->pivot->bugCustom->filters['projectStatus']   = array('class' => 'w-512', 'show' => array('project'), 'type' => 'select', 'required' => false,  'default' => array(), 'options' => 'projectStatus', 'multiple' => true);
$config->pivot->bugCustom->filters['proposalType']    = array('show' => array('project'),                             'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'proposalType', 'multiple' => true);

$config->pivot->cycle = new stdclass();
$config->pivot->cycle->filters = array();
$config->pivot->cycle->filters['team']          = array('class' => 'w-512', 'show' => array('team'),            'type' => 'select',    'required' => false, 'default' => array(),         'options' => 'team',          'multiple' => true);
$config->pivot->cycle->filters['program']       = array('class' => 'w-512', 'show' => array('project'),         'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'program',       'multiple' => true);
$config->pivot->cycle->filters['project']       = array('class' => 'w-512', 'show' => array('project'),         'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'project',       'multiple' => true);
$config->pivot->cycle->filters['projectStatus'] = array('show' => array('project'),                             'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'projectStatus', 'multiple' => true);
$config->pivot->cycle->filters['projectedType'] = array('show' => array('project'),                             'type' => 'select',    'required' => false, 'default' => array(), 'options' => 'projectedType', 'multiple' => true);
$config->pivot->cycle->filters['stageBegin']    = array('show' => array('team', 'project'),                     'type' => 'select',    'required' => true,  'default' => 'status_PRDReviewed',  'options' => 'storyStage',    'multiple' => false);
$config->pivot->cycle->filters['stageEnd']      = array('show' => array('team', 'project'),                     'type' => 'select',    'required' => true,  'default' => 'status_closed',  'options' => 'storyStage',    'multiple' => false);
$config->pivot->cycle->filters['bugOpen']       = array('show' => array('team', 'project'),                     'type' => 'select',    'required' => false,  'default' => array(),  'options' => 'defectedinversion',    'multiple' => true);
$config->pivot->cycle->filters['bugBegin']      = array('show' => array('team', 'project'),                     'type' => 'select',    'required' => true,  'default' => 'active',  'options' => 'bugStatus',    'multiple' => false);
$config->pivot->cycle->filters['bugEnd']        = array('show' => array('team', 'project'),                     'type' => 'select',    'required' => true,  'default' => 'closed',  'options' => 'bugStatus',    'multiple' => false);
$config->pivot->cycle->filters['date']          = array('class' => 'w-512', 'show' => array('team', 'project'), 'type' => 'dateRange', 'required' => true,  'default' => array('-2 weeks', 'today'));
$config->pivot->cycle->filters['type']          = array('class' => 'w-784', 'show' => array('team'),            'type' => 'select',    'required' => true,  'default' => array(),         'options' => 'storyType',     'multiple' => true);

$config->pivot->cycle->cols = array();
$config->pivot->cycle->cols['team']    = array('team', 'storyCount', 'storyCycle', 'bugCount', 'bugCycle');
$config->pivot->cycle->cols['project'] = array('program', 'project', 'storyCount', 'storyCycle', 'bugCount', 'bugCycle');

$config->pivot->cycle->rateCols = array('storyCycle', 'bugCycle');
