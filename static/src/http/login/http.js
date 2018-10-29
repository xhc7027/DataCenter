import { fetchGet, fetchPost, fetchUpload, fetchPut, fetchDelete } from '@/http/base/http.js'

let ajaxUrl = {
    getMsgCode: '/login/login', // 登录
    checkMsgCode: '/login/validate-phone', // 验证码验证
    signOut: '/login/logout' // 退出登录
}

export default {
    // 登录
    getMsgCode(data) {
        return fetchPost(ajaxUrl.getMsgCode, data);
    },

    // 验证码验证
    checkMsgCode(data) {
        return fetchPost(ajaxUrl.checkMsgCode, data);
    },

    // 退出登录
    signOut(config) {
        return fetchGet(ajaxUrl.signOut, config);
    }
}