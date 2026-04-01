<?php
/**
 * Print html for tree.
 *
 * @param object $trees
 * @param bool   $hasProduct true|false
 * @access pubic
 * @return string
 */
public function printTree($trees, $hasProduct = true)
{
    $html = '';
    foreach($trees as $tree)
    {
        if(is_array($tree)) $tree = (object)$tree;
        switch($tree->type)
        {
            case 'module':
                $this->app->loadLang('tree');
                $html .= "<li class='item-module'>";
                $html .= "<a class='tree-toggle'><span class='title' title='{$tree->name}'>" . $tree->name . '</span></a>';
                break;
            case 'task':
                $link = helper::createLink('execution', 'treeTask', "taskID={$tree->id}");
                $html .= '<li class="item-task">';
                $html .= '<a class="tree-link" href="' . $link . '"><span class="label label-type">' . ($tree->parent > 0 ? $this->lang->task->children : $this->lang->task->common) . "</span><span class='title' title='{$tree->title}'>" . $tree->title . '</span> <span class="user"><i class="icon icon-person"></i> ' . (empty($tree->assignedTo) ? $tree->openedBy : $tree->assignedTo) . '</span><span class="label label-id">' . $tree->id . '</span></a>';
                break;
            case 'product':
                $this->app->loadLang('product');
                $productName = $hasProduct ? $this->lang->productCommon : $this->lang->projectCommon;
                if($this->app->tab == 'chteam')
                {
                    $product = $this->loadModel('product')->getById($tree->root);
                    $productName = $product->shadow ? $this->lang->projectCommon : $this->lang->productCommon;
                }
                $html .= '<li class="item-product">';
                $html .= '<a class="tree-toggle"><span class="label label-type">' . $productName . "</span><span class='title' title='{$tree->name}'>" . $tree->name . '</span></a>';
                break;
            case 'story':
                $this->app->loadLang('story');
                $link = helper::createLink('execution', 'treeStory', "storyID={$tree->storyId}");
                $html .= '<li class="item-story">';
                $html .= '<a class="tree-link" href="' . $link . '"><span class="label label-type">' . $this->lang->story->common . "</span><span class='title' title='{$tree->title}'>" . $tree->title . '</span> <span class="user"><i class="icon icon-person"></i> ' . (empty($tree->assignedTo) ? $tree->openedBy : $tree->assignedTo) . '</span><span class="label label-id">' . $tree->storyId . '</span></a>';
                break;
            case 'branch':
                $this->app->loadLang('branch');
                $html .= "<li class='item-module'>";
                $html .= "<a class='tree-toggle'><span class='label label-type'>{$this->lang->branch->common}</span><span class='title' title='{$tree->name}'>{$tree->name}</span></a>";
                break;
        }
        if(isset($tree->children))
        {
            $html .= '<ul>';
            $html .= $this->printTree($tree->children, $hasProduct);
            $html .= '</ul>';
        }
        $html .= '</li>';
    }
    return $html;
}

/**
 * Fill tasks in tree.
 * @param  object $tree
 * @param  int    $executionID
 * @access public
 * @return object
 */
