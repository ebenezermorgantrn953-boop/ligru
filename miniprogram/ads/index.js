/**
 * 广告管理模块 - 模块化封装，不阻塞主功能
 */
const localConfig = require('./config');
const api = require('../config/api');

let app = null;
let serverConfig = {};
let interstitialAd = null;
let rewardVideoAd = null;
let lastInterstitialTime = 0;
let todayInterstitialCount = 0;

const AdManager = {
  init(application) {
    app = application;
    this._loadLocalConfig();
    this._initAds();
  },

  updateConfig(configList) {
    if (!configList || !configList.length) return;
    configList.forEach(item => {
      serverConfig[item.ad_key] = item;
    });
    this._initAds();
  },

  _loadLocalConfig() {
    serverConfig = {
      reward_video: { enabled: localConfig.rewardVideo.enabled ? 1 : 0, ad_unit_id: localConfig.rewardVideo.adUnitId, reward_count: 2 },
      interstitial_home: { enabled: localConfig.interstitial.enabled ? 1 : 0, ad_unit_id: localConfig.interstitial.adUnitId, show_interval: localConfig.interstitial.showInterval / 1000 },
      banner_bottom: { enabled: localConfig.banner.enabled ? 1 : 0, ad_unit_id: localConfig.banner.adUnitId },
    };
  },

  _isEnabled(key) {
    const cfg = serverConfig[key];
    return cfg && cfg.enabled == 1 && cfg.ad_unit_id && !cfg.ad_unit_id.includes('xxxx');
  },

  _initAds() {
    if (this._isEnabled('interstitial_home') && wx.createInterstitialAd) {
      try {
        interstitialAd = wx.createInterstitialAd({
          adUnitId: serverConfig.interstitial_home.ad_unit_id,
        });
        interstitialAd.onError(() => {});
      } catch (e) {}
    }
    if (this._isEnabled('reward_video') && wx.createRewardedVideoAd) {
      try {
        rewardVideoAd = wx.createRewardedVideoAd({
          adUnitId: serverConfig.reward_video.ad_unit_id,
        });
      } catch (e) {}
    }
  },

  /** 展示激励视频，观看完成发放奖励 */
  showRewardVideo(onSuccess, onFail) {
    if (!this._isEnabled('reward_video') || !rewardVideoAd) {
      // 广告未配置时，开发模式直接发放奖励
      api.adReward().then(onSuccess).catch(onFail);
      return;
    }

    const handler = (res) => {
      if (res && res.isEnded) {
        api.adReward().then(onSuccess).catch(onFail);
      } else {
        onFail && onFail({ msg: '请观看完整广告' });
      }
    };

    rewardVideoAd.offClose();
    rewardVideoAd.onClose(handler);
    rewardVideoAd.show().catch(() => {
      rewardVideoAd.load().then(() => rewardVideoAd.show()).catch(onFail);
    });
  },

  /** 展示插屏广告(带频率控制) */
  showInterstitial() {
    if (!this._isEnabled('interstitial_home') || !interstitialAd) return;

    const cfg = serverConfig.interstitial_home;
    const interval = (cfg.show_interval || 120) * 1000;
    const now = Date.now();

    if (now - lastInterstitialTime < interval) return;
    if (todayInterstitialCount >= (localConfig.interstitial.maxDaily || 8)) return;

    interstitialAd.show().then(() => {
      lastInterstitialTime = now;
      todayInterstitialCount++;
    }).catch(() => {});
  },

  /** 获取横幅广告单元ID(供页面 ad 组件使用) */
  getBannerAdUnitId() {
    if (this._isEnabled('banner_bottom')) {
      return serverConfig.banner_bottom.ad_unit_id;
    }
    return '';
  },

  /** 次数不足弹窗 - 引导看广告或开通会员 */
  showNoCountModal(page) {
    page.setData({
      showAdModal: true,
      adModalTitle: '今日次数已用完',
      adModalDesc: '观看短视频广告可获得额外使用次数，或开通会员享无限次使用',
    });
  },
};

module.exports = AdManager;
