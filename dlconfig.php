<?php
/**
 * OAuth配置文件
 * 存储OAuth应用信息和信任等级设置
 */

// 防止直接访问此文件
if (!defined('OAUTH_LOADED')) {
    header('HTTP/1.0 403 Forbidden');
    exit('禁止直接访问此文件');
}

// OAuth应用信息
$oauth_config = [
    // 应用凭证
    'client_id' => 'O4e89zTwtJ7td8En3KIjZHAYCwqZh3Ft',
    'client_secret' => 'A6KPhaJxUTfPcazbvESupdsW9XumyfP3',
    
    // OAuth端点
    'authorize_url' => 'https://connect.linux.do/oauth2/authorize',
    'token_url' => 'https://connect.linux.do/oauth2/token',
    'userinfo_url' => 'https://connect.linux.do/api/user',
    
    // 回调URL（相对于网站根目录）
    'redirect_uri' => 'dlapi.php',
    
    // 访问限制设置
    'min_trust_level' => 2,  // 最低信任等级要求
    
    // Cookie设置
    'cookie_name' => 'dl_oauth_id',  // 存储用户ID的cookie名称
    'cookie_expire' => 86400,  // Cookie有效期（24小时，单位：秒）
    'cookie_path' => '/',  // Cookie路径
    
    // 安全设置
    'state_key' => 'dl_oauth_state',  // 用于防止CSRF攻击的state参数名
];
?>
