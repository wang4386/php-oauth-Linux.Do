<?php
/**
 * index.php集成代码
 * 在原有PHP程序首页增加OAuth登录认证
 * 
 * 使用说明：将此代码放在原有index.php文件的最顶部
 */

// 启动会话
session_start();

// 定义常量，表示已加载OAuth
define('OAUTH_LOADED', true);

// 引入配置文件
require_once 'dlconfig.php';

// 检查用户是否已登录
function checkOAuthLogin() {
    global $oauth_config;
    
    // 检查cookie是否存在
    if (!isset($_COOKIE[$oauth_config['cookie_name']])) {
        return false;
    }
    
    return true;
}

// 显示登录页面
function showLoginPage() {
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录认证</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
        }
        .login-container {
            text-align: center; /* 确保内容居中 */
            padding: 30px;
            border-radius: 5px;
        }
        .login-container img {
            display: block;
            margin: 0 auto; /* 图片水平居中 */
            width: 150px; /* 设置图片宽度 */
            height: auto; /* 保持图片纵横比 */
            margin-bottom: 20px; /* 使按钮和图片间有间距 */
        }
        .login-button {
            display: inline-block;
            margin-top: 0; /* 防止不必要间隙 */
            padding: 12px 24px;
            background-color: #337ab7;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-button:hover {
            background-color: #286090;
        }
        .github-link {
            position: absolute;
            bottom: 20px;
            right: 20px;
            text-decoration: none;
            font-size: 16px;
            color: #333;
            display: flex;
            align-items: center;
        }
        .github-link img {
            width: 24px; /* 设置 GitHub 图标大小 */
            height: 24px;
            margin-right: 8px;
        }
        .github-link:hover {
            color: #337ab7;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo 图片 -->
        <img src="https://assets.qninq.cn/qning/tYM0Zofg.webp" alt="Logo">
        
        <!-- 登录按钮 -->
        <a href="dlapi.php" class="login-button">Linux.Do登录</a>
    </div>
    
    <!-- GitHub 超链接 -->
    <a href="https://github.com/wang4386/php-oauth-Linux.Do" class="github-link" target="_blank">
        <img src="https://assets.qninq.cn/qning/PmajGbs1.webp" alt="GitHub Logo">
        GitHub
    </a>
</body>
</html>';
    exit;
}

// 主逻辑：检查登录状态，未登录则显示登录页面
if (!checkOAuthLogin()) {
    showLoginPage();
}

// 如果已登录，继续执行原有index.php的代码
// 原有index.php的代码从这里开始
?>

<!-- 
以下是原有index.php的代码
请将此注释及以下内容替换为原有的index.php代码
-->
