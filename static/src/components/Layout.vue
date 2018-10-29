<template>
    <div class="v-layout">
        <main>
            <!-- 左侧菜单 -->
            <LayoutAside></LayoutAside>

            <!-- 页面路由 -->
            <router-view class="layout-content"></router-view>
        </main>
    </div>
</template>

<script>
import LayoutAside from '@/components/LayoutAside.vue' // 左侧菜单组件

export default {
    components: {
        LayoutAside
    }
}
</script>

<style lang="less" scoped>
@import './../assets/less/theme.less';

.v-layout {
    main {
        margin-top: 50px;
        display: flex;
        font-size: 0;

        aside {
            flex-shrink: 0;
            width: 180px;
            font-size: 14px;
            vertical-align: top;
        }

        .layout-content {
            flex-grow: 1;
            margin: 10px;
            padding: 10px;
            overflow: hidden;
            font-size: 14px;
            border-radius: 2px;
            background: #fff;
        }
    }
}
</style>


