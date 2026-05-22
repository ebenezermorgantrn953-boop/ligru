# 微信云托管部署与压力测试指南

## 一、部署步骤

### 1. 准备代码包

**⚠️ 必须上传「云托管部署包」，不要上传整个项目 zip！**

- ✅ 正确：压缩包**根目录**直接包含 `Dockerfile`、`index.php`、`api/` 等
- ❌ 错误：上传 `AI智能去水印-完整项目.zip`（Dockerfile 在 `backend/` 子目录里会报错）

桌面已提供：**`云托管部署包-请上传此文件.zip`** 或 **`cloud-run-deploy.zip`**

> 本地上传限制约 2MB；也可手动只 zip `backend` 文件夹内的文件（不是外层文件夹本身）。

### 2. 创建云托管服务

1. 打开 [微信云托管控制台](https://cloud.weixin.qq.com/cloudrun)
2. 选择云开发环境 → **新建服务**
3. 服务名称填：`watermark-api`（与 `miniprogram/config/cloud.js` 一致）
4. 开启 **允许公网访问**（建议，便于上传图片与压测）

### 3. 发布版本

- 上传方式：**本地上传** → 选择 `backend` 文件夹
- 端口：必须填 **8080**（不是 80！Dockerfile 已改为监听 8080）
- 点击发布，等待构建约 3–5 分钟

### 4. 绑定 MySQL

云托管控制台 → 服务 → **数据库** → 绑定云开发 MySQL

绑定后自动注入环境变量：
- `MYSQL_ADDRESS`
- `MYSQL_USERNAME`
- `MYSQL_PASSWORD`
- `MYSQL_DATABASE`

首次需导入表结构：在 MySQL 控制台执行 `database/install.sql`

### 5. 配置环境变量

服务 → **服务设置** → **环境变量**：

| 变量名 | 说明 |
|--------|------|
| `WX_CLOUD_RUN` | `1` |
| `WX_APPID` | 小程序 AppID |
| `WX_APPSECRET` | 小程序 Secret |
| `DEEPSEEK_API_KEY` | DeepSeek 密钥（推荐） |
| `CONTAINER_PUBLIC_URL` | 公网访问地址（复制控制台域名） |
| `STRESS_TEST_KEY` | 压测密钥（自行设置复杂字符串） |
| `APP_DEBUG` | `0` 生产关闭 |

### 6. 扩缩容建议（压测/上线）

| 场景 | minNum | maxNum | CPU | 内存 |
|------|--------|--------|-----|------|
| 开发测试 | 0 | 2 | 0.25 | 0.5G |
| 正式运营 | 1 | 10 | 0.5 | 1G |
| 活动高峰 | 2 | 20 | 1 | 2G |

`container.config.json` 已含默认配置，可在控制台覆盖。

---

## 二、小程序对接

编辑 `miniprogram/config/cloud.js`：

```javascript
module.exports = {
  useCloudRun: true,
  envId: 'prod-xxxx',           // 云开发环境 ID
  serviceName: 'watermark-api', // 服务名
  publicBaseUrl: 'https://xxx.ap-shanghai.app.tcloudbase.com', // 公网域名，上传用
};
```

`app.json` 需开启云开发（已配置 `"cloud": true`）。

开发者工具：**云开发** → 选择对应环境 → 编译运行。

---

## 三、压力测试

### 方式 A：PowerShell（推荐 Windows）

```powershell
cd tests\stress

# 健康检查压测（替换为你的公网域名）
.\stress_test.ps1 -BaseUrl "https://你的云托管域名" -Concurrent 20 -Requests 200 -Endpoint health

# 数据库压测
.\stress_test.ps1 -BaseUrl "https://你的域名" -Concurrent 10 -Requests 50 -Endpoint db -StressKey "你的压测密钥"
```

### 方式 B：PHP CLI

```bash
php tests/stress/stress_test.php https://你的域名 20 200 health
```

### 方式 C：云托管控制台

控制台 → 服务 → **监控** → 查看 QPS、CPU、内存、延迟

### 压测接口说明

| 接口 | 说明 | 鉴权 |
|------|------|------|
| `/api/health.php` | 健康检查+DB探测 | 无 |
| `/api/stress.php?action=ping` | 纯 PHP 响应 | 需 key |
| `/api/stress.php?action=db` | 数据库查询 | 需 key |

### 参考指标（单实例 0.5C1G）

| 接口 | 预期 P95 |
|------|----------|
| health | < 100ms |
| ping | < 50ms |
| db | < 200ms |

> 视频解析/图片处理接口因调用 DeepSeek 或 GD，单次 1–10s，**不要用高并发压测**，避免 Token 费用激增。

---

## 四、常见问题

**Q: Dockerfile 不合法？**  
A: 确保上传目录根下有 `Dockerfile`，且路径为 `backend/Dockerfile`。

**Q: 数据库连接失败？**  
A: 检查 MySQL 是否已绑定、是否已导入 `install.sql`。

**Q: 小程序 callContainer 失败？**  
A: 检查 `envId`、`serviceName`、基础库 ≥ 2.23、已 `wx.cloud.init`。

**Q: 上传图片失败？**  
A: 配置 `publicBaseUrl` 并开启公网访问，或依赖 base64 上传（小图）。

**Q: 压测 429？**  
A: 压测接口使用 `stress.php`+密钥，或临时调高 `config.php` 中 `rate_limit`。
