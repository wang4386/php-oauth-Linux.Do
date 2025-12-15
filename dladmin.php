<?php
/**
 * Auth Gate - 管理后台
 */

// 即使 gate 文件被设置为保护所有页面，我们仍然 require 它来加载所有配置和函数
require_once 'auth_gate.php';

// 如果尚未安装，则不执行任何操作
if (!isset($auth_config)) {
    // auth_gate 会处理重定向到安装程序
    exit;
}

// 独立于用户登录的管理员会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_admin_logged_in = $_SESSION['is_admin_logged_in'] ?? false;
$action = $_POST['action'] ?? $_GET['action'] ?? 'show_panel';
$error_message = '';
$success_message = '';

// --- 路由和逻辑处理 ---

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username === $auth_config['admin_user'] && password_verify($password, $auth_config['admin_pass_hash'])) {
        $_SESSION['is_admin_logged_in'] = true;
        $is_admin_logged_in = true;
        header('Location: dladmin.php'); // 登录后重定向，避免重复提交
        exit;
    } else {
        $error_message = '用户名或密码错误。';
    }
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: dladmin.php');
    exit;
}

if ($is_admin_logged_in && $action === 'update_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 这是最关键的部分：安全地重写配置文件
    $new_config = $auth_config; // 从加载的现有配置开始

    // 更新值
    $new_config['min_trust_level'] = (int)($_POST['min_trust_level'] ?? $new_config['min_trust_level']);
    $new_config['enable_widget'] = (bool)($_POST['enable_widget'] ?? $new_config['enable_widget']);

    // 如果提供了新密码，则更新
    if (!empty($_POST['admin_pass'])) {
        $new_config['admin_pass_hash'] = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
    }
    
    // 使用与安装程序相同的逻辑重写配置文件
    $config_php = "<?php\n";
    $config_php .= "// 由Auth Gate管理后台更新\n\n";
    $config_php .= "\$auth_config = [\n";
    foreach ($new_config as $key => $value) {
        if (is_array($value)) {
            $config_php .= "    '" . addslashes($key) . "' => [\n";
            foreach($value as $sub_key => $sub_value) {
                $config_php .= "        '" . addslashes($sub_key) . "' => '" . addslashes($sub_value) . "',
";
            }
            $config_php .= "    ],
";
        } elseif (is_bool($value)) {
            $config_php .= "    '" . addslashes($key) . "' => " . ($value ? 'true' : 'false') . ",\n";
        } elseif (is_int($value)) {
            $config_php .= "    '" . addslashes($key) . "' => " . $value . ",\n";
        } else {
            $config_php .= "    '" . addslashes($key) . "' => '" . addslashes($value) . "',
";
        }
    }
    $config_php .= "];\n";

    if (file_put_contents(AUTH_CONFIG_PATH, $config_php)) {
        $success_message = '设置已成功保存！';
        $auth_config = $new_config; // 重新加载配置以便页面显示新值
    } else {
        $error_message = '写入配置文件失败！请检查 `auth_config.php` 的文件权限。';
    }
}


// --- UI 渲染 ---

function render_admin_page($title, $content_callable) {
    global $error_message, $success_message;
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>{$title}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
    .panel { width: 100%; max-width: 600px; background: #fff; padding: 30px 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h1 { text-align: center; color: #333; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .btn { display: inline-block; width: 100%; padding: 12px; font-size: 16px; font-weight: bold; text-align: center; text-decoration: none; border-radius: 5px; cursor: pointer; border: none; }
    .btn-primary { background-color: #007bff; color: #fff; }
    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .logout-link { position: absolute; top: 20px; right: 20px; text-decoration: none; color: #6c757d; }
</style>
</head><body><div class="panel">
HTML;
    if ($error_message) echo "<div class='alert alert-danger'>" . htmlspecialchars($error_message) . "</div>";
    if ($success_message) echo "<div class='alert alert-success'>" . htmlspecialchars($success_message) . "</div>";
    $content_callable();
    echo '</div></body></html>';
}

if ($is_admin_logged_in) {
    // 已登录，显示设置面板
    render_admin_page('管理后台', function() {
        global $auth_config;
        echo '<a href="?action=logout" class="logout-link">退出登录</a><h1>管理后台</h1>';
        echo '<form method="post"><input type="hidden" name="action" value="update_settings">';
        
        echo '<div class="form-group">';
        echo '<label for="min_trust_level">最低用户信任等级</label>';
        echo '<input type="number" id="min_trust_level" name="min_trust_level" class="form-control" value="' . (int)$auth_config['min_trust_level'] . '">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label for="enable_widget">启用悬浮用户控件</label>';
        echo '<select id="enable_widget" name="enable_widget" class="form-control">';
        echo '<option value="1"' . ($auth_config['enable_widget'] ? ' selected' : '') . '>是</option>';
        echo '<option value="0"' . (!$auth_config['enable_widget'] ? ' selected' : '') . '>否</option>';
        echo '</select>';
        echo '</div>';

        echo '<h3>修改管理员密码 (可选)</h3>';
        echo '<div class="form-group">';
        echo '<label for="admin_pass">新密码 (留空则不修改)</label>';
        echo '<input type="password" id="admin_pass" name="admin_pass" class="form-control">';
        echo '</div>';

        echo '<button type="submit" class="btn btn-primary">保存设置</button>';
        echo '</form>';
    });
} else {
    // 未登录，显示登录表单
    render_admin_page('管理员登录', function() {
        echo '<h1>管理员登录</h1>';
        echo '<form method="post"><input type="hidden" name="action" value="login">';
        
        echo '<div class="form-group">';
        echo '<label for="username">用户名</label>';
        echo '<input type="text" id="username" name="username" class="form-control" required>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label for="password">密码</label>';
        echo '<input type="password" id="password" name="password" class="form-control" required>';
        echo '</div>';

        echo '<button type="submit" class="btn btn-primary">登录</button>';
        echo '</form>';
    });
}
?>
