<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 17:42
 */

namespace app\models\dao;

use Idouzi\Commons\HashClient;
use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 记录用户访问信息
 * 根据用户唯一UserSign信息去重
 *
 * Class UserUvRecordDAO
 * @package app\models\dao
 */
class UserUvRecordDAO extends ActiveRecord
{
    /**
     * 获取表名
     *
     * @param string $dateTime 商城id
     * @return string
     */
    private static function getTableName(string $dateTime)
    {
        return 'user_uv_record_' . HashClient::lookup($dateTime, 30);
    }


    /**
     * 查出所属用户的来源频道和渠道Id
     *
     * @param $dateTime
     * @param $supplierId
     * @return mixed
     */
    public static function findExistSupplierId($dateTime, $supplierId)
    {
        return Yii::$app->mongodb->getCollection(self::getTableName($dateTime))->
        find(['supplierId' => $supplierId, 'dateTime' => (int)$dateTime], ['platformSource', 'zoneId'])
            ->toArray();
    }

    /**
     * 根据用户唯一标识查询是否有存在的用户
     *
     * @param $dateTime
     * @param $data
     * @return array|ActiveRecord
     */
    public static function findExistUserSign(string $dateTime, array $data)
    {
        return self::find()->from(self::getTableName($dateTime))->select(['userSign'])
            ->where(['in', 'userSign', $data])->andWhere(['dateTime' => (int)$dateTime])->asArray()->all();
    }

    /**
     * 更新用户信息将用户id写入表中
     *
     * @param $dateTime
     * @param $supplierId
     * @param $userSign
     * @return mixed
     */
    public static function updateRegisteredUserInfo($dateTime, $supplierId, $userSign)
    {
        return Yii::$app->mongodb->getCollection(self::getTableName($dateTime))
            ->update(['userSign' => $userSign], ['supplierId' => $supplierId]);
    }


    /**
     * 根据返回的绑定Id查询最近的绑定人数
     * @param $dateTime
     * @param $supplierBlind
     * @return mixed
     */
    public static function findBindCounts($dateTime, $supplierBlind)
    {
        return Yii::$app->mongodb->getCollection(self::getTableName($dateTime))
            ->find(['supplierId' => $supplierBlind], ['supplierId', 'zoneId', 'platformSource'])->toArray();
    }

    /**
     * 根据返回的付费Id查询最近的付费人数
     * @param $dateTime
     * @param $supplierPay
     * @return mixed
     */
    public static function findPayCounts($dateTime, $supplierPay)
    {
        return Yii::$app->mongodb->getCollection(self::getTableName($dateTime))
            ->find(['supplierId' => $supplierPay], ['supplierId', 'zoneId', 'platformSource'])->toArray();
    }


    /**
     * 批量插入用户访问记录
     *
     * @param string $dateTime
     * @param array $data
     * @return mixed
     */
    public static function batchInsert(string $dateTime, array $data)
    {
        return Yii::$app->mongodb->getCollection(self::getTableName($dateTime))->batchInsert($data);
    }


}