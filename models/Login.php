<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/24 0024
 * Time: 19:19
 */

namespace app\models;


use yii\base\Model;

class Login extends Model
{
    public $username;
    public $password;
    public $reserved_phone;

    /**
     * @return array 属性组标签
     */
    public function attributeLabels()
    {
        return [
            'username' => '用户名',
            'password' => '密码',
            'reserved_phone' => '分区名',//区分分区有直接访问、搜索引擎、外链、自媒体
            'nickname' => '昵称',
            'level' => '级别,预留字段',
            'login_time' => '最后登录时间',
            'login_ip' => '最后登录ip',
            'lock_time' => '最近一次锁定账号的时间',
            'retry_number' => '重试次数',
            'reserved_phone' => '预留手机号码',
            'roleId' => '角色id',
        ];
    }


    /**
     * 设置规则
     *
     * @return array
     */
    public function rules()
    {
        return [
            //基本规则
            [
                ['username', 'password', 'reserved_phone', 'nickname', 'level',
                    'login_time', 'login_ip', 'lock_time', 'retry_number', 'reserved_phone', 'roleId'], 'safe'
            ],
            [['username', 'password'], 'required'],


        ];
    }
}