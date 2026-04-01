<?php
/**
 * Get Kanban by execution id.
 *
 * @param  int|array $executionID
 * @param  string    $browseType all|story|bug|task
 * @param  string    $groupBy
 * @param  string    $searchValue
 * @param  string    $orderBy
 *
 * @access public
 * @return array
 */
public function getExecutionKanban($executionID, $browseType = 'all', $groupBy = 'default', $searchValue = '', $orderBy = 'id_asc')
{
    if($groupBy != 'default') return $this->getKanban4Group($executionID, $browseType, $groupBy, $searchValue, $orderBy);

    if($this->app->tab == 'chteam') $executionID = $this->loadModel('chproject')->getIntancesProjectOptions($this->session->chproject, 'executionID', 'executionID', $this->session->intanceProject);

    $lanes = $this->dao->select('*')->from(TABLE_KANBANLANE)
        ->where('execution')->in($executionID)
        ->andWhere('deleted')->eq(0)
        ->beginIF($browseType != 'all')->andWhere('type')->eq($browseType)->fi()
        ->orderBy('order_asc')
        ->fetchAll('id');

    if(empty($lanes)) return array();

    foreach($lanes as $lane) $this->refreshCards($lane);

    $columns = $this->dao->select('t1.cards, t1.lane, t2.id, t2.type, t2.name, t2.color, t2.limit, t2.parent')->from(TABLE_KANBANCELL)->alias('t1')
        ->leftJoin(TABLE_KANBANCOLUMN)->alias('t2')->on('t1.column = t2.id')
        ->where('t2.deleted')->eq(0)
        ->andWhere('t1.lane')->in(array_keys($lanes))
        ->orderBy('id_asc')
        ->fetchGroup('lane', 'id');

    /* Get group objects. */
    if($browseType == 'all' or $browseType == 'story') $objectGroup['story'] = $this->loadModel('story')->getExecutionStories($executionID, 0, 0, 't1.`order`_desc', 'allStory');
    if($browseType == 'all' or $browseType == 'bug')   $objectGroup['bug']   = $this->loadModel('bug')->getExecutionBugs($executionID);
    if($browseType == 'all' or $browseType == 'task')  $objectGroup['task']  = $this->loadModel('execution')->getKanbanTasks($executionID, "id");

    /* Get objects cards menus. */
    if($this->app->tab == 'chteam')
    {
        foreach($executionID as $id)
        {
            if($browseType == 'all' or $browseType == 'story') $storyCardMenu[$id] = $this->getKanbanCardMenu($id, $objectGroup['story'], 'story');
            if($browseType == 'all' or $browseType == 'bug')   $bugCardMenu[$id]   = $this->getKanbanCardMenu($id, $objectGroup['bug'], 'bug');
            if($browseType == 'all' or $browseType == 'task')  $taskCardMenu[$id]  = $this->getKanbanCardMenu($id, $objectGroup['task'], 'task');
        }
    }
    else
    {
        if($browseType == 'all' or $browseType == 'story') $storyCardMenu = $this->getKanbanCardMenu($executionID, $objectGroup['story'], 'story');
        if($browseType == 'all' or $browseType == 'bug')   $bugCardMenu   = $this->getKanbanCardMenu($executionID, $objectGroup['bug'], 'bug');
        if($browseType == 'all' or $browseType == 'task')  $taskCardMenu  = $this->getKanbanCardMenu($executionID, $objectGroup['task'], 'task');
    }

    $projectPairs = $this->dao->select('t1.id, t2.name')->from(TABLE_EXECUTION)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->where('t1.id')->in($executionID)
        ->fetchPairs('id', 'name');
    /* Build kanban group data. */
    $kanbanGroup = array();
    foreach($lanes as $laneID => $lane)
    {
        $laneData   = array();
        $columnData = array();

        $projectName = (isset($lane->execution) && !empty($lane->execution)) ? $projectPairs[$lane->execution] : '';

        $laneData['id']              = $laneID;
        $laneData['laneID']          = $laneID;
        $laneData['name']            = $lane->name . "『" . $projectName . "』";
        $laneData['color']           = $lane->color;
        $laneData['order']           = $lane->order;
        $laneData['defaultCardType'] = $lane->type;

        foreach($columns[$laneID] as $columnID => $column)
        {
            $columnData[$column->id]['id']         = $columnID;
            $columnData[$column->id]['type']       = $column->type;
            $columnData[$column->id]['name']       = $column->name;
            $columnData[$column->id]['color']      = $column->color;
            $columnData[$column->id]['limit']      = $column->limit;
            $columnData[$column->id]['laneType']   = $lane->type;
            $columnData[$column->id]['asParent']   = $column->parent == -1 ? true : false;
            $columnData[$column->id]['parent']     = $column->parent;

            if($column->parent > 0)
            {
                if($column->type == 'developing' or $column->type == 'developed') $columnData[$column->id]['parentType'] = 'develop';
                if($column->type == 'testing' or $column->type == 'tested') $columnData[$column->id]['parentType'] = 'test';
                if($column->type == 'fixing' or $column->type == 'fixed') $columnData[$column->id]['parentType'] = 'resolving';
            }

            $cardOrder  = 1;
            $cardIdList = array_filter(explode(',', $column->cards));
            foreach($cardIdList as $cardID)
            {
                $cardData = array();
                $objects  = zget($objectGroup, $lane->type, array());
                $object   = zget($objects, $cardID, array());

                if(empty($object)) continue;

                if($lane->type == 'story')
                {
                    $storyExecutions = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($object->id)->fetchPairs('project', 'project');

                    if(!in_array($lane->execution, $storyExecutions)) continue;
                }

                $cardData['id']         = $object->id;
                $cardData['order']      = $cardOrder;
                $cardData['pri']        = $object->pri ? $object->pri : '';
                $cardData['estimate']   = $lane->type == 'bug' ? '' : $object->estimate;
                $cardData['assignedTo'] = $object->assignedTo;
                $cardData['deadline']   = $lane->type == 'story' ? '' : $object->deadline;
                $cardData['severity']   = $lane->type == 'bug' ? $object->severity : '';

                if($lane->type == 'task')
                {
                    if($searchValue != '' and strpos($object->name, $searchValue) === false) continue;
                    $cardData['name']       = $object->name;
                    $cardData['status']     = $object->status;
                    $cardData['left']       = $object->left;
                    $cardData['estStarted'] = $object->estStarted;
                    $cardData['mode']       = $object->mode;
                    if($object->mode == 'multi') $cardData['teamMembers'] = $object->teamMembers;
                }
                else
                {
                    if($searchValue != '' and strpos($object->title, $searchValue) === false) continue;
                    $cardData['title'] = $object->title;
                }

                if($this->app->tab == 'chteam')
                {
                    if($lane->type == 'story') $cardData['menus'] = $storyCardMenu[$lane->execution][$object->story];
                    if($lane->type == 'bug')   $cardData['menus'] = $bugCardMenu[$lane->execution][$object->id];
                    if($lane->type == 'task')  $cardData['menus'] = $taskCardMenu[$lane->execution][$object->id];
                }
                else
                {
                    if($lane->type == 'story') $cardData['menus'] = $storyCardMenu[$object->id];
                    if($lane->type == 'bug')   $cardData['menus'] = $bugCardMenu[$object->id];
                    if($lane->type == 'task')  $cardData['menus'] = $taskCardMenu[$object->id];
                }

                $laneData['cards'][$column->type][] = $cardData;
                $cardOrder ++;
            }
            if($searchValue == '' and !isset($laneData['cards'][$column->type])) $laneData['cards'][$column->type] = array();
        }

        if($searchValue != '' and empty($laneData['cards'])) continue;
        $kanbanGroup[$lane->type]['id']              = $laneID;
        $kanbanGroup[$lane->type]['columns']         = array_values($columnData);
        $kanbanGroup[$lane->type]['lanes'][]         = $laneData;
        $kanbanGroup[$lane->type]['defaultCardType'] = $lane->type;
    }

    return $kanbanGroup;
}

