<?php
if($children)
{
    foreach($children as $sub => $child) $subDatas[$sub] = (array)$child;

    $this->view->childDatas = $subDatas;
}
