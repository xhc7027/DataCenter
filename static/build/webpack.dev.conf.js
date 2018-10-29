'use strict'
const utils = require('./utils')
const webpack = require('webpack')
const config = require('../config')
const merge = require('webpack-merge')
const path = require('path')
const baseWebpackConfig = require('./webpack.base.conf')
const CopyWebpackPlugin = require('copy-webpack-plugin')
const HtmlWebpackPlugin = require('html-webpack-plugin')
const FriendlyErrorsPlugin = require('friendly-errors-webpack-plugin')
const portfinder = require('portfinder')

const HOST = process.env.HOST
const PORT = process.env.PORT && Number(process.env.PORT)

const devWebpackConfig = merge(baseWebpackConfig, {
  module: {
    rules: utils.styleLoaders({ sourceMap: config.dev.cssSourceMap, usePostCSS: true })
  },
  // cheap-module-eval-source-map is faster for development
  devtool: config.dev.devtool,

  // these devServer options should be customized in /config/index.js
  devServer: {
    clientLogLevel: 'warning',
    historyApiFallback: {
      rewrites: [
        { from: /.*/, to: path.posix.join(config.dev.assetsPublicPath, 'index.html') },
      ],
    },
    hot: true,
    contentBase: false, // since we use CopyWebpackPlugin.
    compress: true,
    host: HOST || config.dev.host,
    port: PORT || config.dev.port,
    open: config.dev.autoOpenBrowser,
    overlay: config.dev.errorOverlay
      ? { warnings: false, errors: true }
      : false,
    publicPath: config.dev.assetsPublicPath,
    proxy: config.dev.proxyTable,
    quiet: true, // necessary for FriendlyErrorsPlugin
    watchOptions: {
      poll: config.dev.poll,
    },
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
  },
  plugins: [
    new webpack.DefinePlugin({
      'process.env': require('../config/dev.env')
    }),
    new webpack.HotModuleReplacementPlugin(),
    new webpack.NamedModulesPlugin(), // HMR shows correct file names in console on update.
    new webpack.NoEmitOnErrorsPlugin(),
    // https://github.com/ampedandwired/html-webpack-plugin
    new HtmlWebpackPlugin({
      filename: 'index.html',
      template: 'index.html',
      inject: true
    }),
    // copy custom static assets
    new CopyWebpackPlugin([
      {
        from: path.resolve(__dirname, '../static'),
        to: config.dev.assetsSubDirectory,
        ignore: ['.*']
      }
    ])
  ]
})

module.exports = new Promise((resolve, reject) => {
  portfinder.basePort = process.env.PORT || config.dev.port
  portfinder.getPort((err, port) => {
    if (err) {
      reject(err)
    } else {
      // publish the new Port, necessary for e2e tests
      process.env.PORT = port
      // add port to devServer config
      devWebpackConfig.devServer.port = port

      // Add FriendlyErrorsPlugin
      devWebpackConfig.plugins.push(new FriendlyErrorsPlugin({
        compilationSuccessInfo: {
          messages: [`Your application is running here: http://${devWebpackConfig.devServer.host}:${port}`],
        },
        onErrors: config.dev.notifyOnErrors
          ? utils.createNotifierCallback()
          : undefined
      }))

      resolve(devWebpackConfig)
    }
  })
})
