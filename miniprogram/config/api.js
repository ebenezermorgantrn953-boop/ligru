/**
 * API 配置
 * 云托管模式：useCloudRun=true，只需配置 config/cloud.js
 * HTTPS 模式：useCloudRun=false，填写 baseUrl
 */
const cloudCfg = require('./cloud');
const BASE_URL = cloudCfg.useCloudRun ? '' : 'https://your-domain.com/api';

const request = require('../utils/request');

module.exports = {
  baseUrl: BASE_URL,

  login(code, userInfo) {
    return request.post('/login.php', {
      code,
      nickName: userInfo.nickName || '',
      avatarUrl: userInfo.avatarUrl || '',
    });
  },

  getUserInfo() {
    return request.get('/user.php');
  },

  uploadFile(filePath, type = 'image') {
    return request.upload('/upload.php', filePath, { type });
  },

  processImage(imageUrl) {
    return request.post('/process.php', { type: 'image', image_url: imageUrl });
  },

  processBrush(imageUrl, maskUrl) {
    return request.post('/process.php', { type: 'brush', image_url: imageUrl, mask_url: maskUrl });
  },

  parseVideo(url) {
    return request.post('/video.php', { url });
  },

  getRecords(page = 1) {
    return request.get('/record.php', { page });
  },

  adReward() {
    return request.post('/ad_reward.php');
  },

  createPayOrder(planType) {
    return request.post('/pay.php', { plan_type: planType });
  },

  submitFeedback(content, contact) {
    return request.post('/feedback.php', { content, contact });
  },
};
