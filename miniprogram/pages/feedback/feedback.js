const app = getApp();
const api = require('../../config/api');

Page({
  data: { content: '', contact: '' },

  onLoad() { app.ensureLogin(); },

  onInput(e) { this.setData({ content: e.detail.value }); },
  onContact(e) { this.setData({ contact: e.detail.value }); },

  submit() {
    if (!this.data.content.trim()) {
      wx.showToast({ title: '请填写反馈内容', icon: 'none' });
      return;
    }
    api.submitFeedback(this.data.content, this.data.contact).then(() => {
      wx.showToast({ title: '提交成功', icon: 'success' });
      setTimeout(() => wx.navigateBack(), 1500);
    }).catch(err => {
      wx.showToast({ title: err.msg || '提交失败', icon: 'none' });
    });
  },
});
