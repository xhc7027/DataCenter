<?php

$config = require(__DIR__ . '/web.php');
$config['id'] = 'dc-console';
$config['basePath'] = dirname(__DIR__);
$config['controllerNamespace'] = 'app\commands';
$config['components']['log']['targets'][0]['logFile'] = '@runtime/logs/console.log';
$config['components']['log']['targets'][0]['levels'][2] = 'info';
unset(
    $config['homeUrl'], $config['components']['request'], $config['components']['urlManager'],
    $config['components']['user'], $config['components']['errorHandler']
);
return $config;
