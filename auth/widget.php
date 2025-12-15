<?php
/**
 * 悬浮用户信息控件 (灵动岛样式)
 *
 * 由 auth_gate.php 在用户登录后加载。
 * 它会向页面注入 HTML, CSS, 和 JavaScript。
 */

// 从 get_current_auth_user() 获取用户数据
$user = get_current_auth_user();

if (!$user) {
    return;
}

// 准备给 JavaScript 使用的数据
$user_data_json = json_encode([
    'username' => htmlspecialchars($user['username']),
    'trust_level' => (int)$user['trust_level'],
    'avatar_url' => htmlspecialchars(str_replace('{size}', '45', $user['avatar_url'])),
]);

// --- 注入 HTML (移除 avatar-wrapper) ---
echo <<<HTML
<div id="auth-widget-container" class="auth-widget-container">
    <img id="auth-widget-avatar" class="auth-widget-avatar" src="" alt="User Avatar">
    <div id="auth-widget-info" class="auth-widget-info">
        <strong></strong>
        <span></span>
        <a href="logout.php" class="auth-widget-logout">退出</a>
    </div>
</div>
HTML;

// --- 注入 CSS (精确计算版本) ---
echo <<<CSS
<style>
    :root {
        --widget-size: 48px;
        --widget-border-width: 2px;
        --widget-bg: #fff;
        --widget-border-color: #28a745;
        --widget-shadow: 0 4px 12px rgba(0,0,0,0.1);
        --widget-text-color: #333;
        --widget-pill-width: 240px;
        --widget-transition: width 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    }
    .auth-widget-container, .auth-widget-container * {
        box-sizing: border-box;
    }
    .auth-widget-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
        width: var(--widget-size);
        height: var(--widget-size);
        border-radius: 24px; /* 固定为高度的一半 */
        background-color: var(--widget-bg);
        box-shadow: var(--widget-shadow);
        border: var(--widget-border-width) solid var(--widget-border-color);
        transition: var(--widget-transition);
        cursor: pointer;
    }
    .auth-widget-container:hover {
        width: var(--widget-pill-width);
    }
    .auth-widget-avatar {
        position: absolute;
        top: 0;
        left: 0;
        /* 精确计算尺寸以完美嵌入边框内部 */
        width: calc(var(--widget-size) - (var(--widget-border-width) * 2));
        height: calc(var(--widget-size) - (var(--widget-border-width) * 2));
        border-radius: 50%;
        object-fit: cover;
    }
    .auth-widget-info {
        position: absolute;
        top: 0;
        left: var(--widget-size); /* 紧挨着头像定位 */
        right: 0;
        height: 100%;
        display: flex;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease-in-out 0.1s, visibility 0s linear 0.3s;
        white-space: nowrap;
        padding-left: 10px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 14px;
        color: var(--widget-text-color);
    }
    .auth-widget-container:hover .auth-widget-info {
        opacity: 1;
        visibility: visible;
        transition-delay: 0.1s; /* 稍延迟显示 */
    }
    .auth-widget-info strong {
        font-weight: 600;
        display: inline-block;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    .auth-widget-info span {
        color: #6c757d;
        margin-left: 8px;
        vertical-align: middle;
    }
    .auth-widget-logout {
        color: #dc3545;
        font-weight: bold;
        text-decoration: none;
        margin-left: auto;
        padding-right: 15px;
    }
    .auth-widget-logout:hover {
        text-decoration: underline;
    }
</style>
CSS;

// --- 注入 JavaScript ---
echo <<<JS
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userData = {$user_data_json};
        if (!userData) { return; }

        const avatar = document.getElementById('auth-widget-avatar');
        const info = document.getElementById('auth-widget-info');
        const usernameEl = info.querySelector('strong');
        const trustLevelEl = info.querySelector('span');

        avatar.src = userData.avatar_url;
        avatar.alt = userData.username + ' avatar';
        usernameEl.textContent = userData.username;
        trustLevelEl.textContent = 'L' + userData.trust_level;
    });
</script>
JS;
?>
