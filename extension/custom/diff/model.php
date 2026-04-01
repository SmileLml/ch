<?php
/**
 * The model file of diff module of chandao.net.
 *
 * @copyright   Copyright 2009-2022 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      wangxiaomeng <wangxiaomeng@chandao.com>
 * @package     diff
 * @version     $Id$
 * @link        https://www.chandao.net
 */
class diffModel extends model
{
    public function getVersionData($objectID, $objectType, $version)
    {
        $object = $this->dao->select('element')
            ->from(TABLE_OBJECTVERSION)
            ->where('objectID')->eq($objectID)
            ->andWhere('objectType')->eq($objectType)
            ->andWhere('version')->eq($version)
            ->fetch('element');

        if($object) $object = json_decode($object);

        return $object;
    }
    /**
     * 获取业务差异
     *
     * @param array $firstDataMap 第一个版本的数据
     * @param array $secondDataMap 第二个版本的数据
     * @return array 返回两个版本的数据
     */
    public function getBusinessDiff($firstDataMap, $secondDataMap)
    {
        $firstBusinesses = [];
        foreach($firstDataMap as $firstItem)
        {
            if(!isset($firstBusinesses[$firstItem->business])) $firstBusinesses[$firstItem->business] = $firstItem;
        }

        $secondBusinesses = [];
        foreach($secondDataMap as $secondItem)
        {
            if(!isset($secondBusinesses[$secondItem->business])) $secondBusinesses[$secondItem->business] = $secondItem;
        }

        $firstDataMap  = $firstBusinesses;
        $secondDataMap = $secondBusinesses;

        return array($firstDataMap, $secondDataMap);
    }
    /**
     * 获取成员差异
     *
     * @param array $firstDataMap 第一个版本的数据
     * @param array $secondDataMap 第二个版本的数据
     * @return array 返回两个版本的数据
     */
    public function getMemberDiff($firstDataMap, $secondDataMap)
    {
        $firstMembers = [];
        foreach($firstDataMap as $firstMemberItem)
        {
            $firstMemberKey = $firstMemberItem->account . '_' . $firstMemberItem->projectRole;
            if(!isset($firstMembers[$firstMemberKey])) $firstMembers[$firstMemberKey] = $firstMemberItem;
        }

        $secondMembers = [];
        foreach($secondDataMap as $secondMemberItem)
        {
            $secondMemberKey = $secondMemberItem->account . '_' . $secondMemberItem->projectRole;
            if(!isset($secondMembers[$secondMemberKey])) $secondMembers[$secondMemberKey] = $secondMemberItem;
        }

        $firstDataMap  = $firstMembers;
        $secondDataMap = $secondMembers;

        return array($firstDataMap, $secondDataMap);
    }

    /**
     * 获取成本差异
     *
     * @param array $firstDataMap 第一个版本的数据
     * @param array $secondDataMap 第二个版本的数据
     * @return array 返回两个版本的数据
     */
    public function getCostDiff($firstDataMap, $secondDataMap)
    {
        $firstCosts = [];
        foreach($firstDataMap as $firstCostItem)
        {
            $firstCostKey = $firstCostItem->costType . '_' . $firstCostItem->costDept;
            if(!isset($firstCosts[$firstCostKey])) $firstCosts[$firstCostKey] = $firstCostItem;
        }

        $secondCosts = [];
        foreach($secondDataMap as $secondCostItem)
        {
            $secondCostKey = $secondCostItem->costType . '_' . $secondCostItem->costDept;
            if(!isset($secondCosts[$secondCostKey])) $secondCosts[$secondCostKey] = $secondCostItem;
        }

        $firstDataMap  = $firstCosts;
        $secondDataMap = $secondCosts;

        return array($firstDataMap, $secondDataMap);
    }

    /**
     * 获取价值差异
     *
     * @param array $firstDataMap 第一个版本的数据
     * @param array $secondDataMap 第二个版本的数据
     * @return array 返回两个版本的数据
     */
    public function getDiffByID($firstDataMap, $secondDataMap)
    {
        $firstValues   = [];
        $firstValueKey = 1;
        foreach($firstDataMap as $firstValueItem)
        {
            if(!isset($firstValues[$firstValueKey])) $firstValues[$firstValueKey] = $firstValueItem;
            $firstValueKey ++;
        }

        $secondValues   = [];
        $secondValueKey = 1;
        foreach($secondDataMap as $secondValueItem)
        {
            if(!isset($secondValues[$secondValueKey])) $secondValues[$secondValueKey] = $secondValueItem;
            $secondValueKey ++;
        }

        $firstDataMap  = $firstValues;
        $secondDataMap = $secondValues;

        return array($firstDataMap, $secondDataMap);
    }

