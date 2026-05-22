const app = getApp();

Page({
  data: { userInfo: {} },

  onShow() {
    const user = app.globalData.userInfo;
    if (user) {
      this.setData({ userInfo: user });
    } else {
      app.refreshUser().then(u => this.setData({ userInfo: u })).catch(() => {});
    }
  },

  onLogin() {
    if (!wx.getStorageSync('token')) {
      app.doLogin().then(user => this.setData({ userInfo: user }));
    }
  },

  goMember() { wx.navigateTo({ url: '/pages/member/member' }); },
  goHistory() { wx.switchTab({ url: '/pages/history/history' }); },
  goHelp() { wx.navigateTo({ url: '/pages/help/help' }); },
  goFeedback() { wx.navigateTo({ url: '/pages/feedback/feedback' }); },
});
