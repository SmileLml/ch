<?php
helper::importControl('build');
class mybuild extends build
{
    /**
     * View a build.
     *
     * @param  int    $buildID
     * @param  string $type
     * @param  string $link
     * @param  string $param
     * @param  string $orderBy
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @access public
     * @return void
     */
    public function view($buildID, $type = 'story', $link = 'false', $param = '', $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 100, $pageID = 1, $chprojectID = 0)
    {
        if($this->app->tab == 'chteam') $this->loadModel('chproject')->setMenu($chprojectID);

        parent::view($buildID, $type, $link, $param, $orderBy, $recTotal, $recPerPage, $pageID);
    }
}

