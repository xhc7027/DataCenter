<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "fans_item".
 *
 * @property integer $id
 * @property integer $fid
 * @property integer $userSource
 * @property integer $newUser
 * @property integer $cancelUser
 *
 * @property Fans $f
 */
class FansItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fans_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fid'], 'required'],
            [['fid', 'userSource', 'newUser', 'cancelUser'], 'integer'],
            [['fid'], 'exist', 'skipOnError' => true, 'targetClass' => Fans::className(),
                'targetAttribute' => ['fid' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fid' => '粉丝表主键',
            'userSource' => '用户的渠道',
            'newUser' => '新增的用户数量',
            'cancelUser' => '取消关注的用户数量',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getF()
    {
        return $this->hasOne(Fans::className(), ['id' => 'fid']);
    }
}