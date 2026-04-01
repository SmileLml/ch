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
class lowmind
{
    /**
     * Create xml file body content.
     *
     * @param  object $xmlDoc
     * @access public
     * @return object
     */
    public function createXmlBody($xmlDoc)
    {
        $xmapContent = $xmlDoc->createElement('xmap-content');
        $xmapContent->setAttribute('xmlns', 'urn:xmind:xmap:xmlns:content:2.0');
        $xmapContent->setAttribute('xmlns:fo', 'http://www.w3.org/1999/XSL/Format');
        $xmapContent->setAttribute('xmlns:svg', 'http://www.w3.org/2000/svg');
        $xmapContent->setAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $xmapContent->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $xmapContent->setAttribute('modified-by', 'zentao');
        $xmapContent->setAttribute('timestamp', '1607912245582');
        $xmapContent->setAttribute('version', '2.0');
        $xmlDoc->appendChild($xmapContent);

        $sheet = $xmlDoc->createElement('sheet');
        $sheet->setAttribute('id', '3eqb5chq516v2bi0hqo52cvnjd');
        $sheet->setAttribute('modified-by', 'zentao');
        $sheet->setAttribute('theme', '5disc9luc2p2tdqg4fh9sln9ub');
        $sheet->setAttribute('timestamp', '1607912245582');
        $xmapContent->appendChild($sheet);

        $sheetTitle = $xmlDoc->createElement('title', 'canvas');
        $sheet->appendChild($sheetTitle);

        return $sheet;
    }

    /**
     * Add topic node.
     *
     * @param  object $xmlDoc
     * @param  object $sheet
     * @access public
     * @return void
     */
    public function createXmlTopic($xmlDoc, $sheet)
    {
        $topic = $xmlDoc->createElement('topic');
        $topic->setAttribute('id', '0tao7efgjol3d6p97okrr7e9e3');
        $topic->setAttribute('modified-by', 'zentao');
        $topic->setAttribute('structure-class', 'org.xmind.ui.map.unbalanced');
        $topic->setAttribute('timestamp', '1607912245582');
        $sheet->appendChild($topic);
        return $topic;
    }

    /**
     * Add title node.
     *
     * @param  object $xmlDoc
     * @param  object $topic
     * @param  string $value
     * @param  string $nodeID
     * @access public
     * @return void
     */
    public function createXmlTitle($xmlDoc, $topic, $value, $nodeID)
    {
        $title = $xmlDoc->createElement('title', $value);
        $topic->setAttribute('id', $nodeID);
        $topic->appendChild($title);
    }

    /**
     * Add topics node.
     *
     * @param  object $xmlDoc
     * @param  object $currentNode
     * @access public
     * @return void
     */
    public function createXmlTopics($xmlDoc, $currentNode)
    {
        $children = $xmlDoc->createElement('children');
        $topics   = $xmlDoc->createElement('topics');
        $topics->setAttribute('type', 'attached');

        $children->appendChild($topics);
        $currentNode->appendChild($children);

        return $currentNode;
    }

    /**
     * Generate content into xml file.
     *
     * @param  string $filePath
     * @param  object $xmlDoc
     * @access public
     * @return void
     */
    public function createMindContent($filePath, $xmlDoc)
    {
        global $app;

        $directory = dirname($filePath);
        if(!file_exists($filePath)) mkdir($directory, 0777, true);

        $demoFile    = $app->coreLibRoot . 'mind/content.xml';
        $contentFile = $directory . '/content.xml';

        copy($demoFile, $contentFile);

        $xmlContent = $xmlDoc->saveXML();
        file_put_contents($contentFile, $xmlContent);
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

        $styleFile   = $app->coreLibRoot . 'lowmind/style.xml';
        $commentFile = $app->coreLibRoot . 'lowmind/comment.xml';

        $directory = dirname($filePath);
        if(!file_exists($filePath)) mkdir($directory, 0777, true);

        $zip = new ZipArchive();
        $zip->open($filePath, ZipArchive::CREATE);

        $fileList = array($styleFile, $commentFile, $directory . '/content.xml');
        foreach($fileList as $file) $zip->addFile($file, basename($file));

        $zip->close();
    }

