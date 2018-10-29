<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12 0012
 * Time: 16:48
 */

namespace app\models;

use yii\base\Model;
use app\models\dao\ZoneIdDAO;

class SourceZone extends Model
{
//dataCenter_sourceZone{
//createTime:'20180326',//创建时间
//zoneId:123456,//创建的zoneId
//zone:0//区分分区有直接访问（0）、搜索引擎（1）、外链（2）、自媒体（3）
//refererUrl:"https://www.baidu.com/link?...",//访问来源链接
//channelSource:"百度",//来源名称


    public $createTime;
    public $zoneId;
    public $zone;
    public $refererUrl;
    public $channelSource;


    /**
     * @return array 属性组标签
     */
    public function attributeLabels()
    {
        return [
            'createTime' => '创建时间',
            'zoneId' => '创建的zoneId',
            'zone' => '分区名',//区分分区有直接访问、搜索引擎、外链、自媒体
            'refererUrl' => '访问来源链接',
            'channelSource' => '来源名称',
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
                ['createTime', 'zoneId', 'zone', 'channelSource', 'refererUrl', 'status'], 'safe'
            ],

            [
                ['createTime', 'channelSource', 'refererUrl', 'zone', 'status'], 'required', 'on' => ['insertData']
            ],
            [
                ['channelSource', 'refererUrl', 'zoneId'], 'required', 'on' => ['updateData']
            ],

        ];
    }


    /**
     * 获取插入的数据
     *
     * @return array
     */
    public function insertData()
    {
        $data['createTime'] = $this->createTime;
        $data['editorTime'] = date('Y-m-d H:i:s');
        $data['zoneId'] = ZoneIdDAO::updateZoneId();
        $data['zone'] = (int)$this->zone;
        $data['refererUrl'] = $this->refererUrl;
        $data['channelSource'] = $this->channelSource;
        $data['status'] = 0;
        return $data;
    }

    /**
     * 获取修改的数据
     *
     * @return array
     */
    public function updateData()
    {
        $data['editorTime'] = date('Y-m-d H:i:s');
        $data['zoneId'] = (int)$this->zoneId;
        $data['refererUrl'] = $this->refererUrl;
        $data['channelSource'] = $this->channelSource;
        return $data;
    }


}