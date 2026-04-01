<?php
class mytesttask extends testtask
{
    /**
     * View a test task.
     *
     * @param  int    $taskID
     * @param  int    $project
     * @access public
     * @return void
     */
    public function view($taskID, $chproject = 0)
    {
        if($this->app->tab == 'chteam') $this->loadModel('chproject')->setMenu($chproject);

        parent::view($taskID);
    }
}
