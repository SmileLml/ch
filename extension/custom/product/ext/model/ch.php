<?php
/**
 * Build search form.
 *
 * @param  int    $productID
 * @param  array  $products
 * @param  int    $queryID
 * @param  int    $actionURL
 * @param  int    $branch
 * @param  int    $projectID
 * @access public
 * @return void
 */
public function buildSearchForm($productID, $products, $queryID, $actionURL, $branch = 0, $projectID = 0)
{
    $productIdList = ($this->app->tab == 'project' and empty($productID)) ? array_keys($products) : $productID;
    $branchParam   = ($this->app->tab == 'project' and empty($productID)) ? '' : $branch;
    $projectID     = ($this->app->tab == 'project' and empty($projectID)) ? $this->session->project : $projectID;

    $this->config->product->search['actionURL'] = $actionURL;
    $this->config->product->search['queryID']   = $queryID;
    $this->config->product->search['params']['plan']['values'] = $this->loadModel('productplan')->getPairs($productIdList, (empty($branchParam) or $branchParam == 'all') ? '' : $branchParam);

    $product = ($this->app->tab == 'project' and empty($productID)) ? $products : array();
    if(empty($product) and isset($products[$productID])) $product = array($productID => $products[$productID]);

    $this->config->product->search['params']['product']['values'] = array('' => '') + $product + array('all' => $this->lang->product->allProduct);

    $this->config->product->search['params']['stage']['values'] = array('' => '') + $this->lang->story->stageList;

    if($this->config->edition == 'ipd') $this->config->product->search['params']['roadmap']['values'] = $this->loadModel('roadmap')->getPairs($productID);

    /* Get modules. */
    $this->loadModel('tree');
    if($this->app->tab == 'project')
    {
        if($productID)
        {
            $modules          = array();
            $branchList       = $this->loadModel('branch')->getPairs($productID, '', $projectID);
            $branchModuleList = $this->tree->getOptionMenu($productID, 'story', 0, array_keys($branchList));
            foreach($branchModuleList as $branchID => $branchModules) $modules = array_merge($modules, $branchModules);
        }
        else
        {
            $moduleList  = array();
            $modules     = array('/');
            $branchGroup = $this->loadModel('execution')->getBranchByProduct(array_keys($products), $projectID, '');
            foreach($products as $productID => $productName)
            {
                if(isset($branchGroup[$productID]))
                {
                    $branchModuleList = $this->tree->getOptionMenu($productID, 'story', 0, array_keys($branchGroup[$productID]));
                    foreach($branchModuleList as $branchID => $branchModules)
                    {
                        if(is_array($branchModules)) $moduleList += $branchModules;
                    }
                }
                else
                {
                    $moduleList = $this->tree->getOptionMenu($productID, 'story', 0, $branch);
                }

                foreach($moduleList as $moduleID => $moduleName)
                {
                    if(empty($moduleID)) continue;
                    $modules[$moduleID] = $productName . $moduleName;
                }
            }
        }
    }
    else
    {
        $modules = $this->tree->getOptionMenu($productID, 'story', 0, $branch);
    }

    $this->config->product->search['params']['module']['values'] = array('' => '') + $modules;

    if(isset($this->config->product->search['fields']['business']))
    {
        $businessPairs = $this->loadModel('project')->getBusinessPairs($projectID);
        $this->config->product->search['params']['business']['values'] = $businessPairs;
    }

    if(isset($this->config->product->search['fields']['relatedRequirement']))
    {

        $requirementPairs = $this->dao->select('id,title')->from('zt_story')
            ->where('deleted')->eq('0')
            ->andWhere('type')->eq('requirement')
            ->beginIF($this->app->tab == 'project')->andWhere('product')->in($productIdList)->fi()
            ->beginIF($this->app->tab != 'project')->andWhere('product')->eq($productID)->fi()
            ->fetchPairs();

        $this->config->product->search['params']['relatedRequirement']['values'] = array('' => '') + $requirementPairs;
    }

    $productInfo   = $this->getById($productID);

    if(!$productID or $productInfo->type == 'normal' or $this->app->tab == 'assetlib')
    {
        unset($this->config->product->search['fields']['branch']);
        unset($this->config->product->search['params']['branch']);
    }
    else
    {
        $this->config->product->search['fields']['branch'] = sprintf($this->lang->product->branch, $this->lang->product->branchName[$productInfo->type]);
        $this->config->product->search['params']['branch']['values']  = array('' => '', '0' => $this->lang->branch->main) + $this->loadModel('branch')->getPairs($productID, 'noempty');
    }

    if(!empty($productInfo->shadow)) unset($this->config->product->search['fields']['product']);

    $this->loadModel('search')->setSearchParams($this->config->product->search);
}

/**
 * Create the select code of products.
 *
 * @param  array       $products
 * @param  int         $productID
 * @param  string      $currentModule
 * @param  string      $currentMethod
 * @param  string      $extra
 * @param  int|string  $branch
 * @param  int         $module
 * @param  string      $moduleType
 * @param  bool        $withBranch      true|false
 *
 * @access public
 * @return string
 */
