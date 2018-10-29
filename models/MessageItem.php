<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "message_item".
 *
 * @property integer $id
 * @property integer $mid
 * @property integer $msgType
 * @property integer $msgUser
 * @property integer $msgCount
 *
 * @property Message $m
 */
class MessageItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'message_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mid', 'msgType'], 'required'],
            [['mid', 'msgType', 'msgUser', 'msgCount'], 'integer'],
            [
                ['mid'], 'exist', 'skipOnError' => true, 'targetClass' => Message::className(),
                'targetAttribute' => ['mid' => 'id']
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
            'mid' => 'Mid',
            'msgType' => '消息类型',
            'msgUser' => '上行发送了消息的用户数',
            'msgCount' => '上行发送了消息的消息总数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getM()
    {
        return $this->hasOne(Message::className(), ['id' => 'mid']);
    }
}