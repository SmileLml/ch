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
     * @param  int    $executionID
     * @param  int    $number
     * @access public
     * @return void
     */
    public function ajaxGetProductExecutions($productID, $branch = 0, $projectID = 0, $executionID = 0, $number = '')
    {
        $executions = array('0' => '') + $this->loadModel('execution')->getPairsForTestcase($productID, $branch, $projectID);

        $select  = html::select("execution[{$number}]", $executions, $executionID, "class='form-control' onchange='setStories({$productID}, this.value, {$number}, {$projectID})'");

        echo $select;
    }
}