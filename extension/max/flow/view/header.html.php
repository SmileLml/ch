<?php
$rawModule          = $app->rawModule;
$rawMethod          = $app->rawMethod;
$hookRoot           = $app->getExtensionRoot() . 'flowhooks' . DS;
$moduleViewHookRoot = $hookRoot . $rawModule . DS . 'view' . DS;

$topHookFile = $moduleViewHookRoot . $rawMethod . '.top.php';
$commonTopHookFile = $moduleViewHookRoot . 'commonTop.php';

$headerHookFile = $moduleViewHookRoot . $rawMethod . '.header.php';
$commonHeaderHookFile = $moduleViewHookRoot . 'commonHeader.php';

$bottomHookFile = $moduleViewHookRoot . $rawMethod . '.bottom.php';
$commonBottomHookFile = $moduleViewHookRoot . 'commonBottom.php';

$footerHookFile = $moduleViewHookRoot . $rawMethod . '.footer.php';
$commonFooterHookFile = $moduleViewHookRoot . 'commonFooter.php';

if(file_exists($topHookFile))
{
    include $topHookFile;
}
if(file_exists($commonTopHookFile))
{
    include $commonTopHookFile;
}


if(isset($flowAction)) $action = $flowAction;   // The view method has the $flowAction property instead of the $action property.

include $app->getModuleRoot() . 'common/view/header.html.php';

if(!empty($flow->css))   css::internal($flow->css);
if(!empty($action->css)) css::internal($action->css);
js::set('buildin', $flow->buildin);

if(file_exists($headerHookFile))
{
    include $headerHookFile;
}
if(file_exists($commonHeaderHookFile))
{
    include $commonHeaderHookFile;
}
