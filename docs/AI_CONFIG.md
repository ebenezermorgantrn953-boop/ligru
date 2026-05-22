# AI 接口配置教程

## 推荐：DeepSeek（已内置）

详见 **[DEEPSEEK.md](DEEPSEEK.md)**，只需配置 `config/deepseek.php` 中的 API Key 即可。

```php
// config/ai.php
'enabled' => true,
'provider' => 'deepseek',
```

---

## 自定义第三方 API

## 配置文件位置

`backend/config/ai.php`

## 启用真实 AI

将 `enabled` 改为 `true`：

```php
'enabled' => true,
```

## 图片去水印接口

```php
'image_remove' => [
    'url'    => 'https://你的AI服务商.com/v1/inpaint',
    'method' => 'POST',
    'headers' => [
        'Authorization' => 'Bearer 你的API密钥',
        'Content-Type'  => 'application/json',
    ],
    'body_template' => [
        'image' => '{image_url}',  // 会被替换为实际上传图片URL
        'mask'  => '{mask_url}',   // 涂抹模式时的遮罩URL
    ],
    'result_field' => 'data.result_url',  // 响应JSON中结果字段路径
],
```

### 适配不同 API 格式

只需修改以下三项，无需改动业务代码：

1. **url** - API 请求地址
2. **body_template** - 请求体字段名（用 `{image_url}` 等占位符）
3. **result_field** - 响应中结果 URL 的 JSON 路径（点号分隔）

### 示例：某服务商返回格式

```json
{
  "code": 0,
  "data": {
    "output": "https://cdn.example.com/result.jpg"
  }
}
```

则设置 `'result_field' => 'data.output'`

## 短视频解析接口

```php
'video_parse' => [
    'url' => 'https://api.example.com/parse',
    'body_template' => [
        'url' => '{video_url}',
    ],
    'result_field' => 'data.video_url',
],
```

## 演示模式

`enabled => false` 时：
- 图片处理返回原图（模拟成功）
- 视频解析返回输入链接

便于本地开发测试，上线前务必开启真实 AI。

## 推荐 AI 服务

可对接任意支持 HTTP API 的去水印/图像修复服务，例如：
- 自建 Stable Diffusion Inpainting 服务
- 第三方图像处理 API
- 视频解析聚合 API

替换密钥与 URL 即可，核心代码 `AiService.php` 自动处理请求与响应解析。