public function fillTasksInTree($node, $executionID)
{
    $node = (object)$node;
    static $storyGroups, $taskGroups;
    if(empty($storyGroups))
    {
        if($this->config->vision == 'lite')
        {
            $execution = $this->getById($executionID);
            $stories = $this->dao->select('t2.*, t1.version as taskVersion')->from(TABLE_PROJECTSTORY)->alias('t1')
                ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
                ->where('t1.project')->eq((int)$execution->project)
                ->andWhere('t2.deleted')->eq(0)
                ->orderBy('t1.`order`_desc')
                ->fetchAll();
        }
        else
        {
            $stories = $this->dao->select('t2.*, t1.version as taskVersion')->from(TABLE_PROJECTSTORY)->alias('t1')
                ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
                ->where('t1.project')->in($executionID)
                ->andWhere('t2.deleted')->eq(0)
                ->andWhere('t2.type')->eq('story')
                ->orderBy('t1.`order`_desc')
                ->fetchAll();
        }
        $storyGroups = array();
        foreach($stories as $story) $storyGroups[$story->product][$story->module][$story->id] = $story;
    }
    if(empty($taskGroups))
    {
        $tasks = $this->dao->select('*')->from(TABLE_TASK)
            ->where('execution')->in($executionID)
            ->andWhere('deleted')->eq(0)
            ->andWhere('parent')->lt(1)
            ->orderBy('id_desc')
            ->fetchAll();
        $childTasks = $this->dao->select('*')->from(TABLE_TASK)
            ->where('execution')->in($executionID)
            ->andWhere('deleted')->eq(0)
            ->andWhere('parent')->ne(0)
            ->orderBy('id_desc')
            ->fetchGroup('parent');
        $taskGroups = array();
        foreach($tasks as $task)
        {
            $taskGroups[$task->module][$task->story][$task->id] = $task;
            if(!empty($childTasks[$task->id]))
            {
                $taskGroups[$task->module][$task->story][$task->id]->children = $childTasks[$task->id];
            }
        }
    }

    if(!empty($node->children))
    {
        foreach($node->children as $i => $child)
        {
            $subNode = $this->fillTasksInTree($child, $executionID);
            /* Remove no children node. */
            if($subNode->type != 'story' and $subNode->type != 'task' and empty($subNode->children))
            {
                unset($node->children[$i]);
            }
            else
            {
                $node->children[$i] = $subNode;
            }
        }
    }

    if(!isset($node->id))$node->id = 0;
    if($node->type == 'story')
    {
        static $users;
        if(empty($users)) $users = $this->loadModel('user')->getPairs('noletter');

        $node->type = 'module';
        $stories = isset($storyGroups[$node->root][$node->id]) ? $storyGroups[$node->root][$node->id] : array();
        foreach($stories as $story)
        {
            $storyItem = new stdclass();
            $storyItem->type          = 'story';
            $storyItem->id            = 'story' . $story->id;
            $storyItem->title         = $story->title;
            $storyItem->color         = $story->color;
            $storyItem->pri           = $story->pri;
            $storyItem->storyId       = $story->id;
            $storyItem->openedBy      = zget($users, $story->openedBy);
            $storyItem->assignedTo    = zget($users, $story->assignedTo);
            $storyItem->url           = helper::createLink('execution', 'storyView', "storyID=$story->id&execution=$executionID");
            $storyItem->taskCreateUrl = helper::createLink('task', 'batchCreate', "executionID={$executionID}&story={$story->id}");

            $storyTasks = isset($taskGroups[$node->id][$story->id]) ? $taskGroups[$node->id][$story->id] : array();
            if(!empty($storyTasks))
            {
                $taskItems             = $this->formatTasksForTree($storyTasks, $story);
                $storyItem->tasksCount = count($taskItems);
                $storyItem->children   = $taskItems;
            }

            $node->children[] = $storyItem;
        }

        /* Append for task of no story and node is not root. */
        if($node->id and isset($taskGroups[$node->id][0]))
        {
            $taskItems = $this->formatTasksForTree($taskGroups[$node->id][0]);
            $node->tasksCount = count($taskItems);
            foreach($taskItems as $taskItem) $node->children[] = $taskItem;
        }
    }
    elseif($node->type == 'task')
    {
        $node->type       = 'module';
        $node->tasksCount = 0;
        if(isset($taskGroups[$node->id]))
        {
            foreach($taskGroups[$node->id] as $tasks)
            {
                $taskItems = $this->formatTasksForTree($tasks);
                $node->tasksCount += count($taskItems);
                foreach($taskItems as $taskItem)
                {
                    if($taskItem->story > 0) continue; // If a story link to task, display task in story tree.

                    $node->children[$taskItem->id] = $taskItem;
                    if(!empty($tasks[$taskItem->id]->children))
                    {
                        $task = $this->formatTasksForTree($tasks[$taskItem->id]->children);
                        $node->children[$taskItem->id]->children=$task;
                        $node->tasksCount += count($task);
                    }
                }
            }
            $node->children = isset($node->children) ? array_values($node->children) : array();
        }
    }
    elseif($node->type == 'product')
    {
        $node->title = $node->name;
        if(isset($node->children[0]) and empty($node->children[0]->children)) array_shift($node->children);
    }

    $node->actions = false;
    if(!empty($node->children)) $node->children = array_values($node->children);
    return $node;
}

/**
 * Get executions tree data
 * @param  int     $executionID
 * @access public
 * @return array
 */
public function getTree($executionID)
{
    $fullTrees = $this->loadModel('tree')->getTaskStructure($executionID, 0);
    array_unshift($fullTrees, array('id' => 0, 'name' => '/', 'type' => 'task', 'actions' => false, 'root' => $executionID));
    foreach($fullTrees as $i => $tree)
    {
        $tree = (object)$tree;
        if($tree->type == 'product') array_unshift($tree->children, array('id' => 0, 'name' => '/', 'type' => 'story', 'actions' => false, 'root' => $tree->root));
        $fullTree = $this->fillTasksInTree($tree, $executionID);
        if(empty($fullTree->children))
        {
            unset($fullTrees[$i]);
        }
        else
        {
            $fullTrees[$i] = $fullTree;
        }
    }
    if(isset($fullTrees[0]) and empty($fullTrees[0]->children)) array_shift($fullTrees);
    return array_values($fullTrees);
}

/**
 * Get pairs by project id.
 *
 * @param  int    $projectID
 * @param  string $fields
 * @access public
 * @return array
 */
public function getPairsByProjectID($projectID, $fields)
{
    return $this->dao->select($fields)->from(TABLE_PROJECT)->where('project')->eq($projectID)->fetchPairs();
}

/**
 * Get ch project id by execution id.
 *
 * @param  int    $executionID
 * @access public
 * @return int
 */
public function getChProjectByExecution($executionID)
{
    $chProjectID = $this->dao->select('ch')->from(TABLE_CHPROJECTINTANCES)->where('zentao')->eq($executionID)->fetch('ch');
    $chProject   = $this->dao->select('*')->from(TABLE_CHPROJECT)->where('id')->eq($chProjectID)->andWhere('deleted')->eq(0)->limit(1)->fetch();

    return $chProject ? $chProjectID : 0;
}

