<?php
/**
 * AI处理服务 - 支持 DeepSeek / 自定义API / 演示模式
 */
class AiService
{
    public static function removeImageWatermark($imageUrl, $maskUrl = '')
    {
        $cfg = $GLOBALS['wm_ai_config'];
        if (!$cfg['enabled'] || ($cfg['provider'] ?? '') === 'mock') {
            return self::mockImageProcess($imageUrl);
        }
        if (($cfg['provider'] ?? '') === 'deepseek') {
            return DeepseekAi::removeImage($imageUrl);
        }
        return self::callApi($cfg['image_remove'], [
            'image_url' => $imageUrl,
            'mask_url'  => $maskUrl,
        ]);
    }

    public static function brushRemove($imageUrl, $maskUrl)
    {
        $cfg = $GLOBALS['wm_ai_config'];
        if (!$cfg['enabled'] || ($cfg['provider'] ?? '') === 'mock') {
            return self::mockImageProcess($imageUrl);
        }
        if (($cfg['provider'] ?? '') === 'deepseek') {
            return DeepseekAi::brushRemove($imageUrl, $maskUrl);
        }
        return self::callApi($cfg['brush_remove'], [
            'image_url' => $imageUrl,
            'mask_url'  => $maskUrl,
        ]);
    }

    public static function parseVideo($videoUrl)
    {
        $cfg = $GLOBALS['wm_ai_config'];
        if (!$cfg['enabled'] || ($cfg['provider'] ?? '') === 'mock') {
            return self::mockVideoParse($videoUrl);
        }
        if (($cfg['provider'] ?? '') === 'deepseek') {
            return DeepseekAi::parseVideo($videoUrl);
        }
        return self::callApi($cfg['video_parse'], ['video_url' => $videoUrl]);
    }

    private static function callApi($apiCfg, $replacements)
    {
        $body = $apiCfg['body_template'];
        array_walk_recursive($body, function (&$v) use ($replacements) {
            foreach ($replacements as $key => $val) {
                $v = str_replace('{' . $key . '}', $val, $v);
            }
        });

        $ch = curl_init($apiCfg['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $GLOBALS['wm_ai_config']['timeout'] ?? 60,
            CURLOPT_POST           => strtoupper($apiCfg['method']) === 'POST',
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => self::formatHeaders($apiCfg['headers'] ?? []),
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            Response::error('AI服务请求失败');
        }

        $data = json_decode($response, true);
        $result = self::getNestedValue($data, $apiCfg['result_field']);
        if (!$result) {
            Response::error('AI处理未返回有效结果');
        }
        return $result;
    }

    private static function formatHeaders($headers)
    {
        $formatted = ['Content-Type: application/json'];
        foreach ($headers as $k => $v) {
            $formatted[] = "{$k}: {$v}";
        }
        return $formatted;
    }

    private static function getNestedValue($arr, $path)
    {
        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if (!isset($arr[$key])) {
                return null;
            }
            $arr = $arr[$key];
        }
        return $arr;
    }

    private static function mockImageProcess($imageUrl)
    {
        sleep(1);
        return $imageUrl;
    }

    private static function mockVideoParse($videoUrl)
    {
        return [
            'video_url' => $videoUrl,
            'cover'     => '',
            'title'     => '解析成功(演示模式)',
        ];
    }
}
