<?php
class flowProduct extends productModel
{
    public function getProductLink($module, $method, $extra, $branch = false)
    {
        $link = parent::getProductLink($module, $method, $extra, $branch);

        if($module == 'flow' and !empty($extra))
        {
            /* Get flow by extra. */
            $flow = $this->dao->select('*')->from(TABLE_WORKFLOW)->where('id')->eq((int)$extra)->fetch();
            if($flow)
            {
                $labels = $this->dao->select('*')->from(TABLE_WORKFLOWLABEL)->where('module')->eq($flow->module)->orderBy('order')->fetchAll();
                foreach($labels as $label)
                {
                    if($label->buildin) continue;
                    if(!commonModel::hasPriv($flow->module, $label->id)) continue;

                    $link = helper::createLink($flow->module, 'browse', "mode=browse&label={$label->id}");
                    break;
                }
            }
        }

        if($module == 'chproject' && in_array($method, array('bug', 'testcase')))
        {
            $params = explode(',', $extra);
            return helper::createLink($module, $method, "projectID={$this->session->chproject}&intanceProjectID={$this->session->intanceProjectID}&productID=%s" . ($branch ? "&branch=%s" : ''));
        }

        return $link;
    }
}
