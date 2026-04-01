<?php
/**
 * The xmind library of zentaopms.
 *
 * @copyright   Copyright 2009-2024 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Yong Lei <leiyong@cnezsoft.com>
 * @package     Xmind
 * @link        http://www.zentao.net
 */
class mind
{
    /**
     * Build the main content of the xmind file.
     *
     * @param  string $nodeName
     * @param  string $nodeID
     * @access public
     * @return void
     */
    public function createMindBody($nodeName, $nodeID)
    {
        $mindBody = '
        [
            {
                "id": "3d11f509-ea82-4b07-80ca-272b081bd57c",
                "class": "sheet",
                "rootTopic": {
                    "id": "' . $nodeID . '",
                    "class": "topic",
                    "title": "' . $nodeName . '",
                    "titleUnedited": true,
                    "structureClass": "org.xmind.ui.logic.right",
                    "children": {
                        "attached": [
                        ]
                    }
                },
                "title": "canvas",
                "topicOverlapping": "overlap",
                "extensions": [
                    {
                        "provider": "org.xmind.ui.skeleton.structure.style",
                        "content": {
                            "centralTopic": "org.xmind.ui.logic.right"
                        }
                    }
                ]
            }
        ]';

        $mindBody = json_decode($mindBody, true);
        return $mindBody;
    }

    /**
     * Merge node data.
     *
     * @param  array  $mindData
     * @access public
     * @return array
     */
    public function mergeNodeData($mindData)
    {
        $nodeData = array();
        foreach($mindData as $nodes)
        {
            foreach($nodes as $node)
            {
                $nodeData[] = $node;
            }
        }
        return $nodeData;
    }

    /**
     * Generate content into json file.
     *
     * @param  string $filePath
     * @param  array  $mindData
     * @param  array  $mindBody
     * @param  string $exportObject
     * @access public
     * @return void
     */
    public function createMindContent($filePath, $mindData, $mindBody, $exportObject)
    {
        global $app;

        $directory = dirname($filePath);
        if(!file_exists($filePath)) mkdir($directory, 0777, true);

        $demoFile    = $app->coreLibRoot . 'mind/content.json';
        $contentFile = $directory . '/content.json';

        copy($demoFile, $contentFile);

        if($exportObject == 'module')   $children = $this->getModuleChildren($mindData);
        if($exportObject == 'testcase') $children = $this->getCaseChildren($mindData);

        $mindBody[0]['rootTopic']['children']['attached'] = $children;

        $mindBody = json_encode($mindBody, JSON_UNESCAPED_UNICODE);
        file_put_contents($contentFile, $mindBody);
    }

    /**
     * Create mind children content.
     *
     * @param  array  $mindData
     * @access public
     * @return array
     */
    public function getCaseChildren($mindData)
    {
        $output = array();

        foreach($mindData as $item)
        {
            $newItem = array(
                "id"            => $this->generateUUID() . '-' . $item->id,
                "title"         => $item->name,
                "titleUnedited" => true,
                "children"      => array("attached" => array())
            );

            if(!empty($item->children))
            {
                foreach ($item->children as $child)
                {
                    $newChild = array(
                        "id"            => $this->generateUUID() . '-' . $child->id,
                        "title"         => $child->name,
                        "titleUnedited" => true,
                        "children"      => array("attached" => array())
                    );

                    if(!empty($child->children))
                    {
                        $newChild['children']['attached'] = $this->getCaseChildren($child->children);
                    }

                    $newItem['children']['attached'][] = $newChild;
                }
            }

            $output[] = $newItem;
        }

        return $output;
    }

