<?php
class mybuild extends build
{
    /**
     * AJAX: get builds of an execution in html select.
     *
     * @param  int        $executionID
     * @param  int        $productID
     * @param  string     $varName      the name of the select object to create
     * @param  string     $build        build to selected
     * @param  string|int $branch
     * @param  int        $index        the index of batch create bug.
     * @param  bool       $needCreate   if need to append the link of create build
     * @param  string     $type         get all builds or some builds belong to normal releases and executions are not done.
     * @param  int        $number
     * @access public
     * @return string
     */
    public function ajaxGetExecutionBuilds($executionID, $productID, $varName, $build = '', $branch = 'all', $index = 0, $needCreate = false, $type = 'normal', $number = '')
    {
        if($this->app->tab == 'chteam')
        {
            if(!empty($executionID))
            {
                $execution = $this->loadModel('execution')->getById($executionID);
                $project   = $this->loadModel('project')->getById($execution->project);

                if($project->hasProduct == 0)
                {
                    $linkProductID = $this->loadModel('product')->getProductIDByProject($project->id);
                    $linkProduct   = $this->loadModel('product')->getById($linkProductID);

                    if($linkProduct->shadow) $productID = $linkProductID;
                }
            }
        }

        $this->app->loadLang('bug');
        $isJsonView = $this->app->getViewType() == 'json';
        $loadAllBtn = $this->app->tab != 'chteam' ? '<span class="input-group-btn"> <button type="button" class="btn" onclick="loadAllBuilds(this)" style="border-radius: 0px 2px 2px 0px; border-left-color: transparent;">' . $this->lang->bug->allBuilds . '</button></span>' : '';
        if($varName == 'openedBuild')
        {
            if(empty($executionID)) return $this->ajaxGetProductBuilds($productID, $varName, $build, $branch, $index, $type);

            $params = ($type == 'all') ? 'noempty,noreleased' : 'noempty,noterminate,nodone,noreleased';
            $builds = $this->build->getBuildPairs($productID, $branch, $params, $executionID, 'execution', $build);
            if($isJsonView) return print(json_encode($builds));

            $varName = $number === '' ? $varName : $varName . "[$number]";
            return print(html::select($varName . '[]', $builds , '', 'size=4 class=form-control multiple') . $loadAllBtn);
        }
        if($varName == 'openedBuilds')
        {
            if(empty($executionID)) return $this->ajaxGetProductBuilds($productID, $varName, $build, $branch, $index, $type);

            $builds = $this->build->getBuildPairs($productID, $branch, 'noempty,noreleased', $executionID, 'execution', $build);
            if($isJsonView) return print(json_encode($builds));
            return print(html::select($varName . "[$index][]", $builds , $build, 'size=4 class=form-control multiple'));
        }
        if($varName == 'resolvedBuild')
        {
            if(empty($executionID)) return $this->ajaxGetProductBuilds($productID, $varName, $build, $branch, $index, $type);

            $params = ($type == 'all') ? ',noreleased' : 'noterminate,nodone,noreleased';
            $builds = $this->build->getBuildPairs($productID, $branch, $params, $executionID, 'execution', $build);
            if($isJsonView) return print(json_encode($builds));
            return print(html::select($varName, $builds, $build, "class='form-control'"));
        }
        if($varName == 'testTaskBuild')
        {
            $builds = $this->build->getBuildPairs($productID, $branch, 'noempty,notrunk', $executionID, 'execution', '', false);
            if($isJsonView) return print(json_encode($builds));

            if(empty($builds))
            {
                $projectID = $this->dao->select('project')->from(TABLE_EXECUTION)->where('id')->eq($executionID)->fetch('project');

                $html  = html::a($this->createLink('build', 'create', "executionID=$executionID&productID=$productID&projectID=$projectID", '', $onlybody = true), $this->lang->build->create, '', "data-toggle='modal' data-type='iframe'");
                $html .= '&nbsp; ';
                $html .= html::a("javascript:loadExecutionBuilds($executionID)", $this->lang->refresh);
                return print($html);
            }
            return print(html::select('build', array('') + $builds, $build, "class='form-control'"));
        }
        if($varName == 'dropdownList')
        {
            $builds = $this->build->getBuildPairs($productID, $branch, 'noempty,notrunk', $executionID, 'execution');
            if($isJsonView) return print(json_encode($builds));

            $list  = "<div class='list-group'>";
            foreach($builds as $buildID => $buildName) $list .= html::a(inlink('view', "buildID={$buildID}"), $buildName);
            $list .= '</div>';

            return print($list);
        }
    }
}
