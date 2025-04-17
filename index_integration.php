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
            }
            .login-container {
                text-align: center;
                padding: 30px;
                border-radius: 5px;
            }
            .login-button {
                display: inline-block;
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
        </style>
    </head>
    <body>
        <div class="login-container">
            <a href="dlapi.php" class="login-button">登录</a>
        </div>
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
