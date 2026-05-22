const app = getApp();
const api = require('../../config/api');
const adManager = require('../../ads/index');

Page({
  data: { videoUrl: '', processing: false, result: null, showAdModal: false },

  onLoad() { app.ensureLogin(); },

  onInput(e) { this.setData({ videoUrl: e.detail.value }); },

  pasteUrl() {
    wx.getClipboardData({
      success: (res) => {
        if (res.data) this.setData({ videoUrl: res.data });
      },
    });
  },

  startParse() {
    const url = this.data.videoUrl.trim();
    if (!url) {
      wx.showToast({ title: '请输入视频链接', icon: 'none' });
      return;
    }
    this.setData({ processing: true, result: null });
    api.parseVideo(url).then(data => {
      this.setData({ processing: false, result: data.result });
      app.refreshUser();
    }).catch(err => {
      this.setData({ processing: false });
      if (err.needAd) adManager.showNoCountModal(this);
      else wx.showToast({ title: err.msg || '解析失败', icon: 'none' });
    });
  },

  copyUrl() {
    const url = this.data.result.video_url;
    if (url) wx.setClipboardData({ data: url, success: () => wx.showToast({ title: '已复制' }) });
  },

  saveVideo() {
    const url = this.data.result.video_url;
    if (!url) return;
    wx.showLoading({ title: '下载中' });
    wx.downloadFile({
      url,
      success: (res) => {
        wx.saveVideoToPhotosAlbum({
          filePath: res.tempFilePath,
          success: () => { wx.hideLoading(); wx.showToast({ title: '已保存' }); },
          fail: () => { wx.hideLoading(); wx.showToast({ title: '保存失败', icon: 'none' }); },
        });
      },
      fail: () => { wx.hideLoading(); wx.showToast({ title: '下载失败', icon: 'none' }); },
    });
  },

  closeModal() { this.setData({ showAdModal: false }); },
  watchAd() {
    adManager.showRewardVideo(() => {
      this.setData({ showAdModal: false });
      app.refreshUser();
    });
  },
});