/**
 * Get kanban lane count.
 *
 * @param  int    $kanbanID
 * @param  string $type
 * @access public
 * @return int
 */
public function getLaneCount($kanbanID, $type = 'common')
{
    if($type == 'common' or $type == 'kanban')
    {
        return $this->dao->select('COUNT(t2.id) as count')->from(TABLE_KANBANREGION)->alias('t1')
            ->leftJoin(TABLE_KANBANLANE)->alias('t2')->on('t1.id=t2.region')
            ->where('t1.kanban')->eq($kanbanID)
            ->andWhere('t1.deleted')->eq(0)
            ->andWhere('t2.deleted')->eq(0)
            ->beginIF($type == 'common')->andWhere('t2.type')->eq('common')->fi()
            ->beginIF($type != 'common')->andWhere('t2.type')->ne('common')->fi()
            ->fetch('count');
    }
    else
    {
        return $this->dao->select('COUNT(id) as count')->from(TABLE_KANBANLANE)
            ->where('execution')->in($kanbanID)
            ->andWhere('deleted')->eq(0)
            ->fetch('count');
    }
}

/**
 * Get Kanban cards menus by execution id.
 *
 * @param  int    $executionID
 * @param  array  $objects
 * @param  string $objecType story|bug|task
 * @access public
 * @return array
 */
