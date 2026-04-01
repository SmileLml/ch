<?php
/**
 * Get execution modules.
 *
 * @param  int    $executionID
 * @param  bool   $parent
 * @param  string $linkObject
 * @param  array  $extra
 * @access public
 * @return array
 */
public function getTaskTreeModules($executionID, $parent = false, $linkObject = 'story', $extra = array())
{
    $executionModules = array();
    $field = $parent ? 'path' : 'id';

    if($linkObject == 'story')
    {
        $table1 = TABLE_PROJECTSTORY;
        $table2 = TABLE_STORY;
    }
    if($linkObject == 'case')
    {
        $table1 = TABLE_PROJECTCASE;
        $table2 = TABLE_CASE;
    }

    if($linkObject)
    {
        $branch = zget($extra, 'branchID', 0);
        /* Get object paths of this execution. */
        if(strpos(',story,case,', ",$linkObject,") !== false)
        {
            $paths = $this->dao->select('DISTINCT t3.' . $field)->from($table1)->alias('t1')
                ->leftJoin($table2)->alias('t2')->on('t1.' . $linkObject . ' = t2.id')
                ->leftJoin(TABLE_MODULE)->alias('t3')->on('t2.module = t3.id')
                ->where('t1.project')->in($executionID)
                ->andWhere('t3.deleted')->eq(0)
                ->andWhere('t2.deleted')->eq(0)
                ->beginIF(isset($extra['branchID']) and $branch !== 'all')->andWhere('t2.branch')->eq($branch)->fi()
                ->fetchPairs();
        }
        elseif($linkObject == 'bug' and strpos(',project,execution,', ",{$this->app->tab},") !== false)
        {
            $paths = $this->dao->select('DISTINCT t2.' . $field)->from(TABLE_BUG)->alias('t1')
                ->leftJoin(TABLE_MODULE)->alias('t2')->on('t1.module = t2.id')
                ->where('t1.deleted')->eq(0)
                ->andWhere('t2.deleted')->eq(0)
                ->beginIF(isset($extra['branchID']) and $branch !== 'all')->andWhere('t1.branch')->eq($branch)->fi()
                ->andWhere("t1.{$this->app->tab}")->eq($executionID)
                ->fetchPairs();
        }
        else
        {
            return array();
        }
    }
    else
    {
        $productGroups = $this->dao->select('product,branch')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($executionID)->fetchGroup('product', 'branch');
        $modules = $this->dao->select('id,root,branch')->from(TABLE_MODULE)
            ->where('root')->in(array_keys($productGroups))
            ->andWhere('type')->eq('story')
            ->andWhere('deleted')->eq(0)
            ->fetchAll();

        $paths = array();
        foreach($modules as $module)
        {
            if(empty($module->branch)) $paths[$module->id] = $module->id;
            if(isset($productGroups[$module->root][0]) or isset($productGroups[$module->root][$module->branch])) $paths[$module->id] = $module->id;
        }
    }

    if(strpos(',case,bug,', ",$linkObject,") === false)
    {
        /* Add task paths of this execution.*/
        $paths += $this->dao->select($field)->from(TABLE_MODULE)
            ->where('root')->in($executionID)
            ->andWhere('type')->eq('task')
            ->andWhere('deleted')->eq(0)
            ->fetchPairs();

        /* Add task paths of this execution for has existed. */
        $paths += $this->dao->select('DISTINCT t1.' . $field)->from(TABLE_MODULE)->alias('t1')
        ->leftJoin(TABLE_TASK)->alias('t2')->on('t1.id=t2.module')
        ->where('t2.module')->ne(0)
        ->andWhere('t2.execution')->in($executionID)
        ->andWhere('t2.deleted')->eq(0)
        ->andWhere('t1.type')->eq('story')
        ->andWhere('t1.deleted')->eq(0)
        ->fetchPairs();
    }

    /* Get all modules from paths. */
    foreach($paths as $path)
    {
        $modules = explode(',', $path);
        foreach($modules as $module) $executionModules[$module] = $module;
    }
    return $executionModules;
}

