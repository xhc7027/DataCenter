<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "api".
 *
 * @property integer $id
 * @property string $appId
 * @property string $refDate
 * @property integer $callbackCount
 * @property integer $failCount
 * @property integer $totalTimeCost
 * @property integer $maxTimeCost
 * @property double $avgTimeCost
 *
 * @property ApiItem[] $apiItems
 */
class Api extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appId', 'refDate'], 'required'],
            [['callbackCount', 'failCount', 'totalTimeCost', 'maxTimeCost'], 'integer'],
            [['avgTimeCost'], 'number'],
            [['appId'], 'string', 'max' => 18],
            [['refDate'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'appId' => '公众号AppId',
            'refDate' => '数据的日期',
            'callbackCount' => '被动回复用户消息的次数',
            'failCount' => '上述动作的失败次数',
            'totalTimeCost' => '总耗时',
            'maxTimeCost' => '最大耗时',
            'avgTimeCost' => '平均耗时',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getApiItems()
    {
        return $this->hasMany(ApiItem::className(), ['aid' => 'id']);
    }
}