/**
 * Get chproject CFD data to display.
 *
 * @param  array  $executionIdList
 * @param  string $type
 * @param  array  $dateList
 * @access public
 * @return array
 */
public function getChprojectCFDData($executionIdList, $dateList = array(), $type = 'story')
{
    $setGroup = $this->dao->select("date, `count` AS value, `name`, execution")->from(TABLE_CFD)
        ->where('execution')->in($executionIdList)
        ->andWhere('type')->eq($type)
        ->andWhere('date')->in($dateList)
        ->orderBy('date DESC, id asc')
        ->fetchGroup('name');

    $data = [];
    foreach($setGroup as $name => $sets)
    {
        foreach($sets as $set)
        {
            $execution = $this->getById($set->execution);
            $date      = $set->date;

            if($date < $execution->begin) continue;
            if(!isset($data[$name][$date]))
            {
                $data[$name][$date] = $set;
            }
            else
            {
                $data[$name][$date]->value += $set->value;
            }
        }
    }

    return $data;
}

/**
 * Get Kanban tasks
 *
 * @param  int          $executionID
 * @param  string       $orderBy
 * @param  object       $pager
 * @param  array|string $excludeTasks
 * @access public
 * @return void
 */
public function getKanbanTasks($executionID, $orderBy = 'status_asc, id_desc', $pager = null, $excludeTasks = '')
{
    $tasks = $this->dao->select('t1.*, t2.id AS storyID, t2.title AS storyTitle, t2.version AS latestStoryVersion, t2.status AS storyStatus, t3.realname AS assignedToRealName')
        ->from(TABLE_TASK)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_USER)->alias('t3')->on('t1.assignedTo = t3.account')
        ->where('t1.execution')->in($executionID)
        ->andWhere('t1.deleted')->eq(0)
        ->andWhere('t1.parent')->ge(0)
        ->beginIF($excludeTasks)->andWhere('t1.id')->notIN($excludeTasks)->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll('id');

    if($tasks) return $this->loadModel('task')->processTasks($tasks);
    return array();
}

/**
 * Print execution nested list.
 *
 * @param  int    $execution
 * @param  int    $isChild
 * @param  int    $users
 * @param  int    $productID
 * @param  string $project
 * @access public
 * @return void
 */
