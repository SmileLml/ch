<?php
/**
 * Link stories
 *
 * @param  int    $releaseID
 * @access public
 * @return void
 */
public function linkStory($releaseID)
{
    $release = $this->getByID($releaseID);
    $product = $this->loadModel('product')->getByID($release->product);

    foreach($this->post->stories as $i => $storyID)
    {
        if(strpos(",{$release->stories},", ",{$storyID},") !== false) unset($_POST['stories'][$i]);
    }

    $this->loadModel('story')->updateStoryReleasedDate($release->stories, $release->date);
    $release->stories .= ',' . join(',', $this->post->stories);
    $this->dao->update(TABLE_RELEASE)->set('stories')->eq($release->stories)->where('id')->eq((int)$releaseID)->exec();

    if($release->stories)
    {
        $this->loadModel('story');
        $this->loadModel('action');
        foreach($this->post->stories as $storyID)
        {
            /* Reset story stagedBy field for auto compute stage. */
            $this->dao->update(TABLE_STORY)->set('stagedBy')->eq('')->where('id')->eq($storyID)->exec();
            if($product->type != 'normal') $this->dao->update(TABLE_STORYSTAGE)->set('stagedBy')->eq('')->where('story')->eq($storyID)->andWhere('branch')->eq($release->branch)->exec();
            if($release->status == 'normal') $this->dao->update('zt_story')->set('actualonlinedate')->eq($release->date)->where('id')->eq($storyID)->exec();
            $this->story->setStage($storyID);

            $this->action->create('story', $storyID, 'linked2release', '', $releaseID);
        }
    }
}
/**
 * Build release browse action menu.
 *
 * @param  object $release
 * @access public
 * @return string
 */
public function buildOperateBrowseMenu($release)
{
    $canBeChanged = common::canBeChanged('release', $release);
    if(!$canBeChanged) return '';

    $menu          = '';
    $params        = "releaseID=$release->id";
    $changedStatus = $release->status == 'normal' ? 'terminate' : 'normal';

    if(common::hasPriv('release', 'linkStory')) $menu .= html::a(inlink('view', "$params&type=story&link=true"), '<i class="icon-link"></i> ', '', "class='btn' title='{$this->lang->release->linkStory}'");
    if(common::hasPriv('release', 'linkBug'))   $menu .= html::a(inlink('view', "$params&type=bug&link=true"),   '<i class="icon-bug"></i> ',  '', "class='btn' title='{$this->lang->release->linkBug}'");
    //$menu .= $this->buildMenu('release', 'changeStatus', "$params&status=$changedStatus", $release, 'browse', $release->status == 'normal' ? 'pause' : 'play', 'hiddenwin', '', '', '',$this->lang->release->changeStatusList[$changedStatus]);
    $menu .= $this->buildMenu('release', 'edit',   "release=$release->id", $release, 'browse');
    //$menu .= $this->buildMenu('release', 'notify', "release=$release->id", $release, 'browse', 'bullhorn', '', 'iframe', true);
    $clickable = $this->buildMenu('release', 'delete', "release=$release->id", $release, 'browse', '', '', '', '', '', '', false);

    if(common::hasPriv('release', 'delete', $release) && $release->status == 'draft')
    {
        $deleteURL = helper::createLink('release', 'delete', "releaseID=$release->id&confirm=yes");
        $class = 'btn';
        if(!$clickable) $class .= ' disabled';
        $menu .= html::a("javascript:ajaxDelete(\"$deleteURL\", \"releaseList\", confirmDelete)", '<i class="icon-trash"></i>', '', "class='{$class}' title='{$this->lang->release->delete}'");
    }

    return $menu;
}
