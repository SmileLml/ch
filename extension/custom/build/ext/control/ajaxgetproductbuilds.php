<?php
class mybuild extends build
{
    /**
     * AJAX: get builds of a product in html select.
     *
     * @param  int        $productID
     * @param  string     $varName      the name of the select object to create
     * @param  string     $build        build to selected
     * @param  string|int $branch
     * @param  int        $index        the index of batch create bug.
     * @param  string     $type         get all builds or some builds belong to normal releases and executions are not done.
     * @param  string     $extra
     * @access public
     * @return string
     */
    public function ajaxGetProductBuilds($productID, $varName, $build = '', $branch = 'all', $index = 0, $type = 'normal', $extra = '')
    {
        $this->app->loadLang('bug');
        $loadAllBtn = $this->app->tab != 'chteam' ? '<span class="input-group-btn"> <button type="button" class="btn" onclick="loadAllBuilds(this)" style="border-radius: 0px 2px 2px 0px; border-left-color: transparent;">' . $this->lang->bug->allBuilds . '</button></span>' : '';
        $isJsonView = $this->app->getViewType() == 'json';
        if($varName == 'openedBuild' )
        {
            $params = ($type == 'all') ? 'noempty,withbranch,noreleased' : 'noempty,noterminate,nodone,withbranch,noreleased';
            $builds = $this->build->getBuildPairs($productID, $branch, $params, 0, 'project', $build);
            if($isJsonView) return print(json_encode($builds));
            return print(html::select($varName . '[]', $builds, $build, 'size=4 class=form-control multiple'));
        }
        if($varName == 'openedBuilds' )
        {
            $builds = $this->build->getBuildPairs($productID, $branch, 'noempty,noreleased', 0, 'project', $build);
            if($isJsonView) return print(json_encode($builds));
            return print(html::select($varName . "[$index][]", $builds, $build, 'size=4 class=form-control multiple'));
        }
        if($varName == 'resolvedBuild')
        {
            $params = ($type == 'all') ? 'withbranch,noreleased' : 'noterminate,nodone,withbranch,noreleased';
            $builds = $this->build->getBuildPairs($productID, $branch, $params, 0, 'project', $build);
            if($isJsonView) return print(json_encode($builds));
            return print(html::select($varName, $builds, $build, "class='form-control'") . $loadAllBtn);
        }

        $builds = $this->build->getBuildPairs($productID, $branch, $type, 0, 'project', $build, false);
        if(strpos($extra, 'multiple') !== false) $varName .= '[]';
        if($isJsonView) return print(json_encode($builds));
        return print(html::select($varName, $builds, $build, "class='form-control chosen' $extra"));
    }
}