public function getKanbanCardMenu($executionID, $objects, $objecType)
{
    $this->app->loadLang('execution');
    $methodName = $this->app->rawMethod;

    $menus = array();
    switch ($objecType)
    {
        case 'story':
            if(!isset($this->story)) $this->loadModel('story');

            $objects = $this->story->mergeReviewer($objects);

            if($this->app->tab == 'chteam')
            {
                $menus = $this->getTeamStoryMenus($executionID, $objects, $menus);
                break;
            }

            foreach($objects as $story)
            {
                $menu = array();

                $toTaskPriv = strpos('draft,reviewing,closed', $story->status) !== false ? false : true;
                if(common::hasPriv('story', 'edit') and $this->story->isClickable($story, 'edit'))         $menu[] = array('label' => $this->lang->story->edit, 'icon' => 'edit', 'url' => helper::createLink('story', 'edit', "storyID=$story->id", '', true), 'size' => '95%');
                if(common::hasPriv('story', 'change') and $this->story->isClickable($story, 'change'))     $menu[] = array('label' => $this->lang->story->change, 'icon' => 'alter', 'url' => helper::createLink('story', 'change', "storyID=$story->id", '', true), 'size' => '95%');
                if(common::hasPriv('story', 'review') and $this->story->isClickable($story, 'review'))     $menu[] = array('label' => $this->lang->story->review, 'icon' => 'search', 'url' => helper::createLink('story', 'review', "storyID=$story->id", '', true), 'size' => '95%');
                if(common::hasPriv('task', 'create') and $toTaskPriv)                                      $menu[] = array('label' => $this->lang->execution->wbs, 'icon' => 'plus', 'url' => helper::createLink('task', 'create', "executionID=$executionID&storyID=$story->id&moduleID=$story->module", '', true), 'size' => '95%');
                if(common::hasPriv('task', 'batchCreate') and $toTaskPriv && $this->app->tab != 'chteam')                                 $menu[] = array('label' => $this->lang->execution->batchWBS, 'icon' => 'pluses', 'url' => helper::createLink('task', 'batchCreate', "executionID=$executionID&storyID=$story->id&moduleID=0&taskID=0&iframe=true", '', true), 'size' => '95%');
                if(common::hasPriv('story', 'activate') and $this->story->isClickable($story, 'activate')) $menu[] = array('label' => $this->lang->story->activate, 'icon' => 'magic', 'url' => helper::createLink('story', 'activate', "storyID=$story->id", '', true));
                if(common::hasPriv('execution', 'unlinkStory'))                                            $menu[] = array('label' => $this->lang->execution->unlinkStory, 'icon' => 'unlink', 'url' => helper::createLink('execution', 'unlinkStory', "executionID=$executionID&storyID=$story->story&confirm=no&from=taskkanban", '', true));
                if(common::hasPriv('story', 'delete'))                                                     $menu[] = array('label' => $this->lang->story->delete, 'icon' => 'trash', 'url' => helper::createLink('story', 'delete', "storyID=$story->id&confirm=no&from=taskkanban"));

                $menus[$story->id] = $menu;
            }
            break;
        case 'bug':
            if(!isset($this->bug)) $this->loadModel('bug');

            if($this->app->tab == 'chteam')
            {
                $menus = $this->getTeamBugMenus($executionID, $objects, $menus);
                break;
            }

            foreach($objects as $bug)
            {
                $menu = array();

                if(common::hasPriv('bug', 'edit') and $this->bug->isClickable($bug, 'edit'))             $menu[] = array('label' => $this->lang->bug->edit, 'icon' => 'edit', 'url' => helper::createLink('bug', 'edit', "bugID=$bug->id", '', true), 'size' => '95%');
                if(common::hasPriv('bug', 'confirmBug') and $this->bug->isClickable($bug, 'confirmBug')) $menu[] = array('label' => $this->lang->bug->confirmBug, 'icon' => 'ok', 'url' => helper::createLink('bug', 'confirmBug', "bugID=$bug->id&extra=&from=taskkanban", '', true));
                if(common::hasPriv('bug', 'resolve') and $this->bug->isClickable($bug, 'resolve'))       $menu[] = array('label' => $this->lang->bug->resolve, 'icon' => 'checked', 'url' => helper::createLink('bug', 'resolve', "bugID=$bug->id&extra=&from=taskkanban", '', true));
                if(common::hasPriv('bug', 'close') and $this->bug->isClickable($bug, 'close'))           $menu[] = array('label' => $this->lang->bug->close, 'icon' => 'off', 'url' => helper::createLink('bug', 'close', "bugID=$bug->id&extra=&from=taskkanban", '', true));
                if(common::hasPriv('bug', 'create') and $this->bug->isClickable($bug, 'create'))         $menu[] = array('label' => $this->lang->bug->copy, 'icon' => 'copy', 'url' => helper::createLink('bug', 'create', "productID=$bug->product&branch=$bug->branch&extras=bugID=$bug->id", '', true), 'size' => '95%');
                if(common::hasPriv('bug', 'activate') and $this->bug->isClickable($bug, 'activate'))     $menu[] = array('label' => $this->lang->bug->activate, 'icon' => 'magic', 'url' => helper::createLink('bug', 'activate', "bugID=$bug->id&extra=&from=taskkanban", '', true));
                if(common::hasPriv('story', 'create') and $bug->status != 'closed')                      $menu[] = array('label' => $this->lang->bug->toStory, 'icon' => 'lightbulb', 'url' => helper::createLink('story', 'create', "product=$bug->product&branch=$bug->branch&module=0&story=0&execution=0&bugID=$bug->id", '', true), 'size' => '95%');
                if(common::hasPriv('bug', 'delete'))                                                     $menu[] = array('label' => $this->lang->bug->delete, 'icon' => 'trash', 'url' => helper::createLink('bug', 'delete', "bugID=$bug->id&confirm=no&from=taskkanban"));

                $menus[$bug->id] = $menu;
            }
            break;
        case 'task':
            if(!isset($this->task)) $this->loadModel('task');

            if($this->app->tab == 'chteam')
            {
                $menus = $this->getTeamTaskMenus($executionID, $objects, $menus);
                break;
            }

            foreach($objects as $task)
            {
                $menu = array();

                if(common::hasPriv('task', 'edit') and $this->task->isClickable($task, 'edit'))                                $menu[] = array('label' => $this->lang->task->edit, 'icon' => 'edit', 'url' => helper::createLink('task', 'edit', "taskID=$task->id&comment=false&kanbanGroup=default&from=taskkanban", '', true), 'size' => '95%');
                if(common::hasPriv('task', 'pause') and $this->task->isClickable($task, 'pause'))                              $menu[] = array('label' => $this->lang->task->pause, 'icon' => 'pause', 'url' => helper::createLink('task', 'pause', "taskID=$task->id&extra=from=taskkanban", '', true));
                if(common::hasPriv('task', 'restart') and $this->task->isClickable($task, 'restart'))                          $menu[] = array('label' => $this->lang->task->restart, 'icon' => 'play', 'url' => helper::createLink('task', 'restart', "taskID=$task->id&from=taskkanban", '', true));
                if(common::hasPriv('task', 'recordEstimate') and $this->task->isClickable($task, 'recordEstimate'))            $menu[] = array('label' => $this->lang->task->recordEstimate, 'icon' => 'time', 'url' => helper::createLink('task', 'recordEstimate', "taskID=$task->id&from=taskkanban", '', true));
                if(common::hasPriv('task', 'activate') and $this->task->isClickable($task, 'activate'))                        $menu[] = array('label' => $this->lang->task->activate, 'icon' => 'magic', 'url' => helper::createLink('task', 'activate', "taskID=$task->id&extra=from=taskkanban", '', true));
                if(common::hasPriv('task', 'batchCreate') and $this->task->isClickable($task, 'batchCreate') and !$task->mode) $menu[] = array('label' => $this->lang->task->children, 'icon' => 'split', 'url' => helper::createLink('task', 'batchCreate', "execution=$task->execution&storyID=$task->story&moduleID=$task->module&taskID=$task->id", '', true), 'size' => '95%');
                if(common::hasPriv('task', 'create') and $this->task->isClickable($task, 'create'))                            $menu[] = array('label' => $this->lang->task->copy, 'icon' => 'copy', 'url' => helper::createLink('task', 'create', "projctID=$task->execution&storyID=$task->story&moduleID=$task->module&taskID=$task->id", '', true), 'size' => '95%');
                if(common::hasPriv('task', 'cancel') and $this->task->isClickable($task, 'cancel'))                            $menu[] = array('label' => $this->lang->task->cancel, 'icon' => 'ban-circle', 'url' => helper::createLink('task', 'cancel', "taskID=$task->id&extra=from=taskkanban", '', true));
                if(common::hasPriv('task', 'delete'))                                                                          $menu[] = array('label' => $this->lang->task->delete, 'icon' => 'trash', 'url' => helper::createLink('task', 'delete', "executionID=$task->execution&taskID=$task->id&confirm=no&from=taskkanban"));

                $menus[$task->id] = $menu;
            }
            break;
    }
    return $menus;
}