/**
 * Get full task tree
 * @param  integer $executionID, common value is execution id
 * @param  integer $productID
 * @access public
 * @return array
 */
public function getTaskStructure($rootID, $productID = 0)
{
    $extra = array('executionID' => $rootID, 'productID' => $productID, 'tip' => true);

    /* If createdVersion <= 4.1, go to getTreeMenu(). */
    $products      = $this->loadModel('product')->getProductPairsByProject($rootID);
    $branchGroups  = $this->loadModel('branch')->getByProducts(array_keys($products));
    if(!$this->isMergeModule(($this->app->tab == 'chteam' && is_array($rootID)) ? reset($rootID) : $rootID, 'task') or !$products)
    {
        $extra['tip'] = false;
        $stmt = $this->app->dbQuery($this->buildMenuQuery($rootID, 'task', $startModule = 0));
        if(empty($products)) $this->config->execution->task->allModule = 1;
        return $this->getDataStructure($stmt, 'task');
    }

    /* only get linked modules and ignore others. */
    $executionModules = $this->getTaskTreeModules($rootID, true);

    /* Get module according to product. */
    $fullTrees = array();
    foreach($products as $id => $product)
    {
        $productInfo = $this->product->getById($id);
        /* tree menu. */
        $productTree = array();
        $branchTrees = array();
        if(empty($branchGroups[$id])) $branchGroups[$id]['0'] = '';
        foreach($branchGroups[$id] as $branch => $branchName)
        {
            $rootStr = ($this->app->tab == 'chteam' && is_array($rootID)) ? 'root in(' . implode(',', $rootID) . ')' : "root = '" . (int)$rootID . "'";
            $query   = $this->dao->select('*')->from(TABLE_MODULE)->where("((" . $rootStr . " and type = 'task' and parent != 0) OR (root = $id and type = 'story' and branch ='$branch'))")
                ->andWhere('deleted')->eq(0)
                ->orderBy('grade desc, `order`, type')
                ->get();
            $stmt = $this->app->dbQuery($query);
            if($branch == 0) $productTree = $this->getDataStructure($stmt, 'task', $executionModules);
            if($branch != 0)
            {
                $children = $this->getDataStructure($stmt, 'task', $executionModules);
                if($children) $branchTrees[] = array('name' => $branchName, 'root' => $id, 'type' => 'branch', 'actions' => false, 'children' => $children);
            }
        }
        if($branchTrees) $productTree[] = array('name' => $this->lang->product->branchName[$productInfo->type], 'root' => $id, 'type' => 'branch', 'actions' => false, 'children' => $branchTrees);
        $fullTrees[] = array('name' => $productInfo->name, 'root' => $id, 'type' => 'product', 'actions' => false, 'children' => $productTree);
    }

    /* Get execution module. */
    if($this->app->tab == 'chteam')
    {
        $query = $this->dao->select('*')->from(TABLE_MODULE)
            ->where('root')->in($rootID)
            ->andWhere('type')->eq('task')
            ->andWhere('deleted')->eq(0)
            ->orderBy('grade desc, `order`, type')
            ->get();
    }
    else
    {
        $query = $this->dao->select('*')->from(TABLE_MODULE)
            ->where('root')->eq((int)$rootID)
            ->andWhere('type')->eq('task')
            ->andWhere('deleted')->eq(0)
            ->orderBy('grade desc, `order`, type')
            ->get();
    }   
    $stmt       = $this->app->dbQuery($query);
    $taskTrees  = $this->getDataStructure($stmt, 'task', $executionModules);
    foreach($taskTrees as $taskModule) $fullTrees[] = $taskModule;
    return $fullTrees;
}

/**
 * Get the team tree menu for task and story page.
 *
 * @param  string $moduleType
 * @param  int    $chproject
 * @param  int    $startModule
 * @param  array  $linkParams
 * @access public
 * @return string
 */
