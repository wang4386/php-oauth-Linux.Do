<?php
/**
 * OAuth API处理文件
 * 处理OAuth回调和用户认证逻辑
 */

// 启动会话
session_start();

// 定义常量，表示已加载OAuth
define('OAUTH_LOADED', true);

// 引入配置文件
require_once 'dlconfig.php';

// 错误处理函数
function handleError($message) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>认证错误</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #fff;
                color: #333;
                margin: 0;
                padding: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                text-align: center;
            }
            .error-container {
                max-width: 500px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background-color: #f9f9f9;
            }
            .error-message {
                color: #d9534f;
                margin-bottom: 20px;
            }
            .back-button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #337ab7;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                border: none;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h2>认证错误</h2>
            <p class="error-message">' . htmlspecialchars($message) . '</p>
            <a href="/" class="back-button">返回首页</a>
        </div>
        <script>
            alert("' . addslashes($message) . '");
        </script>
    </body>
    </html>';
    exit;
}

// 发起OAuth授权请求
function initiateOAuth() {
    global $oauth_config;
    
    // 生成随机state参数，防止CSRF攻击
    $state = bin2hex(random_bytes(16));
    $_SESSION[$oauth_config['state_key']] = $state;
    
    // 构建授权URL
    $auth_url = $oauth_config['authorize_url'] . '?' . http_build_query([
        'client_id' => $oauth_config['client_id'],
        'redirect_uri' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $oauth_config['redirect_uri'],
        'response_type' => 'code',
        'state' => $state,
        'scope' => 'openid profile'
    ]);
    
    // 重定向到授权页面
    header('Location: ' . $auth_url);
    exit;
}

// 处理OAuth回调
function handleCallback() {
    global $oauth_config;
    
    // 检查是否有错误参数
    if (isset($_GET['error'])) {
        handleError('授权失败: ' . $_GET['error']);
    }
    
    // 检查必要参数
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        handleError('无效的回调请求');
    }
    
    // 验证state参数，防止CSRF攻击
    if (!isset($_SESSION[$oauth_config['state_key']]) || $_GET['state'] !== $_SESSION[$oauth_config['state_key']]) {
        handleError('安全验证失败');
    }
    
    // 清除state会话变量
    unset($_SESSION[$oauth_config['state_key']]);
    
    // 获取授权码
    $code = $_GET['code'];
    
    // 构建获取访问令牌的请求
    $token_url = $oauth_config['token_url'];
    $token_data = [
        'grant_type' => 'authorization_code',
        'client_id' => $oauth_config['client_id'],
        'client_secret' => $oauth_config['client_secret'],
        'redirect_uri' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $oauth_config['redirect_uri'],
        'code' => $code
    ];
    
    // 发送请求获取访问令牌
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 检查响应
    if ($http_code !== 200) {
        handleError('获取访问令牌失败');
    }
    
    // 解析响应
    $token_response = json_decode($response, true);
    if (!isset($token_response['access_token'])) {
        handleError('无效的访问令牌响应');
    }
    
    // 获取访问令牌
    $access_token = $token_response['access_token'];
    
    // 获取用户信息
    $userinfo_url = $oauth_config['userinfo_url'];
    $ch = curl_init($userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 检查响应
    if ($http_code !== 200) {
        handleError('获取用户信息失败');
    }
    
    // 解析用户信息
    $user_info = json_decode($response, true);
    if (!isset($user_info['id']) || !isset($user_info['trust_level'])) {
        handleError('无效的用户信息响应');
    }
    
    // 检查信任等级
    if ($user_info['trust_level'] < $oauth_config['min_trust_level']) {
        handleError('您的信任等级不足，无法访问此内容');
    }
    
    // 设置用户ID cookie
    setcookie(
        $oauth_config['cookie_name'],
        $user_info['id'],
        time() + $oauth_config['cookie_expire'],
        $oauth_config['cookie_path'],
        '',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        true
    );
    
    // 重定向到首页
    header('Location: /');
    exit;
}

// 主逻辑
if (isset($_GET['code'])) {
    // 处理OAuth回调
    handleCallback();
} else {
    // 发起OAuth授权请求
    initiateOAuth();
}
?>
