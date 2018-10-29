<?php

return [
    'adminEmail' => 'admin@example.com',
    'signKey' => [
        'apiSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA',//代理平台跨服务接口安全认证key
        'iDouZiSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA',//爱豆子跨服务接口安全认证key
        'msgSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA',//消息系统跨服务接口安全认证key
        'dcSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA',//数据中心系统跨服务接口安全认证key
    ],
    'serviceDomain' => [
        'weiXinApiDomain' => 'http://weixinapi2.idouzi.com',//代理平台服务域名
        'weiXinMsgDomain' => 'http://weixinmsg.idouzi.com', //消息管理服务域名
        'iDouZiDomain' => 'http://new.idouzi.com', //消息管理服务域名
        'weiXinDCDomain' => 'http://weixindc.idouzi.com', //数据中心管理服务域名
    ],
    'executeScriptTime' => ['0', '00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '23'],//定义每天不能执行脚本的时间
    'syncDataLimit' => 500,//定义脚本每次查询的条数
    'taskOverTime' => 300,//定义超时任务时间秒
    'weiXinDataApi' => [
        ['name' => '获取用户增减数据', 'type' => 'getUserSummary',],
        ['name' => '获取累计用户数据', 'type' => 'getUserCumulate',],
        ['name' => '获取图文群发每日数据', 'type' => 'getArticleSummary',],
        ['name' => '获取图文群发总数据', 'type' => 'getArticleTotal',],
        ['name' => '获取图文统计数据', 'type' => 'getUserRead',],
        ['name' => '获取图文统计分时数据', 'type' => 'getUserReadHour',],
        ['name' => '获取图文分享转发数据', 'type' => 'getUserShare',],
        ['name' => '获取图文分享转发分时数据', 'type' => 'getUserShareHour',],
        ['name' => '获取消息发送概况数据', 'type' => 'getUpstreamMsg',],
        ['name' => '获取消息分送分时数据', 'type' => 'getUpstreamMsgHour',],
//        ['name' => '获取消息发送周数据', 'type' => 'getupstreammsgweek',],
//        ['name' => '获取消息发送月数据', 'type' => 'getupstreammsgmonth',],
//        ['name' => '获取消息发送分布数据', 'type' => 'getupstreammsgdist',],
//        ['name' => '获取消息发送分布周数据', 'type' => 'getupstreammsgdistweek',],
//        ['name' => '获取消息发送分布月数据', 'type' => 'getupstreammsgdistmonth',],
        ['name' => '获取接口分析数据', 'type' => 'getInterfaceSummary',],
        ['name' => '获取接口分析分时数据', 'type' => 'getInterfaceSummaryHour',],
    ],
    'constant' => [
        'cache' => [
            'yestUser' => "yestUser_",
            'threeUser' => "threeUser_",
            'yestIt' => "yestIt_",
            'threeIt' => "threeIt_",
            'yestMsg' => "yestMsg_",
            'threeMsg' => "threeMsg_",
        ],
        'session' => [
            'appId' => 'appId_',
        ]
    ],
    'timeArr' => [
        date('Y-m-d', strtotime('-2 day')), date('Y-m-d', strtotime('-8 day')),
        date('Y-m-d', strtotime('-31 day'))
    ],
];
