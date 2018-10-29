<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/18 0018
 * Time: 14:39
 */

namespace app\models\dao;


use Yii;
use yii\mongodb\ActiveRecord;
use yii\data\Pagination;
use MongoDB\BSON\ObjectID;

class RegisteredRecordDAO extends ActiveRecord
{
    /**
     * 表名
     *
     * @var string
     */
    private static $tableName = 'registered_record';


    /**
     * 向注册表插入初始注册信息
     * @param $data
     * @return mixed
     */
    public static function insertData($data)
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)->insert($data);
    }


    /**
     * 查询注册Id对应的注册时间
     * @param $supplierId
     * @return array|ActiveRecord
     */
    public static function findSupplierIdByDate($supplierId)
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)
            ->find(['in','supplierId',$supplierId], ['supplierId','dateTime'])->toArray();
    }

}