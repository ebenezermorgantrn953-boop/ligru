<?php
/**
 * 环境变量读取（微信云托管控制台注入）
 */
class Env
{
    public static function get($key, $default = '')
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $default;
    }

    public static function isCloudRun()
    {
        return self::get('WX_CLOUD_RUN') === '1'
            || self::get('KUBERNETES_SERVICE_HOST') !== ''
            || self::get('MYSQL_ADDRESS') !== '';
    }

    /**
     * 解析云托管 MySQL 环境变量并合并到配置
     */
    public static function mergeCloudConfig($config)
    {
        $addr = self::get('MYSQL_ADDRESS');
        if ($addr) {
            $parts = explode(':', $addr);
            $config['db']['host'] = $parts[0];
            $config['db']['port'] = isset($parts[1]) ? (int)$parts[1] : 3306;
        }
        if ($host = self::get('MYSQL_HOST')) {
            $config['db']['host'] = $host;
        }
        if ($port = self::get('MYSQL_PORT')) {
            $config['db']['port'] = (int)$port;
        }
        if ($db = self::get('MYSQL_DATABASE')) {
            $config['db']['dbname'] = $db;
        }
        if ($user = self::get('MYSQL_USERNAME')) {
            $config['db']['username'] = $user;
        }
        if ($pass = self::get('MYSQL_PASSWORD')) {
            $config['db']['password'] = $pass;
        }

        if ($appid = self::get('WX_APPID')) {
            $config['wechat']['appid'] = $appid;
        }
        if ($secret = self::get('WX_APPSECRET')) {
            $config['wechat']['secret'] = $secret;
        }
        if ($key = self::get('DEEPSEEK_API_KEY')) {
            // 运行时覆盖 DeepSeek 密钥（比写死在文件更安全）
            $GLOBALS['wm_deepseek_key_override'] = $key;
        }

        $publicUrl = self::get('CONTAINER_PUBLIC_URL');
        if ($publicUrl) {
            $config['site']['url'] = rtrim($publicUrl, '/');
        }

        if (self::isCloudRun()) {
            $config['cloud'] = [
                'enabled'     => true,
                'service'     => self::get('WX_CLOUD_SERVICE', 'watermark-api'),
                'env_id'      => self::get('WX_CLOUD_ENV_ID', ''),
            ];
            // 云托管上传目录使用容器内持久路径
            $config['site']['upload_dir'] = WM_ROOT . '/uploads/';
        }

        if (self::get('APP_DEBUG') === '1') {
            $config['debug'] = true;
        }

        return $config;
    }
}
