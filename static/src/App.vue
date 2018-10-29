<template>
    <div id="app">
         <!-- 头部 -->
        <header v-show="!$route.path.match('login')">
            <a class="logo">爱豆子数据运营系统</a>

            <ul class="user-items">
                <li class="item user-name" v-show="userName !== ''">{{userName}}</li>
                <li class="item sign-out" @click="signOut" v-show="userName !== ''">
                    退出
                </li>
            </ul>
        </header>

        <!-- 布局路由 -->
        <router-view :userName.sync="userName"></router-view>
    </div>
</template>

<script>
import loginHttp from '@/http/login/http' // 登录http配置

export default {
    data() {
        return {
            userName: sessionStorage.getItem('userName') || '', // 用户名
        }
    },

    methods: {
        // 退出登录
        signOut() {
            let _this = this;

            loginHttp.signOut().then(() => {
                _this.userName = '';
                _this.$router.push('/login');

                // 重置session 放在路由跳转后 防止路由进入钩子覆盖
                sessionStorage.setItem('lastPage', '/'); 
                sessionStorage.setItem('userName', '');        
            })
        }
    }
}
</script>

<style lang="less" scoped>
@import './assets/less/theme.less';

#app {
    header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 50px;
        padding: 0 20px;
        line-height: 50px;
        color: #fff;
        background: @theme-color;
        z-index: 100;

        .logo {
            color: #fff;
            font-size: 16px;
        }

        .user-items {
            position: absolute;
            top: 0;
            right: 20px;
            font-size: 14px;

            .item {
                position: relative;
                display: inline-block;
                padding: 0 10px;

                &:after {
                    content: '';
                    position: absolute;
                    width: 2px;
                    height: 15px;
                    top: 18px;
                    right: 0;
                    background: #fff;
                }

                &.sign-out {
                    cursor: pointer;
                }

                &.login {
                    .link {
                        color: #fff;
                    }
                }

                &:last-child:after {
                    display: none;
                }
            }
        }
    }
}
</style>
