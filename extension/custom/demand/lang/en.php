<?php
$lang->demand->common         = 'Demand';
$lang->demand->create         = 'Create Demand';
$lang->demand->batchCreate    = 'Batch Create';
$lang->demand->subdivide      = 'Subdivide';
$lang->demand->browse         = 'Demand List';
$lang->demand->edit           = 'Edit Demand';
$lang->demand->view           = 'Demand View';
$lang->demand->delete         = 'Delete Demand';
$lang->demand->review         = 'Review Demand';
$lang->demand->track          = 'Demand Track';
$lang->demand->submit         = 'Submit';
$lang->demand->export         = 'Export';
$lang->demand->tostory        = 'To Story';
$lang->demand->import         = 'Import';
$lang->demand->comment        = 'Comment';
$lang->demand->exportTemplate = 'Export Template';
$lang->demand->showImport     = 'Show Import';

$lang->demand->id           = 'ID';
$lang->demand->idAB         = 'ID';
$lang->demand->code         = 'Code';
$lang->demand->category     = 'Category';
$lang->demand->pri          = 'Priority';
$lang->demand->priAB        = 'P';
$lang->demand->severityAB   = 'S';
$lang->demand->severity     = 'Severity';
$lang->demand->demand       = 'Demand Pool';
$lang->demand->status       = 'Status';
$lang->demand->name         = 'Name';
$lang->demand->nameAB       = 'Name';
$lang->demand->module       = 'Module';
$lang->demand->source       = 'Source';
$lang->demand->sourceNote   = 'Source Note';
$lang->demand->createdBy    = 'Created By';
$lang->demand->createdDate  = 'Created Date';
$lang->demand->contact      = 'Contact';
$lang->demand->contactInfo  = 'Contact Info';
$lang->demand->group        = 'Group';
$lang->demand->owner        = 'Owner';
$lang->demand->deadline     = 'Deadline';
$lang->demand->desc         = 'Description';
$lang->demand->new          = 'New';
$lang->demand->assignedTo   = 'Assigned To';
$lang->demand->workload     = 'Workload(H)';
$lang->demand->mailto       = 'Mailto';
$lang->demand->feedbackBy   = 'Feedback By';
$lang->demand->email        = 'Email';
$lang->demand->mobile       = 'Mobile';
$lang->demand->reviewer     = 'Reviewer';
$lang->demand->result       = 'Review Result';
$lang->demand->allModule    = 'All Modules';
$lang->demand->noModule     = 'No Module';
$lang->demand->manageTree   = 'Manage Tree';
$lang->demand->manageChild  = 'Manage Child';
$lang->demand->chooseType   = 'Choose Type';
$lang->demand->next         = 'Next';
$lang->demand->storyType    = 'Demand Type';
$lang->demand->to           = 'To';
$lang->demand->URS          = "To {$lang->URCommon}";
$lang->demand->SRS          = "To {$lang->SRCommon}";

$lang->demand->storyTypeList['requirement'] = $lang->URCommon;
$lang->demand->storyTypeList['story']       = $lang->SRCommon;

$lang->demand->ditto = 'Ditto';

$lang->demand->info      = 'Information';
$lang->demand->basicInfo = 'Basic Info';

$lang->demand->importNotice = 'Please export the template first, fill in the data according to the template format and then import it.';
$lang->demand->noRequire    = '%s in line %s is a required field and cannot be empty.';

$lang->demand->priList[''] = '';
$lang->demand->priList[1]  = '1';
$lang->demand->priList[2]  = '2';
$lang->demand->priList[3]  = '3';
$lang->demand->priList[4]  = '4';

$lang->demand->severityList[''] = '';
$lang->demand->severityList[1] = 'Urgent';
$lang->demand->severityList[2] = 'Middle';
$lang->demand->severityList[3] = 'Normal';
$lang->demand->severityList[4] = 'Unimportance';

$lang->demand->categoryList['feature']     = 'Feature';
$lang->demand->categoryList['interface']   = 'Interface';
$lang->demand->categoryList['performance'] = 'Performance';
$lang->demand->categoryList['safe']        = 'Safe';
$lang->demand->categoryList['experience']  = 'Experience';
$lang->demand->categoryList['improve']     = 'Improve';
$lang->demand->categoryList['other']       = 'Other';

$lang->demand->sourceList['']           = '';
$lang->demand->sourceList['customer']   = 'Customer';
$lang->demand->sourceList['user']       = 'User';
$lang->demand->sourceList['po']         = 'Product Owner';
$lang->demand->sourceList['market']     = 'Market';
$lang->demand->sourceList['service']    = 'Service';
$lang->demand->sourceList['operation']  = 'Operation';
$lang->demand->sourceList['support']    = 'Support';
$lang->demand->sourceList['competitor'] = 'Competitor';
$lang->demand->sourceList['partner']    = 'Partner';
$lang->demand->sourceList['dev']        = 'Dev';
$lang->demand->sourceList['tester']     = 'Tester';
$lang->demand->sourceList['bug']        = 'Bug';
$lang->demand->sourceList['other']      = 'Other';

$lang->demand->statusList[''] = '';
$lang->demand->statusList['wait']   = 'Wait';
$lang->demand->statusList['refuse'] = 'Refuse';
$lang->demand->statusList['active'] = 'Active';
$lang->demand->statusList['closed'] = 'Closed';

$lang->demand->resultList['pass']   = 'Pass';
$lang->demand->resultList['refuse'] = 'Refuse';

$lang->demand->labelList = array();
$lang->demand->labelList['all']        = 'All';
$lang->demand->labelList['assigntome'] = 'Assign To Me';
$lang->demand->labelList['wait']       = 'Wait';
$lang->demand->labelList['refuse']     = 'Refuse';
$lang->demand->labelList['openedbyme'] = 'Opened By Me';
$lang->demand->labelList['closed']     = 'Closed';

$lang->demand->errorEmptyProduct = "『Product』can't be empty";

$lang->demand->action = new stdclass();
$lang->demand->action->reviewed  = array('main' => '$date, reviewed by <strong>$actor</strong>， result is $extra。', 'extra' => 'resultList');
$lang->demand->action->submited  = array('main' => '$date, submited by <strong>$actor</strong>.');
$lang->demand->action->tostory   = array('main' => '$date, to story $extra by <strong>$actor</strong>.');

$lang->demand->confirmDelete = 'Are you sure to delete this demand?';
$lang->demand->confirmSub    = 'Performing this operation will create a user requirement with the same name under the corresponding product, and the status of the requirement will change to Split, are you sure?';
