<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 11:27
 */

namespace app\models\dao;

use Idouzi\Commons\StringUtil;
use MongoDB\BSON\ObjectID;
use Yii;

/**
 * 分区记录DAO
 *
 * @package app\models\dao
 */
class SourceZoneDAO
{
    /**
     * 表名
     *
     * @var string
     */
    private static $tableName = 'source_zone';

    /*********************************************  查询操作    ************************************************/


    /**
     * 根据refererUrl查询所属的来源渠道和渠道ID
     */
    public static function findSourceZone()
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)
            ->find(['status' => 0], ['refererUrl', 'zoneId', 'zone', 'channelSource'])->toArray();
    }


    /**
     * 查询全部分区信息
     *
     * @param $zone
     * @return mixed
     */
    public static function showAllSourceZone($zone)
    {
        if ($zone === '') {
            return Yii::$app->mongodb->getCollection(self::$tableName)->find(['status' => 0],
                ['refererUrl', 'zoneId', 'zone', 'channelSource', 'dataTime'])->toArray();
        } else {
            return Yii::$app->mongodb->getCollection(self::$tableName)->find(['status' => 0, 'zone' => (int)$zone],
                ['refererUrl', 'zoneId', 'zone', 'channelSource', 'dataTime'])->toArray();
        }

    }


    /**
     * 查询存在的渠道信息
     *
     * @return mixed
     */
    public static function findAllChannelSource()
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)->find(['status' => 0], ['channelSource'])->toArray();
    }

    /**
     * 根据分区查询存在的渠道信息
     *
     * @param $zone
     * @return mixed
     */
    public static function findChoiceChannelSource($zone)
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)->find(['status' => 0, 'zone' => $zone], ['channelSource'])->toArray();
    }


    /*********************************************  修改操作    ************************************************/


    /**
     * 修改分区信息
     *
     * @param $zoneId
     * @param array $data
     * @return mixed
     */
    public static function updateSourceZone($zoneId, array $data)
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)->update(['zoneId' => (int)$zoneId], $data);
    }




    /*********************************************  插入操作    ************************************************/

    /**
     * 插入新建的来源渠道
     * @param array $data
     * @return
     */
    public static function insertSourceZone(array $data)
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)->insert($data);
    }




    /*********************************************  删除操作    ************************************************/

    /**
     * 删除所选来源渠道(逻辑删除)
     * @param array $data
     * @return
     */
    public static function deleteSourceZone(array $data)
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)->update(['zoneId' => (int)$data['zoneId']], ['status' => 1]);
    }
}