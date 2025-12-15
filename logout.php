<?php
/**
 * 退出登录脚本
 */

// 引入 gate 以加载配置和函数
require_once 'auth_gate.php';

// 只有登录的用户才能登出
if ($current_user) {
    // 1. 清除登录 Cookie
    setcookie(
        $auth_config['cookie_name'], 
        '', 
        [
            'expires' => time() - 3600, // 设置为1小时前
            'path' => $auth_config['cookie_path'],
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );

    // 2. 清理并销毁会话
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// 3. 重定向回之前的页面或首页
$return_to = $_SERVER['HTTP_REFERER'] ?? '/';
header('Location: ' . $return_to);
exit;
?>
