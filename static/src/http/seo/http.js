import { fetchGet, fetchPost, fetchUpload, fetchPut, fetchDelete } from '@/http/base/http.js'

let ajaxUrl = {
    getOrigin: '/user/show-channel-source', // 来源渠道筛选
    getSource: '/user/get-source-data', // 列表数据获取
    getOriginList: '/user/source-zone', // 渠道数据列表
}

export default {
    // 来源渠道筛选
    getOrigin(config) {
        return fetchGet(ajaxUrl.getOrigin, config);
    },
    
    // 渠道数据列表
    getOriginList(config) {
        return fetchGet(ajaxUrl.getOriginList, config);
    },
    
    // 列表数据获取
    getSource(config) {
        return fetchGet(ajaxUrl.getSource, config);
    },

    // 添加渠道
    addOrigin(data) {
        return fetchPost(ajaxUrl.getOriginList, data);
    },

    // 删除渠道
    delOrigin(config) {
        return fetchDelete(ajaxUrl.getOriginList, config);
    },

    // 编辑渠道
    editOrigin(data) {
        return fetchPut(ajaxUrl.getOriginList, data);
    }
}