<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "message_item_hour".
 *
 * @property integer $id
 * @property integer $miId
 * @property integer $msgType
 * @property integer $refHour
 * @property integer $msgUser
 * @property integer $msgCount
 *
 * @property MessageItem $mi
 */
class MessageItemHour extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'message_item_hour';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['miId', 'msgType', 'refHour'], 'required'],
            [['miId', 'refHour', 'msgType', 'msgUser', 'msgCount'], 'integer'],
            [
                ['miId'], 'exist', 'skipOnError' => true, 'targetClass' => MessageItem::className(),
                'targetAttribute' => ['miId' => 'id']
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
            'miId' => 'Mi ID',
            'msgType' => '消息类型',
            'refHour' => '数据的小时',
            'msgUser' => '上行发送了消息的用户数',
            'msgCount' => '上行发送了消息的消息总数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMi()
    {
        return $this->hasOne(MessageItem::className(), ['id' => 'miId']);
    }
}