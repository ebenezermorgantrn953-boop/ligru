<?php
/**
 * 安全校验
 */
class Security
{
    /** 频率限制（文件锁，适配云托管多实例） */
    public static function rateLimit()
    {
        $ip = self::getClientIp();
        $limit = $GLOBALS['wm_config']['security']['rate_limit'] ?? 60;
        $key = md5($ip . '_' . date('Y-m-d-H-i'));
        $file = sys_get_temp_dir() . '/wm_rl_' . $key . '.cnt';

        $count = 0;
        $fp = @fopen($file, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            $count = (int)stream_get_contents($fp);
            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, (string)($count + 1));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            $count = (int)@file_get_contents($file);
            @file_put_contents($file, (string)($count + 1));
        }

        if ($count >= $limit) {
            Response::error('请求过于频繁，请稍后再试', 429);
        }
    }

    /** 压测时跳过限流 */
    public static function rateLimitUnlessStress()
    {
        $key = $_REQUEST['key'] ?? '';
        $secret = Env::get('STRESS_TEST_KEY', '');
        if ($secret && $key === $secret) {
            return;
        }
        self::rateLimit();
    }

    /** 校验上传文件 */
    public static function validateUpload($file, $type = 'image')
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            Response::error('文件上传失败');
        }

        $cfg = $GLOBALS['wm_config']['security'];
        $maxBytes = $cfg['max_upload_mb'] * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            Response::error('文件大小超过限制(' . $cfg['max_upload_mb'] . 'MB)');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $type === 'video' ? $cfg['allowed_video'] : $cfg['allowed_image'];
        if (!in_array($ext, $allowed)) {
            Response::error('不支持的文件格式');
        }

        // MIME 校验
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $validMimes = $type === 'video'
            ? ['video/mp4', 'video/quicktime']
            : ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $validMimes)) {
            Response::error('文件类型不合法');
        }

        return $ext;
    }

    /** 过滤恶意URL */
    public static function sanitizeUrl($url)
    {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Response::error('链接格式不正确');
        }
        $host = parse_url($url, PHP_URL_HOST);
        $blocked = ['localhost', '127.0.0.1', '0.0.0.0'];
        foreach ($blocked as $b) {
            if (stripos($host, $b) !== false) {
                Response::error('非法链接');
            }
        }
        return $url;
    }

    public static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
