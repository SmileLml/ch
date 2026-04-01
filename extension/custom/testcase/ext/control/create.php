<?php
helper::importControl('testcase');
class mytestcase extends testcase
{
    /**
     * Create a test case.
     * @param        $productID
     * @param string $branch
     * @param int    $moduleID
     * @param string $from
     * @param int    $param
     * @param int    $storyID
     * @param string $extras
     * @access public
     * @return void
     */
    public function create($productID, $branch = '', $moduleID = 0, $from = '', $param = 0, $storyID = 0, $extras = '', $chprojectID = 0)
    {
        $testcaseID  = ($from and strpos('testcase|work|contribute', $from) !== false) ? $param : 0;
        $bugID       = $from == 'bug' ? $param : 0;
        $executionID = $from == 'execution' ? $param : 0;

        $extras = str_replace(array(',', ' '), array('&', ''), $extras);
        parse_str($extras, $output);

        if($this->app->tab == 'chteam')
        {
            $executionIdList = $this->loadModel('chproject')->getIntances($chprojectID);
            $executionID     = $output['executionID'] ? $output['executionID'] : key($executionIdList);
        }
        
        $this->loadModel('story');
        if(!empty($_POST))
        {
            if(!empty($_FILES['scriptFile'])) unset($_FILES['scriptFile']);
            $response['result'] = 'success';

            setcookie('lastCaseModule', (int)$this->post->module, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, false);
            setcookie('lastCaseScene', (int)$this->post->scene, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, false);
            $caseResult = $this->testcase->create($bugID);
            if(!$caseResult or dao::isError())
            {
                $response['result']  = 'fail';
                $response['message'] = dao::getError();
                return $this->send($response);
            }

            $caseID = $caseResult['id'];
            if($caseResult['status'] == 'exists')
            {
                $response['message'] = sprintf($this->lang->duplicate, $this->lang->testcase->common);
                $response['locate']  = $this->createLink('testcase', 'view', "caseID=$caseID");
                return $this->send($response);
            }

            $this->loadModel('action');
            $this->action->create('case', $caseID, 'Opened');
            if($this->testcase->getStatus('create') == 'wait') $this->action->create('case', $caseID, 'submitReview');

            /* If the story is linked project, make the case link the project. */
            $this->testcase->syncCase2Project($caseResult['caseInfo'], $caseID);

            $message = $this->executeHooks($caseID);
            if($message) $this->lang->saveSuccess = $message;
            $response['message'] = $this->lang->saveSuccess;

            if($this->viewType == 'json') return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'id' => $caseID));
            /* If link from no head then reload. */
            if(isonlybody()) return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'closeModal' => true));

            setcookie('caseModule', 0, 0, $this->config->webRoot, '', $this->config->cookieSecure, false);

            /* Use this session link, when the tab is not QA, a session of the case list exists, and the session is not from the Dynamic page. */
            $useSession         = ($this->app->tab != 'qa' and $this->session->caseList and strpos($this->session->caseList, 'dynamic') === false);
            $locateLink         = $this->app->tab == 'project' ? $this->createLink('project', 'testcase', "projectID={$this->session->project}") : $this->createLink('testcase', 'browse', "productID={$this->post->product}&branch={$this->post->branch}");
            $response['locate'] = $useSession ? $this->session->caseList : $locateLink;

            if($this->app->tab == 'chteam')
            {
                $response['locate'] = $this->session->teamTestcaseList ? $this->session->teamTestcaseList : $this->createLink('chproject', 'testcase', "chproject=$chprojectID");
            }

            return $this->send($response);
        }
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        /* Init vars. */
        $type         = 'feature';
        $stage        = '';
        $pri          = 3;
        $scene        = 0;
        $caseTitle    = '';
        $precondition = '';
        $keywords     = '';
        $steps        = array();
        $color        = '';
        $auto         = '';

        /* If testcaseID large than 0, use this testcase as template. */
        if($testcaseID > 0)
        {
            $testcase     = $this->testcase->getById($testcaseID);
            $productID    = $testcase->product;
            $type         = $testcase->type ? $testcase->type : 'feature';
            $stage        = $testcase->stage;
            $pri          = $testcase->pri;
            $scene        = $testcase->scene;
            $storyID      = $testcase->story;
            $caseTitle    = $testcase->title;
            $precondition = $testcase->precondition;
            $keywords     = $testcase->keywords;
            $steps        = $testcase->steps;
            $color        = $testcase->color;
            $auto         = $testcase->auto;
        }

        /* If bugID large than 0, use this bug as template. */
        if($bugID > 0)
        {
            $bug       = $this->loadModel('bug')->getById($bugID);
            $type      = $bug->type;
            $pri       = $bug->pri ? $bug->pri : $bug->severity;
            $storyID   = $bug->story;
            $caseTitle = $bug->title;
            $keywords  = $bug->keywords;
            $steps     = $this->testcase->createStepsFromBug($bug->steps);
        }

        /* Set productID and branch. */
        $productID = $this->product->saveState($productID, $this->products);
        if($branch === '') $branch = $this->cookie->preBranch;

        /* Set menu. */
        if($this->app->tab == 'project' or $this->app->tab == 'execution') $this->loadModel('execution');
        if($this->app->tab == 'project')
        {
            $this->loadModel('project')->setMenu($this->session->project);
        }
        elseif($this->app->tab == 'execution')
        {
            $this->execution->setMenu($this->session->execution);
        }
        elseif($this->app->tab == 'chteam')
        {
            $this->loadModel('chproject');
            $this->chproject->setMenu($chprojectID);
        }
        else
        {
            $this->qa->setMenu($this->products, $productID, $branch);
        }

        /* Set branch. */
        $product = $this->product->getById($productID);
        if($this->app->tab == 'chteam')
        {
            $products = $this->product->getProducts($executionID, 'all', '', false);
            
            $execution = $this->loadModel('execution')->getByID($executionID);
            if(!$execution->hasProduct || !isset($products[$productID]))
            {
                $productID = $this->product->getProductIDByProject($execution->id);
                $product   = $this->product->getById($productID);
            }
            $this->view->products = $products;
        }

        if(!isset($this->products[$productID])) $this->products[$productID] = $product->name;
        if($this->app->tab == 'execution' or $this->app->tab == 'project')
        {
            $objectID        = $this->app->tab == 'project' ? $this->session->project : $executionID;
            $productBranches = (isset($product->type) and $product->type != 'normal') ? $this->execution->getBranchByProduct($productID, $objectID, 'noclosed|withMain') : array();
            $branches        = isset($productBranches[$productID]) ? $productBranches[$productID] : array();
            $branch          = key($branches);
        }
        else
        {
            $branches = (isset($product->type) and $product->type != 'normal') ? $this->loadModel('branch')->getPairs($productID, 'active') : array();
        }

        /* Padding the steps to the default steps count. */
        if(count($steps) < $this->config->testcase->defaultSteps)
        {
            $paddingCount = $this->config->testcase->defaultSteps - count($steps);
            $step = new stdclass();
            $step->type   = 'item';
            $step->desc   = '';
            $step->expect = '';
            for($i = 1; $i <= $paddingCount; $i ++) $steps[] = $step;
        }

        $title      = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->create;
        $position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID&branch=$branch"), $this->products[$productID]);
        $position[] = $this->lang->testcase->common;
        $position[] = $this->lang->testcase->create;

        /* Set story and currentModuleID. */
        if($storyID)
        {
            $story = $this->loadModel('story')->getByID($storyID);
            if(empty($moduleID)) $moduleID = $story->module;
        }

        $currentModuleID = $moduleID ? (int)$moduleID : (int)$this->cookie->lastCaseModule;
        $currentSceneID  = (int)$this->cookie->lastCaseScene;
        if($testcaseID > 0) $currentSceneID = $scene;
        /* Get the status of stories are not closed. */
        $modules = array();
        if($currentModuleID)
        {
            $productModules = $this->tree->getOptionMenu($productID, 'story');
            $storyModuleID  = array_key_exists($currentModuleID, $productModules) ? $currentModuleID : 0;
            $modules        = $this->loadModel('tree')->getStoryModule($storyModuleID);
            $modules        = $this->tree->getAllChildID($modules);
        }

        $storyStatus = $this->story->getStatusList('active');
        $stories     = $this->story->getProductStoryPairs($productID, $branch, $modules, $storyStatus, 'id_desc', 0, 'full', 'story', false);
        if($this->app->tab != 'qa' and $this->app->tab != 'product')
        {
            $projectID = $this->app->tab == 'project' ? $this->session->project : $this->session->execution;
            $stories   = $this->story->getExecutionStoryPairs($projectID, $productID, $branch, $modules);
        }
        /* Logic of task 44139. */
        if(!in_array($this->app->tab, array('execution', 'project')) and empty($stories))
        {
            $stories = $this->story->getProductStoryPairs($productID, $branch, 0, $storyStatus, 'id_desc', 0, 'full', 'story', false);
        }

        if($this->app->tab == 'chteam') $stories = $this->story->getExecutionStoryPairs($executionID, $productID, $branch, $modules);

        if($storyID and !isset($stories[$storyID])) $stories = $this->story->formatStories(array($storyID => $story)) + $stories;//Fix bug #2406.
        $productInfo = $this->loadModel('product')->getById($productID);

        /* Set custom. */
        foreach(explode(',', $this->config->testcase->customCreateFields) as $field) $customFields[$field] = $this->lang->testcase->$field;

        if($this->app->tab == 'chteam')
        {
            $executions = $this->loadModel('chproject')->getIntancesProjectOptions($chprojectID);
            $this->view->executions = $executions;
        }

        $this->view->customFields     = $customFields;
        $this->view->showFields       = $this->config->testcase->custom->createFields;
        $this->view->title            = $title;
        $this->view->position         = $position;
        $this->view->projectID        = isset($projectID) ? $projectID : 0;
        $this->view->productID        = $productID;
        $this->view->executionID      = $executionID;
        $this->view->productInfo      = $productInfo;
        $this->view->productName      = $this->products[$productID];
        $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0, ($branch === 'all' or !isset($branches[$branch])) ? 0 : $branch);
        $this->view->currentModuleID  = $currentModuleID;
        $this->view->currentSceneID   = $currentSceneID;
        $this->view->gobackLink       = (isset($output['from']) and $output['from'] == 'global') ? $this->createLink('testcase', 'browse', "productID=$productID") : '';
        $this->view->stories          = $stories;
        $this->view->caseTitle        = $caseTitle;
        $this->view->color            = $color;
        $this->view->type             = $type;
        $this->view->stage            = $stage;
        $this->view->pri              = $pri;
        $this->view->storyID          = $storyID;
        $this->view->precondition     = $precondition;
        $this->view->keywords         = $keywords;
        $this->view->steps            = $steps;
        $this->view->hiddenProduct    = !empty($product->shadow);
        $this->view->users            = $this->user->getPairs('noletter|noclosed|nodeleted');
        $this->view->branch           = $branch;
        $this->view->product          = $product;
        $this->view->branches         = $branches;
        $this->view->auto             = $auto;
        $this->view->chprojectID      = $chprojectID;
        $this->view->sceneOptionMenu  = $this->testcase->getSceneMenu($productID, $moduleID, $viewType = 'case', $startSceneID = 0, ($branch === 'all' or !isset($branches[$branch])) ? 0 : $branch);

        $this->display();
    }
}