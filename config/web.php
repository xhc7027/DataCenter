<?php
$dynamicConfig = [
    'id' => 'data-center',//项目编号标识
    'basePath' => dirname(__DIR__),
];

//需要静态获取的配置
$staticConfig = Yaconf::get($dynamicConfig['id']);
if (!$staticConfig) {
    throw new Exception('不能加载配置文件:' . $dynamicConfig['id']);
}

$staticConfig['components']['db']['slaveConfig']['attributes'] = [PDO::ATTR_TIMEOUT => 10];
$staticConfig['params']['timeArr'] = [
    date('Y-m-d', strtotime('-2 day')), date('Y-m-d', strtotime('-8 day')), date('Y-m-d', strtotime('-31 day'))
];

//需要动态获取的配置
$commonConfig = Yaconf::get('common');
if (!$commonConfig) {
    throw new Exception('不能加载配置文件:common');
}
$staticConfig['params'] = array_merge($commonConfig, $staticConfig['params']);
$staticConfig['params']['zipkin']['endpoint']['ip'] = '119.29.184.154';
$staticConfig['params']['zipkin']['endpoint']['port'] = '80';
$staticConfig['params']['zipkin']['endpoint']['name'] = '集中身份认证系统-dev';

return array_merge($dynamicConfig, $staticConfig);