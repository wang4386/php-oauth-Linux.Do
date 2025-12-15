<?php
/**
 * OAuth 回调处理程序 (dlapi.php)
 *
 * 此文件处理从 Linux.do 返回的请求，
 * 验证用户，更新数据库，并设置登录 Cookie。
 */

// 引入 Auth Gate 来初始化环境。
// auth_gate 会加载配置、启动会话并提供所有必要的函数。
require_once 'auth_gate.php';

// 如果执行到这里，说明 auth_gate 已确认我们处于已安装状态。

/**
 * 处理 OAuth 授权流程的启动
 */
function initiate_auth_flow() {
    global $auth_config;

    // (可选) 保存用户最初想要访问的页面 URL
    if (isset($_SERVER['HTTP_REFERER'])) {
        // 解析来源 URL，确保它不是 dlapi.php 本身
        $referer_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        if (basename($referer_path) !== 'dlapi.php') {
            $_SESSION['auth_return_to'] = $_SERVER['HTTP_REFERER'];
        }
    }
    
    // 生成随机 state 参数，防止 CSRF 攻击
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    // 构建授权 URL
    $params = [
        'client_id' => $auth_config['oauth']['client_id'],
        'redirect_uri' => $auth_config['oauth']['redirect_uri'],
        'response_type' => 'code',
        'state' => $state,
        'scope' => 'openid profile email' // 'profile' and 'email' may not be standard, but good to have
    ];
    
    $auth_url = 'https://connect.linux.do/oauth2/authorize?' . http_build_query($params);
    
    // 重定向到授权页面
    header('Location: ' . $auth_url);
    exit;
}

/**
 * 处理从 OAuth 提供商返回的回调
 */
function handle_auth_callback() {
    global $auth_config;
    
    // 安全检查
    if (isset($_GET['error'])) show_auth_error_page('授权失败: ' . htmlspecialchars($_GET['error']));
    if (!isset($_GET['code']) || !isset($_GET['state'])) show_auth_error_page('无效的回调请求');
    if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) show_auth_error_page('安全验证失败 (CSRF state mismatch)');
    unset($_SESSION['oauth_state']);
    
    // 使用授权码获取访问令牌
    $token_response = auth_http_post('https://connect.linux.do/oauth2/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $auth_config['oauth']['client_id'],
        'client_secret' => $auth_config['oauth']['client_secret'],
        'redirect_uri' => $auth_config['oauth']['redirect_uri'],
        'code' => $_GET['code']
    ]);
    if (!isset($token_response['access_token'])) show_auth_error_page('获取访问令牌失败: ' . ($token_response['error_description'] ?? '未知错误'));

    // 使用访问令牌获取用户信息
    $user_info = auth_http_get('https://connect.linux.do/api/user', $token_response['access_token']);
    if (!isset($user_info['id']) || !isset($user_info['trust_level'])) show_auth_error_page('获取用户信息失败或响应格式无效');

    // 检查信任等级
    if ($user_info['trust_level'] < $auth_config['min_trust_level']) {
        show_auth_error_page('您的信任等级 (' . (int)$user_info['trust_level'] . ') 不足，需要达到 ' . (int)$auth_config['min_trust_level'] . ' 级才能访问。');
    }

    // --- 数据库操作 ---
    try {
        $db = get_auth_db();
        $stmt = $db->prepare("SELECT * FROM auth_users WHERE l_user_id = ?");
        $stmt->execute([$user_info['id']]);
        $user = $stmt->fetch();
        
        $now = date('Y-m-d H:i:s');
        $avatar_url_template = $user_info['avatar_template'] ?? 'https://www.gravatar.com/avatar/{hash}?s={size}&d=identicon';
        
        if ($user) { // 更新现有用户
            $stmt = $db->prepare("UPDATE auth_users SET username = ?, trust_level = ?, last_login_at = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$user_info['username'], $user_info['trust_level'], $now, $avatar_url_template, $user['id']]);
        } else { // 插入新用户
            $stmt = $db->prepare("INSERT INTO auth_users (l_user_id, username, trust_level, first_login_at, last_login_at, avatar_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_info['id'], $user_info['username'], $user_info['trust_level'], $now, $now, $avatar_url_template]);
        }
    } catch (Exception $e) {
        show_auth_error_page("数据库操作失败: " . $e->getMessage());
    }

    // --- 设置登录 Cookie ---
    setcookie(
        $auth_config['cookie_name'],
        $user_info['id'],
        [
            'expires' => time() + $auth_config['cookie_expire'],
            'path' => $auth_config['cookie_path'],
            'domain' => '', // 当前域名
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
    
    // --- 重定向到原始页面或首页 ---
    $return_to = $_SESSION['auth_return_to'] ?? '/';
    unset($_SESSION['auth_return_to']);
    header('Location: ' . $return_to);
    exit;
}


// --- 主路由 ---
// 如果URL中有 'code' 参数, 说明是回调。否则, 是发起授权请求。
if (isset($_GET['code'])) {
    handle_auth_callback();
} else {
    initiate_auth_flow();
}
