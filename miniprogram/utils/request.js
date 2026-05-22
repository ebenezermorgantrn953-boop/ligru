/**
 * 网络请求 - 自动适配微信云托管 callContainer / 普通 HTTPS
 */
const cloudCfg = require('../config/cloud');
const apiCfg = require('../config/api');

function getHeaders(extra = {}) {
  const token = wx.getStorageSync('token');
  const headers = {
    'Content-Type': 'application/json',
    'X-Token': token || '',
    ...extra,
  };
  if (cloudCfg.useCloudRun) {
    headers['X-WX-SERVICE'] = cloudCfg.serviceName;
  }
  return headers;
}

function parseResponse(res) {
  const data = res.data;
  if (typeof data === 'string') {
    try {
      return JSON.parse(data);
    } catch (e) {
      return { code: -1, msg: '响应解析失败' };
    }
  }
  return data;
}

function handleResult(data, resolve, reject) {
  if (data && data.code === 0) {
    resolve(data.data);
  } else if (data && data.code === 1001) {
    reject({ needAd: true, msg: data.msg });
  } else if (data && data.code === 401) {
    wx.removeStorageSync('token');
    reject({ needLogin: true, msg: data.msg });
  } else {
    reject({ msg: (data && data.msg) || '请求失败' });
  }
}

/** 云托管 callContainer */
function cloudRequest(path, method, data) {
  return new Promise((resolve, reject) => {
    if (!wx.cloud || !wx.cloud.callContainer) {
      reject({ msg: '请使用基础库 2.23+ 并开通云开发' });
      return;
    }
    wx.cloud.callContainer({
      config: { env: cloudCfg.envId },
      path: path.startsWith('/') ? path : '/' + path,
      method,
      data,
      header: getHeaders(),
      timeout: 15000,
      success(res) {
        const body = parseResponse(res);
        if (res.statusCode === 200) {
          handleResult(body, resolve, reject);
        } else {
          reject({ msg: body.msg || ('HTTP ' + res.statusCode) });
        }
      },
      fail(err) {
        reject({ msg: '云托管调用失败', err });
      },
    });
  });
}

/** 普通 HTTPS wx.request */
function httpRequest(path, method, data) {
  const base = apiCfg.baseUrl.replace(/\/$/, '');
  const url = base + (path.startsWith('/') ? path : '/' + path);
  return new Promise((resolve, reject) => {
    wx.request({
      url,
      method,
      data,
      header: getHeaders(),
      success(res) {
        handleResult(res.data, resolve, reject);
      },
      fail(err) {
        reject({ msg: '网络连接失败', err });
      },
    });
  });
}

function request(path, method, data) {
  const apiPath = path.replace(/^\//, '');
  const fullPath = apiPath.startsWith('api/') ? '/' + apiPath : '/api/' + apiPath;

  if (cloudCfg.useCloudRun) {
    return cloudRequest(fullPath, method, data);
  }
  return httpRequest('/' + apiPath.replace(/^api\//, ''), method, data);
}

function upload(path, filePath, formData = {}) {
  const apiPath = path.replace(/^\//, '');
  const fullPath = apiPath.startsWith('api/') ? '/' + apiPath : '/api/' + apiPath;

  // 大文件上传优先走公网域名
  if (cloudCfg.publicBaseUrl) {
    const token = wx.getStorageSync('token');
    return new Promise((resolve, reject) => {
      wx.uploadFile({
        url: cloudCfg.publicBaseUrl.replace(/\/$/, '') + fullPath,
        filePath,
        name: 'file',
        formData,
        header: { 'X-Token': token || '' },
        success(res) {
          try {
            const data = JSON.parse(res.data);
            if (data.code === 0) resolve(data.data);
            else reject({ msg: data.msg });
          } catch (e) {
            reject({ msg: '上传响应异常' });
          }
        },
        fail: () => reject({ msg: '上传失败' }),
      });
    });
  }

  if (!cloudCfg.useCloudRun) {
    const base = apiCfg.baseUrl.replace(/\/$/, '');
    const token = wx.getStorageSync('token');
    return new Promise((resolve, reject) => {
      wx.uploadFile({
        url: base + '/' + apiPath.replace(/^api\//, ''),
        filePath,
        name: 'file',
        formData,
        header: { 'X-Token': token || '' },
        success(res) {
          const data = JSON.parse(res.data);
          if (data.code === 0) resolve(data.data);
          else reject({ msg: data.msg });
        },
        fail: () => reject({ msg: '上传失败' }),
      });
    });
  }

  // 云托管无公网时：转 base64 走 callContainer（适合小图）
  return new Promise((resolve, reject) => {
    const fs = wx.getFileSystemManager();
    fs.readFile({
      filePath,
      encoding: 'base64',
      success(fileRes) => {
        const ext = filePath.split('.').pop().toLowerCase();
        const mime = ext === 'png' ? 'image/png' : 'image/jpeg';
        cloudRequest(fullPath, 'POST', {
          type: formData.type || 'image',
          base64: 'data:' + mime + ';base64,' + fileRes.data,
        }).then(resolve).catch(reject);
      },
      fail: () => reject({ msg: '读取文件失败' }),
    });
  });
}

module.exports = {
  get: (url, data) => request(url, 'GET', data),
  post: (url, data) => request(url, 'POST', data),
  upload,
  cloudRequest,
  httpRequest,
};
