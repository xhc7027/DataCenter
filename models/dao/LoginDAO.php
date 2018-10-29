<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 15:36
 */

namespace app\models\dao;


use Yii;

/**
 * 用户登录信息DAO
 *
 * Class LoginDAO
 * @package app\models\dao
 */
class LoginDAO
{
    /**
     * 表名
     *
     * @var string
     */
    private static $tableName = 'houtai_admin';


    public static function findAdmin($data)
    {
        $sql = 'select count(*) as total,reserved_phone,nickname from houtai_admin where username = :username and password = :password';
        $bindValues = [':username' => $data['username'], ':password' => md5($data['password'])];
        $res = Yii::$app->db->createCommand($sql, $bindValues)->queryOne();
        return $res;
    }
}