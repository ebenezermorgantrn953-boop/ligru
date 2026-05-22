# 会员价格修改方法

## 方式一：数据库修改（推荐）

```sql
-- 修改价格（单位：元）
UPDATE wm_settings SET svalue='19.90' WHERE skey='vip_month_price';
UPDATE wm_settings SET svalue='49.90' WHERE skey='vip_quarter_price';
UPDATE wm_settings SET svalue='99.00' WHERE skey='vip_year_price';

-- 修改天数
UPDATE wm_settings SET svalue='30' WHERE skey='vip_month_days';
UPDATE wm_settings SET svalue='90' WHERE skey='vip_quarter_days';
UPDATE wm_settings SET svalue='365' WHERE skey='vip_year_days';

-- 修改每日免费次数
UPDATE wm_settings SET svalue='3' WHERE skey='daily_free_count';
```

修改后小程序重新打开即可获取新价格（通过 `/api/user.php` 的 `plans` 字段）。

## 方式二：SQL 初始化值

安装时 `install.sql` 已写入默认值：

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| vip_month_price | 9.90 | 月卡价格 |
| vip_quarter_price | 24.90 | 季卡价格 |
| vip_year_price | 68.00 | 年卡价格 |
| vip_month_days | 30 | 月卡天数 |
| vip_quarter_days | 90 | 季卡天数 |
| vip_year_days | 365 | 年卡天数 |
| daily_free_count | 5 | 每日免费次数 |

## 微信支付配置

编辑 `backend/config/config.php` 或 `wechat.php`：

```php
'mch_id' => '你的商户号',
'pay_key' => 'APIv2密钥',
```

支付回调地址：`https://你的域名/api/pay_notify.php`

需在微信商户平台配置此回调 URL。

## 手动开通会员（测试用）

```sql
UPDATE wm_users SET is_vip=1, vip_expire='2026-12-31 23:59:59' WHERE id=1;
```

## 套餐类型代码

| plan_type | 对应套餐 |
|-----------|----------|
| month | 月卡 |
| quarter | 季卡 |
| year | 年卡 |

前端 `pages/member/member` 页面自动展示数据库中的价格。
