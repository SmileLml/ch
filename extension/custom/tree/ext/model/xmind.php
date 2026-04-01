<?php
/**
 * Get module level data.
 *
 * @param  array  $data
 * @access public
 * @return array
 */
public function getModuleLevelData($data)
{
    $result = array();

    foreach($data as $item)
    {
        $resultItem         = new stdClass();
        $resultItem->id     = $item->id;
        $resultItem->name   = $item->name;
        $resultItem->parent = $item->parent;
        $resultItem->type   = $item->type;

        if(isset($item->children)) $resultItem->children = $this->getModuleLevelData($item->children);

        $result[] = $resultItem;
    }

    return $result;
}

/**
 * Get xmind file content.
 *
 * @param  string $fileName
 * @access public
 * @return string
 */
public function getXmindImport($fileName)
{
    $xmlNode  = simplexml_load_file($fileName);
    $testData = $this->xmlToArray($xmlNode);

    return json_encode($testData);
}

/**
 * Convert xml to array.
 *
 * @param  object $xml
 * @param  array  $options
 * @access public
 * @return array
 */
function xmlToArray($xml, $options = array())
{
    $defaults = array(
        'namespaceRecursive' => false, // Get XML doc namespaces recursively
        'removeNamespace'    => true, // Remove namespace from resulting keys
        'namespaceSeparator' => ':', // Change separator to something other than a colon
        'attributePrefix'    => '', // Distinguish between attributes and nodes with the same name
        'alwaysArray'        => array(), // Array of XML tag names which should always become arrays
        'autoArray'          => true, // Create arrays for tags which appear more than once
        'textContent'        => 'text', // Key used for the text content of elements
        'autoText'           => true, // Skip textContent key if node has no attributes or child nodes
        'keySearch'          => false, // (Optional) search and replace on tag and attribute names
        'keyReplace'         => false, // (Optional) replace values for above search values
    );

    $options        = array_merge($defaults, $options);
    $namespaces     = $xml->getDocNamespaces($options['namespaceRecursive']);
    $namespaces[''] = null; // Add empty base namespace

    /* Get attributes from all namespaces. */
    $attributesArray = array();
    foreach($namespaces as $prefix => $namespace)
    {
        if($options['removeNamespace']) $prefix = '';

        foreach($xml->attributes($namespace) as $attributeName => $attribute)
        {
            // (Optional) replace characters in attribute name
            if($options['keySearch']) $attributeName = str_replace($options['keySearch'], $options['keyReplace'], $attributeName);

            $attributeKey = $options['attributePrefix'] . ($prefix ? $prefix . $options['namespaceSeparator'] : '') . $attributeName;
            $attributesArray[$attributeKey] = (string) $attribute;
        }
    }

    // Get child nodes from all namespaces
    $tagsArray = array();
    foreach($namespaces as $prefix => $namespace)
    {
        if($options['removeNamespace']) $prefix = '';

        foreach($xml->children($namespace) as $childXml)
        {
            // Recurse into child nodes
            $childArray      = $this->xmlToArray($childXml, $options);
            $childTagName    = key($childArray);
            $childProperties = current($childArray);

            // Replace characters in tag name
            if($options['keySearch']) $childTagName = str_replace($options['keySearch'], $options['keyReplace'], $childTagName);

            // Add namespace prefix, if any
            if($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

            if(!isset($tagsArray[$childTagName]))
            {
                // Only entry with this key
                // Test if tags of this type should always be arrays, no matter the element count
                $tagsArray[$childTagName] = in_array($childTagName, $options['alwaysArray'], true) || !$options['autoArray'] ? array($childProperties) : $childProperties;
            }
            elseif(is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName]) === range(0, count($tagsArray[$childTagName]) - 1))
            {
                // Key already exists and is integer indexed array
                $tagsArray[$childTagName][] = $childProperties;
            }
            else
            {
                // Key exists so convert to integer indexed array with previous value in position 0
                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
            }
        }
    }

    // Get text content of node
    $textContentArray = array();
    $plainText = trim((string) $xml);
    if($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

    // Stick it all together
    $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || $plainText === '' ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

    // Return node as array
    return array($xml->getName() => $propertiesArray);
}

/**
 * Save xmind file content to database.
 *
 * @param  string $viewType
 * @access public
 * @return array
 */
public function saveXmindImport($viewType)
{
    $this->dao->begin();

    $mindList = $this->post->mindList;
    $mindList = $this->getMindGroupByType($mindList);
    if(empty($mindList)) return array('result' => 'error', 'message' => $this->lang->tree->errorSaveXmind);

    if($viewType == 'host')
    {
        $rootID = 0;
    }
    else
    {
        $product = array_shift($mindList['product']);
        $rootID  = isset($product['id']) ? $product['id'] : 0;
        if(empty($rootID)) return array('result' => 'error', 'message' => $this->lang->tree->errorImportBadProduct);
    }

    foreach($mindList as $objectType => $moduleList)
    {
        if($objectType == 'product') continue;
        $this->saveModule($objectType, $moduleList, $rootID);
    }

    $this->dao->commit();

    return array('result' => 'success', 'message' => 1);
}

/**
 * Save module data.
 *
 * @param  string $objectType
 * @param  array  $moduleList
 * @param  int    $rootID
 * @access public
 * @return array
 */
