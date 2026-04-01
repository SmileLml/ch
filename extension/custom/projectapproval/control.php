 <?php
/**
 * The control file of projectapproval currentModule of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     projectapproval
 * @version     $Id: control.php 5107 2013-07-12 01:46:12Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class projectapproval extends control
{
    public function finishReport($projectapprovalID)
    {
        $projectapproval   = $this->projectapproval->getByID($projectapprovalID);

        $currentUserDept = $this->loadModel('dept')->getByID($this->app->user->dept);

        $architect     = $this->loadModel('user')->getUsersByUserGroupName($this->lang->projectapproval->architect);
        $PMO           = $this->loadModel('user')->getUsersByUserGroupName($this->lang->projectapproval->PMO);
        $leader        = $this->loadModel('user')->getUsersByUserGroupName($this->lang->projectapproval->leader);
        $projectmember = $this->dao->select('id, account')->from('zt_flow_projectmembers')->where('parent')->eq($projectapprovalID)->fetchPairs('id');

        $isCorrectGroup = (in_array($this->app->user->account, array_keys($architect)) || in_array($this->app->user->account, array_keys($PMO)) || in_array($this->app->user->account, array_keys($leader)));

        if(!$this->app->user->admin && !$isCorrectGroup && !in_array($this->app->user->account, $projectmember) && $this->app->user->account != $projectapproval->businessPM && strpos($currentUserDept->path, $projectapproval->responsibleDept) === false)
        {
            $response['result']  = 'fail';
            $response['message'] = $this->lang->projectapproval->noAccessFinishReport;
            $this->send($response);
        }


        $flow   = $this->loadModel('workflow', 'flow')->getByModule('projectapproval');
        $action = $this->loadModel('workflowaction', 'flow')->getByModuleAndAction($flow->module, 'approvalreview5');

        $data   = $this->loadModel('flow')->getDataByID($flow, $projectapprovalID);
        $fields = $this->loadModel('workflowaction', 'flow')->getFields($flow->module, $action->action, true, $data);

        $this->setFinishReportRelevanceData($projectapprovalID, $data, $fields, $flow);
        $this->setFlowChild($flow->module, $action->action, $fields, $projectapprovalID);

        $this->view->title             = $this->lang->common->finishReport;
        $this->view->projectapprovalID = $projectapprovalID;
        $this->view->projectapproval   = $projectapproval;
        $this->view->data              = $data;
        $this->view->fields            = $fields;
        $this->display();
    }

    /**
     * Set children of a flow.
     *
     * @param  string $module
     * @param  string $action
     * @param  array  $fields
     * @param  int    $dataID
     * @access public
     * @return void
     */
    public function setFlowChild($module, $action, $fields, $dataID = 0)
    {
        $childFields  = array();
        $childDatas   = array();
        $childModules = $this->loadModel('workflow', 'flow')->getList('browse', 'table', '', $module);

        foreach($childModules as $childModule)
        {
            $key = 'sub_' . $childModule->module;

            if(isset($fields[$key]) && $fields[$key]->show)
            {
                $childData = [];
                if($dataID) $childData = $this->flow->getDataList($childModule, '', 0, '', $dataID, 'id_asc');
                $childFields[$key] = $this->workflowaction->getFields($childModule->module, $action, true, $childData);
                $childDatas[$key]  = $childData;
            }
        }

        if($module != 'projectapproval' && $action != 'view') list($childFields, $childDatas) = $this->addCostFieldForChild($childFields, $childDatas);

        $this->view->childFields = $childFields;
        $this->view->childDatas  = $childDatas;
    }

    /**
     * Add cost fields for child.
     * @param $childFields array
     * @param $childDatas  array
     * @return array
     */
    public function addCostFieldForChild($childFields, $childDatas)
    {
        $newProjectcost = array();

        foreach($childFields['sub_projectcost'] as $projectcostKey => $projectcost)
        {
            $newProjectcost[$projectcostKey] = $projectcost;
            if($projectcostKey == 'actualExpend')
            {
                $actualExpend = new stdClass();
                $actualExpend->field   = 'percentageDifference';
                $actualExpend->width   = '150';
                $actualExpend->name    = $this->lang->projectapproval->percentageDifference;
                $actualExpend->show    = 1;
                $actualExpend->options = array();
                $newProjectcost['percentageDifference'] = $actualExpend;
            }
        }
        $childFields['sub_projectcost'] = $newProjectcost;

        foreach($childDatas['sub_projectcost'] as $projectcostKey => $projectcost)
        {
            $childDatas['sub_projectcost'][$projectcostKey]->percentageDifference = number_format(($projectcost->actualExpend - $projectcost->costBudget)/$projectcost->costBudget*100, 2). '%';
        }

        return array($childFields, $childDatas);
    }

    /**
     * Set finish report relevance data.
     * @param $dadaID int
     * @param $data   object
     * @param $fields array
     * @param $flow   object
     */
    public function setFinishReportRelevanceData($dataID, $data, $fields, $flow)
    {
        $project           = $this->dao->select('*')->from('zt_project')->where('instance')->eq($dataID)->fetch();
        $storyIdList       = $this->dao->select('story')->from('zt_projectstory')->where('project')->eq($project->id)->fetchPairs('story');
        $practicalBegin    = $this->dao->select('openedDate')->from('zt_story')->where('id')->in($storyIdList)->andWhere('type')->eq('requirement')->orderBy('id_asc')->fetch('openedDate');
        $businessIdList    = $this->dao->select('parent, business')->from('zt_flow_projectbusiness')->where('parent')->eq($dataID)->andWhere('deleted')->eq(0)->fetchPairs('parent');
        $practicalEnd      = $this->dao->select('closeDate')->from('zt_flow_business')->where('id')->in($businessIdList)->orderBy('closeDate_desc')->fetch('closeDate');
        $allBugNum         = $this->dao->select('count(1) as number')->from('zt_bug')->where('project')->eq($project->id)->andWhere('deleted')->eq(0)->fetch('number');
        $unsolvedBugNum    = $this->dao->select('count(1) as number')->from('zt_bug')->where('project')->eq($project->id)->andWhere('deleted')->eq(0)->andWhere('status')->eq('active')->fetch('number');
        $onlineBugNum      = $this->dao->select('count(1) as number')->from('zt_bug')->where('project')->eq($project->id)->andWhere('deleted')->eq(0)->andWhere('defectedinversion')->eq('4')->fetch('number');
        $sumEstimate       = $this->dao->select('sum(estimate) as sumEstimate')->from('zt_story')->where('id')->in($storyIdList)->andWhere('type')->eq('requirement')->andWhere('deleted')->eq(0)->fetch('sumEstimate');
        $onlineBugprogress = (float)$sumEstimate == 0 ? '0%' : number_format((int)$onlineBugNum/(float)$sumEstimate*100, 2) . '%';

        $projectUnitPrice = $this->loadModel('setting')->getItem("owner=system&module=common&section=&key=projectUnitPrice");

        if($practicalEnd == '0000-00-00 00:00:00')
        {
            $progressDeviation = '';
            $practicalEnd      = '';

        }
        else
        {
            $deviationBegin    = $data->end > $practicalEnd ? $practicalEnd : $data->end;
            $deviationEnd      = $data->end > $practicalEnd ? $data->end : $practicalEnd;
            $workDays          = $this->loadModel('holiday')->getActualWorkingDays($deviationBegin, $deviationEnd);
            $progressDeviation = $data->end > $practicalEnd ? '+' . (string)count($workDays) : '-' . (string)count($workDays);
        }


        $allChangeNum = 0;
        $changeTypeNum = array();
        foreach($fields['changeType']->options as $changeTypeKey => $changeType)
        {
            if(!empty($changeType)) $changeTypeNum[$changeTypeKey] = 0;
        }

        $tempActions = $this->loadModel('action')->getList($flow->module, $data->id);

        $reviewDetails       = array();
        $reviewKey           = 0;
        $previousChangeTypes = array();
        foreach($tempActions as $tempAction)
        {
            if($tempAction->action == 'approvalreview3')
            {
                $realAction = false;
                foreach($tempAction->history as $historyItem)
                {
                    if($historyItem->field == 'changeType')
                    {
                        $allChangeNum += 1;
                        $realAction = true;
                    }
                }

                if($realAction)
                {
                    $isChangeTypes = false;
                    foreach($tempAction->history as $historyItem)
                    {
                        if($historyItem->field == 'changeType')
                        {
                            $changeTypes         = explode(',', $historyItem->new);
                            $previousChangeTypes = $changeTypes;
                            $isChangeTypes       = true;
                            foreach($changeTypes as $changeType) $changeTypeNum[$changeType] += 1;
                        }
                    }

                    if(!$isChangeTypes)
                    {
                        foreach($previousChangeTypes as $changeType) $changeTypeNum[$changeType] += 1;
                    }
                }
            }

            if($tempAction->action == 'approvalsubmit5')
            {
                $reviewDetails  = array();
                $reviewKey      = 0;
            }

            if($tempAction->action == 'approvalreview5' )
            {

                $realAction = false;
                foreach($tempAction->history as $historyItem)
                {
                    if($historyItem->field == 'status')
                    {
                        $realAction = false;
                        break;
                    }
                    if($historyItem->field == 'reviewResult') $realAction = true;
                }

                if($realAction)
                {
                    $reviewDetail = new stdClass();
                    $reviewer     = $this->loadModel('user')->getByQuery('inside', "account = '{$tempAction->actor}'");
                    $depts        = $this->loadModel('dept')->getOptionMenu();

                    $reviewDetail->reviewer     = zget($this->loadModel('user')->getPairs('noletter'), $tempAction->actor);
                    $reviewDetail->reviewDept   = zget($depts, $reviewer[0]->dept);
                    $reviewDetail->reviewResult = zget($fields['reviewResult']->options, 'pass');

                    foreach($tempAction->history as $historyItem)
                    {
                        if($historyItem->field == 'reviewDate')       $reviewDetail->reviewDate = $historyItem->new;
                        if($historyItem->field == 'remark')           $reviewDetail->remark = $historyItem->new;
                        if($historyItem->field == 'finishReviewDate') $reviewDetail->reviewDate = $historyItem->new;
                    }
                    if($reviewKey == 0)
                    {
                        if(empty($reviewDetail->reviewDate)) $reviewDetail->reviewDate = $data->finishReviewDate;
                        if(empty($reviewDetail->remark))     $reviewDetail->remark = $data->remark;
                    }
                    else
                    {
                        if(empty($reviewDetail->reviewDate)) $reviewDetail->reviewDate = $reviewDetails[$reviewKey-1]->reviewDate;
                        if(empty($reviewDetail->remark))     $reviewDetail->remark = $reviewDetails[$reviewKey-1]->remark;
                    }
                    $reviewDetails[$reviewKey] = $reviewDetail;
                    $reviewKey = $reviewKey + 1;

                }
            }
        }

        $this->view->project           = $project;
        $this->view->practicalBegin    = $practicalBegin;
        $this->view->practicalEnd      = $practicalEnd;
        $this->view->allBugNum         = $allBugNum;
        $this->view->unsolvedBugNum    = $unsolvedBugNum;
        $this->view->onlineBugNum      = $onlineBugNum;
        $this->view->sumEstimate       = $sumEstimate;
        $this->view->progressDeviation = $progressDeviation;
        $this->view->onlineBugprogress = $onlineBugprogress;
        $this->view->allChangeNum      = $allChangeNum;
        $this->view->changeTypeNum     = $changeTypeNum;
        $this->view->reviewDetails     = $reviewDetails;
    }


    public function business($projectapprovalID, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $module = 'business';
        $action = 'browse';

        $this->loadModel('flow');
        $this->loadModel('business');
        $this->loadModel('project');

        $this->session->set('businessViewBackUrl', helper::createLink('projectapproval', 'business', 'projectapprovalID=' . $projectapprovalID));

        $this->app->loadClass('pager', $static = true);
        $projectapproval   = $this->projectapproval->getByID($projectapprovalID);
        $pager             = new pager($recTotal, $recPerPage, $pageID);
        $flow              = $this->loadModel('workflow', 'flow')->getByModule($module);
        $dataList          = $this->projectapproval->getBusinessListByProjectapprovalID($projectapprovalID, $orderBy, $pager);
        $fields            = $this->loadModel('workflowaction', 'flow')->getFields($module, $action, true, $dataList);

        $fields['createdDate']->width = 110;
        $fields['createdBy']->width   = 75;
        $fields['actions']->width     = 120;
        if(isset($fields['project']))
        {
            $fields['project']->options = $this->dao->select('id, name')->from('zt_flow_projectapproval')->fetchPairs('id', 'name');
            $fields['project']->show    = 0;
        }


        $newFields = array();
        foreach($fields as $fieldKey => $field)
        {
            if($fieldKey == 'developmentBudget')
            {
                $newFields['estimate'] = clone $fields['name'];
                $newFields['estimate']->field = 'estimate';
                $newFields['estimate']->name  = $this->lang->project->estimate;
                $newFields['estimate']->width = 100;
            }
            $newFields[$fieldKey] = $field;
        }

        $dataList = $this->loadModel('projectapproval')->processBusiness($dataList, 'id');

        $infoAttache = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoAttache);
        $infoLeqader = $this->loadModel('user')->getUsersByUserGroupName($this->lang->flow->infoLeqader);

        $this->view->title             = $this->lang->common->business;
        $this->view->dataList          = $dataList;
        $this->view->summary           = $this->flow->getSummary($dataList, $fields);
        $this->view->action            = $action;
        $this->view->orderBy           = $orderBy;
        $this->view->pager             = $pager;
        $this->view->fields            = $newFields;
        $this->view->flow              = $flow;
        $this->view->projectapprovalID = $projectapprovalID;
        $this->view->projectapproval   = $projectapproval;
        $this->view->infoAttache       = $infoAttache;
        $this->view->infoLeqader       = $infoLeqader;

        $this->display();
    }

    public function linkBusiness($projectapprovalID)
    {
        $businessList = $this->projectapproval->getProjectapprovalBusiness();
        if($_POST)
        {
            $this->loadModel('flow');

            $projectapproval = $this->projectapproval->getByID($projectapprovalID);

            if($projectapproval->status != 'draft')
            {
                $businessIds       = array_filter($_POST['business']);
                $uniqueBusinessIds = array_filter(array_unique($businessIds));

                if(empty($uniqueBusinessIds)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectbusiness' => $this->lang->flow->emptyBusiness)));
                if(count($uniqueBusinessIds) < count($businessIds)) return $this->send(array('result' => 'fail', 'message' => array('sub_projectbusiness' => $this->lang->flow->sameBusiness)));

                $linkedbusinessIds    = $this->projectapproval->getBusinessIdsByProjectapprovalID($projectapprovalID);
                $developmentBudget          = $this->dao->select("developmentBudget")->from('zt_flow_business')->where('id')->in($uniqueBusinessIds + $linkedbusinessIds)->fetchAll();
                $developmentBudgetReviewing = $this->dao->select("developmentBudget")->from('zt_flow_business')->where('project')->eq($projectapprovalID)->andWhere('status')->eq('conformReviewing')->fetchAll();
                $allDevelopmentBudget       = array_sum(array_map(function($budget){ return (int)$budget->developmentBudget; }, $developmentBudget));
                $allDevelopmentBudget      += array_sum(array_map(function($budget){ return (int)$budget->developmentBudget; }, $developmentBudgetReviewing));
                $costBudget = $this->dao->select("costBudget")->from('zt_flow_projectcost')->where('parent')->eq($projectapprovalID)->andWhere('costType')->eq('itPlanInto')->fetchAll();
                $allCostBudget = array_sum(array_map(function($budget){ return (int)$budget->costBudget; }, $costBudget));

                if($allDevelopmentBudget > $allCostBudget) return $this->send(array('result' => 'fail', 'message' => array(('childrensub_projectcostcostBudget' . $key) => sprintf($this->lang->flow->overSetBudget1, $allDevelopmentBudget - $allCostBudget))));
            }

            $this->projectapproval->linkBusiness($projectapprovalID);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->loadModel('flow')->mergeVersionByObjectType($projectapprovalID, 'projectapproval');

            return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'parent'));
        }
        $this->view->title             = $this->lang->linkBusiness;
        $this->view->businessList      = empty($businessList) ? array() : array('' => '') + $businessList;
        $this->view->projectapprovalID = $projectapprovalID;

        $this->display();
    }

    /**
     * Add export pdf css.
     */
    public function addExportPdfCss($exportHtml)
    {
        $pdfCss = <<<EOT
        table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #A6A6A6;
        table-layout: fixed;
        }

        th, td {
        border: 1px solid #A6A6A6;
        padding: 8px;
        text-align: left;
        word-wrap: break-word;
        vertical-align: top;
        height: 18px;
        min-width: 50px;
        }
        h1, h2 {
        text-align: center;
        }
        </style>
        EOT;

        $exportHtml = str_replace('</style>', $pdfCss, $exportHtml);
        return $exportHtml;
    }

    /**
     * Export word.
     *
     * @param  int    $projectapprovalID
     * @param  string $exportVersion
     *
     * @access public
     * @return mixed
     */
    public function exportReportWord($projectapprovalID, $exportVersion = '', $type = 'word')
    {
        $this->app->loadClass('phpword', true);
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $phpWord->getCompatibility()->setOoxmlVersion(12);
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::ZH_CN));

        $phpWord->setDefaultFontName('宋体');
        $phpWord->setDefaultFontSize(12);
        $phpWord->getSettings()->setUpdateFields(true);

        // 添加文档属性
        $headerName = $this->lang->projectapproval->approvalReport;
        $phpWord->getDocInfo()->setCreator($this->app->user->realname)->setTitle($headerName)->setLastModifiedBy('ZenTao System');

        list($data, $fields, $childFields, $childDatas, $relations) = $this->getProjectApprovalData($projectapprovalID, $exportVersion);

        $section       = $phpWord->addSection(['marginLeft' => 1200, 'marginRight' => 1200, 'marginTop' => 1440, 'marginBottom' => 1440]);
        $exportVersion = $exportVersion ? $exportVersion : $data->version;
        $wordTitle     = $data->name . '#' . $exportVersion;

        // 添加标题
        $phpWord->addTitleStyle(1, ['paragraph' => ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]] + $this->config->projectapproval->headerTitleStyle);
        $section->addTitle($wordTitle, 1);

        // 添加页眉
        $header = $section->addHeader();
        $header->addText('(' . $this->lang->word->headNotice . ')' . (common::checkNotCN() ? ' ' . $this->lang->word->visitZentao : ''), ['color' => '3F3F3F', 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

        if(!helper::isZeroDate($data->projectReviewDate))
        {
            $reviewData = $this->projectapproval->getProjectApprovalCostData($data, $childDatas);
            $this->createWord($phpWord, $section, $reviewData, $fields, $childFields, $childDatas, $relations, $this->config->projectapproval->exportReviewWordFields);
            $section->addPageBreak();
        }

        // 生成主内容
        $this->createWord($phpWord, $section, $data, $fields, $childFields, $childDatas, $relations);
        $this->createProjectCustomWord($phpWord, $section, $data, $fields, $relations);

        if($type == 'word')
        {
            //输出设置
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . rawurlencode($headerName) . '.docx"');
            header('Cache-Control: max-age=0');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Pragma: public');
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save('php://output');
            exit;
        }
        elseif($type == 'pdf')
        {
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->getContent();
            $objWriter = preg_replace("/<td>\s*<\/td>/", '<td style="height:18px;"></td>', $objWriter);
            $objWriter = $this->addExportPdfCss($objWriter);

            $cpdf = $this->app->loadClass('cpdf');
            $pdf  = $cpdf->getMpdf();
            ini_set('pcre.backtrack_limit', strlen($objWriter));

            $pdf->WriteHTML($objWriter);
            $pdf->Output();
        }
    }

    public function createWord($phpWord, $section, $data, $fields, $childFields, $childDatas, $relations, $exportFields = '')
    {
        if(!$exportFields) $exportFields = $this->config->projectapproval->exportApprovalReportFields;

        foreach(explode(',', $exportFields) as $field)
        {
            if(in_array($field, ['projectvalue', 'projectcost', 'projectmembers', 'projectreviewdetails']))
            {
                $this->handleChildTable($phpWord, $section, $field, $fields, $childFields, $childDatas, $relations);
            }
            elseif($field == 'projectbusiness')
            {
                $this->createBusinessCustomWord($phpWord, $section, $childFields, $childDatas);
            }
            else
            {
                $this->createVerticalContent($phpWord, $section, $field, $data, $fields, $relations);
            }
        }
    }

    public function handleChildTable($phpWord, $section, $field, $fields, $childFields, $childDatas, $relations)
    {
        $section->addTextBreak(1);

        $childTable = 'sub_' . $field;
        if(!isset($childDatas[$childTable]) || empty($childDatas[$childTable])) return;

        $titleName = $fields[$childTable]->name;
        $phpWord->addTitleStyle(2, ['paragraph' => ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]] + $this->config->projectapproval->subTitleStyle);
        $section->addTitle($titleName);

        $childDataList = $childDatas[$childTable];
        $exportChildFields = explode(',', $this->config->projectapproval->exportChildFields[$field]);

        // 定义表格样式 - 更兼容Office 2007
        $subTableStyle = ['unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT];
        $tableStyle    = $this->config->projectapproval->subTableStyle + $subTableStyle;

        // 定义单元格样式
        $headerCellStyle = $this->config->projectapproval->headerCellStyle;

        // 创建表格
        $table = $section->addTable($tableStyle);

        // 添加标题行（加粗）
        $headerRow = [];
        foreach($exportChildFields as $childField) $headerRow[] = $childFields[$childTable][$childField]->name; // 获取字段标题


        $table->addRow();
        foreach($headerRow as $headerCell) $table->addCell(null, $headerCellStyle)->addText($headerCell, ['bold' => true]);

        // 添加数据行
        foreach($childDataList as $childData)
        {
            $table->addRow();
            foreach($exportChildFields as $childField)
            {
                $cellValue = $this->projectapproval->getValueByWorkFlow($childFields[$childTable][$childField], $childData, zget($relations, $childField, ''));

                // 处理空值
                if(empty($cellValue)) $cellValue = '-';

                $table->addCell()->addText($cellValue);
            }
        }

        // 表格后添加空行
        $section->addTextBreak(1);
    }

    public function createVerticalContent($phpWord, $section, $field, $content, $fields, $relations)
    {
        $control   = $fields[$field]->control;
        $titleName = $fields[$field]->name;

        if(in_array($field, ['businessInto', 'itPlanInto', 'purchasingBudget', 'itCost']))
        {
            $control   = 'input';
            $titleName = $this->lang->projectapproval->$field;
        }

        $tableStyle       = ['unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT] + $this->config->projectapproval->tableStyle;
        $titleCellStyle   = $this->config->projectapproval->titleCellStyle;
        $contentCellStyle = $this->config->projectapproval->contentCellStyle;

        if($control == 'richtext')
        {
            $table = $section->addTable($tableStyle);

            $table->addRow();
            $titleCell = $table->addCell(null, $titleCellStyle);
            $titleCell->addText($titleName . '：', ['bold' => true]);

            $table->addRow();
            $contentCell = $table->addCell(null, $contentCellStyle);
            $pauseHtml   = $this->pauseHtmlTag($content->$field);

            \PhpOffice\PhpWord\Shared\Html::addHtml($contentCell, $pauseHtml, false, true);
        }
        else
        {
            $fieldValue = $this->projectapproval->getValueByWorkFlow($fields[$field], $content, zget($relations, $field, ''));
            $table      = $section->addTable($tableStyle);

            $table->addRow();
            $cell = $table->addCell(null, $titleCellStyle);
            $cell->addText($titleName . '：', ['bold' => true]);

            $table->addRow();
            $cell = $table->addCell(null, $contentCellStyle);
            $cell->addText($fieldValue, null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        }
    }

    public function createBusinessCustomWord($phpWord, $section, $childFields, $childDatas)
    {
        if(!isset($childDatas['sub_projectbusiness']) || empty($childDatas['sub_projectbusiness'])) return;

        $section->addTextBreak(1);

        $flow           = $this->loadModel('workflow', 'flow')->getByModule('business');
        $action         = $this->loadModel('workflowaction', 'flow')->getByModuleAndAction($flow->module, 'view');
        $fields         = $this->loadModel('workflowaction', 'flow')->getFields($flow->module, $action->action);
        $relations      = $this->loadModel('workflowrelation', 'flow')->getPrevList($flow->module);
        $businessIdList = array_column($childDatas['sub_projectbusiness'], 'business');
        $businesses     = $this->dao->select('*')->from('zt_flow_business')->where('id')->in($businessIdList)->fetchAll();

        // 获取自定义字段
        $customFields = array_keys(array_filter($fields, function ($item) {
            return $item->position === 'info' && $item->field != 'files' && $item->field != 'name';
        }));

        foreach(explode(',', $this->config->projectapproval->exportChildFields['projectbusiness']) as $exportField)
        {
            if(in_array($exportField, $customFields)) unset($customFields[array_search($exportField, $customFields)]);
        }

        $this->loadModel('flow');

        // 添加标题样式
        $phpWord->addTitleStyle(2, ['paragraph' => ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]] + $this->config->projectapproval->subTitleStyle);

        $subTableStyle = ['unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT];
        $tableStyle    = $this->config->projectapproval->subTableStyle + $subTableStyle;

        // 定义单元格样式
        $headerCellStyle = $this->config->projectapproval->headerCellStyle;

        // 处理分组字段
        $exportBusinessFields = $this->config->projectapproval->exportChildFields['projectbusiness'] . ',' . implode(',', $customFields);
        $groupedFields        = array_chunk(explode(',', $exportBusinessFields), 1);                                                       // 每组2个字段

        foreach($businesses as $business)
        {
            // 添加业务标题
            $section->addTitle($business->name, 2);

            // 创建主表格
            $table = $section->addTable($tableStyle);

            // 添加数据行
            foreach($groupedFields as $exportChildField)
            {
                $table->addRow();

                foreach($exportChildField as $childField)
                {
                    if(isset($fields[$childField]))
                    {
                        $fieldData  = $fields[$childField];
                        $fieldValue = $business->$childField;

                        if($fieldData->control == 'multi-select' || $fieldData->control == 'select') $fieldValue = $this->projectapproval->getValueByWorkFlow($fieldData, $business, zget($relations, $childField, ''));

                        // 处理空值
                        if(empty($fieldValue)) $fieldValue = '-';

                        // 添加字段标题单元格
                        $table->addCell(500 * 5, $headerCellStyle)->addText($fieldData->name . '：', ['bold' => true]);

                        // 添加字段值单元格
                        if($fieldData->control == 'richtext')
                        {
                            $contentCell = $table->addCell();
                            $pauseHtml   = $this->pauseHtmlTag($fieldValue);
                            \PhpOffice\PhpWord\Shared\Html::addHtml($contentCell, $pauseHtml, false, true);
                        }

                        if($fieldData->control != 'richtext') $table->addCell()->addText($fieldValue);
                    }
                }
            }
        }

        $section->addTextBreak(1);
    }

    public function pauseHtmlTag($text)
    {
        $processedText = $text;

        // 简化标签结构，去除多余的嵌套标签
        $processedText = preg_replace('/<span[^>]*><strong[^>]*>(.*?)<\/strong><\/span>/is', '<strong>$1</strong>', $processedText);
        $processedText = preg_replace('/<strong[^>]*><span[^>]*>(.*?)<\/span><\/strong>/is', '<strong>$1</strong>', $processedText);

        // 将<strong>和<b>标签统一为一种
        $processedText = str_replace(['<b>', '</b>'], ['<strong>', '</strong>'], $processedText);

        // 合并相邻的相同标签
        $processedText = preg_replace('/<\/strong><strong>/i', '', $processedText);
        $processedText = preg_replace('/<\/span><span>/i', '', $processedText);

        // 处理表格，确保每个单元格有合适的宽度
        $processedText = preg_replace_callback('/<table\b[^>]*>(.*?)<\/table>/is', function($matches)
        {
            $tableContent = $matches[0];

            // 检查是否包含table-kindeditor类，这是需要特殊处理的表格
            if(strpos($tableContent, 'table-kindeditor') !== false)
            {
                // 计算表格中的列数
                preg_match('/<tr[^>]*>(.*?)<\/tr>/is', $tableContent, $firstRowMatch);
                if(isset($firstRowMatch[1]))
                {
                    $tdColumnCount = substr_count($firstRowMatch[1], '<td');
                    $thColumnCount = substr_count($firstRowMatch[1], '<th');

                    // 如果有列，设置每列的宽度
                    if($tdColumnCount > 0 || $thColumnCount > 0)
                    {
                        $colWidth = floor(100 / ($tdColumnCount + $thColumnCount));

                        // 替换所有的th标签，添加宽度属性
                        $tableContent = preg_replace(
                            '/<th\b([^>]*?)style="([^"]*)"([^>]*?)>/is',
                            '<th$1style="$2width:' . $colWidth . '%;min-width:50px;"$3>',
                            $tableContent
                        );

                        // 替换所有的td标签，添加宽度属性
                        $tableContent = preg_replace(
                            '/<td\b([^>]*?)style="([^"]*)"([^>]*?)>/is',
                            '<td$1style="$2width:' . $colWidth . '%;min-width:50px;"$3>',
                            $tableContent
                        );

                        // 处理没有style属性的td
                        $tableContent = preg_replace(
                            '/<td\b(?![^>]*?style=)([^>]*?)>/is',
                            '<td style="width:' . $colWidth . '%;min-width:50px;"$1>',
                            $tableContent
                        );
                    }
                }
            }

            return $tableContent;
        }, $processedText);

        // 匹配所有img标签（加强版正则）
        preg_match_all('/<img\b(?:\s+[^>]*?)?src=(["\']?)(.*?)\1(?:\s+[^>]*?)?\/?>/is', $processedText, $matches, PREG_SET_ORDER);

        foreach($matches as $match)
        {
            $originalTag = $match[0];
            $originalSrc = trim($match[2]);

            // 检查原始标签是否包含width属性
            $hasWidth = preg_match('/width=(["\']?)(\d+)\1/i', $originalTag, $widthMatches);

            // 获取处理后的路径
            $newSrc = $this->addImage($originalSrc);

            // 如果图片存在，替换为新的img标签，否则移除原标签
            if(!empty($newSrc) && file_exists($newSrc))
            {
                // 获取图片尺寸
                list($width, $height) = @getimagesize($newSrc);

                // 设置图片宽度
                if($hasWidth)
                {
                    // 如果原标签有width属性，则使用原width和400的较小值
                    $originalWidth = (int)$widthMatches[2];
                    $imgWidth = $originalWidth > 400 ? 400 : $originalWidth;
                }
                else
                {
                    // 如果原标签没有width属性，则设置为800，但如果实际宽度小于800则使用实际宽度
                    $imgWidth = $width > 800 ? 800 : $width;
                }

                // 创建新的img标签，设置合适的宽度和高度
                $newTag = '<img src="' . $newSrc . '" width="' . $imgWidth . '" style="max-width:100%;" />';
                $processedText = str_replace($originalTag, $newTag, $processedText);
            }
            else
            {
                // 图片不存在，移除原标签
                $processedText = str_replace($originalTag, '', $processedText);
            }
        }

        return $processedText;
    }

    /**
     * Add image
     *
     * @param  int    $path
     * @param  int    $inline
     * @access public
     * @return void
     */
    public function addImage($path, $inline = false)
    {
        // 处理动态路径格式 {id.ext}
        if(preg_match('/^{(\d+)(?:\.(\w+))?}$/', $path, $matches))
        {
            $this->loadModel('file');
            $file = $this->file->getById($matches[1]);
            if (!$file) return null;

            $realFilePath = $this->file->saveAsTempFile($file);
            $extension    = $matches[2] ?? $file->extension;

            return $realFilePath ?: null;
        }

        // 处理静态路径
        $basePath     = rtrim($this->config->word->filePath, '/') . '/';
        $realFilePath = $basePath . ltrim($path, '/');

        // 验证路径有效性
        if(!file_exists($realFilePath))
        {
            error_log("Image path invalid: " . $realFilePath);
            return null;
        }

        return realpath($realFilePath); // 返回绝对路径
    }

    public function createProjectCustomWord($phpWord, $section, $data, $fields, $relations)
    {
        $customFields = array_keys(array_filter($fields, function ($item) {
            return $item->position === 'info';
        }));

        $exportApprovalReportFields = explode(',', $this->config->projectapproval->exportReviewWordFields . ',' . $this->config->projectapproval->exportApprovalReportFields . ',sub_projectreviewdetails,sub_projectbusiness,sub_projectmembers,sub_projectcost,sub_projectvalue,files');

        foreach($exportApprovalReportFields as $exportField)
        {
            if(in_array($exportField, $customFields)) unset($customFields[array_search($exportField, $customFields)]);
        }

        foreach($customFields as $field) $this->createVerticalContent($phpWord, $section, $field, $data, $fields, $relations);
    }

    /**
     * Get project approval data.
     *
     * @param  int    $projectapprovalID
     * @param  string $version
     * @access public
     * @return mixed
     */
    public function getProjectApprovalData($projectapprovalID, $version = '')
    {
        $flow      = $this->loadModel('workflow', 'flow')->getByModule('projectapproval');
        $action    = $this->loadModel('workflowaction', 'flow')->getByModuleAndAction($flow->module, 'view');
        $data      = $this->loadModel('flow')->getDataByID($flow, $projectapprovalID);
        $fields    = $this->loadModel('workflowaction', 'flow')->getFields($flow->module, $action->action, true, $data);
        $relations = $this->loadModel('workflowrelation', 'flow')->getPrevList($flow->module);

        $this->setFlowChild($flow->module, $action->action, $fields, $projectapprovalID);

        $childFields = $this->view->childFields;
        $childDatas  = $this->view->childDatas;

        if($version) list($data, $childDatas) = $this->projectapproval->getProjectApprovalVersionData($data, $version);

        return [$data, $fields, $childFields, $childDatas, $relations];
    }

    /**
     * Change status by business.
     *
     * @access public
     * @return mixed
     */
    public function changeStatusByBusiness()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->projectapproval->changeStatusByBusiness();

        echo 'success';
    }

    /**
     * Update project approval date.
     *
     * @access public
     * @return mixed
     */
    public function updateProjectApprovalDate()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->projectapproval->updateProjectApprovalDate();

        echo 'success';
    }

    public function updateErrorVersion()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->projectapproval->updateErrorVersion();

        echo 'success';
    }

    public function updateProjectProgram()
    {
        ignore_user_abort(true);
        set_time_limit(600);
        session_write_close();

        $this->projectapproval->updateProjectProgram();

        echo 'success';
    }

    public function updateReviewFields()
    {
        $this->projectapproval->updateReviewFields();
    }
}
