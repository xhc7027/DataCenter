import Vue from 'vue'
import App from './App.vue'
import router from './router'
import ElementUI from 'element-ui'
import 'element-ui/lib/theme-chalk/index.css'
import 'font-awesome/css/font-awesome.css'

import "babel-polyfill" // IE promise
import './assets/css/reset.css' // 基础样式reset
import './assets/less/common.less' // 公共less 覆盖element

Vue.config.productionTip = true

Vue.use(ElementUI)

new Vue({
    router,
    render: h => h(App)
}).$mount('#app')