<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "api_item".
 *
 * @property integer $id
 * @property integer $aid
 * @property integer $refHour
 * @property integer $callbackCount
 * @property integer $failCount
 * @property integer $totalTimeCost
 * @property integer $maxTimeCost
 * @property double $avgTimeCost
 *
 * @property Api $a
 */
class ApiItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aid', 'refHour'], 'required'],
            [['aid', 'refHour', 'callbackCount', 'failCount', 'totalTimeCost', 'maxTimeCost'], 'integer'],
            [['avgTimeCost'], 'number'],
            [
                ['aid'], 'exist', 'skipOnError' => true, 'targetClass' => Api::className(),
                'targetAttribute' => ['aid' => 'id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'aid' => 'Aid',
            'refHour' => '数据的小时',
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
    public function getA()
    {
        return $this->hasOne(Api::className(), ['id' => 'aid']);
    }
}