public function getTeamTreeMenu($moduleType, $chproject, $startModule = 0, $linkParams)
{
    $instanceList = $this->loadModel('chproject')->getIntances($chproject);
    $products     = $this->loadModel('product')->getProductPairsByProject($instanceList);
    $branchGroups = $this->loadModel('branch')->getByProducts(array_keys($products));

    $branch = 'all';
    if(in_array($moduleType, array('bug', 'case'))) list($branch, $products, $branchGroups) = $this->filterProductsByBranch($branch, $linkParams, $products, $branchGroups);

    /* Set the start module. */
    $startModulePath = '';
    if($startModule > 0)
    {
        $startModule = $this->getById($startModule);
        if($startModule) $startModulePath = $startModule->path . '%';
    }

    $productTreeData = $this->getProductTreeData($products, $branchGroups, $startModulePath, $moduleType, $branch);
    $projectTreeData = $moduleType == 'task' ? $this->getProjectTreeData(array_keys($instanceList), $startModulePath) : [];

    if(!$productTreeData && !$projectTreeData) return '';

    $menu  = "<ul id='modules' class='tree' data-ride='tree' data-name='tree-{$moduleType}'>";
    $menu .= $this->buildModuelTreeHTML($moduleType, $productTreeData, $linkParams);
    $menu .= $this->buildModuelTreeHTML($moduleType, $projectTreeData, $linkParams);
    $menu .= '</ul>';

    return $menu;
}

/**
 * Get the product tree data.
 *
 * @param  array  $products
 * @param  array  $branches
 * @param  string $startModulePath
 * @param  string $moduleType
 * @param  string $branchID
 * @access private
 * @return array
 */
private function getProductTreeData($products, $branches, $startModulePath, $moduleType, $branchID)
{
    $shadowProducts = $this->dao->select('id')->from(TABLE_PRODUCT)
        ->where('id')->in(array_keys($products))
        ->andWhere('shadow')->eq(1)
        ->andWhere('deleted')->eq(0)
        ->fetchPairs();

    $productIdList = array_keys($products);

    if($moduleType == 'task') $moduleType = 'story';
    if($moduleType == 'bug')  $moduleType = 'bug,story';
    if($moduleType == 'case') $moduleType = 'case,story';

    $moduleList = $this->dao->select('*')->from(TABLE_MODULE)
        ->where('root')->in($productIdList)
        ->andWhere('type')->in($moduleType)
        ->beginIF($startModulePath)->andWhere('path')->like($startModulePath)->fi()
        ->beginIF($branchID != 'all')->andWhere('branch')->eq($branchID)->fi()
        ->andWhere('deleted')->eq(0)
        ->orderBy('grade asc, branch, `order`, type')
        ->fetchAll('id');

    if(!$moduleList) return [];

    $nodes = array();
    foreach($products as $productID => $productName)
    {
        if(!isset($shadowProducts[$productID]))
        {
            $product = new stdclass();
            $product->id       = $productID;
            $product->name     = $productName;
            $product->grade    = 1;
            $product->path     = "P-$productID";
            $product->type     = 'product';
            $product->children = array();

            $nodes["P-{$productID}"] = $product;
        }

        foreach($branches[$productID] as $branchID => $branchName)
        {
            $branchPath = "B-{$branchID}";

            $branch = new stdclass();
            $branch->id       = $branchID;
            $branch->name     = $branchName;
            $branch->type     = 'branch';
            $branch->path     = !isset($shadowProducts[$productID]) ? $product->path . "," . $branchPath : $branchPath;
            $branch->children = array();

            $nodes["B-{$branchID}"] = $branch;
        }
    }

    foreach($moduleList as $module)
    {
        $branch  = ',';
        $product = '';
        if(isset($branches[$module->root][$module->branch])) $branch = ",B-{$module->branch},";
        if(!isset($shadowProducts[$module->root])) $product = "P-{$module->root}";

        $module->path = $product . $branch . trim($module->path, ',');

        $module->path = str_replace(',,', ',', $module->path);

        $nodes[$module->id] = $module;
    }

    $nodes = $this->fixParent($nodes);

    return $this->buildTreeData($nodes);
}

