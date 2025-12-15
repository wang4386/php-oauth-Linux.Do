# LinuxDo OAuth Gate - 嵌入式PHP登录认证

这是一个极简的、可嵌入的 PHP 登录认证模块，专为 [Linux.do](https://linux.do/) 社区设计。您只需在任何现有 PHP 项目的页面顶部引入一个文件，即可为其增加一套完整的、基于数据库的 OAuth 登录系统。

## 核心特性

- **极致简化集成**: 只需一行 `require_once` 即可为任何页面添加登录保护。
- **零配置安装**: 首次访问受保护的页面时，会自动触发一个网页安装向导，无需手动编辑任何文件。
- **灵活的数据库支持**: 安装时可自由选择使用 **SQLite** (零配置，推荐) 或 **MySQL**。
- **安全可靠**: 内置 CSRF 攻击防护、使用 `HttpOnly` 安全 Cookie，并将用户敏感信息存储在您自己的数据库中。
- **无缝体验**: 登录后会自动返回用户最初访问的页面。

## 如何使用

1.  **复制文件**: 将 `auth_gate.php`, `dlapi.php` 和整个 `auth/` 目录复制到您的 PHP 项目中。
2.  **引入脚本**: 在您想保护的任何 PHP 文件的最顶部，添加以下代码：
    ```php
    require_once 'path/to/auth_gate.php';
    ```
3.  **自动安装**: 在浏览器中访问您刚刚修改的那个文件。您将会看到一个安装向导界面。
    -   **环境检查**: 确认您的服务器满足基本要求。
    -   **选择数据库**: 选择 SQLite 或 MySQL。
    -   **填写配置**: 按照提示填写 OAuth 应用信息 (如果您使用 MySQL，还需填写数据库凭据)。
    -   **完成安装**: 点击按钮，程序会自动生成配置文件 (`auth_config.php`) 和数据库/表。
4.  **开始使用**: 安装成功后，刷新页面。您会看到“通过 Linux.Do 登录”的按钮。点击登录，完成认证流程后，您将能看到您原来的页面内容。

此后，任何未登录的用户访问该页面时，都会被登录界面拦截。

## 在您的代码中访问用户信息及渲染控件

在 `require_once 'auth_gate.php';` 这一行之后，如果用户已登录，您可以随时调用 `get_current_auth_user()` 函数来获取当前用户的信息。

此外，您还需要在页面的 HTML 中调用 `render_auth_widget()` 函数来显示悬浮控件。

**示例:**

```php
<?php
// 1. 在页面最顶部引入认证模块
require_once 'auth_gate.php';

// ... 您页面的主要PHP逻辑 ...
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>我的网站</title>
    <!-- 您自己的 CSS 和 JS -->
</head>
<body>

    <h1>我的网站内容</h1>
    <p>这里是受保护的页面内容。</p>

    <?php
        // (可选) 获取并显示用户信息
        $user = get_current_auth_user();
        if ($user) {
            echo "<p>欢迎回来, " . htmlspecialchars($user['username']) . "！</p>";
        }
    ?>

    <?php
        // 2. 在 </body> 标签前调用此函数来渲染悬浮控件
        render_auth_widget(); 
    ?>
</body>
</html>
```

## 后台管理

安装完成后，您可以访问 `https://你的域名/dladmin.php` 来进入管理后台。

后台的用户名和密码是您在安装过程中设置的。

在后台，您可以随时修改以下设置：
- 最低用户信任等级
- 是否启用悬浮用户控件
- 重设管理员密码

## 文件结构

-   `auth_gate.php`: **(入口文件)** 您唯一需要 `require` 的文件。
-   `dlapi.php`: 处理来自 Linux.do 的 OAuth 回调，您无需直接访问或修改它。
-   `auth/`: 存放所有模块化逻辑的目录。
    -   `installer.php`: 网页安装向导的逻辑。
    -   `database.php`: 数据库连接逻辑 (MySQL/SQLite)。
    -   `functions.php`: 核心辅助函数。
    -   `ui.php`: 渲染登录页、错误页等HTML界面。
-   `auth_config.php`: **(自动生成)** 存储所有配置，请勿手动修改，并建议将其加入 `.gitignore`。
-   `auth.sqlite`: **(自动生成)** 如果您选择使用 SQLite，这是您的数据库文件。
