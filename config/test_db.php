<?php
$db = require(__DIR__ . '/db.php');
// test database! Important not to run tests on production or development databases
$config['components']['db']['dsn'] = 'mysql:host=localhost;dbname=weixindc_tests';
$config['components']['mongodb']['class'] = '\yii\mongodb\Connection';
$config['components']['mongodb']['dsn'] = 'mongodb://mongouser:Eequuch0phei5gai@10.66.135.56:27017/weixindc_dev_unitTest?authSource=admin&readPreference=secondaryPreferred';

return $config;