/**
 * Get the project tree data.
 *
 * @param  array  $projectIdList
 * @param  string $startModulePath
 * @access private
 * @return array
 */
private function getProjectTreeData($projectIdList, $startModulePath)
{
    $moduleList = $this->dao->select('*')->from(TABLE_MODULE)
        ->where('root')->in($projectIdList)
        ->andWhere('type')->eq('task')
        ->beginIF($startModulePath)->andWhere('path')->like($startModulePath)->fi()
        ->andWhere('deleted')->eq(0)
        ->orderBy('grade asc, branch, `order`, type')
        ->fetchAll('id');

    if(!$moduleList) return [];

    $moduleList = $this->fixParent($moduleList);

    return $this->buildTreeData($moduleList);
}

/**
 * Get productID and branch.
 *
 * @param  object  $module
 * @param  array   $linkParams
 * @access private
 * @return array
 */
private function getProductAndBranch($module, $linkParams)
{
    preg_match('/P-(\d+)/', $module->path, $matches);
    $linkParams['productID'] = $matches[1];

    preg_match('/B-(\d+)/', $module->path, $branchMatches);
    $linkParams['branch'] = isset($branchMatches[1]) ? $branchMatches[1] : 'all';

    return $linkParams;
}

/**
 * Filter products by branch.
 *
 * @param  string  $branch
 * @param  array   $linkParams
 * @param  array   $products
 * @param  array   $branchGroups
 * @access private
 * @return array
 */
private function filterProductsByBranch($branch, $linkParams, $products, $branchGroups)
{
    if(isset($linkParams['productID']) && $linkParams['productID'])
    {
        $productID    = $linkParams['productID'];
        $products     = $this->dao->select('id, name')->from(TABLE_PRODUCT)->where('id')->eq($productID)->fetchPairs('id', 'name');
        $branchGroups = $this->loadModel('branch')->getByProducts(array_keys($products));
    }

    if(isset($linkParams['branch']) && $linkParams['branch'] != 'all')
    {
        $branchGroups = [];
        $branch       = $linkParams['branch'];

        $branchData = $this->dao->select('name, product')->from(TABLE_BRANCH)->where('id')->eq($branch)->fetch();

        $branchGroups[$linkParams['productID']][$branch] = !empty($branchData) ? $branchData->name : $this->lang->trunk;
    }

    return array($branch, $products, $branchGroups);
}

/**
 * Create the link for the tree.
 *
 * @param  array $params
 * @access private
 * @return string
 */
private function create_TaskLink($params)
{
    $paramNames = array('chproject', 'intanceProjectID', 'type', 'param');
    foreach($paramNames as $paramName) $sotedParams[$paramName] = $params[$paramName];
    $params = http_build_query($sotedParams);

    return helper::createLink('chproject', 'task', $params);
}

/**
 * Create the link for the tree.
 *
 * @param  array $params
 * @access private
 * @return string
 */
private function create_StoryLink($params)
{
    $paramNames = array('chproject', 'intanceProjectID', 'storyType', 'orderBy', 'type', 'param');
    foreach($paramNames as $paramName) $sotedParams[$paramName] = $params[$paramName];
    $params = http_build_query($sotedParams);

    return helper::createLink('chproject', 'story', $params);
}

/**
 * Create the bug link for the tree.
 *
 * @param  array   $params
 * @access private
 * @return string
 */
private function create_BugLink($params)
{
    $paramNames = array('chproject', 'intanceProjectID', 'productID', 'branch', 'orderBy', 'build', 'type', 'param');
    foreach($paramNames as $paramName) $sotedParams[$paramName] = $params[$paramName];
    $params = http_build_query($sotedParams);

    return helper::createLink('chproject', 'bug', $params);
}

/**
 * Create the case link for the tree.
 *
 * @param  array   $params
 * @access private
 * @return string
 */
