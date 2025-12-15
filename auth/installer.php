<?php
/**
 * 安装程序模块
 */

function get_auth_redirect_uri() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\\\') . '/dlapi.php';
}

/**
 * 主安装函数，由 auth_gate.php 调用
 */
function run_installer() {
    $auth_config = [];
    $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($step) {
            case 2: // 从数据库类型选择进入
                handle_installer_step2();
                break;
            case 3: // 从配置表单提交进入
                handle_installer_step3();
                break;
        }
    } else {
        handle_installer_step1();
    }
    exit;
}

/**
 * 步骤 1: 环境检查和数据库类型选择
 */
function handle_installer_step1() {
    // 环境检查
    $checks = [
        'php_version' => version_compare(phpversion(), '7.4.0', '>='),
        'pdo' => extension_loaded('pdo'),
        'session' => extension_loaded('session'),
        'curl' => extension_loaded('curl'),
    ];
    $all_ok = !in_array(false, $checks, true);

    render_installer_page('步骤 1: 环境检查', function() use ($checks, $all_ok) {
        echo '<h1>欢迎！</h1><p>这是一个嵌入式 LinuxDo OAuth 认证模块。在开始之前，请确认您的服务器环境满足要求。</p>';
        echo '<h2>环境检查</h2><ul class="check-list">';
        echo '<li class="' . ($checks['php_version'] ? '' : 'fail') . '">PHP 版本 >= 7.4.0</li>';
        echo '<li class="' . ($checks['pdo'] ? '' : 'fail') . '">PDO 扩展</li>';
        echo '<li class="' . ($checks['session'] ? '' : 'fail') . '">Session 扩展</li>';
        echo '<li class="' . ($checks['curl'] ? '' : 'fail') . '">cURL 扩展</li>';
        echo '</ul>';
        
        if ($all_ok) {
            echo '<form action="" method="post" class="mt-40">';
            echo '<input type="hidden" name="step" value="2">';
            echo '<h2>选择数据库类型</h2>';
            echo '<p>您可以选择使用轻量的 SQLite (无需配置，推荐) 或 MySQL。</p>';
            echo '<div class="form-group">';
            echo '<select name="db_type" class="form-control"><option value="sqlite">SQLite</option><option value="mysql">MySQL</option></select>';
            echo '</div>';
            echo '<button type="submit" class="btn btn-primary">下一步</button>';
            echo '</form>';
        } else {
            echo '<p class="error-box">您的环境不满足所有要求。请修复后重试。</p>';
        }
    });
}

/**
 * 步骤 2: 显示配置表单
 */
function handle_installer_step2() {
    $db_type = $_POST['db_type'] ?? 'sqlite';

    render_installer_page('步骤 2: 填写配置', function() use ($db_type) {
        $redirect_uri = get_auth_redirect_uri();
        
        echo '<h1>填写配置</h1>';
        echo '<form action="" method="post">';
        echo '<input type="hidden" name="step" value="3">';
        echo '<input type="hidden" name="db_type" value="' . htmlspecialchars($db_type) . '">';

        echo '<h2>数据库设置 (' . htmlspecialchars(strtoupper($db_type)) . ')</h2>';

        if ($db_type === 'mysql') {
            echo '<div class="form-group"><label>主机</label><input type="text" name="db_host" class="form-control" value="127.0.0.1"></div>';
            echo '<div class="form-group"><label>库名</label><input type="text" name="db_name" class="form-control"></div>';
            echo '<div class="form-group"><label>用户</label><input type="text" name="db_user" class="form-control"></div>';
            echo '<div class="form-group"><label>密码</label><input type="password" name="db_pass" class="form-control"></div>';
        } else { // sqlite
            $sqlite_path = AUTH_ROOT . '/auth.sqlite';
            echo '<p>SQLite 数据库文件将被创建于: <code>' . htmlspecialchars($sqlite_path) . '</code></p>';
            echo '<p>请确保该目录 (<code>'.htmlspecialchars(AUTH_ROOT).'</code>) 对于PHP进程是可写的。</p>';
        }

        echo '<h2 class="mt-40">OAuth 设置</h2>';
        echo '<div class="form-group"><label>Client ID</label><input type="text" name="client_id" class="form-control" required></div>';
        echo '<div class="form-group"><label>Client Secret</label><input type="text" name="client_secret" class="form-control" required></div>';
        echo '<div class="form-group"><label>最低信任等级 (Min Trust Level)</label><input type="number" name="min_trust_level" class="form-control" value="2" required></div>';
        echo '<p>您的回调 URL 是: <code>' . htmlspecialchars($redirect_uri) . '</code></p>';

        echo '<h2 class="mt-40">后台及控件设置</h2>';
        echo '<div class="form-group"><label>管理员用户名</label><input type="text" name="admin_user" class="form-control" value="admin" required></div>';
        echo '<div class="form-group"><label>管理员密码</label><input type="password" name="admin_pass" class="form-control" required></div>';
        echo '<div class="form-group"><label>启用悬浮用户控件</label><select name="enable_widget" class="form-control"><option value="1" selected>是</option><option value="0">否</option></select></div>';

        echo '<button type="submit" class="btn btn-primary mt-20">完成安装</button>';
        echo '</form>';
    });
}

