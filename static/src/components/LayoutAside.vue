<template>
    <aside class="layout-aside">
        <el-menu 
            class="el-menu-vertical-demo"
            background-color="#545c64"
            text-color="#fff"
            active-text-color="#ffd04b"
            :default-active="$route.path"
            :router="true">
            <div v-for="(menu,index) in menus" :key="index">

                <!-- 一级菜单含子菜单 -->
                <el-submenu :index="index.toString()" v-if="menu.children">
                    <template slot="title">
                        <i :class="menu.class"></i>
                        <span>{{menu.name}}</span>
                    </template>

                    <!-- 二级菜单 -->
                    <el-menu-item 
                        v-for="submenu in menu.children"
                        :key="submenu.link"
                        :class="{
                            active: submenu.link.indexOf('/index/seo/OriginManage') != -1
                            && matchOriginManage
                        }"
                        :index="submenu.link">
                        {{submenu.name}}
                    </el-menu-item>
                </el-submenu>

                <!-- 一级菜单不含有子菜单 -->
                <el-menu-item 
                    :index="menu.link"
                    v-if="!menu.children">
                    <i :class="menu.class"></i>
                    <span slot="title">{{menu.name}}</span>
                </el-menu-item>
            </div>
        </el-menu>
    </aside>
</template>

<script>
import config from '@/config/config'; // 配置信息

export default {
    data() {
        return {
            menus: config.MENUS, // 左侧菜单配置
            matchOriginManage: false // 是否匹配渠道管理
        }
    },

    watch: {
        $route(newValue, oldValue) {
            let _this = this;

            if(newValue.path.indexOf('/index/seo/OriginManage') != -1) {
                _this.matchOriginManage = true;
            } else {
                _this.matchOriginManage = false;
            }
        }
    }
}
</script>

<style lang="less">

// 左侧菜单样式修改 element
.layout-aside {
    > .el-menu {
        min-height: 100vh;
        border: none;

        .fa {
            vertical-align: middle;
            margin-right: 5px;
            width: 24px;
            text-align: center;
            font-size: 18px;
        }

        .el-submenu {
            .el-menu-item {
                padding: 0;
                min-width: 0;

                &.active {
                    color: rgb(255, 208, 75) !important;
                }
            }
        }
    }
}
</style>
