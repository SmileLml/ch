<?php
/**
 * Get the hierarchical structure data of stages and tasks.
 *
 * @param  int    $projectID
 * @param  string $range
 * @access public
 * @return array
 */
public function getStageAndTaskData($projectID, $range)
{
    $stages = $this->dao->select('id,concat(name,"(",begin," - ",end,")") as name,parent,type')->from(TABLE_EXECUTION)
        ->where('project')->eq($projectID)
        ->andWhere('type')->eq('stage')
        ->andWhere('deleted')->eq('0')
        ->orderBy('id_asc')
        ->fetchAll();

    $stageTree = $this->getStageTree($stages, $projectID);

    if($range == 'task')
    {
        $stageTree = $this->addTaskNode($stageTree, $projectID);
    }

    return $stageTree;
}

/**
 * Add task node.
 *
 * @param  array  $stageTree
 * @param  int    $projectID
 * @access public
 * @return array
 */
public function addTaskNode($stageTree, $projectID)
{
    $lowIdList = $this->getLowStageID($stageTree);
    if(empty($lowIdList)) return $stageTree;

    $tasks = $this->dao->select('id,name,execution,parent')->from(TABLE_TASK)
        ->where('project')->eq($projectID)
        ->andWhere('execution')->in($lowIdList)
        ->andWhere('deleted')->eq('0')
        ->orderBy('id_asc')
        ->fetchAll('id');

    if(empty($tasks)) return $stageTree;

    $tasks     = $this->getStageTaskList($tasks);
    $stageTree = $this->processTaskNode($stageTree, $tasks);
    return $stageTree;
}

/**
 * Get the tasks under the stage.
 *
 * @param  array  $stageTree
 * @param  array  $tasks
 * @access public
 * @return array
 */
public function processTaskNode($stageTree, $tasks)
{
    foreach($stageTree as $stage)
    {
        if(!empty($stage->children))
        {
            $this->processTaskNode($stage->children, $tasks);
        }
        else
        {
            if(isset($tasks[$stage->id]))
            {
                $stage->children = $tasks[$stage->id];
            }
        }
        if(empty($stage->children)) unset($stage->children);
    }
    return $stageTree;
}

/**
 * Get the tasks under the stage.
 *
 * @param  array  $tasks
 * @access public
 * @return array
 */
public function getStageTaskList($tasks)
{
    $executionTaskList = array();
    foreach($tasks as $task)
    {
        $task->type = 'task';
        if($task->parent > 0)
        {
            unset($task->execution);
            $tasks[$task->parent]->children[] = $task;
            continue;
        }
        else
        {
            $task->parent = $task->execution;
        }
        $executionTaskList[$task->execution][] = $task;
        unset($task->execution);
    }

    return $executionTaskList;
}

/**
 * Get the lowest stage ID.
 *
 * @param  array  $stageTree
 * @param  array  $lowIdList
 * @access public
 * @return array
 */
public function getLowStageID($stageTree, $lowIdList = array())
{
    foreach($stageTree as $stage)
    {
        if(!empty($stage->children))
        {
            $lowIdList = $this->getLowStageID($stage->children, $lowIdList);
        }
        else
        {
            $lowIdList[] = $stage->id;
        }
    }
    return $lowIdList;
}

/**
 * Get the hierarchical structure data of the stage.
 *
 * @param  object $stages
 * @param  int    $parentID
 * @access public
 * @return array
 */
public function getStageTree($stages, $parentID)
{
    $tree = array();
    foreach($stages as $stage)
    {
        if($stage->parent == $parentID)
        {
            $stage->children = $this->getStageTree($stages, $stage->id);
            $tree[] = $stage;
        }
    }
    return $tree;
}
