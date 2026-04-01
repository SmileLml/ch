<?php
helper::importControl('project');
class myProject extends project
{
    /**
     * Select product.
     *
     * @param int projectID
     * @param int businessID
     * @access public
     * @return void
     */
    public function selectProduct($projectID, $businessID)
    {
        $products = $this->loadModel('product')->getProductPairsByProject($projectID);

        $this->view->title      = $this->lang->project->selectProduct;
        $this->view->projectID  = $projectID;
        $this->view->businessID = $businessID;
        $this->view->products   = $products;

        $this->display();
    }
}
