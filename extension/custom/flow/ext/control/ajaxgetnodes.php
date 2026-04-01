<?php
class myFlow extends flow
{
    public function ajaxGetNodes($object = '', $id = 0, $action = '')
    {
        if(!$object || empty($this->config->openedApproval)) die($this->lang->noData);

        $this->loadModel('user');

        $data = new stdclass();
        if($id > 0)
        {
            $flow = $this->loadModel('workflow')->getByModule($object);
            $data = $this->loadModel('flow')->getDataByID($flow, $id);
        }

        $users          = $this->user->getDeptPairs('nodeleted|noclosed');
        $flowID         = $this->loadModel('approvalflow')->getFlowIDByObject(0, $object, $data, $action);
        $nodes          = $this->loadModel('approval')->getNodesToConfirm($flowID);
        $PMOUsers       = $this->user->getUsersByUserGroupName('PMO');
        $architectUsers = $this->user->getUsersByUserGroupName($this->lang->flow->architect);

        if(empty($nodes)) die($this->lang->noData);

        $html   = "<table class='table table-form mg-0 table-bordered' style='border: 1px solid #ddd'>";
        $html  .= "<thead><tr class='text-center'>";
        $html  .= "<th class='w-100px'>" . $this->lang->approval->node . '</th>';
        $html  .= "<th>" . $this->lang->approval->reviewer . '</th>';
        $html  .= "<th>" . $this->lang->approval->ccer . '</th>';
        $html  .= '</tr></thead><tbody>';

        foreach($nodes as $node)
        {
            $html .= '<tr>';
            $html .= "<td class='text-center'>" . $node['title'] . html::hidden('approval_id[]', $node['id']) . '</td>';
            $html .= '<td>';

            $reviewers = array();
            if(isset($node['appointees']['reviewer']))
            {
                foreach($node['appointees']['reviewer'] as $appointee) $reviewers[$appointee] = zget($users, $appointee);
            }
            if(isset($node['upLevel']['reviewer']))
            {
                foreach($node['upLevel']['reviewer'] as $upLevel) $reviewers[$upLevel] = zget($users, $upLevel);
            }
            if(isset($node['role']['reviewer']))
            {
                foreach($node['role']['reviewer'] as $roleUser) $reviewers[$roleUser] = zget($users, $roleUser);
            }
            if(in_array('reviewer', $node['types']))
            {
                $html .= html::select('approval_reviewer[' . $node['id'] . '][]', array_diff($users, $reviewers), '', "multiple class='form-control picker-select' data-drop_direction='down'");
                if($reviewers) $html .= "<div class='otherReviewer' style='margin-top:10px'>" . $this->lang->approval->otherReviewer . join(',', $reviewers) . '</div>';
            }
            if(isset($node['groupMember']['reviewer']))
            {
                $classes = '';

                $groupMembers = [];

                foreach($node['groupMember']['reviewer'] as $class)
                {
                    $classes .= "projectapproval$class,";
                    if($class == 'businessManager')
                    {
                        $realname     = $this->dao->select('realname')->from(TABLE_USER)->where('account')->eq($data->businessPM)->fetch('realname');
                        $groupMembers[$data->businessPM] = $realname;
                    }
                    else
                    {
                        $projectMembers = $this->dao->select('account')->from('zt_flow_projectmembers')
                            ->where('parent')->eq($id)
                            ->andWhere('projectRole')->eq($class)
                            ->andWhere('deleted')->eq(0)
                            ->fetchPairs();

                        if(!empty(array_filter($projectMembers))) $groupMembers += $this->user->getPairs('nodeleted|noclosed', '', 0, implode(',', $projectMembers));
                    }
                }

                $html .= html::hidden(trim($classes, ','), '');
                $html .= html::select('approval_reviewer[' . $node['id'] . '][]', $groupMembers, '', "multiple class='form-control picker-select'");
            }
            if(isset($node['permissionGrouping']['reviewer']))
            {
                $class = reset($node['permissionGrouping']['reviewer']);
                $permissionGroupings = [];

                $permissionGroupings = $this->user->getUsersByUserGroupName($class, 'id');
                if($class == 'PMO')       $permissionGroupings = $PMOUsers;
                if($class == 'architect') $permissionGroupings = $architectUsers;

                $html .= html::select('approval_reviewer[' . $node['id'] . '][]', $permissionGroupings, '', "multiple class='form-control picker-select'");
            }
            else
            {
                $html .= html::hidden('approval_reviewer[' . $node['id'] . '][]', '');
                if($reviewers) $html .= join(',', $reviewers);
            }

            $html .= '</td>';
            $html .= '<td>';

            $ccers = array();
            if(isset($node['appointees']['ccer']))
            {
                foreach($node['appointees']['ccer'] as $appointee) $ccers[$appointee] = zget($users, $appointee);
            }
            if(isset($node['upLevel']['ccer']))
            {
                foreach($node['upLevel']['ccer'] as $upLevel) $ccers[$upLevel] = zget($users, $upLevel);
            }
            if(isset($node['role']['ccer']))
            {
                foreach($node['role']['ccer'] as $roleUser) $ccers[$roleUser] = zget($users, $roleUser);
            }

            if(in_array('ccer', $node['types']))
            {
                $html .= html::select('approval_ccer[' . $node['id'] . '][]', array_diff($users, $ccers), '', "multiple class='form-control chosen'");
                if($ccers) $html .= "<div class='otherCcer' style='margin-top:10px'>" . $this->lang->approval->otherCcer . join(',', $ccers) . '</div>';
            }
            else
            {
                $html .= html::hidden('approval_ccer[' . $node['id'] . '][]', '');
                if($ccers) $html .= join(',', $ccers);
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html  .= '</tbody></table>';

        return print($html);
    }
}
