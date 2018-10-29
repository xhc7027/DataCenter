<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 15:36
 */

namespace app\models\dao;

use Idouzi\Commons\StringUtil;
use MongoDB\BSON\ObjectID;
use Yii;

class ZoneIdDAO
{
    /**
     * 表名
     *
     * @var string
     */
    private static $tableName = 'zone_id';



    /**
     * 修改表中id自增加一，营造source_zone表zone_id自增的样子
     */
    public static function updateZoneId()
    {
        $zoneId = Yii::$app->mongodb->getCollection(self::$tableName)->findAndModify(
            [
                'name' => 'zoneId'
            ],
            [
                '$inc' => ['id' => 1],
            ],
            ['new' => 1, 'upsert' => 1]
        );
        return $zoneId['id'];
    }
}