    public function getChangeDiff($firstDataMap, $secondDataMap)
    {
        $moduleChanges = array('added' => array(), 'deleted' => array(), 'modified' => array(), 'unchanged' => array());
        if(!empty($secondDataMap) || !empty($firstDataMap))
        {
            // 查找新增和修改的数据
            foreach($firstDataMap as $id => $item)
            {
                if(!isset($secondDataMap[$id]))
                {
                    // 新增的数据
                    $moduleChanges['added'][] = $item;
                }
                else
                {
                    // 可能修改的数据，检查是否有变化
                    $secondItem = $secondDataMap[$id];
                    $hasChanges = false;

                    foreach($item as $field => $value)
                    {
                        if(in_array($field, array('id', 'createdBy', 'createdDate', 'project'))) continue;
                        if(!isset($secondItem->$field) || $secondItem->$field !== $value)
                        {
                            $hasChanges = true;
                            break;
                        }
                    }

                    if($hasChanges)
                    {
                        // 保存修改前的数据到 oldData 属性
                        $item->oldData = $secondItem;
                        $moduleChanges['modified'][] = $item;
                    }
                    else
                    {
                        // 未变化的数据
                        $moduleChanges['unchanged'][] = $item;
                    }
                }
            }
        }

        // 查找删除的数据
        foreach($secondDataMap as $id => $item)
        {
            if(!isset($firstDataMap[$id]))
            {
                $moduleChanges['deleted'][] = $item;
            }
        }

        return $moduleChanges;
    }

    public function getDiffData($objectType, $firstObject, $secondObject, $allObjectFields, $fields)
    {
        $diffData  = array();
        $relations = $this->loadModel('workflowrelation', 'flow')->getPrevList($objectType);

        $this->loadModel('flow');
        foreach($allObjectFields as $field)
        {
            $secondValue = isset($secondObject->$field) ? $secondObject->$field : '';
            $firstValue  = isset($firstObject->$field) ? $firstObject->$field : '';

            if($firstValue === $secondValue && $firstValue === '') continue;

            $firstValue = '';
            $secondValue = '';
            $relation = zget($relations, $field, '');
            if(!empty($firstObject->{$field}))
            {
                if(is_array($firstObject->{$field}))
                {
                    foreach($firstObject->{$field} as $value) $firstValue .= $this->flow->processFieldValue($field, $relation, $value) . ' ';
                }
                else
                {
                    $firstValue = $this->flow->processFieldValue($fields[$field], $relation, $firstObject->{$field});
                }
            }
            else
            {
                if(isset($firstObject->{$field}) && $firstObject->{$field} === '0') $firstValue = '0';
            }

            if(!empty($secondObject->{$field}))
            {
                if(is_array($secondObject->{$field}))
                {
                    foreach($secondObject->{$field} as $value) $secondValue .= $this->flow->processFieldValue($field, $relation, $value) . ' ';
                }
                else
                {
                    $secondValue = $this->flow->processFieldValue($fields[$field], $relation, $secondObject->{$field});
                }
            }
            else
            {
                if(isset($secondObject->{$field}) && $secondObject->{$field} === '0') $secondValue = '0';
            }

            $fieldName = isset($allFields->$field) ? $allFields->$field : $field;

            $diffData[$field] = array('name' => $fieldName, 'first' => $firstValue, 'second'=> $secondValue, 'diff' => ($firstValue != $secondValue), 'field' => isset($fields[$field]) ? $fields[$field] : null);
        }

        return $diffData;
    }

    public function getChildInfo($objectID, $objectType, $fields)
    {
        $this->loadModel('flow');
        $this->loadModel('workflowaction');
        $childModules = $this->loadModel('workflow', 'flow')->getList('browse', 'table', '', $objectType);
        foreach($childModules as $childModule)
        {
            $key = 'sub_' . $childModule->module;

            if(isset($fields[$key]) && $fields[$key]->show)
            {
                $childData = [];
                if($objectID) $childData = $this->flow->getDataList($childModule, '', 0, '', $objectID, 'id_asc');

                $childFields[$key] = $this->workflowaction->getFields($childModule->module, 'view', true, $childData);
                $childDatas[$key]  = $childData;
            }
        }

        return array($childFields, $childDatas);
    }

    /**
     * Get child version data.
     *
     * @param  int    $actionID
     * @access public
     * @return array
     */
    public function getChildVersionData($actionID)
    {
        $object = $this->dao->select('old,new')
            ->from(TABLE_CHILDHISTORY)
            ->where('action')->eq($actionID)
            ->fetch();

        return array(json_decode($object->new), json_decode($object->old));
    }
}
