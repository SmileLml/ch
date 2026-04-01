<?php
helper::importControl('execution');
class myExecution extends execution
{
    /**
     * Compute cfd datas.
     *
     * @param  string $reload
     * @param  int    $executionID
     * @access public
     * @return void
     */
    public function computeCFD($reload = 'no', $executionID = 0)
    {
        $from = '';
        if($this->app->tab == 'chteam')
        {
            $from        = 'chproject';
            $executionID = $this->loadModel('chproject')->getIntances($executionID);
        }

        $this->execution->computeCFD($executionID, $from);
        if($reload == 'yes') return print(js::reload('parent'));
    }
}
