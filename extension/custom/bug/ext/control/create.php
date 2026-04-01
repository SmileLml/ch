<?php
helper::importControl('bug');
class mybug extends bug
{
    /**
     * Create a bug.
     *
     * @param  int    $productID
     * @param  string $branch
     * @param  string $extras       others params, forexample, executionID=10, moduleID=10
     * @param  int    $chprojectID
     * @param  int    $copyBugID
     * @access public
     * @return void
     */
    public function create($productID, $branch = '', $extras = '', $chprojectID = 0, $copyBugID = 0)
    {
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        /* Unset discarded types. */
        foreach($this->config->bug->discardedTypes as $type) unset($this->lang->bug->typeList[$type]);

        /* Whether there is a object to transfer bug, for example feedback. */
        $extras = str_replace(array(',', ' '), array('&', ''), $extras);
        parse_str($extras, $output);
        $from = isset($output['from']) ? $output['from'] : '';

        if($this->app->tab == 'execution')
        {
            if(isset($output['executionID'])) $this->loadModel('execution')->setMenu($output['executionID']);
            $execution = $this->dao->findById($this->session->execution)->from(TABLE_EXECUTION)->fetch();
            if($execution->type == 'kanban')
            {
                $this->loadModel('kanban');
                $regionPairs = $this->kanban->getRegionPairs($execution->id, 0, 'execution');
                $regionID    = !empty($output['regionID']) ? $output['regionID'] : key($regionPairs);
                $lanePairs   = $this->kanban->getLanePairsByRegion($regionID, 'bug');
                $laneID      = isset($output['laneID']) ? $output['laneID'] : key($lanePairs);

                $this->view->executionType = $execution->type;
                $this->view->regionID      = $regionID;
                $this->view->laneID        = $laneID;
                $this->view->regionPairs   = $regionPairs;
                $this->view->lanePairs     = $lanePairs;
            }
        }
        else if($this->app->tab == 'project')
        {
            if(isset($output['projectID'])) $this->loadModel('project')->setMenu($output['projectID']);
        }
        else if($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject');
            $this->chproject->setMenu($chprojectID);

            if(isset($output['projectID']) && $output['projectID'])
            {
                $project = $this->loadModel('project')->getById($output['projectID']);
                if(!$project->hasProduct) $productID = $this->loadModel('product')->getProductIDByProject($project->id);
            }
        }
        else
        {
            $this->qa->setMenu($this->products, $productID, $branch);
        }

        $this->view->users = $this->user->getPairs('devfirst|noclosed|nodeleted');
        $this->app->loadLang('release');

        if(!empty($_POST))
        {
            if($chprojectID)
            {
                $noProductExecutions = $chprojectID ? $this->chproject->getIntances($chprojectID, true) : [];
                if(!isset($noProductExecutions[$_POST['execution']])) $_POST['product'] = $this->loadModel('product')->getProductIDByProject($_POST['execution']);
            }

            $response['result'] = 'success';

            /* Set from param if there is a object to transfer bug. */
            setcookie('lastBugModule', (int)$this->post->module, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, false);

            $bugResult = $this->bug->create('', $extras);
            if(!$bugResult or dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }

            $bugID = $bugResult['id'];
            if($bugResult['status'] == 'exists')
            {
                $response['message'] = sprintf($this->lang->duplicate, $this->lang->bug->common);
                $response['locate']  = $this->createLink('bug', 'view', "bugID=$bugID");
                $response['id']      = $bugResult['id'];
                return $this->send($response);
            }

            /* Record related action, for example FromSonarqube. */
            $createAction = $from == 'sonarqube' ? 'fromSonarqube' : 'Opened';
            $actionID     = $this->action->create('bug', $bugID, $createAction);

            $extras = str_replace(array(',', ' '), array('&', ''), $extras);
            parse_str($extras, $output);
            if(isset($output['todoID']))
            {
                $this->dao->update(TABLE_TODO)->set('status')->eq('done')->where('id')->eq($output['todoID'])->exec();
                $this->action->create('todo', $output['todoID'], 'finished', '', "BUG:$bugID");

                if($this->config->edition != 'open')
                {
                    $todo = $this->dao->select('type, idvalue')->from(TABLE_TODO)->where('id')->eq($output['todoID'])->fetch();
                    if($todo->type == 'feedback' && $todo->idvalue) $this->loadModel('feedback')->updateStatus('todo', $todo->idvalue, 'done');
                }
            }

            $message = $this->executeHooks($bugID);
            if($message) $this->lang->saveSuccess = $message;

            /* Return bug id when call the API. */
            if($this->viewType == 'json') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'id' => $bugID));

