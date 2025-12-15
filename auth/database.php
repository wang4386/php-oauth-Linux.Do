<?php
/**
 * 数据库连接模块
 */

// 使用一个静态变量来缓存数据库连接
global $auth_pdo_instance;
$auth_pdo_instance = null;

/**
 * 获取数据库连接实例 (PDO)
 *
 * @return PDO
 * @throws PDOException 如果连接失败
 */
function get_auth_db() {
    global $auth_pdo_instance, $auth_config;

    // 如果已有连接实例，直接返回
    if ($auth_pdo_instance !== null) {
        return $auth_pdo_instance;
    }

    // 检查配置是否加载
    if (!isset($auth_config) || !isset($auth_config['db_type'])) {
        // 在调用get_auth_db()之前，配置应该总是存在的
        // 如果发生这种情况，说明调用顺序有误
        throw new Exception("数据库配置未加载，无法建立连接。");
    }

    $db_type = $auth_config['db_type'];
    $db_config = $auth_config['db'];

    $dsn = '';
    $user = null;
    $pass = null;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if ($db_type === 'mysql') {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4";
        $user = $db_config['user'];
        $pass = $db_config['pass'];
    } elseif ($db_type === 'sqlite') {
        $dsn = "sqlite:" . $db_config['path'];
    } else {
        throw new Exception("不支持的数据库类型: " . htmlspecialchars($db_type));
    }
    
    // 创建PDO实例
    $auth_pdo_instance = new PDO($dsn, $user, $pass, $options);
    
    // 对SQLite连接进行一些初始化设置
    if ($db_type === 'sqlite') {
        $auth_pdo_instance->exec('PRAGMA journal_mode = WAL;');
        $auth_pdo_instance->exec('PRAGMA busy_timeout = 5000;');
    }

    return $auth_pdo_instance;
}
