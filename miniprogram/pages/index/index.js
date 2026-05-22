const app = getApp();
const adManager = require('../../ads/index');

Page({
  data: {
    userInfo: null,
    bannerAdUnitId: '',
    showAdModal: false,
    adModalTitle: '',
    adModalDesc: '',
  },

  onShow() {
    this.loadUser();
    adManager.showInterstitial();
    this.setData({ bannerAdUnitId: adManager.getBannerAdUnitId() });
  },

  loadUser() {
    app.ensureLogin().then(() => {
      return app.refreshUser();
    }).then(user => {
      this.setData({ userInfo: user });
    }).catch(() => {});
  },

  goImage() { wx.navigateTo({ url: '/pages/image/image' }); },
  goVideo() { wx.navigateTo({ url: '/pages/video/video' }); },
  goBrush() { wx.navigateTo({ url: '/pages/brush/brush' }); },
  goMember() { wx.navigateTo({ url: '/pages/member/member' }); },
  goHelp() { wx.navigateTo({ url: '/pages/help/help' }); },

  closeAdModal() { this.setData({ showAdModal: false }); },

  watchAd() {
    adManager.showRewardVideo(() => {
      this.setData({ showAdModal: false });
      app.refreshUser().then(user => {
        this.setData({ userInfo: user });
        wx.showToast({ title: '已获得额外次数', icon: 'success' });
      });
    }, (err) => {
      wx.showToast({ title: err.msg || '广告加载失败', icon: 'none' });
    });
  },
});
