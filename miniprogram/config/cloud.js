/**
 * 微信云托管配置
 * 部署后请在微信云托管控制台获取 envId 和 serviceName
 */
module.exports = {
  // 是否使用云托管 callContainer（true=云托管，false=普通 HTTPS）
  useCloudRun: true,

  // 云开发环境 ID（云托管控制台 → 环境 → 环境 ID）
  envId: 'your-cloud-env-id',

  // 云托管服务名称（创建服务时填写的名称）
  serviceName: 'watermark-api',

  // 若开启公网访问，可填公网域名，用于 wx.uploadFile 大文件上传（可选）
  // 不填则上传也走 callContainer（小文件）或需在控制台开启公网
  publicBaseUrl: '',
};
