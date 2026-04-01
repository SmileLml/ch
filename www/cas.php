<?php
/* Load the framework. */
include '../framework/router.class.php';
include '../framework/control.class.php';
include '../framework/model.class.php';
include '../framework/helper.class.php';

/* Instance the app. */
$app = router::createApp('pms', dirname(dirname(__FILE__)), 'router');
/* installed or not. */
if(!isset($config->installed) or !$config->installed) die(header('location: install.php'));
/* Run the app. */
$common = $app->loadCommon();

require_once '../lib/CAS/CAS.php';

$referer = isset($_GET['referer']) ? $_GET['referer'] : 'index.php';

$systemUrl = common::getSysURL();
if(strpos('|/zentao/|/biz/|/max/|', "|{$app->config->webRoot}|") !== false) $systemUrl .= rtrim($app->config->webRoot,'/');
$systemUrl .= '/cas.php?referer=' . urlencode($referer);

phpCAS::client(CAS_VERSION_2_0, $config->cas->server, $config->cas->port, $config->cas->path, $systemUrl);
phpCAS::setFixedServiceURL($systemUrl);
phpCAS::setNoCasServerValidation();
if (!phpCAS::isAuthenticated()) phpCAS::forceAuthentication();
$loginName = phpCAS::getUser();
if(!isset($loginName)) errorMessage('获取用户失败!');

if(isset($loginName))
{
    $user = $dao->select('*')->from(TABLE_USER)->where('account')->eq($loginName)->fetch();
    if(empty($user)) errorMessage('通过系统账号：' . $loginName . '，查询用户为空!');

    /* Verify user identity. */
    $hasTmp = $common->loadModel('user')->checkTmp();
    if($hasTmp === false)
    {
        echo "<html><head><meta charset='utf-8'></head>";
        echo "<body><table align='center' style='width:700px; margin-top:100px; border:1px solid gray; font-size:14px;'><tr><td style='padding:8px'>";
        echo "<div style='margin-bottom:8px;'>不能创建临时目录，请确认目录<strong style='color:#ed980f'>{$app->tmpRoot}</strong>是否存在并有操作权限。</div>";
        echo "<div>Can't create tmp directory, make sure the directory <strong style='color:#ed980f'>{$app->tmpRoot}</strong> exists and has permission to operate.</div>";
        die("</td></tr></table></body></html>");
    }

    /* Successful login, jump to the home page. */
    $password = $user->password;
    $account  = $user->account;
    unset($_SESSION['rand']);
    $user = $common->user->identify($account, $password);
    $_POST['account'] = $account;
    $app->setVision();
    $common->user->login($user);

    // 安全检查，确保referer是站内链接
    if(!preg_match('/^(https?:\/\/)?' . preg_quote($_SERVER['HTTP_HOST'], '/') . '/', $referer) && strpos($referer, '://') !== false) {
        $referer = 'index.php'; // 如果是外部链接，重置为首页
    }
    
    // 如果referer不是完整URL，添加域名部分
    if(strpos($referer, '://') === false) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'];
        if(strpos($referer, '/') !== 0) $referer = '/' . $referer;
        $referer = $domain . $referer;
    }

    /* Synchronize users to GitLab. */
    $gitlabConfig = array();
    foreach($app->config->system->assetlibcustom as $ssoauth) $gitlabConfig[$ssoauth->key] = $ssoauth->value;
    if(isset($gitlabConfig['accessToken']))
    {
        $gitlabUrl   = htmlspecialchars_decode($gitlabConfig['insideLink']);
        $accessToken = htmlspecialchars_decode($gitlabConfig['accessToken']);

        $params = array(
            'username'              => $account,
            'name'                  => $user->realname,
            'email'                 => !empty($user->email) ? $user->email : $account . '@gitlab.com',
            'reset_password'        => 'false',
            'force_random_password' => 'false',
            'password'              => $gitlabConfig['defaultPassword']
        );

        $headers   = array('Private-Token: ' . $accessToken);
        $gitlabUrl = rawurldecode($gitlabUrl);
        $gitlabUrl = $gitlabUrl . '/api/v4/users';
        $common->loadModel('requestlog');
        $response = $common->requestlog->http($gitlabUrl, $params, 'POST', 'data', $headers);
        $result   = json_decode($response, true);
        $status   = isset($result['id']) ? 'success' : 'fail';
        $common->requestlog->saveRequestLog($gitlabUrl, 'gitlab', 'createUser', 'POST', $params, json_encode($result), $status);
    }

    // 使用referer进行重定向
    echo '<script language="JavaScript">window.location.href="' . $referer . '"</script>';
}
else
{
    errorMessage('查询用户为空!');
}

function errorMessage($message)
{
    //$redirectUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
    $redirectUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
echo <<<EOF
<!DOCTYPE html>
<html lang="zh-cn">
  <head>
    <meta charset="UTF-8">
    <title>登录提示</title>
    <style>
      #countdown {color: red;}
    </style>
  </head>
  <body>
    <h2>Error：{$message}</h2>
    <h2>
      <spen id="countdown">5</spen>秒之后自动跳转<a href='{$redirectUrl}'>登录页面</a> ...
    </h2>
  </body>
  <script>
      var countdown = document.getElementById("countdown");
      var second    = countdown.innerHTML;
      function showTime()
      {
          second --;
          if(second >= 0)
          {
              countdown.innerHTML = second;
          }
          else
          {
              if(second == -1) location.href = '{$redirectUrl}';
          }
      }
      setInterval(showTime, 1000);
  </script>
</html>
EOF;
die();
}