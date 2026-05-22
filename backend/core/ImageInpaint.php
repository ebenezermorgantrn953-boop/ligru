<?php
/**
 * 本地图像修复（配合 DeepSeek 方案）
 * 基于 PHP GD 的简易 inpainting，适用于涂抹遮罩去水印
 */
class ImageInpaint
{
    /**
     * 根据遮罩图修复原图，返回保存后的 URL
     */
    public static function process($imagePath, $maskPath)
    {
        if (!extension_loaded('gd')) {
            Response::error('服务器未安装 GD 扩展，无法处理图片');
        }

        $src = self::loadImage($imagePath);
        $mask = self::loadImage($maskPath);
        if (!$src || !$mask) {
            Response::error('图片或遮罩加载失败');
        }

        $w = imagesx($src);
        $h = imagesy($src);
        imagesavealpha($src, true);

        // 统一遮罩尺寸
        $maskResized = imagecreatetruecolor($w, $h);
        imagecopyresampled($maskResized, $mask, 0, 0, 0, 0, $w, $h, imagesx($mask), imagesy($mask));

        // 标记需要修复的像素
        $pixels = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $mc = imagecolorat($maskResized, $x, $y);
                $mr = ($mc >> 16) & 0xFF;
                $mg = ($mc >> 8) & 0xFF;
                $mb = $mc & 0xFF;
                // 遮罩中非透明/高亮区域视为水印
                if ($mr > 50 || $mg > 50 || $mb > 50) {
                    $pixels[] = [$x, $y];
                }
            }
        }

        if (empty($pixels)) {
            imagedestroy($src);
            imagedestroy($mask);
            imagedestroy($maskResized);
            Response::error('未检测到有效的涂抹区域');
        }

        // 多轮扩散修复：用邻域平均色填充
        $radius = 3;
        $iterations = 8;
        for ($i = 0; $i < $iterations; $i++) {
            foreach ($pixels as $p) {
                self::fillFromNeighbors($src, $p[0], $p[1], $w, $h, $radius, $maskResized);
            }
        }

        $outDir = rtrim($GLOBALS['wm_config']['site']['upload_dir'], '/') . '/results/';
        $filename = date('Ymd') . '_ds_' . uniqid() . '.png';
        $outPath = $outDir . $filename;
        imagepng($src, $outPath, 6);

        imagedestroy($src);
        imagedestroy($mask);
        imagedestroy($maskResized);

        $baseUrl = rtrim($GLOBALS['wm_config']['site']['url'], '/');
        return $baseUrl . $GLOBALS['wm_config']['site']['upload_url'] . 'results/' . $filename;
    }

    /**
     * 智能去水印（无遮罩）：修复常见角落水印区域
     */
    public static function autoRemove($imagePath)
    {
        if (!extension_loaded('gd')) {
            Response::error('服务器未安装 GD 扩展');
        }

        $src = self::loadImage($imagePath);
        if (!$src) {
            Response::error('图片加载失败');
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // 常见水印位置：右下、左下、底部横条
        $regions = [
            ['x' => (int)($w * 0.65), 'y' => (int)($h * 0.85), 'rw' => (int)($w * 0.35), 'rh' => (int)($h * 0.15)],
            ['x' => 0, 'y' => (int)($h * 0.88), 'rw' => $w, 'rh' => (int)($h * 0.12)],
        ];

        foreach ($regions as $r) {
            for ($y = $r['y']; $y < min($h, $r['y'] + $r['rh']); $y++) {
                for ($x = $r['x']; $x < min($w, $r['x'] + $r['rw']); $x++) {
                    self::fillFromNeighbors($src, $x, $y, $w, $h, 5, null);
                }
            }
        }

        $outDir = rtrim($GLOBALS['wm_config']['site']['upload_dir'], '/') . '/results/';
        $filename = date('Ymd') . '_auto_' . uniqid() . '.png';
        $outPath = $outDir . $filename;
        imagepng($src, $outPath, 6);
        imagedestroy($src);

        $baseUrl = rtrim($GLOBALS['wm_config']['site']['url'], '/');
        return $baseUrl . $GLOBALS['wm_config']['site']['upload_url'] . 'results/' . $filename;
    }

    private static function fillFromNeighbors($img, $x, $y, $w, $h, $radius, $mask = null)
    {
        $rSum = $gSum = $bSum = 0;
        $count = 0;

        for ($dy = -$radius; $dy <= $radius; $dy++) {
            for ($dx = -$radius; $dx <= $radius; $dx++) {
                $nx = $x + $dx;
                $ny = $y + $dy;
                if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                    continue;
                }
                if ($mask) {
                    $mc = imagecolorat($mask, $nx, $ny);
                    $mr = ($mc >> 16) & 0xFF;
                    if ($mr > 50) {
                        continue; // 跳过遮罩内像素
                    }
                }
                $c = imagecolorat($img, $nx, $ny);
                $rSum += ($c >> 16) & 0xFF;
                $gSum += ($c >> 8) & 0xFF;
                $bSum += $c & 0xFF;
                $count++;
            }
        }

        if ($count > 0) {
            $color = imagecolorallocate($img, (int)($rSum / $count), (int)($gSum / $count), (int)($bSum / $count));
            imagesetpixel($img, $x, $y, $color);
        }
    }

    private static function loadImage($path)
    {
        if (!file_exists($path)) {
            // 尝试从 URL 路径转本地
            $path = self::urlToLocalPath($path);
        }
        if (!file_exists($path)) {
            return null;
        }
        $info = getimagesize($path);
        if (!$info) {
            return null;
        }
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null;
            default:
                return null;
        }
    }

    private static function urlToLocalPath($url)
    {
        $uploadUrl = $GLOBALS['wm_config']['site']['upload_url'];
        $uploadDir = $GLOBALS['wm_config']['site']['upload_dir'];
        $baseUrl = rtrim($GLOBALS['wm_config']['site']['url'], '/');
        if (strpos($url, $baseUrl) === 0) {
            $rel = str_replace($baseUrl . $uploadUrl, '', $url);
            return rtrim($uploadDir, '/') . '/' . ltrim($rel, '/');
        }
        return $url;
    }
}
