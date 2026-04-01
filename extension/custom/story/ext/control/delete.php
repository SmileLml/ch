<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * Delete a story.
     *
     * @param  int    $storyID
     * @param  string $confirm   yes|no
     * @param  string $from      taskkanban
     * @param  string $storyType story|requirement
     * @access public
     * @return void
     */
    public function delete($storyID, $confirm = 'no', $from = '', $storyType = 'story')
    {
        $story = $this->story->getById($storyID);
        if($story->parent < 0) return print(js::alert($this->lang->story->cannotDeleteParent));

        if($confirm == 'no')
        {
            if($storyType == 'requirement') $this->lang->story->confirmDelete = str_replace($this->lang->SRCommon, $this->lang->URCommon, $this->lang->story->confirmDelete);
            return print(js::confirm($this->lang->story->confirmDelete, $this->createLink('story', 'delete', "story=$storyID&confirm=yes&from=$from&storyType=$storyType")));
        }
        else
        {
            $this->dao->update(TABLE_STORY)->set('deleted')->eq(1)->where('id')->eq($storyID)->exec();
            $this->loadModel('action')->create($story->type, $storyID, 'deleted', '', ACTIONMODEL::CAN_UNDELETED);

            if($story->parent > 0)
            {
                $this->story->updateParentStatus($story->id);
                $this->action->create('story', $story->parent, 'deleteChildrenStory', '', $storyID);
            }

            $this->executeHooks($storyID);

            $this->story->changeRequirementStatusByStoryStage(array($storyID));

            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'success'));

            if((in_array($this->app->tab, array('execution', 'chteam'))) and $from == 'taskkanban')
            {
                $execLaneType    = $this->session->execLaneType ? $this->session->execLaneType : 'all';
                $execGroupBy     = $this->session->execGroupBy ? $this->session->execGroupBy : 'default';
                $taskSearchValue = $this->session->taskSearchValue ? $this->session->taskSearchValue : '';
                $kanbanData      = $this->loadModel('kanban')->getExecutionKanban($this->session->execution, $execLaneType, $execGroupBy, $taskSearchValue);
                $kanbanType      = $execLaneType == 'all' ? 'story' : key($kanbanData);
                $kanbanData      = $kanbanData[$kanbanType];
                $kanbanData      = json_encode($kanbanData);
                return print(js::closeModal('parent', '', "parent.updateKanban(\"story\", $kanbanData)"));
            }

            if(isonlybody()) return print(js::reload('parent.parent'));

            $locateLink = $this->session->storyList ? $this->session->storyList : $this->createLink('product', 'browse', "productID={$story->product}");

            if($this->app->tab == 'chteam') $locateLink = $this->session->teamStoryList;

            return print(js::locate($locateLink, 'parent'));
        }
    }
}
