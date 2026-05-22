# DeepSeek API 接入指南

本项目已内置 DeepSeek 接入，开箱即用。

## 一、获取 API Key

1. 打开 [DeepSeek 开放平台](https://platform.deepseek.com/api_keys)
2. 注册并创建 API Key
3. 复制密钥（格式 `sk-xxxxxxxx`）

## 二、配置

编辑 `backend/config/deepseek.php`：

```php
return [
    'api_key' => 'sk-你的真实密钥',
    'base_url' => 'https://api.deepseek.com',
    'model' => 'deepseek-v4-flash',  // 速度快、成本低
    // 'model' => 'deepseek-v4-pro', // 效果更好、稍慢
    'thinking' => ['type' => 'disabled'], // 关闭思考模式，响应更快
];
```

确认 `backend/config/ai.php`：

```php
'enabled' => true,
'provider' => 'deepseek',
```

## 三、各功能如何使用 DeepSeek

| 功能 | DeepSeek 作用 | 说明 |
|------|---------------|------|
| **短视频去水印** | ✅ 核心能力 | 从分享文案中智能提取链接、识别平台、生成标题 |
| **手动涂抹去水印** | 配合本地算法 | 用户涂抹遮罩 + PHP GD 图像修复（效果较好） |
| **图片智能去水印** | 配合本地算法 | 自动修复常见角落水印区域（GD 扩散修复） |

> **说明**：DeepSeek 官方 API 目前是**文本对话**接口，不能直接输出修好的图片。因此图片类功能采用「本地 GD 智能修复 + DeepSeek 短视频解析」的组合方案，既真实可用又控制成本。

## 四、服务器要求

图片处理需 PHP **GD 扩展**：

```bash
# 检查是否已安装
php -m | grep gd
```

未安装时（Ubuntu）：

```bash
sudo apt install php-gd
sudo systemctl restart php-fpm
```

## 五、费用参考

DeepSeek 按 Token 计费，短视频解析单次约几百 Token，成本极低。图片去水印主要走本地 GD，**不消耗 DeepSeek Token**。

可在 [定价页](https://api-docs.deepseek.com/quick_start/pricing) 查看最新价格。

## 六、切换回其他 AI

修改 `ai.php`：

```php
'provider' => 'custom',  // 使用自定义 HTTP 接口
// 或
'provider' => 'mock',     // 演示模式
'enabled' => false,
```

## 七、常见问题

**Q: 提示 API Key 无效？**  
A: 检查 `deepseek.php` 中密钥是否完整，账户是否有余额。

**Q: 短视频解析后无法下载？**  
A: DeepSeek 只能从文案中提取链接，**无水印直链**还需第三方视频解析 API。可在 `ai.php` 将 `provider` 改为 `custom` 并配置专业解析接口。

**Q: 图片去水印效果不理想？**  
A: 建议使用「手动涂抹」功能精准框选水印区域；或接入专业 inpainting API（`provider=custom`）。

**Q: 想同时用 DeepSeek 和专业图片 API？**  
A: 可将 `provider` 设为 `custom` 处理图片，视频解析单独在 `DeepseekAi::parseVideo` 中调用（需小幅改代码），或联系我们定制。
