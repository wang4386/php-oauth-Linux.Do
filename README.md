# LinuxDo Auth Gate - 嵌入式 PHP 登录认证模块

[![LICENSE](https://img.shields.io/badge/license-MIT-green)](https://github.com/wang4386/php-oauth-Linux.Do/blob/main/LICENSE)

**Auth Gate** 是一个为 PHP 项目设计的、极简且功能强大的嵌入式登录认证模块，专为 [Linux.do](https://linux.do/) 社区定制。

它的核心理念是：**用最简单的方式，为任何新、旧 PHP 项目增加一套安全、可靠且带数据库支持的 OAuth 登录系统。**

无论您是经验丰富的开发者还是PHP新手，都无需关心复杂的依赖和配置，只需一行代码，即可为您的项目加上登录保护。

---

## ✨ 核心特性

- **🚀 一行代码集成**: 只需在任何页面顶部 `require_once 'auth_gate.php';`，即可自动拦截未登录用户。
- **🔮 网页安装向导**: 首次使用时会自动进入引导式安装界面，无需手动修改任何配置文件。
- **💾 双数据库支持**: 安装时可自由选择使用 **SQLite** (零配置，推荐) 或 **MySQL** 存储用户信息。
- **💎 “灵动岛”悬浮控件**: 登录后，可在页面左上角显示一个交互式的用户信息控件，外观精致，且可在安装时选择是否启用。
- **⚙️ 独立管理后台**: 提供 `dladmin.php` 管理页面，允许您在安装后随时通过独立的账号密码登录，修改系统设置。
- **🛡️ 安全可靠**: 内置 CSRF 攻击防护，使用 `HttpOnly` 安全 Cookie，管理员密码使用 `password_hash` 加密存储。

## 📋 系统要求

- PHP >= 7.4
- PHP 扩展: `pdo`, `curl`, `session`
- 如果您选择使用 MySQL，还需要 `pdo_mysql` 扩展。

## 🛠️ 安装与部署

1.  **上传文件**: 将 `auth_gate.php`, `dlapi.php`, `logout.php`, `dladmin.php` 和整个 `auth/` 目录上传到您的 PHP 项目中。
2.  **引入脚本**: 在您想保护的任何 PHP 文件的**最顶部**，添加以下代码：
    ```php
    require_once 'path/to/auth_gate.php';
    ```
3.  **开始安装**: 在浏览器中访问您刚刚修改的那个文件。您将会看到一个安装向导界面。
4.  **完成配置**: 根据页面提示完成环境检查、数据库选择和各项信息的填写。
5.  **⚠️ 安全收尾 (重要!)**: 安装成功后，**请务必从服务器上删除 `install.php` 文件** (`auth/installer.php`)，以防被他人再次执行。

安装完成后，刷新页面，您会看到“通过 Linux.Do 登录”的按钮。

## 💡 使用方法

### 1. 保护一个页面

如上所述，只需在页面顶部引入 `auth_gate.php` 即可。该脚本会自动检查用户的登录状态，如果未登录，则会显示登录界面并终止后续代码的执行。

### 2. 显示悬浮控件和用户信息

为了获得最佳体验，您需要手动调用一个函数来渲染“灵动岛”控件。

- `get_current_auth_user()`: 如果用户已登录，调用此函数可以获取一个包含用户信息的数组。
- `render_auth_widget()`: 调用此函数来输出控件的 HTML, CSS 和 JavaScript。**推荐在 `</body>` 标签前调用。**

**完整示例:**
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

## 🧑‍💻 后台管理

安装完成后，您可以访问 `https://你的域名/dladmin.php` 来进入管理后台。

后台的用户名和密码是您在安装过程中设置的。

在后台，您可以随时修改以下设置：
- 最低用户信任等级
- 是否启用悬浮用户控件
- 重设管理员密码

## 📁 文件结构

-   `auth_gate.php`: **(入口文件)** 您唯一需要 `require` 的文件。
-   `dlapi.php`: 处理来自 Linux.do 的 OAuth 回调。
-   `logout.php`: 处理用户登出逻辑。
-   `dladmin.php`: 管理员后台。
-   `auth/`: 存放所有模块化逻辑的目录。
    -   `installer.php`: **(安装后需删除)** 网页安装向导。
    -   `database.php`: 数据库连接逻辑 (MySQL/SQLite)。
    -   `functions.php`: 核心辅助函数。
    -   `ui.php`: 渲染登录页、错误页等HTML界面。
    -   `widget.php`: “灵动岛”悬浮控件的全部代码。
-   `auth_config.php`: **(自动生成)** 存储所有配置，请勿手动修改，并强烈建议将其加入 `.gitignore`。
-   `auth.sqlite`: **(自动生成)** 如果您选择使用 SQLite，这是您的数据库文件。