private function create_CaseLink($params)
{
    $paramNames = array('chproject', 'intanceProjectID', 'productID', 'branch', 'type', 'param', 'orderBy');
    foreach($paramNames as $paramName) $sotedParams[$paramName] = $params[$paramName];
    $params = http_build_query($sotedParams);

    return helper::createLink('chproject', 'testcase', $params);
}

/**
 * Fix module's parent by path.
 *
 * @param  array $modules
 * @access private
 * @return array
 */
private function fixParent($modules)
{
    foreach($modules as $moduleID => $module)
    {
        $paths = explode(',', trim($module->path, ','));

        if(count($paths) > 1)
        {
            $parentID = $paths[count($paths) - 2];
            $modules[$moduleID]->parent = $parentID;
        }
        else
        {
            $modules[$moduleID]->parent = 0;
        }
    }

    return $modules;
}

/**
 * Build the tree data.
 *
 * @param  array $modules
 * @param  int   $parentID
 * @param  array $tree
 * @access private
 * @return array
 */
public function buildTreeData($modules)
{
    $nodes = [];
    $tree  = [];

    foreach($modules as $moduleID => $module)
    {
        $module->path = trim($module->path, ',');

        $grade = count(explode(',', $module->path));

        $nodes[$moduleID] = new stdclass();
        $nodes[$moduleID]->id       = $module->id;
        $nodes[$moduleID]->name     = $module->name;
        $nodes[$moduleID]->path     = $module->path;
        $nodes[$moduleID]->grade    = $grade;
        $nodes[$moduleID]->type     = isset($module->type) ? $module->type : 'module';
        $nodes[$moduleID]->children = [];
    }

    foreach($nodes as $id => $node)
    {
        if($node->grade == 1)
        {
            $tree[$id] = $node;
        }
        else
        {
            $currentNode = &$tree;

            foreach(explode(',', $node->path) as $path)
            {
                if(!isset($currentNode[$path]))
                {
                    $currentNode[$path] = new stdclass();
                    $currentNode[$path]->id       = $nodes[$path]->id;
                    $currentNode[$path]->name     = $nodes[$path]->name;
                    $currentNode[$path]->path     = $nodes[$path]->path;
                    $currentNode[$path]->grade    = $nodes[$path]->grade;
                    $currentNode[$path]->type     = $nodes[$path]->type;
                    $currentNode[$path]->children = [];
                }

                $currentNode = &$currentNode[$path]->children;
            }
        }
    }

    $tree = array_map(function($node)
    {
        if (empty($node->children)) unset($node->children);

        return $node;
    }, $tree);

    return $tree;
}

/**
 * Build the module tree HTML.
 *
 * @param  string $linkType
 * @param  array  $modules
 * @param  array  $linkParams
 * @access private
 * @return string
 */
private function buildModuelTreeHTML($linkType, $modules, $linkParams)
{
    $html     = '';
    $linkFunc = "create_{$linkType}Link";

    foreach($modules as $module)
    {
        if(in_array($linkType, array('bug', 'case'))) $linkParams = $this->getProductAndBranch($module, $linkParams);

        $linkParams['productID'] = is_numeric($linkParams['productID']) ? $linkParams['productID'] : 0;

        if($module->type == 'branch')
        {
            $html .= '<li>' . $module->name;
        }
        else
        {
            $linkParams['param'] = $module->id;

            if(!in_array($linkType, array('bug', 'case')))
            {
                $linkParams['type'] = $module->type == 'product' ? 'byProduct' : 'byModule';
            }

            if(in_array($linkType, array('bug', 'case')))
            {
                if($module->type == 'product')
                {
                    $linkParams['productID'] = $module->id;
                    $linkParams['param']     = 0;
                }
            }

            $link  = call_user_func_array(array($this, $linkFunc), array($linkParams));
            $html .= '<li>' . html::a($link, $module->name, '_self', "id='module{$module->id}'");
        }

        if(!empty($module->children))
        {
            $html .= "<ul>" . $this->buildModuelTreeHTML($linkType, $module->children, $linkParams) . "</ul>";
        }

        $html .= '</li>';
    }

    return $html;
}
