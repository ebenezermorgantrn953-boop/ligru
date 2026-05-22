<?php
/**
 * 文件上传处理
 */
class Upload
{
    public static function save($file, $subdir = 'images')
    {
        $ext = Security::validateUpload($file, $subdir === 'videos' ? 'video' : 'image');
        $cfg = $GLOBALS['wm_config']['site'];
        $filename = date('Ymd') . '_' . uniqid() . '.' . $ext;
        $dir = rtrim($cfg['upload_dir'], '/') . '/' . $subdir . '/';
        $path = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            Response::error('文件保存失败');
        }

        $baseUrl = rtrim($GLOBALS['wm_config']['site']['url'], '/');
        $url = $baseUrl . $cfg['upload_url'] . $subdir . '/' . $filename;

        return ['path' => $path, 'url' => $url, 'filename' => $filename];
    }

    public static function saveBase64($base64, $subdir = 'masks')
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } else {
            $ext = 'png';
        }
        $data = base64_decode($base64);
        if (!$data) {
            Response::error('遮罩数据无效');
        }

        $filename = date('Ymd') . '_mask_' . uniqid() . '.' . $ext;
        $dir = rtrim($GLOBALS['wm_config']['site']['upload_dir'], '/') . '/' . $subdir . '/';
        $path = $dir . $filename;
        file_put_contents($path, $data);

        $baseUrl = rtrim($GLOBALS['wm_config']['site']['url'], '/');
        $url = $baseUrl . $GLOBALS['wm_config']['site']['upload_url'] . $subdir . '/' . $filename;

        return ['path' => $path, 'url' => $url];
    }
}