/**
 * Get need update lane columns.
 *
 * @param  int    $chprojectID
 * @param  int    $columnID
 * @param  string $type
 * @access public
 * @return array
 */
public function getNeedUpdateLaneColumns($chprojectID, $columnID, $type)
{
    $executionIdList       = $this->loadModel('chproject')->getIntances($chprojectID);
    $kanbanType            = $this->dao->select('type')->from(TABLE_KANBANCELL)->where('id')->eq($columnID)->andWhere('kanban')->in($executionIdList)->fetch('type');
    $kanbanColumns         = $this->dao->select('`column`')->from(TABLE_KANBANCELL)->where('kanban')->in($executionIdList)->andWhere('type')->eq($kanbanType)->fetchPairs();
    $needUpdateLaneColumns = $this->dao->select('id')->from(TABLE_KANBANCOLUMN)->where('id')->in($kanbanColumns)->andWhere('type')->eq($type)->fetchPairs();

    return [$needUpdateLaneColumns, $kanbanType];
}

/**
 * Get team story menus.
 *
 * @param  int    $executionID
 * @param  array  $objects
 * @param  string $menu
 * @access public
 * @return string
 */
public function getTeamStoryMenus($executionID, $objects, $menus)
{
    foreach($objects as $story)
    {
        $menu = array();

        $toTaskPriv = strpos('draft,reviewing,closed', $story->status) !== false ? false : true;
        if(common::hasPriv('story', 'edit') and $this->story->isClickable($story, 'edit'))         $menu[] = array('label' => $this->lang->story->edit, 'icon' => 'edit', 'url' => helper::createLink('story', 'edit', "storyID=$story->id", '', true), 'size' => '95%');
        if(common::hasPriv('story', 'change') and $this->story->isClickable($story, 'change'))     $menu[] = array('label' => $this->lang->story->change, 'icon' => 'alter', 'url' => helper::createLink('story', 'change', "storyID=$story->id", '', true), 'size' => '95%');
        if(common::hasPriv('story', 'review') and $this->story->isClickable($story, 'review'))     $menu[] = array('label' => $this->lang->story->review, 'icon' => 'search', 'url' => helper::createLink('story', 'review', "storyID=$story->id", '', true), 'size' => '95%');
        if(common::hasPriv('task', 'create') and $toTaskPriv)                                      $menu[] = array('label' => $this->lang->execution->wbs, 'icon' => 'plus', 'url' => helper::createLink('task', 'create', "executionID=$executionID&storyID=$story->id&moduleID=$story->module", '', true), 'size' => '95%');
        if(common::hasPriv('task', 'batchCreate') and $toTaskPriv && $this->app->tab != 'chteam')                                 $menu[] = array('label' => $this->lang->execution->batchWBS, 'icon' => 'pluses', 'url' => helper::createLink('task', 'batchCreate', "executionID=$executionID&storyID=$story->id&moduleID=0&taskID=0&iframe=true", '', true), 'size' => '95%');
        if(common::hasPriv('story', 'activate') and $this->story->isClickable($story, 'activate')) $menu[] = array('label' => $this->lang->story->activate, 'icon' => 'magic', 'url' => helper::createLink('story', 'activate', "storyID=$story->id", '', true));
        if(common::hasPriv('execution', 'unlinkStory'))                                            $menu[] = array('label' => $this->lang->execution->unlinkStory, 'icon' => 'unlink', 'url' => helper::createLink('execution', 'unlinkStory', "executionID=$executionID&storyID=$story->story&confirm=no&from=taskkanban", '', true));
        if(common::hasPriv('story', 'delete'))                                                     $menu[] = array('label' => $this->lang->story->delete, 'icon' => 'trash', 'url' => helper::createLink('story', 'delete', "storyID=$story->id&confirm=no&from=taskkanban"));

        $menus[$story->id] = $menu;
    }

    return $menus;
}

