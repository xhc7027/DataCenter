<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 14:47
 */

namespace app\services;

use app\services\user\ViewInfoApiImpl;
use app\services\user\ViewInfoApi;

use Yii;


class Factory
{
    /**
     * <p>简单工厂，用来获取模块接口实例。</p>
     *
     * 各个模块的类实例创建都不允许通过new的方式，一定要本工厂来创建。
     *
     * @package app\services
     */


    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     *
     */
    public static function getViewInfoService(): ViewInfoApi
    {
        return new ViewInfoApiImpl();
    }


}