<?php
/**
 * The control file of diff module of chandao.net.
 *
 * @copyright   Copyright 2009-2022 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      wangxiaomeng <wangxiaomeng@chandao.com>
 * @package     diff
 * @version     $Id$
 * @link        https://www.chandao.net
 */
class diff extends control
{
    /**
     * 版本对比方法
     *
     * @param  string $objectType 对象类型
     * @param  int    $objectID   对象ID
     * @param  int    $firstVersion  第一个版本号
     * @param  int    $secondVersion 第二个版本号
     * @access public
     * @return void
     */
    public function index($objectType = '', $objectID = 0, $firstVersion = 0, $secondVersion = 0)
    {
        if(empty($objectType) || empty($objectID) || empty($firstVersion) || empty($secondVersion)) die(js::error($this->lang->diff->paramError) . js::locate('back'));

        $firstObject  = $this->diff->getVersionData($objectID, $objectType, $firstVersion);
        $secondObject = $this->diff->getVersionData($objectID, $objectType, $secondVersion);

        if(empty($firstObject) || empty($secondObject)) die(js::error($this->lang->diff->versionNotFound) . js::locate('back'));

        // 获取字段定义
        $this->loadModel('flow');
        list($flow, $action) = $this->setFlowAction($objectType, 'view');

        $data   = $this->flow->getDataByID($flow, $objectID);
        $fields = $this->setFields($flow, $action, $data);

        $excludeFields = array('children', 'uid', 'version', 'id', 'parent', 'oldStatus', 'approval');

        $allObjectFields = array();
        foreach($secondObject as $field => $value)
        {
            if(!in_array($field, $excludeFields)) $allObjectFields[$field] = $field;
        }

        foreach($firstObject as $field => $value)
        {
            if(!in_array($field, $excludeFields) && !isset($allObjectFields[$field])) $allObjectFields[$field] = $field;
        }

        $diffData = $this->diff->getDiffData($objectType, $firstObject, $secondObject, $allObjectFields, $fields);

        list($childFields, $childDatas) = $this->diff->getChildInfo($objectID, $objectType, $fields);

        // 比较子表数据
        $firstChildren  = isset($firstObject->children) ? $firstObject->children : array();
        $secondChildren = isset($secondObject->children) ? $secondObject->children : array();

        // 处理子表数据对比
        $childrenDiff = array();
        foreach($childFields as $key => $childField)
        {
            // 获取第一个版本的子表数据
            $firstModuleData = isset($firstChildren->$key) ? $firstChildren->$key : array();
            // 获取第二个版本的子表数据
            $secondModuleData = isset($secondChildren->$key) ? $secondChildren->$key : array();

            // 构建数据映射，以便比较
            $firstDataMap = array();
            foreach($firstModuleData as $item)
            {
                if(isset($item->id)) $firstDataMap[$item->id] = $item;
            }

            $secondDataMap = array();
            foreach($secondModuleData as $item)
            {
                if(isset($item->id)) $secondDataMap[$item->id] = $item;
            }

            if($key == 'sub_projectbusiness')      list($firstDataMap, $secondDataMap) = $this->diff->getBusinessDiff($firstDataMap, $secondDataMap);
            if($key == 'sub_projectmembers')       list($firstDataMap, $secondDataMap) = $this->diff->getMemberDiff($firstDataMap, $secondDataMap);
            if($key == 'sub_projectcost')          list($firstDataMap, $secondDataMap) = $this->diff->getCostDiff($firstDataMap, $secondDataMap);
            if($key == 'sub_projectvalue')         list($firstDataMap, $secondDataMap) = $this->diff->getDiffByID($firstDataMap, $secondDataMap);
            if($key == 'sub_projectprocess')       list($firstDataMap, $secondDataMap) = $this->diff->getDiffByID($firstDataMap, $secondDataMap);
            if($key == 'sub_projectreviewdetails') list($firstDataMap, $secondDataMap) = $this->diff->getDiffByID($firstDataMap, $secondDataMap);

            $childrenDiff[$key] = $this->diff->getChangeDiff($firstDataMap, $secondDataMap);
        }

        $this->view->title         = $this->lang->diff->title;
        $this->view->objectID      = $objectID;
        $this->view->objectType    = $objectType;
        $this->view->firstVersion  = $firstVersion;
        $this->view->secondVersion = $secondVersion;
        $this->view->diffData      = $diffData;
        $this->view->fields        = $fields;
        $this->view->childFields   = $childFields;
        $this->view->childrenDiff  = $childrenDiff;

        $this->display();
    }

     /**
     * Set flow, action.
     *
     * @param  string $module
     * @param  string $action
     * @access public
     * @return array
     */
    public function setFlowAction($module, $action)
    {
        $flow   = $this->loadModel('workflow', 'flow')->getByModule($module);
        $action = $this->loadModel('workflowaction', 'flow')->getByModuleAndAction($flow->module, $action);

        $this->view->title  = $action->name;
        $this->view->flow   = $flow;
        if($action->method == 'view') $this->view->flowAction = $action;
        if($action->method != 'view') $this->view->action     = $action;

        if(!isset($this->lang->apps->{$flow->app}))
        {
            /* If the flow's app isn't a built-in entry, refactor the main menu. */
            $entry = $this->loadModel('entry')->getByCode($flow->app);

            if($entry)
            {
                $this->lang->admin->common = $entry->name;
                $this->lang->apps->sys = $entry->name;
                $this->lang->menu->sys = $this->lang->menu->{$flow->app};
            }
        }

        return array($flow, $action);
    }

