/**
 * 广告配置文件 - 粘贴微信流量主广告单元ID即可生效
 * 所有开关独立可控，修改 enabled 为 true 开启对应广告位
 */
module.exports = {
  // 流量主开通后，将下方 adUnitId 替换为真实广告单元ID

  /** 激励视频广告 - 次数不足时触发 */
  rewardVideo: {
    enabled: false,  // 改为 true 开启
    adUnitId: 'adunit-xxxxxxxxxxxxxxxx',  // 粘贴激励视频广告ID
  },

  /** 插屏广告 - 首页切换/空闲展示 */
  interstitial: {
    enabled: false,
    adUnitId: 'adunit-xxxxxxxxxxxxxxxx',
    showInterval: 120000,  // 展示间隔(毫秒)，建议≥60秒
    maxDaily: 8,           // 每日最大展示次数
  },

  /** 底部横幅广告 - 页面底部常驻 */
  banner: {
    enabled: false,
    adUnitId: 'adunit-xxxxxxxxxxxxxxxx',
  },
};
