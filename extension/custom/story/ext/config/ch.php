<?php
$config->story->create->requiredFields = 'title,project';

$config->story->datatable->defaultFieldRequirement = ['business', 'residueEstimate'];

$config->story->datatable->fieldList['business']['title']    = 'business';
$config->story->datatable->fieldList['business']['fixed']    = 'left';
$config->story->datatable->fieldList['business']['width']    = '150';
$config->story->datatable->fieldList['business']['required'] = 'yes';
$config->story->datatable->fieldList['business']['type']     = 'html';
$config->story->datatable->fieldList['business']['name']     = $lang->story->business;

$config->story->datatable->fieldList['residueEstimate']['title']    = 'residueEstimate';
$config->story->datatable->fieldList['residueEstimate']['fixed']    = 'left';
$config->story->datatable->fieldList['residueEstimate']['width']    = '100';
$config->story->datatable->fieldList['residueEstimate']['required'] = 'yes';
$config->story->datatable->fieldList['residueEstimate']['type']     = 'html';
$config->story->datatable->fieldList['residueEstimate']['name']     = $lang->story->residueEstimate;

$config->story->datatable->fieldList['relatedRequirement']['title']    = 'relatedRequirement';
$config->story->datatable->fieldList['relatedRequirement']['fixed']    = 'left';
$config->story->datatable->fieldList['relatedRequirement']['width']    = '100';
$config->story->datatable->fieldList['relatedRequirement']['required'] = 'no';
$config->story->datatable->fieldList['relatedRequirement']['type']     = 'html';
$config->story->datatable->fieldList['relatedRequirement']['name']     = $lang->story->relatedRequirement;

$config->story->datatable->fieldList['actualConsumed']['title']    = 'actualConsumed';
$config->story->datatable->fieldList['actualConsumed']['fixed']    = 'left';
$config->story->datatable->fieldList['actualConsumed']['width']    = '100';
$config->story->datatable->fieldList['actualConsumed']['required'] = 'no';
$config->story->datatable->fieldList['actualConsumed']['type']     = 'html';
$config->story->datatable->fieldList['actualConsumed']['name']     = $lang->story->actualConsumed;
