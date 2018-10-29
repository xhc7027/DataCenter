import IdouziTools from '@idouzi/idouzi-tools';

// 当前域名
let env = IdouziTools.getEnv(),
    curLink = '';

switch (env) {
    case 'dev':
        curLink = 'http://weixindc-dev.idouzi.com';
        break;
    case 'test':
        curLink = 'http://weixindc-test.idouzi.com';
        break;
    case 'prod':
        curLink = 'http://weixindc.idouzi.com';
        break;
    default:
        curLink = 'http://weixindc.idouzi.com';
        break;
}

// 菜单配置
const MENUS = [
    {
        name: 'SEO数据',
        class: 'fa fa-diamond',
        children: [
            {
                name: '效果报告',
                link: '/index/seo/EffectReport'
            }, {
                name: '自媒体专区',
                link: '/index/seo/WeMedia'
            }, {
                name: '外链专区',
                link: '/index/seo/ExternalLink'
            }, {
                name: '搜索引擎专区',
                link: '/index/seo/SearchEngine'
            }, {
                name: '直接访问',
                link: '/index/seo/DirectVisit'
            }, {
                name: '渠道管理',
                link: '/index/seo/OriginManage/-1'
            }
        ]
    }
]

export default {
    curLink,
    MENUS
}

