
const Mock = require('mockJS')
const Random = Mock.Random;
var path = require('path')

module.exports = {
    baseUrl: '/',
    outputDir: '/diss',
    productionSourceMap: false,

    devServer: {
        clientLogLevel: 'warning',
        historyApiFallback: true,
        hot: true,
        compress: true,
        host: '192.168.1.141',
        port: 8080,
        proxy: {
            '/api': { //替换代理地址名称
                target: 'http://weixindc-dev.idouzi.com/', //代理地址
                changeOrigin: true, //可否跨域
                pathRewrite: {
                    '^/api': '' //重写接口，去掉/api
                }
            }
        },
        // open: config.dev.autoOpenBrowser,
        // overlay: config.dev.errorOverlay
        //     ? { warnings: false, errors: true }
        //     : false,
        // publicPath: config.dev.assetsPublicPath,
        // proxy: config.dev.proxyTable,
        // quiet: true, // necessary for FriendlyErrorsPlugin
        // watchOptions: {
        //     poll: config.dev.poll,
        // },
        before(app) {
            // mock数据
            let express = require('express'),
                apiRouters = express.Router();

            // 获取布局数据
            app.get('/layout', (req, res) => {
                let isMenus = Random.natural(0, 1),
                    data = Mock.mock({
                        'menus|3-6': [{
                            isPullDown: Random.natural(0, 1),
                            link: isMenus ? '' : Random.url(),
                            name: Random.cword(2, 5),
                            className: 'icon-gongneng',
                            color: Random.color(),
                            'submenus|2-4': isMenus ? [{
                                link: Random.natural(0, 1),
                                name: Random.cword(2, 5),
                            }] : [],
                        }],
                        userName: Random.cword(2, 5),
                    });

                res.json({
                    return_code: 'SUCCESS',
                })
            })

            // login

            // 登录
            app.post('/login', (req, res) => {
                res.json({
                    return_code: 'SUCCESS',
                })
            })

            // 验证码验证
            app.post('/checkcode', (req, res) => {
                let data = Mock.mock({
                    nickname: Random.cword(2, 5)
                });

                res.json({
                    return_code: 'SUCCESS',
                    return_msg: data.nickname
                })
            });

            // 退出登录
            app.get('/signOut', (req, res) => {
                res.json({
                    return_code: 'SUCCESS',
                })
            });

            // seo

            // 来源渠道筛选
            app.get('/seo/origin', (req, res) => {
                let data = Mock.mock({
                    'channelSource|3-10': [
                        Random.cword(2, 5)
                    ]
                });

                res.json({
                    return_code: 'SUCCESS',
                    return_msg: data.channelSource
                })
            })

            // 列表数据获取
            app.get('/seo/source', (req, res) => {
                let data = Mock.mock({
                    'list|10': [{
                        'bindNum': Random.natural(0, 100000),
                        'channelSource': Random.cword(2, 10),
                        'dateTime': Date.now(),
                        'payNum': Random.natural(0, 100000),
                        'platformSource': Random.cword(2, 10),
                        'pv': Random.natural(0, 100000),
                        'registerNum': Random.natural(0, 100000),
                        'uv': Random.natural(0, 100000),
                    }],
                    'totalPage': Random.natural(0, 100000)
                });

                res.json({
                    return_code: 'SUCCESS',
                    return_msg: data
                })
            })

            // 渠道数据列表
            app.get('/seo/originlist', (req, res) => {
                let data = Mock.mock({
                    'list|5': [{
                        'channelSource': Random.cword(2, 10),
                        'refererUrl': Random.cword(2, 10),
                        'zone': Random.natural(0, 3),
                        'zoneId': Random.natural(0, 1000)
                    }],
                    'totalPage': Random.natural(0, 10)
                });

                res.json({
                    return_code: 'SUCCESS',
                    return_msg: data
                })
            });

            // 添加渠道
            app.post('/seo/addorigin', (req, res) => {
                res.json({
                    return_code: 'SUCCESS',
                })
            });

            // 删除渠道
            app.post('/seo/delorigin', (req, res) => {
                res.json({
                    return_code: 'SUCCESS',
                })
            });

            // 编辑渠道
            app.post('/seo/editorigin', (req, res) => {
                res.json({
                    return_code: 'SUCCESS',
                })
            });

            app.use('/api', apiRouters);
        }
    }
}