public function printNestedList($execution, $isChild, $users, $productID, $project = '')
{
    $this->loadModel('task');
    $this->loadModel('execution');
    $this->loadModel('programplan');

    $today = helper::today();

    if(!$isChild)
    {
        $trClass = 'is-top-level table-nest-child-hide';
        $trAttrs = "data-id='$execution->id' data-order='$execution->order' data-nested='true' data-status={$execution->status}";
    }
    else
    {
        if(strpos($execution->path, ",$execution->project,") !== false)
        {
            $path = explode(',', trim($execution->path, ','));
            $path = array_slice($path, array_search($execution->project, $path) + 1);
            $path = implode(',', $path);
        }

        $trClass  = 'table-nest-hide';
        $trAttrs  = "data-id={$execution->id} data-parent={$execution->parent} data-status={$execution->status}";
        $trAttrs .= " data-nest-parent='$execution->parent' data-order='$execution->order' data-nest-path='$path'";
    }

    $burns = join(',', $execution->burns);
    echo "<tr $trAttrs class='$trClass'>";
    echo "<td class='c-name text-left flex sort-handler'>";
    if(common::hasPriv('execution', 'batchEdit')) echo "<span id=$execution->id class='table-nest-icon icon table-nest-toggle'></span>";
    $spanClass = $execution->type == 'stage' ? 'label-warning' : 'label-info';
    echo "<span class='project-type-label label label-outline $spanClass'>{$this->lang->execution->typeList[$execution->type]}</span> ";
    if(empty($execution->children))
    {
        echo html::a(helper::createLink('execution', 'view', "executionID=$execution->id"), $execution->name, '', "class='text-ellipsis' title='{$execution->name}'");
        if(!helper::isZeroDate($execution->end))
        {
            if($execution->status != 'closed')
            {
                echo strtotime($today) > strtotime($execution->end) ? '<span class="label label-danger label-badge">' . $this->lang->execution->delayed . '</span>' : '';
            }
        }
    }
    else
    {
        echo "<span class='text-ellipsis'>" . $execution->name . '</span>';
        if(!helper::isZeroDate($execution->end))
        {
            if($execution->status != 'closed')
            {
                echo strtotime($today) > strtotime($execution->end) ? '<span class="label label-danger label-badge">' . $this->lang->execution->delayed . '</span>' : '';
            }
        }
    }
    if(!empty($execution->division) and $execution->hasProduct) echo "<td class='text-left' title='{$execution->productName}'>{$execution->productName}</td>";
    echo "<td class='status-{$execution->status} text-center'>" . zget($this->lang->project->statusList, $execution->status) . '</td>';
    echo '<td>' . zget($users, $execution->PM) . '</td>';
    echo helper::isZeroDate($execution->begin) ? '<td class="c-date"></td>' : '<td class="c-date">' . $execution->begin . '</td>';
    echo helper::isZeroDate($execution->end) ? '<td class="endDate c-date"></td>' : '<td class="endDate c-date">' . $execution->end . '</td>';
    echo "<td class='hours text-right' title='{$execution->estimate}{$this->lang->execution->workHour}'>" . $execution->estimate . $this->lang->execution->workHourUnit . '</td>';
    echo "<td class='hours text-right' title='{$execution->consumed}{$this->lang->execution->workHour}'>" . $execution->consumed . $this->lang->execution->workHourUnit . '</td>';
    echo "<td class='hours text-right' title='{$execution->left}{$this->lang->execution->workHour}'>" . $execution->left . $this->lang->execution->workHourUnit . '</td>';
    echo '<td>' . html::ring($execution->progress) . '</td>';
    echo "<td id='spark-{$execution->id}' class='sparkline text-left no-padding' values='$burns'></td>";
    echo '<td class="c-actions text-left">';

    $title    = '';
    $disabled = $execution->status == 'wait' ? '' : 'disabled';
    $this->app->loadLang('stage');
    if($project and $project->model == 'ipd' and !$execution->parallel)
    {
        $title    = ($execution->ipdStage['canStart'] or $execution->ipdStage['isFirst']) ? '' : sprintf($this->lang->execution->disabledTip->startTip, $this->lang->stage->ipdTypeList[$execution->ipdStage['preAttribute']], $this->lang->stage->ipdTypeList[$execution->attribute]);
        $disabled = $execution->ipdStage['canStart'] ? $disabled : 'disabled';
    }
    echo common::buildIconButton('execution', 'start', "executionID={$execution->id}", $execution, 'list', '', '', 'iframe', true, $disabled, $title, '', empty($disabled));

    $class = !empty($execution->children) ? 'disabled' : '';
    echo $this->buildMenu('task', 'create', "executionID={$execution->id}", '', 'browse', '', '', $class, false, "data-app='execution'");

    if(empty($project)) $project = $this->loadModel('project')->getByID($execution->project);
    if($execution->type == 'stage' or ($this->app->tab == 'project' and !empty($project->model) and $project->model == 'waterfallplus'))
    {
        $isCreateTask = $this->loadModel('programplan')->isCreateTask($execution->id);
        $disabled     = ($isCreateTask and $execution->type == 'stage') ? '' : ' disabled';
        $title        = !$isCreateTask ? $this->lang->programplan->error->createdTask : $this->lang->programplan->createSubPlan;
        $title        = (!empty($disabled) and $execution->type != 'stage') ? $this->lang->programplan->error->notStage : $title;
        echo $this->buildMenu('programplan', 'create', "program={$execution->project}&productID=$productID&planID=$execution->id", $execution, 'browse', 'split', '', $disabled, '', '', $title);
    }

    $chProjectID = $this->getChProjectByExecution($execution->id);

    if($execution->type == 'stage') echo $this->buildMenu('programplan', 'edit', "stageID=$execution->id&projectID=$execution->project", $execution, 'browse', '', '', 'iframe', true);
    if($execution->type != 'stage' && empty($chProjectID)) echo $this->buildMenu('execution', 'edit', "executionID=$execution->id", $execution, 'browse', '', '', 'iframe', true);

    $disabled = !empty($execution->children) ? ' disabled' : '';
    if($this->config->systemMode == 'PLM' and in_array($execution->attribute, array_keys($this->lang->stage->ipdTypeList))) $disabled = '';

    if(empty($chProjectID))
    {
        if($execution->status != 'closed' && common::hasPriv('execution', 'close', $execution))
        {
            $ipdDisabled = '';
            $title = $this->lang->execution->close;
            if(isset($execution->ipdStage['canClose']) and !$execution->ipdStage['canClose'] and !$isChild)
            {
                $ipdDisabled = ' disabled ';
                $title       = $execution->attribute == 'launch' ? $this->lang->execution->disabledTip->launchTip : $this->lang->execution->disabledTip->closeTip;
            }
            echo common::buildIconButton('execution', 'close', "stageID={$execution->id}", $execution, 'list', 'off', 'hiddenwin', $disabled . $ipdDisabled . ' iframe', true, (!empty($disabled) || !empty($ipdDisabled)) ? ' disabled' : '', $title, 0, empty($disabled) && empty($ipdDisabled));
        }
        elseif($execution->status == 'closed' and common::hasPriv('execution', 'activate', $execution))
        {
            echo $this->buildMenu('execution', 'activate', "stageID=$execution->id", $execution, 'browse', 'magic', 'hiddenwin' , $disabled . ' iframe', true, '', $this->lang->execution->activate);
        }
    }

    if(common::hasPriv('execution', 'delete', $execution)) echo $this->buildMenu('execution', 'delete', "stageID=$execution->id&confirm=no", $execution, 'browse', 'trash', 'hiddenwin' , $disabled, '', '', $this->lang->delete);

    echo '</td>';
    echo '</tr>';

    if(!empty($execution->children))
    {
        foreach($execution->children as $child)
        {
            $child->division = $execution->division;
            $this->printNestedList($child, true, $users, $productID, $project);
        }
    }

    if(!empty($execution->tasks))
    {
        foreach($execution->tasks as $task)
        {
            $showmore = (count($execution->tasks) == 50) && ($task == end($execution->tasks));
            if($project->model == 'ipd')
            {
                $canStart = $execution->status == 'wait' ? $execution->ipdStage['canStart'] : 1;
                if($execution->status == 'close') $canStart = false;
                if($project->parallel) $canStart = true;
                $task->ipdStage = new stdclass();
                $task->ipdStage->canStart      = $canStart;
                $task->ipdStage->taskStartTip  = sprintf($this->lang->execution->disabledTip->taskStartTip, $this->lang->stage->ipdTypeList[$execution->ipdStage['preAttribute']], $this->lang->stage->ipdTypeList[$execution->attribute]);
                $task->ipdStage->taskFinishTip = sprintf($this->lang->execution->disabledTip->taskFinishTip, $this->lang->stage->ipdTypeList[$execution->ipdStage['preAttribute']], $this->lang->stage->ipdTypeList[$execution->attribute]);
                $task->ipdStage->taskRecordTip = sprintf($this->lang->execution->disabledTip->taskRecordTip, $this->lang->stage->ipdTypeList[$execution->ipdStage['preAttribute']], $this->lang->stage->ipdTypeList[$execution->attribute]);
            }
            echo $this->task->buildNestedList($execution, $task, false, $showmore, $users);
        }
    }

    if(!empty($execution->points) and $this->cookie->showStage)
    {
        $pendingReviews = $this->loadModel('approval')->getPendingReviews('review');
        foreach($execution->points as $point) echo $this->buildPointList($execution, $point, $pendingReviews);
    }
}

