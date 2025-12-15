<?php
/**
 * 核心函数库
 */

// --- 包含模块 ---
require_once AUTH_ROOT . '/auth/database.php';
require_once AUTH_ROOT . '/auth/ui.php';
require_once AUTH_ROOT . '/auth/installer.php';


/**
 * 获取当前已登录用户的信息
 *
 * @return array|null 如果用户已登录，返回用户信息数组；否则返回 null
 */
function get_current_auth_user() {
    // 检查 config 是否已加载，cookie 名称是否存在
    if (!isset($GLOBALS['auth_config']) || !isset($GLOBALS['auth_config']['cookie_name'])) {
        return null;
    }
    
    $config = $GLOBALS['auth_config'];
    
    if (!isset($_COOKIE[$config['cookie_name']])) {
        return null;
    }
    
    $l_user_id = $_COOKIE[$config['cookie_name']];
    if (empty($l_user_id)) {
        return null;
    }
    
    try {
        $db = get_auth_db();
        $stmt = $db->prepare("SELECT * FROM auth_users WHERE l_user_id = ?");
        $stmt->execute([$l_user_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        // 如果数据库出错，也视为未登录
        // 可在此处添加日志记录
        return null;
    }
    
    if (!$user) {
        // 如果cookie有效但数据库中没有用户，清除无效cookie
        setcookie($config['cookie_name'], '', time() - 3600, $config['cookie_path']);
        return null;
    }
    
    return $user;
}

/**
 * HTTP GET 请求的辅助函数
 * @param string $url
 * @param string $token
 * @return array|null
 */
function auth_http_get($url, $token) {
    $options = [
        'http' => [
            'header'  => "Authorization: Bearer " . $token . "\r\n" .
                         "User-Agent: PHP-OAuth-Gate/2.0\r\n",
            'method'  => 'GET',
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) return null;
    
    return json_decode($result, true);
}

/**
 * HTTP POST 请求的辅助函数
 * @param string $url
 * @param array $data
 * @return array|null
 */
function auth_http_post($url, $data) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                         "User-Agent: PHP-OAuth-Gate/2.0\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) return null;

    return json_decode($result, true);
}

