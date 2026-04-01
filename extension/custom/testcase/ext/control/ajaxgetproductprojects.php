<?php
helper::importControl('testcase');
class mytestcase extends testcase
{
    /**
     * AJAX: get projects of a product in html select.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  int    $projectID
     * @param  int    $number
     * @access public
     * @return void
     */
    public function ajaxGetProductProjects($productID, $branch = 0, $projectID = 0, $number = '')
    {
        $projects = array('0' => '') + $this->loadModel('project')->getPairsByProduct($productID, $branch);

        $select  = html::select("project[{$number}]", $projects, $projectID, "class='form-control'  onchange='loadExecutions({$productID}, this.value, {$number})'");

        echo $select;
    }
}