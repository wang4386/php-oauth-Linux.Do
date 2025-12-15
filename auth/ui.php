<?php
/**
 * UI渲染模块
 *
 * 负责显示登录页面、安装页面等
 */

/**
 * 显示登录页面并终止脚本
 */
function show_auth_login_page() {
    // 保存用户最初尝试访问的页面，以便登录后返回
    $_SESSION['auth_return_to'] = $_SERVER['REQUEST_URI'];

    // 确保 content-type 为 HTML
    header('Content-Type: text/html; charset=utf-8');

    // OAuth 发起页面的 URL
    $login_url = 'dlapi.php';

    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>需要登录</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; background-color: #f8f9fa; margin: 0; }
        .login-box { background: #fff; padding: 40px 50px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .login-button { display: inline-block; padding: 12px 24px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: bold; transition: background-color 0.2s; }
        .login-button:hover { background-color: #286090; }
        h2 { margin-top: 0; margin-bottom: 15px; color: #333; }
        p { color: #6c757d; margin-top: 0; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>需要身份认证</h2>
        <p>请使用您的 Linux.Do 账户登录以继续</p>
        <a href="{$login_url}" class="login-button">通过 Linux.Do 登录</a>
    </div>
</body>
</html>
HTML;
    exit;
}

/**
 * 显示错误页面并终止脚本
 */
function show_auth_error_page($message) {
    header('Content-Type: text/html; charset=utf-8');
    $message_html = htmlspecialchars($message);
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发生错误</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; background-color: #f8f9fa; margin: 0; }
        .error-box { max-width: 600px; background: #fff; padding: 40px 50px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; color: #dc3545; }
        p { color: #333; font-size: 18px; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="error-box">
        <h2>发生错误</h2>
        <p>{$message_html}</p>
        <a href="#" onclick="window.location.reload();" class="btn">重试</a>
    </div>
</body>
</html>
HTML;
    exit;
}

// installer.php 将会包含渲染安装界面的函数
// function render_installer_step_1(...) { ... }
// function render_installer_step_2(...) { ... }
// ... etc
