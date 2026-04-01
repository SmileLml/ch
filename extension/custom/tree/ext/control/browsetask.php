<?php
helper::importControl('tree');
class mytree extends tree
{
    /**
     * Browse task module.
     *
     * @param  int    $rootID
     * @param  int    $productID
     * @param  int    $currentModuleID
     * @param  string $currentModuleID
     * @access public
     * @return void
     */
    public function browseTask($rootID, $productID = 0, $currentModuleID = 0, $from = '')
    {
        $this->view->from = $from;

        return parent::browseTask($rootID, $productID, $currentModuleID);
    }
}
