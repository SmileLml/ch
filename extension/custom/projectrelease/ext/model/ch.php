<?php
/**
 * Build project release browse action menu.
 *
 * @param  object $release
 * @access public
 * @return string
 */
public function buildOperateBrowseMenu($release)
{
    $canBeChanged = common::canBeChanged('projectrelease', $release);
    if(!$canBeChanged) return '';

    $menu          = '';
    $params        = "releaseID=$release->id";
    $changedStatus = $release->status == 'normal' ? 'terminate' : 'normal';

    if(common::hasPriv('projectrelease', 'linkStory')) $menu .= html::a(inlink('view', "$params&type=story&link=true"), '<i class="icon-link"></i> ', '', "class='btn' title='{$this->lang->release->linkStory}'");
    if(common::hasPriv('projectrelease', 'linkBug'))   $menu .= html::a(inlink('view', "$params&type=bug&link=true"),   '<i class="icon-bug"></i> ',  '', "class='btn' title='{$this->lang->release->linkBug}'");
    //$menu .= $this->buildMenu('projectrelease', 'changeStatus', "$params&status=$changedStatus", $release, 'browse', $release->status == 'normal' ? 'pause' : 'play', 'hiddenwin', '', '', '',$this->lang->release->changeStatusList[$changedStatus]);
    $menu .= $this->buildMenu('projectrelease', 'edit',   $params, $release, 'browse');
    //$menu .= $this->buildMenu('projectrelease', 'notify', $params, $release, 'browse', 'bullhorn', '', 'iframe', true);
    $clickable = $this->buildMenu('projectrelease', 'delete', $params, $release, 'browse', '', '', '', '', '', '', false);
    if(common::hasPriv('projectrelease', 'delete', $release) && $release->status == 'draft')
    {
        $deleteURL = helper::createLink('projectrelease', 'delete', "$params&confirm=yes");
        $class = 'btn';
        if(!$clickable) $class .= ' disabled';
        $menu .= html::a("javascript:ajaxDelete(\"$deleteURL\", \"releaseList\", confirmDelete)", '<i class="icon-trash"></i>', '', "class='{$class}' title='{$this->lang->release->delete}'");
    }

    return $menu;
}

/**
 * Get releases by project id list.
 *
 * @param  string $projectIdList
 * @access public
 * @return array
 */
public function getByProjectIdList($projectIdList)
{
    $projectReleases = array();
    foreach(explode(',', $projectIdList) as $projectID)
    {
        $releases = $this->dao->select('id,project,product,branch,build,name,date,`desc`,status')->from(TABLE_RELEASE)
            ->where('deleted')->eq(0)
            ->andWhere("FIND_IN_SET($projectID, project)")
            ->fetchAll();

        $projectReleases[$projectID] = $releases;
    }

    return $projectReleases;
}

/**
 * Update status and date.
 *
 * @param  array  $releaseIdList
 * @param  string $status
 * @param  string $date
 * @access public
 * @return bool
 */
public function updateStatusAndDate($releaseIdList, $status, $date)
{
    $releases = $this->dao->select('id,status,date,stories')->from(TABLE_RELEASE)->where('id')->in($releaseIdList)->fetchAll('id');

    $this->dao->update(TABLE_RELEASE)->set('status')->eq($status)->set('date')->eq($date)->where('id')->in($releaseIdList)->exec();

    $this->loadModel('action');
    foreach($releaseIdList as $releaseID)
    {
        $changes = array();
        if($date   != $releases[$releaseID]->date)   $changes[] = array('field' => 'date',   'old' => $releases[$releaseID]->date,   'new' => $date);
        if($status != $releases[$releaseID]->status) $changes[] = array('field' => 'status', 'old' => $releases[$releaseID]->status, 'new' => $status);
        if($changes)
        {
            $actionID = $this->action->create('release', $releaseID, 'apiUpdated');
            $this->action->logHistory($actionID, $changes);
        }

        if($status == 'normal' && $releases[$releaseID]->status != 'normal')
        {
            $storyIdList = explode(',', $releases[$releaseID]->stories);
            foreach($storyIdList as $storyID)
            {
                $story       = $this->dao->findById($storyID)->from(TABLE_STORY)->fetch();
                $noSetStages = array('verified', 'released', 'closed');
                if(in_array($story->stage, $noSetStages)) continue;
                $this->dao->update('zt_story')->set('actualonlinedate')->eq($releases[$releaseID]->date)->where('id')->eq($storyID)->exec();
                $this->loadModel('story')->setStage($storyID);
            }
            $this->story->changeRequirementStatusByStoryStage($storyIdList);
        }
    }

    return true;
}