    /**
     * Create module node.
     *
     * @param  DOMDocument $xmlDoc
     * @param  array       $context
     * @param  DOMElement  $productNode
     * @param  array       $moduleNodes
     * @access public
     * @return void
     */
    public function createModuleNode($xmlDoc, $context, $productNode, &$moduleNodes)
    {
        $config     = $context['config'];
        $moduleList = $context['moduleList'];

        foreach($moduleList as $index => $name)
        {
            $suffix     = $config['module'] . ':' . $index;
            $moduleNode = $this->createNode($xmlDoc, $name, $suffix, array('id' => 'module' . $index));

            $productNode->appendChild($moduleNode);

            $moduleNodes[$index] = $moduleNode;
        }
    }

    /**
     * Create scene node.
     *
     * @param  DOMDocument $xmlDoc
     * @param  array       $context
     * @param  DOMElement  $productNode
     * @param  array       $moduleNodes
     * @param  array       $sceneNodes
     * @access public
     * @return void
     */
    public function createSceneNode($xmlDoc, $context, $productNode, &$moduleNodes, &$sceneNodes)
    {
        $config    = $context['config'];
        $topScenes = $context['topScenes'];

        foreach($topScenes as $index => $scene)
        {
            $suffix    = $config['scene'] . ':' . $scene->sceneID;
            $sceneNode = $this->createNode($xmlDoc, $scene->sceneName, $suffix, array('id' => 'topScene' . $index));

            $this->createNextChildScenesNode($scene, $sceneNode, $xmlDoc, $context, $moduleNodes, $sceneNodes);

            if(isset($moduleNodes[$scene->moduleID]))
            {
                $moduleNode = $moduleNodes[$scene->moduleID];

                $moduleNode->appendChild($sceneNode);
            }
            else
            {
                $productNode->appendChild($sceneNode);
            }

            $sceneNodes[$scene->sceneID] = $sceneNode;
        }
    }

    /**
     * Create next child scene node.
     *
     * @param  object      $parentScene
     * @param  object      $parentNode
     * @param  DOMDocument $xmlDoc
     * @param  array       $context
     * @param  array       $moduleNodes
     * @param  array       $sceneNodes
     * @access public
     * @return void
     */
    public function createNextChildScenesNode($parentScene, $parentNode, $xmlDoc, $context, &$moduleNodes, &$sceneNodes)
    {
        $sceneMaps = $context['sceneMaps'];
        $config    = $context['config'];

        foreach($sceneMaps as $key => $scene)
        {
            if($scene->parentID != $parentScene->sceneID) continue;

            $suffix    = $config['scene'] . ':' . $scene->sceneID;
            $sceneNode = $this->createNode($xmlDoc, $scene->sceneName, $suffix, array('id' => 'scene' . $key));

            $this->createNextChildScenesNode($scene, $sceneNode, $xmlDoc, $context, $moduleNodes, $sceneNodes);

            $parentNode->appendChild($sceneNode);
            $sceneNodes[$scene->sceneID] = $sceneNode;
        }
    }

    /**
     * Create test case node.
     *
     * @param  DOMDocument $xmlDoc
     * @param  array       $context
     * @param  object      $productNode
     * @param  array       $moduleNodes
     * @param  array       $sceneNodes
     * @access public
     * @return void
     */
    public function createTestcaseNode($xmlDoc, $context, $productNode, &$moduleNodes, &$sceneNodes)
    {
        $caseList = $context['caseList'];

        foreach($caseList as $case)
        {
            if(empty($case->testcaseID)) continue;

            $parentNode = $sceneNodes[$case->sceneID];
            if(!isset($parentNode)) $parentNode = $moduleNodes[$case->moduleID];
            if(!isset($parentNode)) $parentNode = $productNode;

            $this->createOneTestcaseNode($case, $xmlDoc, $context, $parentNode);
        }
    }