/**
 * Set menu.
 *
 * @param  int    $executionID
 * @param  int    $buildID
 * @param  string $extra
 * @access public
 * @return void
 */
public function setMenu($executionID, $buildID = 0, $extra = '')
{
    $execution = $this->getByID($executionID);
    if(!$execution) return;

    if($execution and $execution->type == 'kanban')
    {
        global $lang;
        $lang->executionCommon = $lang->execution->kanban;
        include $this->app->getModulePath('', 'execution') . 'lang/' . $this->app->getClientLang() . '.php';

        $this->lang->execution->menu           = new stdclass();
        $this->lang->execution->menu->kanban   = array('link' => "{$this->lang->kanban->common}|execution|kanban|executionID=%s", 'subModule' => 'task');
        $this->lang->execution->menu->CFD      = array('link' => "{$this->lang->execution->CFD}|execution|cfd|executionID=%s");
        $this->lang->execution->menu->build    = array('link' => "{$this->lang->build->common}|execution|build|executionID=%s");
        $this->lang->execution->menu->settings = array('link' => "{$this->lang->settings}|execution|view|executionID=%s", 'subModule' => 'personnel', 'alias' => 'edit,manageproducts,team,whitelist,addwhitelist,managemembers', 'class' => 'dropdown dropdown-hover');
        $this->lang->execution->dividerMenu    = '';

        $this->lang->execution->menu->settings['subMenu']            = new stdclass();
        $this->lang->execution->menu->settings['subMenu']->view      = array('link' => "{$this->lang->overview}|execution|view|executionID=%s", 'subModule' => 'view', 'alias' => 'edit,start,suspend,putoff,close');
        $this->lang->execution->menu->settings['subMenu']->products  = array('link' => "{$this->lang->productCommon}|execution|manageproducts|executionID=%s");
        $this->lang->execution->menu->settings['subMenu']->team      = array('link' => "{$this->lang->team->common}|execution|team|executionID=%s", 'alias' => 'managemembers');
        $this->lang->execution->menu->settings['subMenu']->whitelist = array('link' => "{$this->lang->whitelist}|execution|whitelist|executionID=%s", 'subModule' => 'personnel', 'alias' => 'addwhitelist');
    }

    $project = $this->loadModel('project')->getByID($execution->project);

    if($execution->type == 'stage' or (!empty($project) and $project->model == 'waterfallplus')) unset($this->lang->execution->menu->settings['subMenu']->products);

    if(!$this->app->user->admin and strpos(",{$this->app->user->view->sprints},", ",$executionID,") === false and !defined('TUTORIAL') and $executionID != 0) return print(js::error($this->lang->execution->accessDenied) . js::locate('back'));

    $executions = $this->fetchPairs($execution->project, 'all');
    if(!$executionID and $this->session->execution) $executionID = $this->session->execution;
    if(!$executionID) $executionID = key($executions);
    if($execution->multiple and !isset($executions[$executionID])) $executionID = key($executions);
    if($execution->multiple and $executions and (!isset($executions[$executionID]) or !$this->checkPriv($executionID))) $this->accessDenied();
    $this->session->set('execution', $executionID, $this->app->tab);

    if($execution and $execution->type == 'stage')
    {
        global $lang;
        $this->app->loadLang('project');
        $lang->executionCommon = $lang->project->stage;
        include $this->app->getModulePath('', 'execution') . 'lang/' . $this->app->getClientLang() . '.php';
    }

    if(isset($execution->acl) and $execution->acl != 'private') unset($this->lang->execution->menu->settings['subMenu']->whitelist);

    $chProjectID = $this->getChProjectByExecution($execution->id);
    if($chProjectID)
    {
        unset($this->lang->execution->menu->settings['subMenu']->products);
        unset($this->lang->execution->menu->settings['subMenu']->team);
        unset($this->lang->execution->menu->settings['subMenu']->whitelist);
    }

    /* Redjust menus. */
    $features = $this->getExecutionFeatures($execution);
    if(!$features['story'])  unset($this->lang->execution->menu->story);
    if(!$features['story'])  unset($this->lang->execution->menu->view['subMenu']->groupTask);
    if(!$features['story'])  unset($this->lang->execution->menu->view['subMenu']->tree);
    if(!$features['qa'])     unset($this->lang->execution->menu->qa);
    if(!$features['devops']) unset($this->lang->execution->menu->devops);
    if(!$features['build'])  unset($this->lang->execution->menu->build);
    if(!$features['burn'])   unset($this->lang->execution->menu->burn);
    if(!$features['other'])  unset($this->lang->execution->menu->other);
    if(!$features['story'] and $this->config->edition == 'open') unset($this->lang->execution->menu->view);

    $moduleName = $this->app->getModuleName();
    $methodName = $this->app->getMethodName();
    if($moduleName == 'repo' || $moduleName == 'mr')
    {
        $repoPairs = $this->loadModel('repo')->getRepoPairs('execution', $executionID);

        $showMR = false;
        if(common::hasPriv('mr', 'browse'))
        {
            foreach($repoPairs as $repoName)
            {
                preg_match('/^\[(\w+)\]/', $repoName, $matches);
                if(isset($matches[1]) && in_array($matches[1], $this->config->repo->gitServiceList)) $showMR = true;
            }
        }
        if(!$showMR) unset($this->lang->execution->menu->devops['subMenu']->mr);
        if(!$repoPairs || !common::hasPriv('repo', 'review')) unset($this->lang->execution->menu->devops['subMenu']->review);

        if(empty($this->lang->execution->menu->devops['subMenu']->mr) && empty($this->lang->execution->menu->devops['subMenu']->review)) unset($this->lang->execution->menu->devops['subMenu']);
    }

    if($this->cookie->executionMode == 'noclosed' and $execution and ($execution->status == 'done' or $execution->status == 'closed'))
    {
        setcookie('executionMode', 'all');
        $this->cookie->executionMode = 'all';
    }

    if(empty($execution->hasProduct)) unset($this->lang->execution->menu->settings['subMenu']->products);

    $this->lang->switcherMenu = $this->getSwitcher($executionID, $this->app->rawModule, $this->app->rawMethod);
    common::setMenuVars('execution', $executionID);

    $this->loadModel('project')->setNoMultipleMenu($executionID);

    if(isset($this->lang->execution->menu->storyGroup)) unset($this->lang->execution->menu->storyGroup);
    if(isset($this->lang->execution->menu->story['dropMenu']) and $methodName == 'storykanban')
    {
        $this->lang->execution->menu->story['link']            = str_replace(array($this->lang->common->story, 'story'), array($this->lang->SRCommon, 'storykanban'), $this->lang->execution->menu->story['link']);
        $this->lang->execution->menu->story['dropMenu']->story = str_replace('execution|story', 'execution|storykanban', $this->lang->execution->menu->story['dropMenu']->story);
    }
}


