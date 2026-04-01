<?php
helper::importControl('story');
class myStory extends story
{
    /**
     * AJAX: get projects of a product in html select.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  int    $projectID
     * @access public
     * @return void
     */
    public function ajaxGetProductProjects($productID, $branch = 0, $projectID = 0)
    {
        $projects  = array(0 => '');
        $projects += $this->product->getProjectPairsByProduct($productID, $branch);
        if($this->app->getViewType() == 'json') return print(json_encode($projects));

        return print(html::select('project', $projects, $projectID, "class='form-control' onchange='loadProjectBusinesses(this.value)'"));
    }
}
