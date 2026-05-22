# 服务器部署教程

## 环境要求

- PHP >= 7.4（需开启 curl、pdo_mysql、fileinfo、gd 扩展）
- MySQL >= 5.7 或 MariaDB >= 10.2
- Apache（mod_rewrite）或 Nginx
- HTTPS 证书（小程序要求后端必须 HTTPS）

## 一、上传代码

1. 将 `backend` 文件夹上传到服务器，例如 `/www/wwwroot/your-domain.com/`
2. 目录结构应为：
   ```
   your-domain.com/
   ├── api/
   ├── config/
   ├── core/
   ├── admin/
   ├── uploads/
   └── database/
   ```

## 二、导入数据库

```bash
mysql -u root -p < database/install.sql
```

或在 phpMyAdmin 中导入 `install.sql`

## 三、修改配置

### config/config.php

```php
'db' => [
    'host'     => '127.0.0.1',
    'dbname'   => 'ai_watermark',
    'username' => '你的数据库用户',
    'password' => '你的数据库密码',
],
'site' => [
    'url' => 'https://your-domain.com',  // 必须 HTTPS
],
'wechat' => [
    'appid'  => '小程序AppID',
    'secret' => '小程序Secret',
    'mch_id' => '商户号',
    'pay_key' => '支付API密钥',
],
```

### config/wechat.php（可选，覆盖上述微信配置）

## 四、目录权限

```bash
chmod -R 755 uploads/
chown -R www:www uploads/
```

确保 `uploads` 及其子目录可写。

## 五、Nginx 配置示例

```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;
    root /www/wwwroot/your-domain.com;
    index index.php;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /uploads/ {
        expires 30d;
    }

    location ~ /config/ {
        deny all;
    }
}
```

## 六、Apache 配置

确保开启 `mod_rewrite`，项目已包含 `.htaccess`。

## 七、小程序后台配置

1. 登录 [微信公众平台](https://mp.weixin.qq.com)
2. 开发 → 开发管理 → 开发设置 → 服务器域名
3. 添加 request 合法域名：`https://your-domain.com`
4. 添加 uploadFile 合法域名：`https://your-domain.com`
5. 添加 downloadFile 合法域名（如需下载处理结果）

## 八、验证部署

访问 `https://your-domain.com/api/user.php` 应返回 JSON（未登录时为 401）。

访问 `https://your-domain.com/admin/index.php` 可进入后台。

## 九、虚拟主机部署

1. 将 backend 内所有文件上传到网站根目录
2. 在主机面板创建 MySQL 数据库并导入 SQL
3. 修改 config/config.php 数据库信息
4. 确保 PHP 版本 >= 7.4

## 常见问题

**Q: 上传失败？**  
A: 检查 uploads 目录权限，以及 PHP `upload_max_filesize` 配置。

**Q: 跨域错误？**  
A: bootstrap.php 已设置 CORS，检查服务器是否拦截了 OPTIONS 请求。

**Q: 登录失败？**  
A: 确认 AppID/Secret 正确，且服务器可访问 `api.weixin.qq.com`。
