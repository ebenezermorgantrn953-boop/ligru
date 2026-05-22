<?php
/**
 * DeepSeek Chat API 客户端（OpenAI 兼容格式）
 */
class DeepseekClient
{
    private static function config()
    {
        $file = WM_ROOT . '/config/deepseek.php';
        if (!file_exists($file)) {
            $example = WM_ROOT . '/config/deepseek.example.php';
            if (file_exists($example)) {
                return require $example;
            }
            Response::error('未找到 DeepSeek 配置，请复制 deepseek.example.php 为 deepseek.php 或设置 DEEPSEEK_API_KEY');
        }
        return require $file;
    }

    /**
     * 发起对话请求
     */
    public static function chat($systemPrompt, $userPrompt, $jsonMode = true)
    {
        $cfg = self::config();
        if (!empty($GLOBALS['wm_deepseek_key_override'])) {
            $cfg['api_key'] = $GLOBALS['wm_deepseek_key_override'];
        }
        if (empty($cfg['api_key']) || strpos($cfg['api_key'], 'your-deepseek') !== false) {
            Response::error('请配置 DeepSeek API Key（deepseek.php 或环境变量 DEEPSEEK_API_KEY）');
        }

        $body = [
            'model'    => $cfg['model'] ?? 'deepseek-v4-flash',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'stream'   => false,
            'max_tokens' => 2048,
        ];

        if (!empty($cfg['thinking'])) {
            $body['thinking'] = $cfg['thinking'];
        }

        if ($jsonMode) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $url = rtrim($cfg['base_url'], '/') . '/chat/completions';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $cfg['api_key'],
            ],
            CURLOPT_TIMEOUT        => $cfg['timeout'] ?? 90,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            Response::error('DeepSeek 连接失败: ' . $curlErr);
        }

        $data = json_decode($response, true);
        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? ('HTTP ' . $httpCode);
            Response::error('DeepSeek API 错误: ' . $errMsg);
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        if ($content === '') {
            Response::error('DeepSeek 返回内容为空');
        }

        return $content;
    }

    /**
     * 解析 JSON 响应
     */
    public static function chatJson($systemPrompt, $userPrompt)
    {
        $content = self::chat($systemPrompt, $userPrompt, true);
        // 去除可能的 markdown 代码块包裹
        $content = preg_replace('/^```json\s*|\s*```$/s', '', trim($content));
        $json = json_decode($content, true);
        if (!is_array($json)) {
            Response::error('DeepSeek 返回的 JSON 格式无效');
        }
        return $json;
    }
}
