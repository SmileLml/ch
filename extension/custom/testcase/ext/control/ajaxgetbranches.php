<?php
helper::importControl('testcase');
class mytestcase extends testcase
{
    /**
     * Ajax get branches.
     *
     * @param  int    $productID
     * @param  int    $oldBranch
     * @param  string $browseType
     * @param  int    $projectID
     * @param  int    $index
     * @param  bool   $withMainBranch
     * @access public
     * @return void
     */
    public function ajaxGetBranches($productID, $oldBranch = 0, $browseType = 'all', $projectID = 0, $index = 1, $withMainBranch = true)
    {
        $product = $this->loadModel('product')->getById($productID);
        if(empty($product) or $product->type == 'normal') return;

        $branches = $this->loadModel('branch')->getList($productID, $projectID, $browseType, 'order', null, $withMainBranch);
        $branchTagOption = array();
        foreach($branches as $branchInfo)
        {
            $branchTagOption[$branchInfo->id] = $branchInfo->name . ($branchInfo->status == 'closed' ? ' (' . $this->lang->branch->statusList['closed'] . ')' : '');
        }
        if(is_numeric($oldBranch) and !isset($branchTagOption[$oldBranch]))
        {
            $branch = $this->branch->getById($oldBranch, $productID, '');
            $branchTagOption[$oldBranch] = $oldBranch == BRANCH_MAIN ? $branch : ($branch->name . ($branch->status == 'closed' ? ' (' . $this->lang->branch->statusList['closed'] . ')' : ''));
        }

        return print(html::select("branch[{$index}]", $branchTagOption, $oldBranch, "class='form-control' onchange='loadProductModules({$productID}, this.value, {$index})' data-last='{$oldBranch}'"));
    }
}