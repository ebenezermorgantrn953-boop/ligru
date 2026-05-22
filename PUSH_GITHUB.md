# 推送到 GitHub 仓库

目标仓库：`https://github.com/ebenezermorgantrn953-boop/ligru`

## 云托管绑定 GitHub 时注意

- **端口填 `8080`**（不要填 80）
- **Dockerfile 路径**：`Dockerfile`（在仓库根目录）
- 环境变量在云托管控制台配置 `DEEPSEEK_API_KEY`、`WX_APPID` 等

## 本地首次推送

```bash
cd ai-watermark-remover
git init
git add .
git commit -m "feat: AI智能去水印 小程序+PHP云托管"
git branch -M main
git remote add origin https://github.com/ebenezermorgantrn953-boop/ligru.git
git push -u origin main
```

## 密钥说明

`backend/config/deepseek.php` 已加入 `.gitignore`，不会上传。
请在云托管环境变量设置 `DEEPSEEK_API_KEY`。