/**
 * Get team bug menus.
 *
 * @param  int    $executionID
 * @param  array  $objects
 * @param  array  $menus
 * @access public
 * @return string
 */
public function getTeamBugMenus($executionID, $objects, $menus)
{
    $chprojectID = $this->session->chproject;

    foreach($objects as $bug)
    {
        $menu = array();

        if(common::hasPriv('bug', 'edit') and $this->bug->isClickable($bug, 'edit'))             $menu[] = array('label' => $this->lang->bug->edit, 'icon' => 'edit', 'url' => helper::createLink('bug', 'edit', "bugID=$bug->id&comment=&kanbanGroup=&chprojectID=$chprojectID", '', true), 'size' => '95%');
        if(common::hasPriv('bug', 'confirmBug') and $this->bug->isClickable($bug, 'confirmBug')) $menu[] = array('label' => $this->lang->bug->confirmBug, 'icon' => 'ok', 'url' => helper::createLink('bug', 'confirmBug', "bugID=$bug->id&extra=&from=taskkanban", '', true));
        if(common::hasPriv('bug', 'resolve') and $this->bug->isClickable($bug, 'resolve'))       $menu[] = array('label' => $this->lang->bug->resolve, 'icon' => 'checked', 'url' => helper::createLink('bug', 'resolve', "bugID=$bug->id&extra=&from=taskkanban", '', true));
        if(common::hasPriv('bug', 'close') and $this->bug->isClickable($bug, 'close'))           $menu[] = array('label' => $this->lang->bug->close, 'icon' => 'off', 'url' => helper::createLink('bug', 'close', "bugID=$bug->id&extra=&from=taskkanban", '', true));
        if(common::hasPriv('bug', 'create') and $this->bug->isClickable($bug, 'create'))         $menu[] = array('label' => $this->lang->bug->copy, 'icon' => 'copy', 'url' => helper::createLink('bug', 'create', "productID=$bug->product&branch=$bug->branch&extras=bugID=$bug->id&chprojectID=$chprojectID", '', true), 'size' => '95%');
        if(common::hasPriv('bug', 'activate') and $this->bug->isClickable($bug, 'activate'))     $menu[] = array('label' => $this->lang->bug->activate, 'icon' => 'magic', 'url' => helper::createLink('bug', 'activate', "bugID=$bug->id&extra=&from=taskkanban", '', true));
        if(common::hasPriv('story', 'create') and $bug->status != 'closed')                      $menu[] = array('label' => $this->lang->bug->toStory, 'icon' => 'lightbulb', 'url' => helper::createLink('story', 'create', "product=$bug->product&branch=$bug->branch&module=0&story=0&execution=$executionID&bugID=$bug->id&planID=0&todoID=0&extra=&storyType=story&chproject=$chprojectID", '', true), 'size' => '95%');
        if(common::hasPriv('bug', 'delete'))                                                     $menu[] = array('label' => $this->lang->bug->delete, 'icon' => 'trash', 'url' => helper::createLink('bug', 'delete', "bugID=$bug->id&confirm=no&from=taskkanban"));

        $menus[$bug->id] = $menu;
    }

    return $menus;
}