    /**
     * Create one test case node.
     *
     * @param  object      $case
     * @param  DOMDocument $xmlDoc
     * @param  array       $context
     * @param  object      $parentNode
     * @access public
     * @return void
     */
    public function createOneTestcaseNode($case, $xmlDoc, $context, $parentNode)
    {
        $stepList = $context['stepList'];
        $config   = $context['config'];
        $suffix   = $config['case'] . ':' . $case->testcaseID . ',' . $config['pri'] . ':' . $case->pri;
        $caseNode = $this->createNode($xmlDoc, $case->name, $suffix, array('id' => 'testcase' . $case->testcaseID));

        $parentNode->appendChild($caseNode);

        $topStepList = $this->findTopStepListByCase($case, $stepList);

        foreach($topStepList as $step)
        {
            $subStepList = $this->findSubStepListByStep($step, $stepList);

            $suffix   = count($subStepList) > 0 ? $config['group'] : '';
            $stepNode = $this->createNode($xmlDoc, $step->desc, $suffix, array('id' => 'step' . $step->stepID));
            $caseNode->appendChild($stepNode);

            if(count($subStepList))
            {
                foreach($subStepList as $sub)
                {
                    $subNode = $this->createNode($xmlDoc, $sub->desc, '', array('id' => 'subStep' . $sub->stepID));
                    $stepNode->appendChild($subNode);

                    if(!empty($sub->expect))
                    {
                        $expectNode = $this->createNode($xmlDoc, $sub->expect, '', array('id' => 'subExpect' . $sub->stepID));
                        $subNode->appendChild($expectNode);
                    }
                }
            }

            if(count($subStepList) == 0 && !empty($step->expect))
            {
                $expectNode = $this->createNode($xmlDoc, $step->expect, '', array('id' => 'expect' . $step->stepID));
                $stepNode->appendChild($expectNode);
            }
        }
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
     * Create xmind node.
     *
     * @param  DOMDocument $xmlDoc
     * @param  string      $text
     * @param  string      $suffix
     * @param  array       $attrs
     * @access public
     * @return object
     */
    public function createNode($xmlDoc, $text, $suffix = '', $attrs = array())
    {
        $node = $xmlDoc->createElement('topic');

        $value = $this->toText($text, $suffix);
        $title = $xmlDoc->createElement('title', $value);
        $node->appendChild($title);

        foreach($attrs as $key => $value)
        {
            $attr      = $xmlDoc->createAttribute($key);
            $attrValue = $xmlDoc->createTextNode($value);

            $attr->appendChild($attrValue);
            $node->appendChild($attr);
        }

        return $node;
    }

    /**
     * Insert topics node.
     *
     * @param  DOMDocument $xmlDoc
     * @access public
     * @return object
     */
    public function insertTopicsNode($dom)
    {
        $topics = $dom->getElementsByTagName('topic');

        for($i = 0; $i < $topics->length; $i++)
        {
            $topic = $topics->item($i);

            $childTopics = $topic->getElementsByTagName('topic');
            if($childTopics->length > 0)
            {
                $newTopics = $dom->createElement('topics');
                $newTopics->setAttribute('type', 'attached');

                while($childTopics->length > 0)
                {
                    $childTopic = $childTopics->item(0);
                    $newTopics->appendChild($childTopic);
                }

                $topic->appendChild($newTopics);
            }
        }
    }

    /**
     * Insert children node.
     *
     * @param  DOMDocument $xmlDoc
     * @access public
     * @return object
     */
    public function insertChildrenNode($xmlDoc)
    {
        $topics = $xmlDoc->getElementsByTagName('topics');

        foreach($topics as $topic)
        {
            $childrenNode = $xmlDoc->createElement('children');

            $topicClone = $topic->cloneNode(true);
            $childrenNode->appendChild($topicClone);

            $topic->parentNode->insertBefore($childrenNode, $topic);

            $topic->parentNode->removeChild($topic);
        }
    }

    /**
     * Insert extensions node.
     *
     * @param  DOMDocument $xmlDoc
     * @access public
     * @return object
     */
    public function insertExtensionsNode($xmlDoc)
    {
        $topics = $xmlDoc->getElementsByTagName('topic');

        if($topics->length > 0)
        {
            $rightNumber = $topics->length;

            $right      = $xmlDoc->createElement('right-number', $rightNumber);
            $content    = $xmlDoc->createElement('content');
            $extension  = $xmlDoc->createElement('extension');
            $extensions = $xmlDoc->createElement('extensions');

            $extension->setAttribute('provider', 'org.xmind.ui.map.unbalanced');

            $content->appendChild($right);
            $extension->appendChild($content);
            $extensions->appendChild($extension);

            foreach($topics as $index => $topics)
            {
                if($index == 0)
                {
                    $topics->insertBefore($extensions);
                    break;
                }
            }
        }
    }

    /**
     * Create tree node.
     *
     * @param  DOMDocument $xmlDoc
     * @param  array       $context
     * @param  DOMDocument $body
     * @access public
     * @return void
     */
    public function createTreeNode($xmlDoc, $context, $body)
    {
        foreach($context as $tree)
        {
            $suffix   = $tree->type . ':' . $tree->id;
            $treeNode = $this->createNode($xmlDoc, $tree->name, $suffix, array('id' => $tree->type . $tree->id));

            if(isset($tree->children)) $this->createTreeNode($xmlDoc, $tree->children, $treeNode);
            $body->appendChild($treeNode);
        }
    }
}
