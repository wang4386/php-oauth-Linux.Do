# PHP OAuth登录鉴权系统集成说明

## 文件说明

本系统包含以下三个主要文件：

1. **dlconfig.php** - 配置文件，包含OAuth应用信息和信任等级设置
2. **dlapi.php** - API处理文件，处理OAuth回调和用户认证逻辑
3. **index.php集成代码** - 用于在原有PHP程序首页中集成OAuth登录认证

## 集成步骤

### 1. 上传文件

将 `dlconfig.php` 和 `dlapi.php` 文件上传到您的网站根目录。

### 2. 修改原有index.php

打开您原有的 `index.php` 文件，在文件最顶部（任何其他PHP代码之前）添加集成代码。

集成代码已经提供在 `index_integration.php` 文件中，您需要将其内容复制到您的 `index.php` 文件的最顶部。

### 3. 确认配置

确认 `dlconfig.php` 中的配置信息是否正确，特别是：

- client_id 和 client_secret 是否与您的OAuth应用匹配
- 回调URL是否正确（默认为网站根目录下的dlapi.php）
- 最低信任等级要求是否符合您的需求（默认为2）
- Cookie有效期是否符合您的需求（默认为24小时）

## 工作原理

1. 当用户访问您的网站首页时，系统会检查用户是否已登录（通过检查cookie）
2. 如果用户未登录，系统会显示一个简洁的登录页面
3. 用户点击登录按钮后，系统会将用户重定向到OAuth授权页面
4. 用户在OAuth授权页面完成登录后，系统会将用户重定向回您的网站
5. 系统会验证用户的信任等级，如果达到要求，则允许用户访问原有内容
6. 如果用户的信任等级不足，系统会显示错误信息并阻止访问

## 安全特性

- 使用随机生成的state参数防止CSRF攻击
- 验证所有OAuth响应参数
- 使用HttpOnly cookie存储用户ID
- 防止直接访问配置文件

## 应用注册
[LinuxDo Connect](https://connect.linux.do/)
回调地址填写http(s)://你的域名/dlapi.php

## 自定义选项

如需自定义登录页面样式，可以修改 `index_integration.php` 文件中的 `showLoginPage()` 函数。

如需修改错误页面样式，可以修改 `dlapi.php` 文件中的 `handleError()` 函数。

## 故障排除

如果遇到问题，请检查：

1. 确保所有文件都已上传到正确位置
2. 确保OAuth应用信息正确
3. 确保回调URL与OAuth应用中设置的回调URL匹配
4. 检查服务器错误日志以获取更多信息
