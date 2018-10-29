import Vue from 'vue'
import Router from 'vue-router'

import Index from './components/Layout.vue'
import Login from './views/login/Login.vue'

// seo
import EffectReport from './views/seo/EffectReport.vue'
import WeMedia from './views/seo/WeMedia.vue'
import SearchEngine from './views/seo/SearchEngine.vue'
import ExternalLink from './views/seo/ExternalLink.vue'
import OriginManage from './views/seo/OriginManage.vue'
import DirectVisit from './views/seo/DirectVisit.vue'

Vue.use(Router);

export default new Router({
    routes: [
        {
            path: '/',
            redirect: '/index/seo/EffectReport'
        },
        {
            path: '/index',
            name: 'Index',
            component: Index,
            children: [{ // 效果报告
                    path: 'seo/EffectReport',
                    name: 'EffectReport',
                    component: EffectReport,
                },
                { // 自媒体专区
                    path: 'seo/WeMedia',
                    name: 'WeMedia',
                    component: WeMedia
                },
                { // 搜索引擎专区
                    path: 'seo/SearchEngine',
                    name: 'SearchEngine',
                    component: SearchEngine
                },
                { // 外链专区
                    path: 'seo/ExternalLink',
                    name: 'ExternalLink',
                    component: ExternalLink
                },
                { // 直接访问专区
                    path: 'seo/DirectVisit',
                    name: 'DirectVisit',
                    component: DirectVisit
                },
                { // 渠道管理
                    path: 'seo/OriginManage/:zone',
                    name: 'OriginManage',
                    component: OriginManage
                }
            ]
        },
        { // 登录页
            path: '/login',
            name: 'Login',
            component: Login,
        }
    ]
})