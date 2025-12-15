<?php
/**
 * LinuxDo OAuth Gate
 *
 * 在您想保护的任何 PHP 页面顶部引入此文件:
 * require_once 'auth_gate.php';
 *
 * @version 2.0
 */

// --- 1. 初始化常量和依赖 ---
define('AUTH_ROOT', __DIR__);
define('AUTH_CONFIG_PATH', AUTH_ROOT . '/auth_config.php');

// 包含核心函数、安装程序逻辑和UI渲染
require_once AUTH_ROOT . '/auth/functions.php';

// --- 2. 检查安装状态 ---
// 如果配置文件不存在，运行安装程序。
// run_installer() 函数会处理页面渲染并终止脚本。
if (!file_exists(AUTH_CONFIG_PATH)) {
    run_installer();
}

// --- 3. 加载配置并验证用户 ---
// 如果已安装，加载配置并启动会话
require_once AUTH_CONFIG_PATH;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查当前用户登录状态
$current_user = get_current_auth_user();

if (!$current_user) {
    // 如果用户未登录，显示登录页面并终止脚本
    
    // 检查当前请求的脚本是否是API端点或管理后台
    $script_name = basename($_SERVER['PHP_SELF']);
    if ($script_name !== 'dlapi.php' && $script_name !== 'dladmin.php') {
        show_auth_login_page();
    }
}

// --- 4. 验证通过 ---
// 如果脚本执行到这里，意味着用户已登录。

/**
 * 渲染悬浮用户控件（如果已开启且用户已登录）
 * 用户需要在他们页面的 HTML 中（建议在 </body> 前）调用此函数
 */
function render_auth_widget() {
    global $current_user, $auth_config;
    if ($current_user && !empty($auth_config['enable_widget'])) {
        require_once AUTH_ROOT . '/auth/widget.php';
    }
}

?>
