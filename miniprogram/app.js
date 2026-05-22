// app.js
const api = require('./config/api');
const cloudCfg = require('./config/cloud');
const adManager = require('./ads/index');

App({
  globalData: {
    userInfo: null,
    adConfig: [],
    useCloudRun: cloudCfg.useCloudRun,
  },

  onLaunch() {
    if (cloudCfg.useCloudRun && wx.cloud) {
      wx.cloud.init({
        env: cloudCfg.envId,
        traceUser: true,
      });
    }
    this.checkLogin();
    adManager.init(this);
  },

  checkLogin() {
    const token = wx.getStorageSync('token');
    if (token) {
      api.getUserInfo().then(res => {
        this.globalData.userInfo = res;
      }).catch(() => {
        wx.removeStorageSync('token');
      });
    }
  },

  /** 微信授权登录 */
  doLogin() {
    return new Promise((resolve, reject) => {
      wx.login({
        success: (loginRes) => {
          wx.getUserProfile({
            desc: '用于完善用户资料',
            success: (profileRes) => {
              api.login(loginRes.code, profileRes.userInfo).then(user => {
                wx.setStorageSync('token', user.token);
                this.globalData.userInfo = user;
                resolve(user);
              }).catch(reject);
            },
            fail: () => {
              // 用户拒绝授权，使用基础登录
              api.login(loginRes.code, {}).then(user => {
                wx.setStorageSync('token', user.token);
                this.globalData.userInfo = user;
                resolve(user);
              }).catch(reject);
            }
          });
        },
        fail: reject
      });
    });
  },

  /** 确保已登录 */
  ensureLogin() {
    if (this.globalData.userInfo && wx.getStorageSync('token')) {
      return Promise.resolve(this.globalData.userInfo);
    }
    return this.doLogin();
  },

  /** 刷新用户信息 */
  refreshUser() {
    return api.getUserInfo().then(res => {
      this.globalData.userInfo = res;
      if (res.ad_config) {
        this.globalData.adConfig = res.ad_config;
        adManager.updateConfig(res.ad_config);
      }
      return res;
    });
  }
});