public function select($products, $productID, $currentModule, $currentMethod, $extra = '', $branch = '', $module = 0, $moduleType = '', $withBranch = true)
{
    $isQaModule = (strpos(',project,execution,chteam,', ",{$this->app->tab},") !== false and strpos(',bug,testcase,testtask,ajaxselectstory,', ",{$this->app->rawMethod},") !== false and isset($products[0])) ? true : false;
    $isFeedbackModel  = strpos(',feedback,', ",{$this->app->tab},") !== false ? true : false;
    if(count($products) <= 2 && isset($products[0]) && $this->app->tab != 'chteam')
    {
        unset($products[0]);
        $productID = key($products);
    }

    if(empty($products)) return;

    if($this->app->tab == 'project' and strpos(',zeroCase,browseUnits,groupCase,', ",$currentMethod,") !== false) $isQaModule = true;

    $this->app->loadLang('product');
    if(!$isQaModule and !$productID and !$isFeedbackModel)
    {
        unset($this->lang->product->menu->settings['subMenu']->branch);
        return;
    }
    $isMobile = $this->app->viewType == 'mhtml';

    $productID = $productID == 'all' ? 0 : $productID;
    setcookie("lastProduct", $productID, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, true);
    if($productID) $currentProduct = $this->getById($productID);

    if($isQaModule and $this->app->tab == 'project')
    {
        if($this->app->tab == 'project')   $extra = strpos(',testcase,groupCase,zeroCase,', ",$currentMethod,") !== false ? $extra : $this->session->project;
        if($this->app->tab == 'execution') $extra = $this->session->execution;
    }
    if($isQaModule and !$productID)
    {
        $currentProduct = new stdclass();
        $currentProduct->name = $products[$productID];
        $currentProduct->type = 'normal';
    }
    if($isFeedbackModel and !$productID)
    {
        $currentProduct = new stdclass();
        $currentProduct->name = isset($products[$productID]) ? $products[$productID] : current($products);
        $currentProduct->type = 'normal';
    }
    $this->session->set('currentProductType', $currentProduct->type);

    $output = '';
    if(!empty($products))
    {
        $moduleName = 'product';
        if($isQaModule)       $moduleName = 'bug';
        if($isFeedbackModel)  $moduleName = 'feedback';

        $dropMenuLink = helper::createLink($moduleName, 'ajaxGetDropMenu', "objectID=$productID&module=$currentModule&method=$currentMethod&extra=$extra");
        $output  = "<div class='btn-group angle-btn'><div class='btn-group'><button data-toggle='dropdown' type='button' class='btn btn-limit' id='currentItem' title='{$currentProduct->name}' style='width: 90%'><span class='text'>{$currentProduct->name}</span> <span class='caret'></span></button><div id='dropMenu' class='dropdown-menu search-list' data-ride='searchList' data-url='$dropMenuLink'>";
        $output .= '<div class="input-control search-box has-icon-left has-icon-right search-example"><input type="search" class="form-control search-input" /><label class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label><a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a></div>';
        $output .= "</div></div>";
        if($isMobile) $output = "<a id='currentItem' href=\"javascript:showSearchMenu('product', '$productID', '$currentModule', '$currentMethod', '$extra')\"><span class='text'>{$currentProduct->name}</span> <span class='icon-caret-down'></span></a><div id='currentItemDropMenu' class='hidden affix enter-from-bottom layer'></div>";

        if($currentProduct->type == 'normal' || !$withBranch) unset($this->lang->product->menu->settings['subMenu']->branch);
        if($currentProduct->type != 'normal' && $currentModule != 'programplan' && $withBranch)
        {
            $this->lang->product->branch = sprintf($this->lang->product->branch, $this->lang->product->branchName[$currentProduct->type]);
            $this->lang->product->menu->settings['subMenu']->branch = str_replace('@branch@', $this->lang->product->branch, $this->lang->product->menu->settings['subMenu']->branch);

            $branches   = $this->loadModel('branch')->getPairs($productID, 'all');
            $branchName = $branches[$branch];
            if(!$isMobile)
            {
                $params       = explode(',', $extra);
                $dropMenuLink = helper::createLink('branch', 'ajaxGetDropMenu', "objectID=$productID&branch=$branch&module=$currentModule&method=$currentMethod&extra={$params[0]}");
                $output .= "<div class='btn-group'><button id='currentBranch' data-toggle='dropdown' type='button' class='btn btn-limit' title='{$branchName}' style='width: 90%'>{$branchName} <span class='caret'></span></button><div id='dropMenu' class='dropdown-menu search-list' data-ride='searchList' data-url='$dropMenuLink'>";
                $output .= '<div class="input-control search-box has-icon-left has-icon-right search-example"><input type="search" class="form-control search-input" /><label class="input-control-icon-left search-icon"><i class="icon icon-search"></i></label><a class="input-control-icon-right search-clear-btn"><i class="icon icon-close icon-sm"></i></a></div>';
                $output .= "</div></div>";
            }
            else
            {
                $output .= "<a id='currentBranch' href=\"javascript:showSearchMenu('branch', '$productID', '$currentModule', '$currentMethod', '$extra')\">{$branchName} <span class='icon-caret-down'></span></a><div id='currentBranchDropMenu' class='hidden affix enter-from-bottom layer'></div>";
            }
        }

        if(!$isMobile) $output .= '</div>';
    }

    return $output;
}

public function getPairsByIDList($productIDList)
{
    return $this->dao->select('id,name')->from(TABLE_PRODUCT)
        ->where('id')->in($productIDList)
        ->andWhere('shadow')->eq('0')
        ->fetchPairs();
}