/**
 * 步骤 3: 处理提交，生成配置
 */
function handle_installer_step3() {
    $config_data = $_POST;
    $errors = [];

    // 1. 生成配置数组
    $redirect_uri = get_auth_redirect_uri();
    $generated_config = [
        'db_type' => $config_data['db_type'],
        'db' => [],
        'oauth' => [
            'client_id' => $config_data['client_id'],
            'client_secret' => $config_data['client_secret'],
            'redirect_uri' => $redirect_uri,
        ],
        'min_trust_level' => (int)$config_data['min_trust_level'],
        'cookie_name' => 'ld_auth_user',
        'cookie_expire' => 86400 * 7, // 7 days
        'cookie_path' => '/',
        'enable_widget' => (bool)$config_data['enable_widget'],
        'admin_user' => $config_data['admin_user'],
        'admin_pass_hash' => password_hash($config_data['admin_pass'], PASSWORD_DEFAULT),
    ];

    // 2. 测试数据库 & 创建表
    try {
        if ($config_data['db_type'] === 'mysql') {
            $generated_config['db'] = [
                'host' => $config_data['db_host'],
                'name' => $config_data['db_name'],
                'user' => $config_data['db_user'],
                'pass' => $config_data['db_pass'],
            ];
            $dsn = "mysql:host={$config_data['db_host']};dbname={$config_data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config_data['db_user'], $config_data['db_pass']);
        } else { // sqlite
            $sqlite_path = AUTH_ROOT . '/auth.sqlite';
            $generated_config['db'] = ['path' => $sqlite_path];
            if (!is_writable(AUTH_ROOT)) {
                throw new Exception("目录 " . AUTH_ROOT . " 不可写，无法创建SQLite数据库。");
            }
            $dsn = "sqlite:" . $sqlite_path;
            $pdo = new PDO($dsn);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 根据数据库类型选择正确的建表SQL
        if ($config_data['db_type'] === 'mysql') {
            $sql = "
            CREATE TABLE IF NOT EXISTS `auth_users` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `l_user_id` BIGINT UNIQUE NOT NULL,
              `username` VARCHAR(255) NOT NULL,
              `trust_level` INT NOT NULL,
              `first_login_at` DATETIME NOT NULL,
              `last_login_at` DATETIME NOT NULL,
              `avatar_url` VARCHAR(255)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        } else { // sqlite
            $sql = "
            CREATE TABLE IF NOT EXISTS `auth_users` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `l_user_id` BIGINT UNIQUE NOT NULL,
              `username` VARCHAR(255) NOT NULL,
              `trust_level` INT NOT NULL,
              `first_login_at` DATETIME NOT NULL,
              `last_login_at` DATETIME NOT NULL,
              `avatar_url` VARCHAR(255)
            );";
        }
        $pdo->exec($sql);

    } catch (Exception $e) {
        $errors[] = "数据库错误: " . $e->getMessage();
    }

    // 3. 写入配置文件
    if (empty($errors)) {
        // 手动构建配置字符串以保证兼容性
        $config_php = "<?php\n";
        $config_php .= "// 由Auth Gate安装程序自动生成\n\n";
        $config_php .= "\$auth_config = [\n";
        $config_php .= "    'db_type' => '" . addslashes($generated_config['db_type']) . "',\n";
        $config_php .= "    'db' => [\n";
        if ($generated_config['db_type'] === 'mysql') {
            $config_php .= "        'host' => '" . addslashes($generated_config['db']['host']) . "',\n";
            $config_php .= "        'name' => '" . addslashes($generated_config['db']['name']) . "',\n";
            $config_php .= "        'user' => '" . addslashes($generated_config['db']['user']) . "',\n";
            $config_php .= "        'pass' => '" . addslashes($generated_config['db']['pass']) . "',\n";
        } else {
            // 对于Windows路径，需要额外转义 `\`
            $path = str_replace('\\', '\\\\', $generated_config['db']['path']);
            $config_php .= "        'path' => '" . $path . "',\n";
        }
        $config_php .= "    ],\n";
        $config_php .= "    'oauth' => [\n";
        $config_php .= "        'client_id' => '" . addslashes($generated_config['oauth']['client_id']) . "',\n";
        $config_php .= "        'client_secret' => '" . addslashes($generated_config['oauth']['client_secret']) . "',\n";
        $config_php .= "        'redirect_uri' => '" . addslashes($generated_config['oauth']['redirect_uri']) . "',\n";
        $config_php .= "    ],\n";
        $config_php .= "    'min_trust_level' => " . $generated_config['min_trust_level'] . ",\n";
        $config_php .= "    'cookie_name' => '" . addslashes($generated_config['cookie_name']) . "',\n";
        $config_php .= "    'cookie_expire' => " . $generated_config['cookie_expire'] . ",\n";
        $config_php .= "    'cookie_path' => '" . addslashes($generated_config['cookie_path']) . "',\n";
        $config_php .= "    'enable_widget' => " . ($generated_config['enable_widget'] ? 'true' : 'false') . ",\n";
        $config_php .= "    'admin_user' => '" . addslashes($generated_config['admin_user']) . "',\n";
        $config_php .= "    'admin_pass_hash' => '" . addslashes($generated_config['admin_pass_hash']) . "',\n";
        $config_php .= "];\n";

        if (file_put_contents(AUTH_CONFIG_PATH, $config_php) === false) {
            $errors[] = "写入 `auth_config.php` 失败。请检查目录 `" . AUTH_ROOT . "` 的写入权限。";
        }
    }
    
    // 4. 显示最终结果
    if (empty($errors)) {
        render_installer_page('安装成功', function() {
            echo '<h1>🎉 安装成功！</h1>';
            echo '<p>认证模块已成功配置。</p>';
            echo '<p><b>为了安全，`auth_gate.php` 不会自动重定向，请手动刷新您想要访问的页面。</b></p>';
            echo '<a href="" class="btn btn-primary mt-20">刷新页面</a>';
        });
    } else {
        render_installer_page('安装失败', function() use ($errors) {
            echo '<h1>安装失败</h1>';
            echo '<div class="error-box">' . implode('<br>', array_map('htmlspecialchars', $errors)) . '</div>';
            echo '<a href="" class="btn btn-secondary mt-20">重试</a>';
        });
    }
}


/**
 * 渲染安装器页面的通用模板
 * @param string $title
 * @param callable $content_renderer
 */
function render_installer_page($title, $content_renderer) {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8"><title>{$title}</title><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 700px; margin: 20px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        h1, h2 { color: #212529; } h1 { text-align: center; }
        .btn { display: inline-block; padding: 10px 20px; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 5px; cursor: pointer; border: none; }
        .btn-primary { background-color: #007bff; color: #fff; }
        .check-list { list-style: none; padding: 0; }
        .check-list li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .check-list li::before { content: "✓ "; color: #28a745; font-weight: bold; }
        .check-list li.fail::before { content: "✗ "; color: #dc3545; }
        .mt-20 { margin-top: 20px; } .mt-40 { margin-top: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .error-box { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body><div class="container">
HTML;
    $content_renderer();
    echo '</div></body></html>';
}
