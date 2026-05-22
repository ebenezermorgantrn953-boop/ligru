# AI 智能去水印 - 微信小程序全栈项目

轻奢科技风 UI · PHP7.4 原生后端 · 模块化广告与会员体系

## 项目结构

```
ai-watermark-remover/
├── miniprogram/          # 微信小程序前端
│   ├── ads/              # 广告模块（独立配置）
│   ├── config/           # API 地址配置
│   ├── pages/            # 全部页面
│   └── assets/           # 静态资源
├── backend/              # PHP 后端
│   ├── api/              # RESTful 接口
│   ├── config/           # 配置文件
│   ├── core/             # 核心业务类
│   ├── admin/            # 简易后台
│   └── database/         # SQL 安装脚本
├── docs/                 # 配置与部署文档
└── preview/              # UI 预览效果图
```

## 快速开始

### 1. 数据库

```bash
mysql -u root -p < backend/database/install.sql
```

### 2. 后端配置

编辑 `backend/config/config.php`：
- 数据库账号密码
- 站点域名 `site.url`
- 微信小程序 AppID / Secret

编辑 `backend/config/deepseek.php`（**推荐 DeepSeek**）：
- 填入 API Key：`https://platform.deepseek.com/api_keys`
- 确认 `backend/config/ai.php` 中 `provider` 为 `deepseek`

或使用自定义 AI：编辑 `backend/config/ai.php`，将 `provider` 改为 `custom` 并填入接口地址

### 3. 小程序配置

编辑 `miniprogram/config/api.js`：
```javascript
const BASE_URL = 'https://你的域名.com/api';
```

编辑 `miniprogram/project.config.json` 填入 AppID

### 4. 广告配置

编辑 `miniprogram/ads/config.js`，粘贴流量主广告单元 ID，将 `enabled` 改为 `true`

### 5. 部署

将 `backend` 目录上传至服务器 Web 根目录，确保 `uploads` 可写。详见 `docs/DEPLOY.md`

## 核心功能

| 功能 | 说明 |
|------|------|
| 图片去水印 | 相册/拍照上传，AI 智能清除 |
| 短视频去水印 | 粘贴平台链接解析 |
| 手动涂抹 | Canvas 手绘遮罩精准消除 |
| 次数管控 | 每日免费次数 + 广告激励 |
| 会员体系 | 月/季/年卡 + 微信支付 |
| 广告变现 | 激励视频/插屏/横幅 模块化 |

## 接口列表

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/login.php | POST | 微信登录 |
| /api/user.php | GET | 用户信息 |
| /api/upload.php | POST | 文件上传 |
| /api/process.php | POST | 图片/涂抹处理 |
| /api/video.php | POST | 视频解析 |
| /api/record.php | GET | 历史记录 |
| /api/ad_reward.php | POST | 广告奖励 |
| /api/pay.php | POST | 创建支付 |
| /api/feedback.php | POST | 问题反馈 |

## 后台管理

访问 `https://域名/admin/index.php`，默认密码见数据库 `wm_settings.admin_password`（默认 admin123，请修改）

## 详细文档

- [服务器部署教程](docs/DEPLOY.md)
- [微信云托管部署](docs/CLOUD_RUN.md) ⭐ 云托管用户
- [DeepSeek 接入指南](docs/DEEPSEEK.md) ⭐ 推荐
- [AI 接口配置](docs/AI_CONFIG.md)
- [广告接入指南](docs/AD_CONFIG.md)
- [会员价格配置](docs/VIP_CONFIG.md)

## 技术栈

- 前端：微信原生 WXML / WXSS / JS
- 后端：PHP 7.4+ / MySQL 5.7+
- 通信：RESTful JSON API

## 许可证

仅供学习与交流使用，请遵守相关法律法规及平台规则。
