-- AI智能去水印 - 数据库初始化脚本
-- MySQL 5.7+ / MariaDB 10.2+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `ai_watermark` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ai_watermark`;

-- 用户表
DROP TABLE IF EXISTS `wm_users`;
CREATE TABLE `wm_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `openid` varchar(64) NOT NULL COMMENT '微信openid',
  `unionid` varchar(64) DEFAULT '' COMMENT '微信unionid',
  `nickname` varchar(64) DEFAULT '微信用户',
  `avatar` varchar(512) DEFAULT '',
  `session_key` varchar(128) DEFAULT '',
  `token` varchar(64) DEFAULT '' COMMENT '登录令牌',
  `is_vip` tinyint(1) DEFAULT 0 COMMENT '是否会员',
  `vip_expire` datetime DEFAULT NULL COMMENT '会员到期时间',
  `daily_free` int(11) DEFAULT 5 COMMENT '每日免费次数',
  `extra_count` int(11) DEFAULT 0 COMMENT '广告奖励额外次数',
  `total_use` int(11) DEFAULT 0 COMMENT '累计使用次数',
  `last_use_date` date DEFAULT NULL COMMENT '上次使用日期',
  `today_use` int(11) DEFAULT 0 COMMENT '今日已用次数',
  `status` tinyint(1) DEFAULT 1 COMMENT '1正常 0禁用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid` (`openid`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 处理记录表
DROP TABLE IF EXISTS `wm_records`;
CREATE TABLE `wm_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'image/video/brush',
  `source_url` varchar(1024) DEFAULT '' COMMENT '原始资源',
  `result_url` varchar(1024) DEFAULT '' COMMENT '处理结果',
  `status` tinyint(1) DEFAULT 0 COMMENT '0处理中 1成功 2失败',
  `remark` varchar(255) DEFAULT '',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材处理记录';

-- 会员订单表
DROP TABLE IF EXISTS `wm_orders`;
CREATE TABLE `wm_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `user_id` int(11) unsigned NOT NULL,
  `plan_type` varchar(20) NOT NULL COMMENT 'month/quarter/year',
  `amount` decimal(10,2) NOT NULL COMMENT '金额(元)',
  `days` int(11) NOT NULL COMMENT '会员天数',
  `pay_status` tinyint(1) DEFAULT 0 COMMENT '0待支付 1已支付 2已取消',
  `transaction_id` varchar(64) DEFAULT '' COMMENT '微信支付单号',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员充值订单';

-- 广告配置表
DROP TABLE IF EXISTS `wm_ad_config`;
CREATE TABLE `wm_ad_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ad_key` varchar(32) NOT NULL COMMENT '广告位标识',
  `ad_name` varchar(64) DEFAULT '',
  `enabled` tinyint(1) DEFAULT 0 COMMENT '是否启用',
  `ad_unit_id` varchar(64) DEFAULT '' COMMENT '广告单元ID',
  `show_interval` int(11) DEFAULT 60 COMMENT '展示间隔(秒)',
  `daily_limit` int(11) DEFAULT 10 COMMENT '每日上限',
  `reward_count` int(11) DEFAULT 2 COMMENT '激励奖励次数',
  `extra_config` text COMMENT 'JSON扩展配置',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ad_key` (`ad_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告配置';

-- 每日统计表
DROP TABLE IF EXISTS `wm_daily_stats`;
CREATE TABLE `wm_daily_stats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL,
  `pv` int(11) DEFAULT 0 COMMENT '访问量',
  `new_users` int(11) DEFAULT 0,
  `image_count` int(11) DEFAULT 0,
  `video_count` int(11) DEFAULT 0,
  `brush_count` int(11) DEFAULT 0,
  `ad_reward_count` int(11) DEFAULT 0,
  `order_count` int(11) DEFAULT 0,
  `order_amount` decimal(10,2) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日统计';

-- 反馈表
DROP TABLE IF EXISTS `wm_feedback`;
CREATE TABLE `wm_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT 0,
  `content` text NOT NULL,
  `contact` varchar(64) DEFAULT '',
  `status` tinyint(1) DEFAULT 0 COMMENT '0待处理 1已处理',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户反馈';

-- 系统配置表
DROP TABLE IF EXISTS `wm_settings`;
CREATE TABLE `wm_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `skey` varchar(64) NOT NULL,
  `svalue` text,
  `remark` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_skey` (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';

-- 初始化广告配置
INSERT INTO `wm_ad_config` (`ad_key`, `ad_name`, `enabled`, `ad_unit_id`, `show_interval`, `daily_limit`, `reward_count`) VALUES
('reward_video', '激励视频-次数不足', 0, '', 0, 20, 2),
('interstitial_home', '首页插屏广告', 0, '', 120, 8, 0),
('banner_bottom', '底部横幅广告', 0, '', 0, 0, 0);

-- 初始化系统配置
INSERT INTO `wm_settings` (`skey`, `svalue`, `remark`) VALUES
('daily_free_count', '5', '每日免费次数'),
('vip_month_price', '9.90', '月卡价格'),
('vip_quarter_price', '24.90', '季卡价格'),
('vip_year_price', '68.00', '年卡价格'),
('vip_month_days', '30', '月卡天数'),
('vip_quarter_days', '90', '季卡天数'),
('vip_year_days', '365', '年卡天数'),
('site_name', 'AI智能去水印', '站点名称'),
('admin_password', 'admin123', '后台登录密码(请修改)');

SET FOREIGN_KEY_CHECKS = 1;