    /**
     * Create mind children content.
     *
     * @param  array  $mindData
     * @access public
     * @return array
     */
    public function getModuleChildren($mindData)
    {
        $output = array();

        foreach($mindData as $item)
        {
            $newItem = array(
                "id"            => $item->type . $item->id,
                "title"         => $item->name . "[$item->type:$item->id]",
                "titleUnedited" => true,
                "children"      => array("attached" => array())
            );

            if(!empty($item->children))
            {
                foreach($item->children as $child)
                {
                    $newChild = array(
                        "id"            => $child->type . $child->id,
                        "title"         => $child->name . "[$child->type:$child->id]",
                        "titleUnedited" => true,
                        "children"      => array("attached" => array())
                    );

                    if(!empty($child->children))
                    {
                        $newChild['children']['attached'] = $this->getModuleChildren($child->children);
                    }

                    $newItem['children']['attached'][] = $newChild;
                }
            }

            $output[] = $newItem;
        }

        return $output;
    }

    /**
     * Get uuid.
     *
     * @access public
     * @return string
     */
    public function generateUUID()
    {
        $bytes = random_bytes(16);

        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); 
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));

        return $uuid;
    }

    /**
     * Export xmind file.
     *
     * @param  string $filePath
     * @access public
     * @return void
     */
    public function export($filePath)
    {
        global $app;

        $content  = $app->coreLibRoot . 'mind/content.xml';
        $manifest = $app->coreLibRoot . 'mind/manifest.json';
        $metadata = $app->coreLibRoot . 'mind/metadata.json';

        $directory = dirname($filePath);
        if(!file_exists($filePath)) mkdir($directory, 0777, true);

        $zip = new ZipArchive();
        $zip->open($filePath, ZipArchive::CREATE);

        $fileList = array($content, $manifest, $metadata, $directory . '/content.json');
        foreach($fileList as $file) $zip->addFile($file, basename($file));

        $zip->close();
    }

    /**
     * Create module node.
     *
     * @param  array  $context
     * @param  array  $moduleNodes
     * @access public
     * @return void
     */
    public function createModuleNode($context, &$moduleNodes)
    {
        $config     = $context['config'];
        $moduleList = $context['moduleList'];

        foreach($moduleList as $index => $name)
        {
            $suffix = $config['module'] . ':' . $index;

            $moduleNode = new stdClass();
            $moduleNode->name     = $this->toText($name, $suffix);
            $moduleNode->id       = 'module' . $index;
            $moduleNode->children = array();

            $moduleNodes[$index] = $moduleNode;
        }
    }

    /**
     * Create scene node.
     *
     * @param  array  $context
     * @param  array  $moduleNodes
     * @param  array  $sceneNodes
     * @access public
     * @return void
     */
    public function createSceneNode($context, &$moduleNodes, &$sceneNodes)
    {
        $config    = $context['config'];
        $topScenes = $context['topScenes'];

        foreach($topScenes as $scene)
        {
            $suffix = $config['scene'] . ':' . $scene->sceneID;

            $sceneNode = new stdClass();
            $sceneNode->name     = $this->toText($scene->sceneName, $suffix);
            $sceneNode->id       = 'scene' . $scene->sceneID;
            $sceneNode->children = array();

            $this->createNextChildScenesNode($scene, $sceneNode, $context, $moduleNodes, $sceneNodes);

            if(isset($moduleNodes[$scene->moduleID]))
            {
                $moduleNodes[$scene->moduleID]->children[$scene->sceneID] = $sceneNode;
            }
            else
            {
                $sceneNodes[$scene->sceneID] = $sceneNode;
            }
        }
    }

    /**
     * Create next child scene node.
     *
     * @param  object $parentScene
     * @param  array  $context
     * @param  array  $moduleNodes
     * @param  array  $sceneNodes
     * @access public
     * @return void
     */
    public function createNextChildScenesNode($parentScene, $parentNode, $context, &$moduleNodes, &$sceneNodes)
    {
        $sceneMaps = $context['sceneMaps'];
        $config    = $context['config'];

        foreach($sceneMaps as $scene)
        {
            if($scene->parentID != $parentScene->sceneID) continue;

            $suffix = $config['scene'] . ':' . $scene->sceneID;

            $sceneNode = new stdClass();
            $sceneNode->name     = $this->toText($scene->sceneName, $suffix);
            $sceneNode->id       = 'scene' . $scene->sceneID;
            $sceneNode->children = array();

            $this->createNextChildScenesNode($scene, $sceneNode, $context, $moduleNodes, $sceneNodes);

            $parentNode->children[$scene->sceneID] = $sceneNode;
        }
    }

    /**
     * Create test case node.
     *
     * @param  array $context
     * @param  array $moduleNodes
     * @param  array $sceneNodes
     * @access public
     * @return void
     */
    public function createTestcaseNode($context, &$moduleNodes, &$sceneNodes)
    {
        $caseList = $context['caseList'];
        $mindData = array();
        foreach($caseList as $case)
        {
            if(empty($case->testcaseID)) continue;

            $parentNode = $this->getParentSceneNode($sceneNodes, $case);
            if(!isset($parentNode))
            {
                $parentNode = $this->getParentModuleNode($moduleNodes, $case);
                if($case->sceneID)
                {
                    $parentNode = $this->getParentSceneNode($parentNode->children, $case);
                }
            }

            if(isset($parentNode))
            {
                $parentNode = $this->createOneTestcaseNode($case, $context, $parentNode);
            }
            else
            {
                $parentNode = $this->createOneTestcaseNode($case, $context, $parentNode);
                $mindData[] = $parentNode;
            }
        }

        $mindData[] = $moduleNodes;
        $mindData[] = $sceneNodes;

        return $mindData;
    }

    /**
     * Get the use case parent node.
     *
     * @param  array  $nodes
     * @param  object $case
     * @access public
     * @return object
     */
    public function getParentModuleNode($nodes, $case)
    {
        foreach($nodes as $index => $node)
        {
            if($case->moduleID == $index)
            {
                return $node;
            }
        }

        return null;
    }

    /**
     * Get the use case parent node.
     *
     * @param  array  $nodes
     * @param  object $case
     * @access public
     * @return object
     */
    public function getParentSceneNode($nodes, $case)
    {
        if(empty($case->sceneID)) return null;

        foreach($nodes as $index => $node)
        {
            if($case->sceneID == $index)
            {
                return $node;
            }
            else
            {
                $children = $this->getParentSceneChildrenNode($node->children, $case);
                if(!empty($children)) return $children;
            }
        }
    }

    /**
     * Get the use case parent node.
     *
     * @param  array  $nodes
     * @param  object $case
     * @access public
     * @return object
     */
    public function getParentSceneChildrenNode($nodes, $case)
    {
        foreach($nodes as $index => $node)
        {
            if($case->sceneID == $index)
            {
                return $node;
            }
            else
            {
                $this->getParentSceneChildrenNode($node->children, $case);
            }
        }
    }

    /**
     * Create one test case node.
     *
     * @param  object      $case
     * @param  array       $context
     * @param  object      $parentNode
     * @access public
     * @return void
     */
    public function createOneTestcaseNode($case, $context, $parentNode)
    {
        $stepList = $context['stepList'];
        $config   = $context['config'];
        $suffix   = $config['case'] . ':' . $case->testcaseID . ',' . $config['pri'] . ':' . $case->pri;

        $caseNode = new stdClass();
        $caseNode->name     = $this->toText($case->name, $suffix);
        $caseNode->id       = 'testcase' . $case->testcaseID;
        $caseNode->children = array();

        if(isset($parentNode->children))
        {
            $parentNode->children[] = $caseNode;
        }
        else
        {
            $parentNode[] = $caseNode;
        }

        if (!empty($case->precondition))
        {
            $this->createPreconditionNode($case->precondition, $config, $caseNode);
        }

        $topStepList = $this->findTopStepListByCase($case, $stepList);

        foreach($topStepList as $step)
        {
            $subStepList = $this->findSubStepListByStep($step, $stepList);

            $suffix = count($subStepList) > 0 ? $config['group'] : '';

            $stepNode = new stdClass();
            $stepNode->name     = $this->toText($step->desc, $suffix);
            $stepNode->id       = 'step' . $step->stepID;
            $stepNode->children = array();

            $caseNode->children[] = $stepNode;

            if(count($subStepList))
            {
                foreach($subStepList as $sub)
                {
                    $subNode = new stdClass();
                    $subNode->name     = $sub->desc;
                    $subNode->id       = 'subStep' . $sub->stepID;
                    $subNode->children = array();

                    $stepNode->children[] = $subNode;

                    if(!empty($sub->expect))
                    {
                        $expectNode = new stdClass();
                        $expectNode->name     = $sub->expect;
                        $expectNode->id       = 'subExpect' . $sub->stepID;
                        $expectNode->children = array();

                        $subNode->children[] = $expectNode;
                    }
                }
            }

            if(count($subStepList) == 0 && !empty($step->expect))
            {
                $expectNode = new stdClass();
                $expectNode->name     = $step->expect;
                $expectNode->id       = 'expect' . $step->stepID;
                $expectNode->children = array();

                $stepNode->children[] = $expectNode;
            }
        }
        return $parentNode;
    }

    /**
     * Find substep list by step.
     *
     * @param  object $step
     * @param  array  $stepList
     * @access public
     * @return array
     */
    public function findSubStepListByStep($step, $stepList)
    {
        $subList = array();
        foreach($stepList as $one)
        {
            if($one->parentID == $step->stepID)
            {
                $subList[] = $one;
            }
        }

        return $subList;
    }

    /**
     * Find top step list by case.
     *
     * @param  object $case
     * @param  array  $stepList
     * @access public
     * @return array
     */
    public function findTopStepListByCase($case, $stepList)
    {
        $topList = array();
        foreach($stepList as $step)
        {
            if($step->parentID == '0' && $step->testcaseID == $case->testcaseID)
            {
                $topList[] = $step;
            }
        }

        return $topList;
    }

    /**
     * Add suffix before string.
     *
     * @param  string $str
     * @param  string $suffix
     * @access public
     * @return string
     */
    public function toText($str, $suffix)
    {
        if(empty($suffix)) return $str;
        return $str . '[' . $suffix . ']';
    }

    /**
     * Get the number in the string.
     *
     * @param  string $inputString
     * @access public
     * @return string
     */
    public function getRootID($inputString)
    {
        if(preg_match('/product:(\d+)/', $inputString, $matches))
        {
            return $matches[1];
        }
        else
        {
            return 0;
        }
    }

    /**
     * Judgment ends with a string.
     *
     * @param  string $haystack
     * @param  string $needle
     * @access public
     * @return string
     */
    public function endsWith($haystack, $needle)
    {
        return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    /**
     * Get substring between mark1 and mark2 from kw.
     *
     * @param  string $str
     * @param  string $suffix
     * @access public
     * @return string
     */
    public function getBetween($kw1, $mark1, $mark2)
    {
        $kw = $kw1;
        $kw = '123' . $kw . '123';
        $st = strripos($kw, $mark1);
        $ed = strripos($kw, $mark2);

        if(($st == false || $ed == false) || $st >= $ed) return 0;

        $st = $st + strlen($mark1);
        $kw = substr($kw, ($st), ($ed - $st));
        return $kw;
    }

    /**
     * Create precondition node.
     *
     * @param  string      $precondition
     * @param  array       $config
     * @param  object      $parentNode
     * @access private
     * @return void
     */
    private function createPreconditionNode($precondition, $config, $parentNode)
    {
        if(empty($precondition)) return;

        $preconditionNode = new stdClass();
        $preconditionNode->name     = $this->toText($precondition, $config['precondition']);
        $preconditionNode->id       = 'precondition' . uniqid();
        $preconditionNode->children = array();

        $parentNode->children[] = $preconditionNode;
    }
}
