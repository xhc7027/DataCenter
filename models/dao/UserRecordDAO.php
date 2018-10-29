<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 16:46
 */

namespace app\models\dao;

use Idouzi\Commons\HashClient;
use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 用户每次访问记录表（未去重）
 *
 * Class UserRecordDAO
 * @package app\models\dao
 */
class UserRecordDAO extends ActiveRecord
{
    /**
     * 获取表名
     *
     * @param string $dateTime 插入时间
     * @return string
     */
    private static function getTableName(string $dateTime)
    {
        return 'user_record_' . HashClient::lookup($dateTime, 30);
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