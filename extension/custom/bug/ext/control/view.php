<?php
helper::importControl('bug');
class mybug extends bug
{
    /**
     * View a bug.
     *
     * @param  int    $bugID
     * @param  string $form
     * @access public
     * @return void
     */
    public function view($bugID, $from = 'bug')
    {
        /* Judge bug exits or not. */
        $bugID = (int)$bugID;
        $bug   = $this->bug->getById($bugID, true);
        if(!$bug) return print(js::error($this->lang->notFound) . js::locate($this->createLink('qa', 'index')));

        $this->session->set('storyList', '', 'product');
        $this->session->set('projectList', $this->app->getURI(true) . "#app={$this->app->tab}", 'project');
        $this->bug->checkBugExecutionPriv($bug);

        /* Update action. */
        if($bug->assignedTo == $this->app->user->account) $this->loadModel('action')->read('bug', $bugID);

        /* Set menu. */
        if(!isonlybody())
        {
            if($this->app->tab == 'project')   $this->loadModel('project')->setMenu($bug->project);
            if($this->app->tab == 'execution') $this->loadModel('execution')->setMenu($bug->execution);
            if($this->app->tab == 'qa')        $this->qa->setMenu($this->products, $bug->product, $bug->branch);

            if($this->app->tab == 'chteam')
            {
                $chprojectID = $this->dao->select('ch')->from(TABLE_CHPROJECTINTANCES)->where('zentao')->eq($bug->execution)->fetch('ch');
                $this->loadModel('chproject')->setMenu($chprojectID);
            }

            if($this->app->tab == 'devops')
            {
                $repos = $this->loadModel('repo')->getRepoPairs('project', $bug->project);
                $this->repo->setMenu($repos);
                $this->lang->navGroup->bug = 'devops';
            }

            if($this->app->tab == 'product')
            {
                $this->loadModel('product')->setMenu($bug->product);
                $this->lang->product->menu->plan['subModule'] .= ',bug';
            }
        }

        /* Get product info. */
        $productID = $bug->product;
        $product   = $this->loadModel('product')->getByID($productID);
        $branches  = $product->type == 'normal' ? array() : $this->loadModel('branch')->getPairs($bug->product);

        $projects = $this->loadModel('product')->getProjectPairsByProduct($productID, $bug->branch);
        $this->session->set("project", key($projects), 'project');

        $this->executeHooks($bugID);
        if($this->config->edition == 'ipd') $bug = $this->loadModel('story')->getAffectObject('', 'bug', $bug);

        /* Header and positon. */
        $this->view->title      = "BUG #$bug->id $bug->title - " . $product->name;
        $this->view->position[] = html::a($this->createLink('bug', 'browse', "productID=$productID"), $product->name);
        $this->view->position[] = $this->lang->bug->view;

        /* Assign. */
        $this->view->project     = $this->loadModel('project')->getByID($bug->project);
        $this->view->productID   = $productID;
        $this->view->branches    = $branches;
        $this->view->modulePath  = $this->tree->getParents($bug->module);
        $this->view->bugModule   = empty($bug->module) ? '' : $this->tree->getById($bug->module);
        $this->view->bug         = $bug;
        $this->view->from        = $from;
        $this->view->branchName  = $product->type == 'normal' ? '' : zget($branches, $bug->branch, '');
        $this->view->users       = $this->user->getPairs('noletter');
        $this->view->actions     = $this->action->getList('bug', $bugID);
        $this->view->builds      = $this->loadModel('build')->getBuildPairs($productID, 'all', 'noterminate, nodone, hasdeleted');
        $this->view->preAndNext  = $this->loadModel('common')->getPreAndNextObject('bug', $bugID);
        $this->view->product     = $product;
        $this->view->linkCommits = $this->loadModel('repo')->getCommitsByObject($bugID, 'bug');
        $this->view->chprojectID = isset($chprojectID) ? $chprojectID : 0;

        $this->view->projects = array('' => '') + $projects;

        $this->display();
    }
}
