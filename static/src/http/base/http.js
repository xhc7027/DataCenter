import axios from 'axios';
import qs from 'qs';
import { Message } from 'element-ui'

axios.defaults.timeout = 60 * 1000;

let _csrf = document.querySelector('meta[name=csrf]').getAttribute('content');

// 环境判断
let baseUrl = '';
if(location.href.match('8080')) {
    baseUrl = '/api';
}

// 请求拦截器
axios.interceptors.request.use(
    config => {
        // 代理接口切换
        config.url =  baseUrl + config.url;

        // 用于qs stringify
        let method = config.method;
        if (method === 'post' || method === 'put' || method === 'delete') {
            config.data = qs.stringify(config.data);

            config.headers = {
                'X-CSRF-TOKEN': _csrf,
                'Content-Type': 'application/x-www-form-urlencoded'
            };
        }

        return config;
    },

    // 错误处理
    error => {
        Message('请求参数错误');
        return Promise.reject(error);
    }
);

// 返回过滤器
axios.interceptors.response.use(
    // 正确处理
    res => {
        let data = res.data,
            headCsrf = res.headers._csrf,
            status = data.return_code,
            msg = data.return_msg;

        if(headCsrf) {
            _csrf = headCsrf;
        }

        // status   SUCCESS:成功  
        if (status === 'SUCCESS') {
            return msg;
        } else {
            // 未登录
            if (msg === 'GUEST') {
                // 重定向
                location.hash = '#/login';
            } else {
                Message(msg);
                return Promise.reject(msg);
            }
        }
    },

    // 错误处理
    error => {
        let res = error.response;

        if (res) {
            Message(res.status + ':网络错误，请刷新重试');
        } else {
            Message('请求超时，请刷新重试');
        };

        return Promise.reject(error);
    }
);

// get请求方法
export function fetchGet(url, config) {
    return new Promise((resolve, reject) => {
        axios.get(url, config).then(
            res => {
                resolve(res);
            },

            error => {
                reject(error)
            }
        ).catch((error) => {
            reject(error);
        });
    })
}

// post请求方法
export function fetchPost(url, data, config) {
    return new Promise((resolve, reject) => {
        axios.post(url, data, config).then(
            res => {
                resolve(res);
            },

            error => {
                reject(error)
            }
        ).catch((error) => {
            reject(error);
        })
    })
}

// put请求方法
export function fetchPut(url, data, config) {
    return new Promise((resolve, reject) => {
        axios.put(url, data, config).then(
            res => {
                resolve(res);
            },

            error => {
                reject(error)
            }
        ).catch((error) => {
            reject(error);
        })
    })
}

// delete请求方法
export function fetchDelete(url, config) {
    return new Promise((resolve, reject) => {
        axios.delete(url, config).then(
            res => {
                resolve(res);
            },

            error => {
                reject(error)
            }
        ).catch((error) => {
            reject(error);
        })
    })
}