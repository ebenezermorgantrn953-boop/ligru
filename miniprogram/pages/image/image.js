const app = getApp();
const api = require('../../config/api');
const adManager = require('../../ads/index');

Page({
  data: {
    imageUrl: '',
    serverUrl: '',
    resultUrl: '',
    processing: false,
    showAdModal: false,
  },

  onLoad() {
    app.ensureLogin();
  },

  chooseImage() {
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const path = res.tempFiles[0].tempFilePath;
        this.setData({ imageUrl: path, resultUrl: '', serverUrl: '' });
        this.uploadImage(path);
      },
    });
  },

  uploadImage(path) {
    wx.showLoading({ title: '上传中' });
    api.uploadFile(path, 'image').then(data => {
      wx.hideLoading();
      this.setData({ serverUrl: data.url });
    }).catch(err => {
      wx.hideLoading();
      wx.showToast({ title: err.msg || '上传失败', icon: 'none' });
    });
  },

  startProcess() {
    if (!this.data.serverUrl) {
      wx.showToast({ title: '请等待图片上传完成', icon: 'none' });
      return;
    }
    this.setData({ processing: true });
    api.processImage(this.data.serverUrl).then(data => {
      this.setData({ processing: false, resultUrl: data.result_url });
      app.refreshUser();
      wx.showToast({ title: '处理完成', icon: 'success' });
    }).catch(err => {
      this.setData({ processing: false });
      if (err.needAd) {
        adManager.showNoCountModal(this);
      } else {
        wx.showToast({ title: err.msg || '处理失败', icon: 'none' });
      }
    });
  },

  previewResult() {
    wx.previewImage({ urls: [this.data.resultUrl] });
  },

  saveImage() {
    wx.downloadFile({
      url: this.data.resultUrl,
      success: (res) => {
        wx.saveImageToPhotosAlbum({
          filePath: res.tempFilePath,
          success: () => wx.showToast({ title: '已保存到相册' }),
          fail: () => wx.showToast({ title: '保存失败，请授权相册', icon: 'none' }),
        });
      },
    });
  },

  reset() {
    this.setData({ imageUrl: '', serverUrl: '', resultUrl: '' });
  },

  closeModal() { this.setData({ showAdModal: false }); },

  watchAd() {
    adManager.showRewardVideo(() => {
      this.setData({ showAdModal: false });
      app.refreshUser();
      wx.showToast({ title: '已获得额外次数', icon: 'success' });
    });
  },
});
