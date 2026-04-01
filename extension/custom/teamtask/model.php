<?php
/**
 * The model file of teamtask module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     teamtask
 * @version     $Id: model.php 5118 2013-07-12 07:41:41Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
class teamtaskModel extends model
{
    /**
     * Generate col for dtable.
     *
     * @param  string $orderBy
     * @access public
     * @return void
     */
    public function generateCol($orderBy = '')
    {
        $setting   = $this->loadModel('datatable')->getSetting('chproject');
        $fieldList = $this->config->teamtask->datatable->fieldList;

        foreach($fieldList as $field => $items)
        {
            if(isset($items['title'])) continue;

            $title    = $field == 'id' ? 'ID' : zget($this->lang->teamtask, $field, zget($this->lang, $field, $field));
            $fieldList[$field]['title'] = $title;
        }

        if(empty($setting))
        {
            $setting = $this->config->teamtask->datatable->defaultField;
            $order   = 1;
            foreach($setting as $key => $value)
            {
                $set = new stdclass();;
                $set->id    = $value;
                $set->order = $order ++;
                $set->show  = true;
                $setting[$key] = $set;
            }
        }

        foreach($setting as $key => $set)
        {
            if(empty($set->show))
            {
                unset($setting[$key]);
                continue;
            }

            $sortType = '';
            if(!strpos($orderBy, ',') && strpos($orderBy, $set->id) !== false)
            {
                $sort = str_replace("{$set->id}_", '', $orderBy);
                $sortType = $sort == 'asc' ? 'up' : 'down';
            }

            $set->name  = $set->id;
            $set->title = $fieldList[$set->id]['title'];

            if(isset($fieldList[$set->id]['checkbox']))     $set->checkbox     = $fieldList[$set->id]['checkbox'];
            if(isset($fieldList[$set->id]['nestedToggle'])) $set->nestedToggle = $fieldList[$set->id]['nestedToggle'];
            if(isset($fieldList[$set->id]['fixed']))        $set->fixed        = $fieldList[$set->id]['fixed'];
            if(isset($fieldList[$set->id]['type']))         $set->type         = $fieldList[$set->id]['type'];
            if(isset($fieldList[$set->id]['sortType']))     $set->sortType     = $fieldList[$set->id]['sortType'];
            if(isset($fieldList[$set->id]['flex']))         $set->flex         = $fieldList[$set->id]['flex'];
            if(isset($fieldList[$set->id]['minWidth']))     $set->minWidth     = $fieldList[$set->id]['minWidth'];
            if(isset($fieldList[$set->id]['maxWidth']))     $set->maxWidth     = $fieldList[$set->id]['maxWidth'];
            if(isset($fieldList[$set->id]['pri']))          $set->pri          = $fieldList[$set->id]['pri'];
            if(isset($fieldList[$set->id]['map']))          $set->map          = $fieldList[$set->id]['map'];

            if($sortType) $set->sortType = $sortType;

            if(isset($set->fixed) && $set->fixed == 'no') unset($set->fixed);
            if(isset($set->width)) $set->width = str_replace('px', '', $set->width);
            unset($set->id);
        }

        usort($setting, array('datatableModel', 'sortCols'));
        return $setting;
    }

    /**
     * Generate row for dtable.
     *
     * @param  array  $tasks
     * @param  array  $users
     * @param  object $execution
     * @param  bool   $showBranch
     * @param  array  $branchGroups
     * @param  array  $modulePairs
     * @param  array  $projectPairs
     * @access public
     * @return array
     */
    public function generateRow($tasks, $users, $execution, $showBranch, $branchGroups, $modulePairs, $projectPairs = [])
    {
        $this->loadModel('task');

        $userFields = array('assignedTo', 'openedBy', 'closedBy', 'lastEditedBy', 'finishedBy');
        $dateFields = array('assignedDate', 'openedDate', 'deadline', 'finishedDate', 'closedDate', 'lastEditedDate', 'canceledDate', 'activatedDate', 'estStarted', 'realStarted', 'replacetypeDate');
        $canView    = common::hasPriv('task', 'view');
        $rows       = array();
        if($showBranch) $showBranch = isset($this->config->execution->task->showBranch) ? $this->config->execution->task->showBranch : 1;

        if($this->config->edition != 'open')
        {
            $this->loadModel('flow');
            $this->loadModel('workflowfield');
        }

        foreach($tasks as $task)
        {
            $task->actions    = '<div class="c-actions">' . $this->task->buildOperateBrowseMenu($task, $execution) . '</div>';
            $task->assignedTo = $this->task->printAssignedHtml($task, $users, false);

            $taskName  = '';
            $taskLink  = helper::createLink('task', 'view', "taskID=$task->id", '', $this->config->vision == 'lite' ? true : false);
            $linkClass = $this->config->vision == 'lite' ? 'data-toggle="modal" data-type="iframe"' : '';

            if($task->parent > 0 and isset($task->parentName)) $task->name = "{$task->parentName} / {$task->name}";
            if(!empty($task->product) and isset($branchGroups[$task->product][$task->branch]) and $showBranch) $taskName .= "<span class='label label-badge label-outline'>" . $branchGroups[$task->product][$task->branch] . '</span> ';
            if($task->module and isset($modulePairs[$task->module])) $taskName .= "<span class='label label-gray label-badge'>" . $modulePairs[$task->module] . '</span> ';
            if($task->parent > 0) $taskName .= '<span class="label label-badge label-light" title="' . $this->lang->task->children . '">' . $this->lang->task->childrenAB . '</span> ';
            if(!empty($task->team)) $taskName .= '<span class="label label-badge label-light" title="' . $this->lang->task->multiple . '">' . $this->lang->task->multipleAB . '</span> ';
            $taskName .= $canView ? html::a($taskLink, $task->name, null, "$linkClass style='color: $task->color' title='$task->name' data-app='chteam'") : "<span style='color: $task->color'>$task->name</span>";
            if(!empty($task->children)) $taskName .= '<a class="task-toggle" data-id="' . $task->id . '"><i class="icon icon-angle-double-right"></i></a>';
            if($task->fromBug) $taskName .= html::a(helper::createLink('bug', 'view', "id=$task->fromBug"), "[BUG#$task->fromBug]", '', "class='bug'");
            $task->name = $taskName;

            $priClass  = $task->pri ? "label-pri label-pri-{$task->pri}" : '';
            $task->pri = "<span class='{$priClass}'>" . zget($this->lang->task->priList, $task->pri) . '</span>';

            $task->statusCode = $task->status;
            $storyChanged     = (!empty($task->storyStatus) && $task->storyStatus == 'active' && $task->latestStoryVersion > $task->storyVersion && !in_array($task->status, array('cancel', 'closed')));
            $task->status     = $storyChanged ? "<span class='status-story status-changed' title='{$this->lang->story->changed}'>{$this->lang->story->changed}</span>" : "<span class='status-task status-{$task->status}' title='{$this->processStatus('task', $task)}'> " . $this->processStatus('task', $task) . "</span>";

            $task->project = $projectPairs ? "<span title='" . zget($projectPairs, $task->project) ."'>" . zget($projectPairs, $task->project) . '</span>' : '';

            $task->estimateNum = round($task->estimate, 1);
            $task->consumedNum = round($task->consumed, 1);
            $task->leftNum     = round($task->left, 1);

            $task->estimate = round($task->estimate, 1) . $this->lang->execution->workHourUnit;
            $task->consumed = round($task->consumed, 1) . $this->lang->execution->workHourUnit;
            $task->left     = round($task->left, 1) . $this->lang->execution->workHourUnit;

            $task->design = '';
            $task->type   = zget($this->lang->task->typeList, $task->type, $task->type);
            $task->story  = '';
            if(!empty($task->storyID))
            {
                if(common::hasPriv('story', 'view'))
                {
                    $task->story = html::a(helper::createLink('story', 'view', "storyid=$task->storyID", 'html', true), "<i class='icon icon-{$this->lang->icons['story']}'></i>", '', "class='iframe' data-width='1050' title='{$task->storyTitle}' data-toggle='modal' data-size='lg'");
                }
                else
                {
                   $task->story = "<i class='icon icon-{$this->lang->icons['story']}' title='{$task->storyTitle}'></i>";
                }
            }

            $mailto = explode(',', $task->mailto);
            foreach($mailto as $key => $account)
            {
                $account = trim($account);
                if(empty($account))
                {
                    unset($mailto[$key]);
                    continue;
                }

                $mailto[$key] = zget($users, $account);
            }
            $task->mailto = implode(' &nbsp;', $mailto);

            foreach($userFields as $field) $task->$field = zget($users, $task->$field);
            foreach($dateFields as $field)
            {
                $task->$field = empty($task->$field) || helper::isZeroDate($task->$field) ? '' : $task->$field;
                if($field == 'deadline')
                {
                    $delayed = isset($task->delay) ? "class='delayed'" : '';
                    $task->deadline = "<span $delayed>" . substr($task->deadline, 5, 6) . '</span>';
                }
            }

            $children = isset($task->children) ? $task->children : array();
            unset($task->children);

            $task->isParent = false;
            if($task->parent == -1)
            {
                $task->isParent = true;
                $task->parent   = 0;
            }

            if($this->config->edition != 'open')
            {
                $extendFields = $this->workflowfield->getList('task');
                foreach($extendFields as $fieldCode => $field)
                {
                    if(isset($field->buildin) && $field->buildin == 0) $task->$fieldCode = $this->flow->printFlowCell('task', $task, $fieldCode, true);
                }
            }

            $rows[] = $task;

            if(!empty($children))
            {
                $rows = array_merge($rows, $this->generateRow($children, $users, $execution, $showBranch, $branchGroups, $modulePairs, $projectPairs));
            }
        }
        return $rows;
    }
}