/**
 * Get team task menus.
 *
 * @param  int    $executionID
 * @param  array  $objects
 * @param  array  $menus
 * @access public
 * @return string
 */
public function getTeamTaskMenus($executionID, $objects, $menus)
{
    foreach($objects as $task)
    {
        $menu = array();

        if(common::hasPriv('task', 'edit') and $this->task->isClickable($task, 'edit'))                                $menu[] = array('label' => $this->lang->task->edit, 'icon' => 'edit', 'url' => helper::createLink('task', 'edit', "taskID=$task->id&comment=false&kanbanGroup=default&from=taskkanban", '', true), 'size' => '95%');
        if(common::hasPriv('task', 'pause') and $this->task->isClickable($task, 'pause'))                              $menu[] = array('label' => $this->lang->task->pause, 'icon' => 'pause', 'url' => helper::createLink('task', 'pause', "taskID=$task->id&extra=from=taskkanban", '', true));
        if(common::hasPriv('task', 'restart') and $this->task->isClickable($task, 'restart'))                          $menu[] = array('label' => $this->lang->task->restart, 'icon' => 'play', 'url' => helper::createLink('task', 'restart', "taskID=$task->id&from=taskkanban", '', true));
        if(common::hasPriv('task', 'recordEstimate') and $this->task->isClickable($task, 'recordEstimate'))            $menu[] = array('label' => $this->lang->task->recordEstimate, 'icon' => 'time', 'url' => helper::createLink('task', 'recordEstimate', "taskID=$task->id&from=taskkanban", '', true));
        if(common::hasPriv('task', 'activate') and $this->task->isClickable($task, 'activate'))                        $menu[] = array('label' => $this->lang->task->activate, 'icon' => 'magic', 'url' => helper::createLink('task', 'activate', "taskID=$task->id&extra=from=taskkanban", '', true));
        if(common::hasPriv('task', 'batchCreate') and $this->task->isClickable($task, 'batchCreate') and !$task->mode) $menu[] = array('label' => $this->lang->task->children, 'icon' => 'split', 'url' => helper::createLink('task', 'batchCreate', "execution=$task->execution&storyID=$task->story&moduleID=$task->module&taskID=$task->id", '', true), 'size' => '95%');
        if(common::hasPriv('task', 'create') and $this->task->isClickable($task, 'create'))                            $menu[] = array('label' => $this->lang->task->copy, 'icon' => 'copy', 'url' => helper::createLink('task', 'create', "projctID=$task->execution&storyID=$task->story&moduleID=$task->module&taskID=$task->id&todoID=0&extra=&bugID=0&chprojectID={$this->session->chproject}", '', true), 'size' => '95%');
        if(common::hasPriv('task', 'cancel') and $this->task->isClickable($task, 'cancel'))                            $menu[] = array('label' => $this->lang->task->cancel, 'icon' => 'ban-circle', 'url' => helper::createLink('task', 'cancel', "taskID=$task->id&extra=from=taskkanban", '', true));
        if(common::hasPriv('task', 'delete'))                                                                          $menu[] = array('label' => $this->lang->task->delete, 'icon' => 'trash', 'url' => helper::createLink('task', 'delete', "executionID=$task->execution&taskID=$task->id&confirm=no&from=taskkanban"));

        $menus[$task->id] = $menu;
    }

    return $menus;
}
