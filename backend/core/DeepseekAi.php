<?php
/**
 * DeepSeek AI 业务适配层
 */
class DeepseekAi
{
    /**
     * 图片智能去水印
     * DeepSeek 文本 API 无法直接改图，使用本地 GD 智能修复常见水印区域
     */
    public static function removeImage($imageUrl)
    {
        $localPath = self::resolveLocalPath($imageUrl);
        return ImageInpaint::autoRemove($localPath);
    }

    /**
     * 手动涂抹去水印（遮罩 + 本地修复）
     */
    public static function brushRemove($imageUrl, $maskUrl)
    {
        $imagePath = self::resolveLocalPath($imageUrl);
        $maskPath = self::resolveLocalPath($maskUrl);
        return ImageInpaint::process($imagePath, $maskPath);
    }

    /**
     * 短视频链接智能解析（DeepSeek 核心能力）
     */
    public static function parseVideo($shareText)
    {
        $system = '你是短视频链接解析专家。用户会粘贴来自抖音、快手、小红书、B站等平台的分享文案或链接。'
            . '请从中提取最有价值的视频相关信息，以 JSON 格式返回，字段如下：'
            . '{"platform":"平台名","title":"视频标题或描述","video_url":"可访问的视频直链或页面链接(尽量提取)","cover":"","tips":"给用户的提示"}'
            . '若无法提取直链，video_url 填提取到的页面链接。只返回 JSON，不要其他文字。';

        $user = "请解析以下分享内容：\n" . $shareText;

        $result = DeepseekClient::chatJson($system, $user);

        return [
            'video_url' => $result['video_url'] ?? $shareText,
            'cover'     => $result['cover'] ?? '',
            'title'     => $result['title'] ?? ('[' . ($result['platform'] ?? '未知') . '] 解析完成'),
            'platform'  => $result['platform'] ?? '',
            'tips'      => $result['tips'] ?? '',
        ];
    }

    private static function resolveLocalPath($url)
    {
        $uploadUrl = $GLOBALS['wm_config']['site']['upload_url'];
        $uploadDir = rtrim($GLOBALS['wm_config']['site']['upload_dir'], '/');
        $baseUrl = rtrim($GLOBALS['wm_config']['site']['url'], '/');

        if (strpos($url, $baseUrl) === 0) {
            $rel = str_replace($baseUrl . $uploadUrl, '', $url);
            return $uploadDir . '/' . ltrim($rel, '/');
        }
        if (file_exists($url)) {
            return $url;
        }
        Response::error('无法定位图片文件路径');
    }
}
