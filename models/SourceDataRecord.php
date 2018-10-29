<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/23 0023
 * Time: 16:14
 */

namespace app\models;


use yii\base\Model;

/**
 * 聚合表校验model
 *
 * Class SourceDataRecord
 * @package app\models
 */
class SourceDataRecord extends Model
{


    public $dateTime;
    public $zoneId;
    public $zone;
    public $platformSource;
    public $channelSource;


    /**
     * @return array 属性组标签
     */
    public function attributeLabels()
    {
        return [
            'dateTime' => '创建时间',
            'platformSource' => '来源频道名称',
            'zone' => '分区号',//区分分区有直接访问（0）、搜索引擎（1）、外链（2）、自媒体（3）
            'channelSource' => '来源渠道名称',
            'zoneId' => '分区id',
            'pv' => 'pv',
            'uv' => 'uv',
            'registerNum' => '注册数',
            'bindNum' => '绑定数',
            'paynum' => '付费数',
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
                ['dateTime', 'zoneId', 'zone', 'channelSource', 'platformSource',
                    'pv', 'uv', 'registerNum', 'bindNum', 'payNum', 'zoneId'], 'safe'
            ],
            [
                ['dateTime', 'zoneId', 'zone', 'channelSource', 'platformSource'], 'required'
            ],

        ];
    }


    /**
     * 组装插入聚合表的数据
     *
     * @return mixed
     */
    public function insertData()
    {
        $data['dateTime'] = $this->dateTime;
        $data['zoneId'] = $this->zoneId;
        $data['zone'] = $this->zone;
        $data['platformSource'] = $this->platformSource;
        $data['channelSource'] = $this->channelSource;
        $data['pv'] = 0;
        $data['uv'] = 0;
        $data['registerNum'] = 0;
        $data['bindNum'] = 0;
        $data['payNum'] = 0;
        return $data;
    }
}