/**
 * Get burn data for flot
 *
 * @param  array  $executionIDs
 * @param  string $burnBy
 * @param  bool   $showDelay
 * @param  array  $dateList
 * @access public
 * @return array
 */
public function getExecutionBurnDataFlot($executionIDs, $burnBy = '', $showDelay = false, $dateList = array())
{
    /* Get execution and burn counts. */
    $executionBegin = "";
    $executionEnd   = "";

    foreach($executionIDs as $executionID)
    {
        $execution = $this->getById($executionID);
        if(empty($executionBegin))
        {
            $executionBegin = $execution->begin;
        }
        else
        {
            if($executionBegin > $execution->begin) $executionBegin = $execution->begin;
        }

        if(empty($executionEnd))
        {
            $executionEnd = $execution->end;
        }
        else
        {
            if($executionEnd < $execution->end) $executionEnd = $execution->end;
        }
    }

    /* If the burnCounts > $itemCounts, get the latest $itemCounts records. */
    $sets = $this->dao->select("date AS name, sum(`$burnBy`) AS value, sum(`$burnBy`)")->from(TABLE_BURN)
        ->where('execution')->in($executionIDs)
        ->andWhere('task')->eq(0)
        ->groupBy('date')
        ->orderBy('date DESC')->fetchAll('name');

    $burnData = array();
    foreach($sets as $date => $set)
    {
        if($date < $executionBegin) continue;
        if(!$showDelay and $date > $executionEnd) $set->value = 'null';
        if($showDelay  and $date < $executionEnd) $set->value = 'null';

        $burnData[$date] = $set;
    }

    foreach($dateList as $date)
    {
        if(!isset($burnData[$date]))
        {
            if(($showDelay and $date < $executionEnd) or (!$showDelay and $date > $executionEnd))
            {
                $set = new stdClass();
                $set->name    = $date;
                $set->value   = 'null';
                $set->$burnBy = 0;

                $burnData[$date] = $set;
            }
        }
    }

    krsort($burnData);
    $burnData = array_reverse($burnData);

    return $burnData;
}


