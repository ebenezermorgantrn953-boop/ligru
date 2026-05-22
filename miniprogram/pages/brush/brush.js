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

  ctx: null,
  canvas: null,
  hasDrawn: false,

  onLoad() { app.ensureLogin(); },

  chooseImage() {
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const path = res.tempFiles[0].tempFilePath;
        this.setData({ imageUrl: path, resultUrl: '' });
        api.uploadFile(path, 'image').then(data => {
          this.setData({ serverUrl: data.url });
          this.$nextTick(() => this.initCanvas());
        });
      },
    });
  },

  $nextTick(fn) { setTimeout(fn, 300); },

  initCanvas() {
    const query = wx.createSelectorQuery();
    query.select('#brushCanvas').fields({ node: true, size: true }).exec((res) => {
      if (!res[0]) return;
      const canvas = res[0].node;
      const ctx = canvas.getContext('2d');
      const dpr = wx.getSystemInfoSync().pixelRatio;
      canvas.width = res[0].width * dpr;
      canvas.height = res[0].height * dpr;
      ctx.scale(dpr, dpr);
      ctx.strokeStyle = 'rgba(91, 108, 248, 0.6)';
      ctx.lineWidth = 30;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      this.canvas = canvas;
      this.ctx = ctx;
      this.canvasW = res[0].width;
      this.canvasH = res[0].height;
    });
  },

  onTouchStart(e) {
    if (!this.ctx) return;
    const t = e.touches[0];
    this.lastX = t.x;
    this.lastY = t.y;
    this.ctx.beginPath();
    this.ctx.moveTo(t.x, t.y);
  },

  onTouchMove(e) {
    if (!this.ctx) return;
    const t = e.touches[0];
    this.ctx.lineTo(t.x, t.y);
    this.ctx.stroke();
    this.lastX = t.x;
    this.lastY = t.y;
    this.hasDrawn = true;
  },

  onTouchEnd() {},

  clearMask() {
    if (this.ctx && this.canvas) {
      this.ctx.clearRect(0, 0, this.canvasW, this.canvasH);
      this.hasDrawn = false;
    }
  },

  startProcess() {
    if (!this.hasDrawn) {
      wx.showToast({ title: '请先涂抹水印区域', icon: 'none' });
      return;
    }
    if (!this.data.serverUrl) return;

    this.setData({ processing: true });
    wx.canvasToTempFilePath({
      canvas: this.canvas,
      success: (res) => {
        api.uploadFile(res.tempFilePath, 'mask').then(maskData => {
          return api.processBrush(this.data.serverUrl, maskData.url);
        }).then(data => {
          this.setData({ processing: false, resultUrl: data.result_url });
          app.refreshUser();
        }).catch(err => {
          this.setData({ processing: false });
          if (err.needAd) adManager.showNoCountModal(this);
          else wx.showToast({ title: err.msg || '处理失败', icon: 'none' });
        });
      },
      fail: () => {
        this.setData({ processing: false });
        wx.showToast({ title: '遮罩生成失败', icon: 'none' });
      },
    }, this);
  },

  saveImage() {
    wx.downloadFile({
      url: this.data.resultUrl,
      success: (res) => {
        wx.saveImageToPhotosAlbum({
          filePath: res.tempFilePath,
          success: () => wx.showToast({ title: '已保存' }),
        });
      },
    });
  },
});