            /* If link from no head then reload. */
            if(isonlybody())
            {
                $executionID = isset($output['executionID']) ? $output['executionID'] : $this->session->execution;
                $executionID = $this->post->execution ? $this->post->execution : $executionID;
                $execution   = $this->loadModel('execution')->getByID($executionID);
                if($this->app->tab == 'execution')
                {
                    $execLaneType = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                    $execGroupBy  = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';

                    if($execution->type == 'kanban')
                    {
                        $rdSearchValue = $this->session->rdSearchValue ? $this->session->rdSearchValue : '';
                        $kanbanData    = $this->loadModel('kanban')->getRDKanban($executionID, $execLaneType, 'id_desc', 0, $execGroupBy, $rdSearchValue);
                        $kanbanData    = json_encode($kanbanData);
                        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.updateKanban($kanbanData, 0)"));
                    }
                    else
                    {
                        $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                        $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($executionID, $execLaneType, $execGroupBy, $taskSearchValue);
                        $kanbanType      = $execLaneType == 'all' ? 'bug' : key($kanbanData);
                        $kanbanData      = $kanbanData[$kanbanType];
                        $kanbanData      = json_encode($kanbanData);
                        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'callback' => "parent.updateKanban(\"bug\", $kanbanData)"));
                    }
                }
                else
                {
                    return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true, 'locate' => 'parent'));
                }
            }

            if(isonlybody()) return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => 'parent'));
            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success', 'data' => $bugID));

            if($this->app->tab == 'execution')
            {
                if(!preg_match("/(m=|\/)execution(&f=|-)bug(&|-|\.)?/", $this->session->bugList))
                {
                    $location = $this->session->bugList;
                }
                else
                {
                    $executionID = $this->post->execution ? $this->post->execution : zget($output, 'executionID', $this->session->execution);
                    $location    = $this->createLink('execution', 'bug', "executionID=$executionID");
                }

            }
            elseif($this->app->tab == 'project')
            {
                $location = $this->createLink('project', 'bug', "projectID=" . zget($output, 'projectID', $this->session->project));
            }
            elseif($this->app->tab == 'chteam')
            {
                $location = $this->createLink('chproject', 'bug', "chproject=$chprojectID");
            }
            else
            {
                setcookie('bugModule', 0, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);
                $location = $this->createLink('bug', 'browse', "productID={$this->post->product}&branch=$branch&browseType=byModule&param={$this->post->module}&orderBy=id_desc");
            }
            if($this->app->getViewType() == 'xhtml') $location = $this->createLink('bug', 'view', "bugID=$bugID", 'html');
            $response['message'] = $this->lang->saveSuccess;
            $response['locate']  = $location;
            return $this->send($response);
        }

        if($this->app->tab == 'chteam')
        {
            $executionIdList = $this->chproject->getIntances($chprojectID);
            if(!$productID) $productID = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->in($executionIdList)->fetch('product');
        }

        /* Get product, then set menu. */
        if($this->app->tab != 'chteam') $productID = $this->product->saveState($productID, $this->products);

        $currentProduct = $this->product->getById($productID);

        if($branch === '') $branch = (int)$this->cookie->preBranch;

        /* Init vars. */
        $projectID   = 0;
        $moduleID    = 0;
        $executionID = 0;
        $taskID      = 0;
        $storyID     = 0;
        $buildID     = 0;
        $caseID      = 0;
        $runID       = 0;
        $testtask    = 0;
        $version     = 0;
        $title       = $from == 'sonarqube' ? $_COOKIE['sonarqubeIssue'] : '';
        $steps       = $this->lang->bug->tplStep . $this->lang->bug->tplResult . $this->lang->bug->tplExpect;
        $os          = '';
        $browser     = '';
        $assignedTo  = isset($currentProduct->QD) ? $currentProduct->QD : '';
        $deadline    = '';
        $mailto      = '';
        $keywords    = '';
        $severity    = 3;
        $type        = 'codeerror';
        $pri         = 3;
        $color       = '';
        $feedbackBy  = '';
        $notifyEmail = '';

        /* Parse the extras. extract fix php7.2. */
        $extras = str_replace(array(',', ' '), array('&', ''), $extras);
        parse_str($extras, $output);
        extract($output);

        if($runID and $resultID) extract($this->bug->getBugInfoFromResult($resultID, 0, 0, isset($stepIdList) ? $stepIdList : ''));// If set runID and resultID, get the result info by resultID as template.
        if(!$runID and $caseID)  extract($this->bug->getBugInfoFromResult($resultID, $caseID, $version, isset($stepIdList) ? $stepIdList : ''));// If not set runID but set caseID, get the result info by resultID and case info.

        /* If bugID setted, use this bug as template. */
        if($copyBugID) $bugID = $copyBugID;

        if(isset($bugID))
        {
            $bug = $this->bug->getById($bugID);

            extract((array)$bug);
            $executionID = $bug->execution;
            $moduleID    = $bug->module;
            $taskID      = $bug->task;
            $storyID     = $bug->story;
            $buildID     = $bug->openedBuild;
            $severity    = $bug->severity;
            $type        = $bug->type;
            $assignedTo  = $bug->assignedTo;
            $deadline    = helper::isZeroDate($bug->deadline) ? '' : $bug->deadline;
            $color       = $bug->color;
            $testtask    = $bug->testtask;
            $feedbackBy  = $bug->feedbackBy;
            $notifyEmail = $bug->notifyEmail;
            if($pri == 0) $pri = '3';

            if($this->app->tab == 'chteam')
            {
                $projectID = $bug->project;
                $productID = $bug->product;
            }
        }

        if($testtask)
        {
            $testtask = $this->loadModel('testtask')->getById($testtask);
            $buildID  = $testtask->build;
        }

        if(isset($todoID))
        {
            $todo  = $this->loadModel('todo')->getById($todoID);
            $title = $todo->name;
            $steps = $todo->desc;
            $pri   = $todo->pri;
        }

        /* Get branches. */
        if($this->app->tab == 'execution' or $this->app->tab == 'project')
        {
            $objectID        = $this->app->tab == 'project' ? $projectID : $executionID;
            $productBranches = $currentProduct->type != 'normal' ? $this->loadModel('execution')->getBranchByProduct($productID, $objectID, 'noclosed|withMain') : array();
            $branches        = isset($productBranches[$productID]) ? $productBranches[$productID] : array();
            $branch          = key($branches);
        }
        else
        {
            $branches = $currentProduct->type != 'normal' ? $this->loadModel('branch')->getPairs($productID, 'active') : array();
        }

        if($this->app->tab == 'chteam')
        {
            $executionIdList = $this->chproject->getIntances($chprojectID);
            if(!$projectID)
            {
                $projectIdList = $this->chproject->getIntanceProjectPairs($chprojectID, 'project,project');
                if($productID) $projectID = $this->dao->select('project')->from(TABLE_PROJECTPRODUCT)
                    ->where('product')->eq($productID)
                    ->andWhere('project')->in($projectIdList)
                    ->fetch('project');
            }

            if(!$executionID)
            {
                $executionID = $this->dao->select('t2.id')->from(TABLE_PROJECTPRODUCT)->alias('t1')
                    ->leftJoin(TABLE_EXECUTION)->alias('t2')->on('t1.project = t2.id')
                    ->where('t2.id')->in($executionIdList)
                    ->andWhere('t2.project')->eq($projectID)
                    ->beginIF($productID)->andWhere('t1.product')->eq($productID)->fi()
                    ->fetch('id');
            }

            $execution = $this->dao->findById($executionID)->from(TABLE_EXECUTION)->fetch();
        }

        /* If executionID is setted, get builds and stories of this execution. */
        if($executionID)
        {
            $builds  = $this->loadModel('build')->getBuildPairs($productID, $branch, 'noempty,noterminate,nodone,noreleased', $executionID, 'execution');
            $stories = $this->story->getExecutionStoryPairs($executionID);
            if(!$projectID) $projectID = $this->dao->select('project')->from(TABLE_EXECUTION)->where('id')->eq($executionID)->fetch('project');
        }
        else
        {
            $moduleID = $moduleID ? $moduleID : 0;
            $builds   = $this->loadModel('build')->getBuildPairs($productID, $branch, 'noempty,noterminate,nodone,withbranch,noreleased');
            $stories  = $this->story->getProductStoryPairs($productID, $branch, $moduleID, 'all','id_desc', 0, 'full', 'story', false);
        }

        $moduleOwner = $this->bug->getModuleOwner($moduleID, $productID);

        /* Get all project team members linked with this product. */
        $productMembers = $this->bug->getProductMemberPairs($productID, $branch);
        $productMembers = array_filter($productMembers);
        if(empty($productMembers)) $productMembers = $this->view->users;

        $moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'bug', $startModuleID = 0, ($branch === 'all' or !isset($branches[$branch])) ? 0 : $branch);
        if(empty($moduleOptionMenu)) return print(js::locate(helper::createLink('tree', 'browse', "productID=$productID&view=story")));

        /* Get products and projects. */
        $products = $this->config->CRProduct ? $this->products : $this->product->getPairs('noclosed', 0, '', 'all');

        $projects = array(0 => '');

        if($executionID)
        {
            $products       = array();
            $linkedProducts = $this->loadModel('product')->getProducts($executionID);
            foreach($linkedProducts as $product) $products[$product->id] = $product->name;
        }
        elseif($projectID)
        {
            $products    = array();
            $productList = $this->config->CRProduct ? $this->product->getOrderedProducts('all', 40, $projectID) : $this->product->getOrderedProducts('normal', 40, $projectID);
            foreach($productList as $product) $products[$product->id] = $product->name;

            /* Set project menu. */
            if($this->app->tab == 'project') $this->project->setMenu($projectID);
        }
        else
        {
            $projects += $this->product->getProjectPairsByProduct($productID, $branch);
        }

        if($this->app->tab == 'chteam') $projects = $this->chproject->getIntancesProjectOptions($chprojectID, 'projectID', 'projectName');

        $projectModel = '';
        if($projectID)
        {
            $project = $this->loadModel('project')->getByID($projectID);
            if($project)
            {
                if(empty($bugID) or $this->app->tab != 'qa') $projects += array($projectID => $project->name);

                /* Replace language. */
                if(!empty($project->model) and $project->model == 'waterfall') $this->lang->bug->execution = str_replace($this->lang->executionCommon, $this->lang->project->stage, $this->lang->bug->execution);
                $projectModel = $project->model;

                if(!$project->multiple) $executionID = $this->loadModel('execution')->getNoMultipleID($projectID);
            }
        }

        /* Link all projects to product when copying bug under qa.*/
        if(!empty($bugID) and $this->app->tab == 'qa') $projects += $this->product->getProjectPairsByProduct($productID, $branch);

        /* Get block id of assinge to me. */
        $blockID = 0;
        if(isonlybody())
        {
            $blockID = $this->dao->select('id')->from(TABLE_BLOCK)
                ->where('block')->eq('assingtome')
                ->andWhere('module')->eq('my')
                ->andWhere('account')->eq($this->app->user->account)
                ->orderBy('order_desc')
                ->fetch('id');
        }

        /* Get executions. */
        $executions = array(0 => '');
        if(isset($projects[$projectID])) $executions += $this->product->getExecutionPairsByProduct($productID, $branch ? "0,$branch" : 0, 'id_desc', $projectID, !$projectID ? 'multiple|stagefilter' : 'stagefilter');
        $execution  = $executionID ? $this->loadModel('execution')->getByID($executionID) : '';
        $executions = isset($executions[$executionID]) ? $executions : $executions + array($executionID => $execution->name);

        /* Set custom. */
        if($execution and !$execution->multiple)
        {
            $this->config->bug->list->customCreateFields = str_replace('execution,', '', $this->config->bug->list->customCreateFields);
            $this->config->bug->custom->createFields = str_replace('execution,', '', $this->config->bug->custom->createFields);
        }
        foreach(explode(',', $this->config->bug->list->customCreateFields) as $field) $customFields[$field] = $this->lang->bug->$field;


        $this->view->title        = isset($this->products[$productID]) ? $this->products[$productID] . $this->lang->colon . $this->lang->bug->create : $this->lang->bug->create;
        $this->view->customFields = $customFields;
        $this->view->showFields   = $this->config->bug->custom->createFields;

        if($this->app->tab == 'chteam')
        {
            $this->view->title      = $execution->name . $this->lang->colon . $this->lang->bug->create;
            $this->view->showFields = 'execution,noticefeedbackBy,story,task,pri,severity,os,browser,deadline,mailto,keywords';
            $this->config->bug->create->requiredFields .= ',execution,product,project';

            $executions = $this->chproject->getIntancesProjectOptions($chprojectID, 'executionID', 'executionName', $projectID);
            $products   = $this->chproject->getIntanceProductsPairs($chprojectID, true);
        }

        $this->view->gobackLink            = (isset($output['from']) and $output['from'] == 'global') ? $this->createLink('bug', 'browse', "productID=$productID") : '';
        $this->view->products              = $products;
        $this->view->productID             = $productID;
        $this->view->productName           = isset($this->products[$productID]) ? $this->products[$productID] : '';
        $this->view->moduleOptionMenu      = $moduleOptionMenu;
        $this->view->stories               = $stories;
        $this->view->projects              = defined('TUTORIAL') ? $this->loadModel('tutorial')->getProjectPairs() : $projects;
        $this->view->projectExecutionPairs = $this->loadModel('project')->getProjectExecutionPairs();
        $this->view->executions            = defined('TUTORIAL') ? $this->loadModel('tutorial')->getExecutionPairs() : $executions;
        $this->view->builds                = $builds;
        $this->view->releasedBuilds        = $this->loadModel('release')->getReleasedBuilds($productID, $branch);
        $this->view->moduleID              = (int)$moduleID;
        $this->view->projectID             = $projectID;
        $this->view->projectModel          = $projectModel;
        $this->view->execution             = $execution;
        $this->view->taskID                = $taskID;
        $this->view->storyID               = $storyID;
        $this->view->buildID               = $buildID;
        $this->view->caseID                = $caseID;
        $this->view->resultFiles           = (!empty($resultID) and !empty($stepIdList)) ? $this->loadModel('file')->getByObject('stepResult', $resultID, str_replace('_', ',', $stepIdList)) : array();
        $this->view->runID                 = $runID;
        $this->view->version               = $version;
        $this->view->testtask              = $testtask;
        $this->view->bugTitle              = $title;
        $this->view->pri                   = $pri;
        $this->view->steps                 = htmlSpecialString($steps);
        $this->view->os                    = $os;
        $this->view->browser               = $browser;
        $this->view->productMembers        = $productMembers;
        $this->view->assignedTo            = $assignedTo;
        $this->view->deadline              = $deadline;
        $this->view->mailto                = $mailto;
        $this->view->keywords              = $keywords;
        $this->view->severity              = $severity;
        $this->view->type                  = $type;
        $this->view->product               = $currentProduct;
        $this->view->branch                = $branch;
        $this->view->branches              = $branches;
        $this->view->blockID               = $blockID;
        $this->view->color                 = $color;
        $this->view->stepsRequired         = strpos($this->config->bug->create->requiredFields, 'steps');
        $this->view->isStepsTemplate       = $steps == $this->lang->bug->tplStep . $this->lang->bug->tplResult . $this->lang->bug->tplExpect ? true : false;
        $this->view->issueKey              = $from == 'sonarqube' ? $output['sonarqubeID'] . ':' . $output['issueKey'] : '';
        $this->view->feedbackBy            = $feedbackBy;
        $this->view->notifyEmail           = $notifyEmail;
        $this->view->chprojectID           = $chprojectID;
        $this->view->bugID                 = isset($bugID) ? $bugID : 0;

        $this->display();
    }
}