/**
 * Build burn data.
 *
 * @param  array  $executionIDs
 * @param  array  $dateList
 * @param  string $type
 * @param  string $burnBy
 * @param  string $executionEnd
 * @access public
 * @return array
 */
public function buildExecutionBurnData($executionIDs, $dateList, $type, $burnBy = 'left', $executionEnd = '')
{
    $this->loadModel('report');
    $burnBy = $burnBy ? $burnBy : 'left';

    $sets         = $this->getExecutionBurnDataFlot($executionIDs, $burnBy, false, $dateList);
    $limitJSON    = '[]';
    $baselineJSON = '[]';

    $firstBurn    = empty($sets) ? 0 : reset($sets);
    $firstTime    = !empty($firstBurn->$burnBy) ? $firstBurn->$burnBy : (!empty($firstBurn->value) ? $firstBurn->value : 0);
    $firstTime    = $firstTime == 'null' ? 0 : $firstTime;
    /* If the $executionEnd  is passed, the guide should end of execution. */
    $days         = $executionEnd ? array_search($executionEnd, $dateList) : count($dateList) - 1;
    $rate         = $days ? $firstTime / $days : '';
    $baselineJSON = '[';
    foreach($dateList as $i => $date)
    {
        $value = ($i > $days ? 0 : round(($days - $i) * (float)$rate, 3)) . ',';
        $baselineJSON .= $value;
    }
    $baselineJSON = rtrim($baselineJSON, ',') . ']';

    $chartData['labels']   = $this->report->convertFormat($dateList, DT_DATE5);
    $chartData['burnLine'] = $this->report->createSingleJSON($sets, $dateList);
    $chartData['baseLine'] = $baselineJSON;


    $delayLineArray = array();
    foreach($executionIDs as $executionID)
    {
        $execution = $this->getById($executionID);
        if((strpos('closed,suspended', $execution->status) === false and helper::today() > $execution->end)
            or ($execution->status == 'closed'    and substr($execution->closedDate, 0, 10) > $execution->end)
            or ($execution->status == 'suspended' and $execution->suspendedDate > $execution->end))
        {
            $delayLineArray[] = $executionID;
        }
    }

    if(count($delayLineArray) > 0)
    {
        $delaySets = $this->getExecutionBurnDataFlot($delayLineArray, $burnBy, true, $dateList);
        $chartData['delayLine'] = $this->report->createSingleJSON($delaySets, $dateList);
    }

    return $chartData;
}

/**
 * Build story search form.
 *
 * @param  array  $products
 * @param  array  $branchGroups
 * @param  array  $modules
 * @param  int    $queryID
 * @param  string $actionURL
 * @param  string $type
 * @param  object $execution
 * @param  string $storyType
 * @access public
 * @return void
 */
public function buildStorySearchForm($products, $branchGroups, $modules, $queryID, $actionURL, $type = 'executionStory', $execution = null, $storyType = 'story')
{
    $this->app->loadLang('branch');
    $branchPairs  = array(BRANCH_MAIN => $this->lang->branch->main);
    $productType  = 'normal';
    $productNum   = count($products);
    $productPairs = array(0 => '');
    $branches     = empty($execution) ? array() : $this->loadModel('project')->getBranchesByProject($execution->id);

    foreach($products as $product)
    {
        $productPairs[$product->id] = $product->name;
        if($product->type != 'normal')
        {
            $productType = $product->type;
            if(isset($branches[$product->id]))
            {
                foreach($branches[$product->id] as $branchID => $branch)
                {
                    if(!isset($branchGroups[$product->id][$branchID])) continue;
                    if($branchID != BRANCH_MAIN) $branchPairs[$branchID] = ((count($products) > 1) ? $product->name . '/' : '') . $branchGroups[$product->id][$branchID];
                }
            }
        }
    }

    /* Build search form. */
    if($type == 'executionStory') $this->config->product->search['module'] = 'executionStory';
    if($type == 'chprojectStory') $this->config->product->search['module'] = 'chprojectStory';

    $this->config->product->search['fields']['title'] = str_replace($this->lang->SRCommon, $this->lang->SRCommon, $this->lang->story->title);
    $this->config->product->search['actionURL'] = $actionURL;
    $this->config->product->search['queryID']   = $queryID;
    $this->config->product->search['params']['product']['values'] = $productPairs + array('all' => $this->lang->product->allProductsOfProject);
    $this->config->product->search['params']['stage']['values']   = array('' => '') + $this->lang->story->stageList;

    if($this->config->edition != 'ipd' || ($this->config->edition == 'ipd' && $storyType == 'story')) unset($this->config->product->search['fields']['roadmap']);

    $this->loadModel('productplan');
    $plans     = array();
    $planPairs = array('' => '');
    foreach($products as $productID => $product)
    {
        $plans = $this->productplan->getBranchPlanPairs($productID, array(BRANCH_MAIN) + $product->branches, 'unexpired', true);
        foreach($plans as $plan) $planPairs += $plan;
    }
    $this->config->product->search['params']['plan']['values']   = $planPairs;
    $this->config->product->search['params']['module']['values'] = array('' => '') + $modules;
    if($productType == 'normal')
    {
        unset($this->config->product->search['fields']['branch']);
        unset($this->config->product->search['params']['branch']);
    }
    else
    {
        $this->config->product->search['fields']['branch'] = sprintf($this->lang->product->branch, $this->lang->product->branchName[$productType]);
        $this->config->product->search['params']['branch']['values'] = array('' => '') + $branchPairs;
    }

    $this->config->product->search['params']['status'] = array('operator' => '=', 'control' => 'select', 'values' => $this->lang->story->statusList);

    $project = $execution;
    if(strpos('sprint,stage,kanban', $execution->type) !== false) $project = $this->loadModel('project')->getByID($execution->project);
    if(isset($project->hasProduct) && empty($project->hasProduct))
    {
        unset($this->config->product->search['fields']['product']);

        if($project->model != 'kanban') unset($this->config->product->search['fields']['plan']);
    }

    if($storyType == 'requirement')
    {
        unset($this->config->product->search['fields']['plan']);
        if($project->model == 'ipd')
        {
            unset($this->config->product->search['fields']['stage']);
            unset($this->config->product->search['fields']['status']);
        }
    }

    $this->loadModel('search')->setSearchParams($this->config->product->search);
}