public function saveModule($objectType, $moduleList, $rootID)
{
    $tmpModules = array();
    $index      = 1;

    $createIdList  = array();
    $editIdList    = array();
    $moduleChanges = array();
    $oldModules    = $objectType != 'story' ? array() : $this->getOptionMenu($rootID, 'story', 0, 'all');
    foreach($moduleList as $id => $module)
    {
        $moduleID = $module['id'];
        if($moduleID)
        {
            if($objectType == 'story') $oldModule = $this->getByID($moduleID);

            $data = new stdClass();
            $data->root = $rootID;
            $data->name = $module['name'];
            $data->type = $objectType;

            $data = $this->getExtraModuleFields($tmpModules, $module['parent'], $index, $data);
            $this->dao->update(TABLE_MODULE)->data($data)->where('id')->eq($moduleID)->exec();
            $this->updateModulePath($moduleID, $data->parent);

            if($objectType == 'story')
            {
                $newModule = $this->getByID($moduleID);
                if(common::createChanges($oldModule, $newModule))
                {
                    $editIdList[] = $moduleID;
                    $moduleChanges[$moduleID] = common::createChanges($oldModule, $newModule);
                }
            }
        }
        else
        {
            $data = new stdClass();
            $data->root = $rootID;
            $data->name = $module['name'];
            $data->type = $objectType;

            $data = $this->getExtraModuleFields($tmpModules, $module['parent'], $index, $data);
            $this->dao->insert(TABLE_MODULE)->data($data)->exec();

            $moduleID = $this->dao->lastInsertID();
            $this->updateModulePath($moduleID, $data->parent);
            $createIdList[] = $moduleID;
        }

        $index++;

        $tmpModules[$id]['id']    = $moduleID;
        $tmpModules[$id]['grade'] = $data->grade;
        $tmpModules[$id]['order'] = $data->order;
    }

    if($objectType == 'story') $this->saveActionHistory($rootID, $objectType, $createIdList, $editIdList, $moduleChanges, $oldModules);
}

/**
 * Update the module's path field.
 *
 * @param  int    $rootID
 * @param  string $objectType
 * @param  array  $createIdList
 * @param  array  $editIdList
 * @param  array  $moduleChanges
 * @param  array  $oldModules
 * @access public
 * @return void
 */
public function saveActionHistory($rootID, $objectType, $createIdList, $editIdList, $moduleChanges, $oldModules)
{
    $this->loadModel('action');
    $objectType = $objectType == 'story' ? 'module' : '';
    if(!empty($createIdList)) $actionID = $this->action->create($objectType, $rootID, 'created', $this->lang->tree->importNoteAdd, implode(',', $createIdList));

    if(!empty($editIdList))
    {
        $changes    = array();
        $newModules = $this->getOptionMenu($rootID, 'story', 0, 'all');
        foreach($moduleChanges as $moduleID => $moduleChange)
        {
            foreach($moduleChange as $change)
            {
                if($change['field'] == 'name')
                {
                    $change['old']  = zget($oldModules, $moduleID);
                    $change['new']  = zget($newModules, $moduleID);
                    $change['diff'] = '';
                }
                $changes[] = $change;
            }
        }

        $actionID = $this->action->create($objectType, $rootID, 'edited', $this->lang->tree->importNoteEdit, implode(',', $editIdList));
        if(!empty($changes)) $this->action->logHistory($actionID, $changes);
    }
}

/**
 * Update the module's path field.
 *
 * @param  int    $moduleID
 * @param  int    $parentID
 * @access public
 * @return void
 */
public function updateModulePath($moduleID, $parentID)
{
    $module = $this->dao->findById((int)$moduleID)->from(TABLE_MODULE)->fetch();
    if($module->grade == 1)
    {
        $path = ',' . $module->id . ',';
        $this->dao->update(TABLE_MODULE)->set('path')->eq($path)->where('id')->eq($module->id)->exec();
    }
    else
    {
        $parentModule = $this->dao->findById((int)$parentID)->from(TABLE_MODULE)->fetch();
        $path = $parentModule->path . $moduleID . ',';
        $this->dao->update(TABLE_MODULE)->set('path')->eq($path)->where('id')->eq($module->id)->exec();
    }
}

/**
 * Get extra module fields.
 *
 * @param  array  $tmpModules
 * @param  string $parent
 * @param  int    $index
 * @param  object $data
 * @access public
 * @return object
 */
public function getExtraModuleFields($tmpModules, $parent, $index, $data)
{
    if(isset($tmpModules[$parent]))
    {
        $parentModule = $tmpModules[$parent];

        $parent = $parentModule['id'];
        $grade  = $parentModule['grade'] + 1;
        $order  = $parentModule['order'] + 10;
    }
    else
    {
        $parent = 0;
        $grade  = 1;
        $order  = $index * 10;
    }

    $data->parent = $parent;
    $data->grade  = $grade;
    $data->order  = $order;
    return $data;
}

/**
 * Group objects by type.
 *
 * @param  array $data
 * @access public
 * @return array
 */
public function getMindGroupByType($data)
{
    $mindGroup = array();
    array_unshift($this->config->tree->exportObjects, 'product');

    foreach($this->config->tree->exportObjects as $object)
    {
        foreach($data as $item)
        {
            $name   = $item['name'];
            $tmpId  = $item['tmpId'];
            $tmpPId = $item['tmpPId'];

            $topic = $this->getMindTopic($name);
            if(strpos($name, "[$object:") !== false)
            {
                $type = $object;
                $id   = substr($name, strpos($name, ':') + 1, strpos($name, ']') - strpos($name, ':') - 1);
                $mindGroup[$object][$tmpId] = ['name' => $topic['name'], 'type' => $type, 'id' => $topic['id'], 'parent' => $tmpPId];
            }
        }
    }

    return $mindGroup;
}

/**
 * Get topic name and ID.
 *
 * @param  string $name
 * @access public
 * @return array
 */
public function getMindTopic($name)
{
    $id   = substr($name, strpos($name, ':') + 1, strpos($name, ']') - strpos($name, ':') - 1);
    $name = preg_replace("/\[.*$/", "", $name);
    return array('id' => $id, 'name' => $name);
}
