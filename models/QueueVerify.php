<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/2 0002
 * Time: 19:26
 */

namespace app\models;


use yii\base\Model;

class QueueVerify extends Model
{
    public $dateTime;
    public $userSign;
    public $viewUrl;
    public $userIp;
    public $userAgent;
    public $platformSource;
    public $refererUrl;
    public $channelSource;
    public $zone;
    public $zoneId;

    /**
     * @return array 属性组标签
     */
    public function attributeLabels()
    {
        return [
            'dateTime' => '创建时间',
            'userSign' => '用户唯一凭证标识',
            'viewUrl' => '访问页面',//区分分区有直接访问、搜索引擎、外链、自媒体
            'userIp' => '用户访问IP',
            'userAgent' => '用户浏览器版本',
            'platformSource' => '来源频道名称',
            'refererUrl' => '访问前跳转Url',
            'channelSource' => '来源渠道名称',
            'zone' => '分区',
            'zoneId' => '分区Id',
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
                ['dateTime', 'userSign', 'viewUrl', 'userIp', 'userAgent',
                    'platformSource', 'refererUrl', 'channelSource'], 'safe'
            ],
            [
                ['userSign', 'dateTime', 'userAgent', 'userIp', 'viewUrl', 'platformSource'], 'required', 'on' => ['queueVerify']
            ],
            [
                ['dateTime', 'platformSource', 'channelSource', 'zone'], 'required', 'on' => ['SourceDataVerify']
            ],

        ];
    }


    public function queueVerify($data)
    {
        $this->dateTime = $data['dateTime'];
        $this->userSign = $data['userSign'];
        $this->viewUrl = $data['viewUrl'];
        $this->userIp = $data['userIp'];
        $this->userAgent = $data['userAgent'];
        $this->platformSource = $data['platformSource'];

        if (!$this->load($data, '') || !$this->validate()) {
            throw new SystemException(current($this->getFirstErrors()));
            return false;
        }
        return true;
    }


    public function SourceDataVerify($data)
    {
        $this->dateTime = $data['dateTime'];
        $this->channelSource = $data['channelSource'];
        $this->zone = $data['zone'];
        $this->platformSource = $data['platformSource'];

        if (!$this->load($data, '') || !$this->validate()) {
            throw new SystemException(current($this->getFirstErrors()));
            return false;
        }
        return true;
    }
}