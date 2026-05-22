const app = getApp();
const api = require('../../config/api');

Page({
  data: { list: [], loading: true },

  onShow() {
    app.ensureLogin().then(() => this.loadRecords());
  },

  loadRecords() {
    this.setData({ loading: true });
    api.getRecords().then(data => {
      this.setData({ list: data.list || [], loading: false });
    }).catch(() => {
      this.setData({ loading: false });
    });
  },

  viewResult(e) {
    const item = e.currentTarget.dataset.item;
    let url = item.result_url;
    if (item.type === 'video') {
      try {
        const r = JSON.parse(item.result_url);
        url = r.video_url;
      } catch (ex) {}
      if (url) wx.setClipboardData({ data: url });
      return;
    }
    if (url) wx.previewImage({ urls: [url] });
  },
});