    /**
     * Set fields.
     *
     * @param  object $flow
     * @param  object $action
     * @param  array  $datas
     * @access public
     * @return array
     */
    public function setFields($flow, $action, $datas = array())
    {
        if($action->type == 'batch' && $action->batchMode == 'same') $datas = array();

        $fields = $this->loadModel('workflowaction', 'flow')->getFields($flow->module, $action->action, true, $datas);

        if($action->type == 'batch' && $action->batchMode == 'different') $fields = $this->flow->addDitto($fields);

        $this->view->fields = $fields;

        return $fields;
    }

    /**
     * 版本对比方法
     *
     * @param  string $objectType 对象类型
     * @param  int    $objectID   对象ID
     * @param  int    $firstVersion  第一个版本号
     * @param  int    $secondVersion 第二个版本号
     * @access public
     * @return void
     */
    public function childDiff($objectType = '', $objectID = 0, $actionID = 0)
    {
        if(empty($objectType) || empty($objectID) || empty($actionID)) return $this->send(array('result' => 'fail', 'message' => $this->lang->diff->paramError));

        list($firstObject, $secondObject) = $this->diff->getChildVersionData($actionID);

        if(empty($firstObject) || empty($secondObject)) return $this->send(array('result' => 'fail', 'message' => $this->lang->diff->notFound));

        $this->loadModel('flow');
        list($flow, $action) = $this->setFlowAction($objectType, 'view');

        $data   = $this->flow->getDataByID($flow, $objectID);
        $fields = $this->setFields($flow, $action, $data);

        list($childFields, $childDatas) = $this->diff->getChildInfo($objectID, $objectType, $fields);

        $firstChildren  = isset($firstObject->children) ? $firstObject->children : array();
        $secondChildren = isset($secondObject->children) ? $secondObject->children : array();

        $childrenDiff = array();
        foreach($childFields as $key => $childField)
        {
            $firstModuleData  = isset($firstChildren->$key) ? $firstChildren->$key : array();
            $secondModuleData = isset($secondChildren->$key) ? $secondChildren->$key : array();

            // 构建数据映射，以便比较
            $firstDataMap = array();
            foreach($firstModuleData as $item)
            {
                if(isset($item->id)) $firstDataMap[$item->id] = $item;
            }

            $secondDataMap = array();
            foreach($secondModuleData as $item)
            {
                if(isset($item->id)) $secondDataMap[$item->id] = $item;
            }

            if($key == 'sub_projectbusiness')      list($firstDataMap, $secondDataMap) = $this->diff->getBusinessDiff($firstDataMap, $secondDataMap);
            if($key == 'sub_projectmembers')       list($firstDataMap, $secondDataMap) = $this->diff->getMemberDiff($firstDataMap, $secondDataMap);
            if($key == 'sub_projectcost')          list($firstDataMap, $secondDataMap) = $this->diff->getCostDiff($firstDataMap, $secondDataMap);
            if($key == 'sub_projectvalue')         list($firstDataMap, $secondDataMap) = $this->diff->getDiffByID($firstDataMap, $secondDataMap);
            if($key == 'sub_projectprocess')       list($firstDataMap, $secondDataMap) = $this->diff->getDiffByID($firstDataMap, $secondDataMap);
            if($key == 'sub_projectreviewdetails') list($firstDataMap, $secondDataMap) = $this->diff->getDiffByID($firstDataMap, $secondDataMap);

            $childrenDiff[$key] = $this->diff->getChangeDiff($firstDataMap, $secondDataMap);
        }

        $this->view->title        = $this->lang->diff->title;
        $this->view->objectID     = $objectID;
        $this->view->objectType   = $objectType;
        $this->view->childFields  = $childFields;
        $this->view->childrenDiff = $childrenDiff;

        $this->display();
    }

    /**
     * 版本对比方法
     *
     * @param  string $objectType 对象类型
     * @param  int    $objectID   对象ID
     * @param  int    $firstVersion  第一个版本号
     * @param  int    $secondVersion 第二个版本号
     * @access public
     * @return void
     */
    public function ajaxGetChildDiffError($objectType = '', $objectID = 0, $actionID = 0)
    {
        if(empty($objectType) || empty($objectID) || empty($actionID)) return $this->send(array('result' => 'fail', 'message' => $this->lang->diff->paramError));

        list($firstObject, $secondObject) = $this->diff->getChildVersionData($actionID);

        if(empty($firstObject) || empty($secondObject)) return $this->send(array('result' => 'fail', 'message' => $this->lang->diff->notFound));

        return $this->send(array('result' => 'success'));
    }
}