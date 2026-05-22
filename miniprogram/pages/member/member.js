const app = getApp();
const api = require('../../config/api');

Page({
  data: {
    selectedPlan: 'quarter',
    plans: {
      month: { price: '9.90', days: 30 },
      quarter: { price: '24.90', days: 90 },
      year: { price: '68.00', days: 365 },
    },
  },

  onLoad() {
    app.ensureLogin().then(() => app.refreshUser()).then(user => {
      if (user.plans) {
        this.setData({ plans: user.plans });
      }
    });
  },

  selectPlan(e) {
    this.setData({ selectedPlan: e.currentTarget.dataset.plan });
  },

  doPay() {
    const plan = this.data.selectedPlan;
    wx.showLoading({ title: '创建订单' });
    api.createPayOrder(plan).then(data => {
      wx.hideLoading();
      if (data.dev_mode) {
        wx.showModal({
          title: '提示',
          content: '请在后端配置微信支付参数后使用真实支付。开发模式可手动在数据库开通会员。',
          showCancel: false,
        });
        return;
      }
      if (data.pay_params) {
        wx.requestPayment({
          ...data.pay_params,
          success: () => {
            app.refreshUser();
            wx.showToast({ title: '开通成功', icon: 'success' });
            setTimeout(() => wx.navigateBack(), 1500);
          },
          fail: () => wx.showToast({ title: '支付取消', icon: 'none' }),
        });
      }
    }).catch(err => {
      wx.hideLoading();
      wx.showToast({ title: err.msg || '下单失败', icon: 'none' });
    });
  },
});
