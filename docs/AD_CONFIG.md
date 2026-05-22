# 广告 ID 配置步骤

## 前置条件

1. 小程序已发布或体验版
2. 已在 [微信公众平台](https://mp.weixin.qq.com) 开通 **流量主**
3. 已创建对应广告位并获取 **广告单元 ID**

## 配置文件

`miniprogram/ads/config.js`

## 一、激励视频广告（次数不足时）

```javascript
rewardVideo: {
  enabled: true,  // 改为 true 开启
  adUnitId: 'adunit-xxxxxxxxxx',  // 粘贴激励视频广告单元ID
},
```

**触发场景：**
- 每日免费次数用完
- 用户点击「看广告领次数」

**奖励逻辑：** 观看完整视频后调用 `/api/ad_reward.php` 增加次数（默认 +2 次，可在数据库 `wm_ad_config` 表修改）

## 二、插屏广告（首页空闲展示）

```javascript
interstitial: {
  enabled: true,
  adUnitId: 'adunit-xxxxxxxxxx',
  showInterval: 120000,  // 两次展示最小间隔(毫秒)，建议 ≥ 60000
  maxDaily: 8,           // 每日最多展示次数
},
```

**触发场景：** 首页 `onShow` 时自动尝试展示（带频率控制，不阻塞功能）

## 三、底部横幅广告

```javascript
banner: {
  enabled: true,
  adUnitId: 'adunit-xxxxxxxxxx',
},
```

**展示位置：** 首页底部 `<ad>` 组件

## 后端数据库配置（可选）

也可在数据库 `wm_ad_config` 表中管理：

```sql
UPDATE wm_ad_config SET enabled=1, ad_unit_id='你的广告ID' WHERE ad_key='reward_video';
```

小程序启动时会从 `/api/user.php` 拉取广告配置并覆盖本地设置。

## 风控建议

| 配置项 | 建议值 |
|--------|--------|
| 插屏间隔 | ≥ 60 秒 |
| 每日插屏上限 | 5-10 次 |
| 激励视频 | 仅在次数不足时弹出，不强制 |

## 开发调试

广告未配置时（`enabled: false` 或 ID 含 xxxx）：
- 激励视频：直接发放奖励次数（便于测试）
- 插屏/横幅：不展示

## 上线检查清单

- [ ] 三个广告位 ID 已替换
- [ ] `enabled` 已设为 `true`
- [ ] 插屏间隔符合平台规范
- [ ] 激励广告有明确用户利益说明（获得次数）
- [ ] 广告不遮挡核心操作按钮