/**
 * Build CFD data.
 *
 * @param  int    $executionID
 * @param  string $type
 * @param  array  $dateList
 * @access public
 * @return array
 */
public function buildCFDData($executionID, $dateList, $type)
{
    $this->loadModel('report');
    $setGroup = $this->getCFDData($executionID, $dateList, $type);

    if($this->app->tab == 'chteam') $setGroup = $this->getChprojectCFDData($executionID, $dateList, $type);

    if(empty($setGroup)) return array();

    $chartData['labels'] = $this->report->convertFormat($dateList, DT_DATE5);
    $chartData['line']   = array();

    foreach($setGroup as $name => $sets)
    {
        $chartData['line'][$name] = $this->report->createSingleJSON($sets, $dateList);
    }

    return $chartData;
}

/**
 * Compute cfd of a execution.
 *
 * @param  int|array $executionID
 * @param  string    $type
 * @access public
 * @return array
 */
public function computeCFD($executionID = 0, $from = '')
{
    $today = helper::today();
    $executions = $this->dao->select('id, code')->from(TABLE_EXECUTION)
        ->beginIF($from != 'chproject')->where('type')->eq('kanban')->fi()
        ->beginIF($from == 'chproject')->where('type')->in('kanban,sprint')->fi()
        ->andWhere('status')->notin('done,closed,suspended')
        ->beginIF($executionID)->andWhere('id')->in($executionID)->fi()
        ->fetchPairs();
    if(!$executions) return array();

    /* Update today's data of cfd. */
    $cells = $this->dao->select("t1.id, t1.kanban as execution, t1.`column`, t1.type, t1.cards, t1.lane, t2.name, t2.parent")
        ->from(TABLE_KANBANCELL)->alias('t1')
        ->leftJoin(TABLE_KANBANCOLUMN)->alias('t2')->on('t1.column = t2.id')
        ->where('t1.kanban')->in(array_keys($executions))
        ->andWhere('t2.deleted')->eq('0')
        ->andWhere('t1.type')->in('story,bug,task')
        ->orderBy('t2.id asc')
        ->fetchAll('id');

    /* Group by execution/type/name/lane/column. */
    $columnGroup = array();
    $parentNames = array();
    foreach($cells as $id => $column)
    {
        if($column->parent == '-1')
        {
            $parentNames[$column->column] = $column->name;
            continue;
        }

        $column->name = isset($parentNames[$column->parent]) ? $parentNames[$column->parent] . "($column->name)" : $column->name;
        $columnGroup[$column->execution][$column->type][$column->name][$column->lane][$column->column] = $column;
    }

    foreach($columnGroup as $executionID => $executionGroup)
    {
        foreach($executionGroup as $type => $columns)
        {
            foreach($columns as $colName => $laneGroup)
            {
                $cfd = new stdclass();
                $cfd->count = 0;
                $cfd->date  = $today;
                $cfd->type  = $type;
                foreach($laneGroup as $laneID => $columnGroup)
                {
                    foreach($columnGroup as $colID => $columnCard)
                    {
                        $cards = trim($columnCard->cards, ',');
                        $cfd->count += $cards ? count(explode(',', $cards)) : 0;
                    }
                }

                $cfd->name      = $colName;
                $cfd->execution = $executionID;
                $this->dao->replace(TABLE_CFD)->data($cfd)->exec();
            }
        }
    }
}
