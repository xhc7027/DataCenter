<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "article_user_share".
 *
 * @property integer $id
 * @property integer $aid
 * @property integer $shareScene
 * @property integer $shareCount
 * @property integer $shareUser
 *
 * @property Article $a
 */
class ArticleUserShare extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article_user_share';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aid', 'shareScene'], 'required'],
            [['aid', 'shareScene', 'shareCount', 'shareUser'], 'integer'],
            [
                ['aid'], 'exist', 'skipOnError' => true, 'targetClass' => Article::className(),
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
            'shareScene' => '分享的场景',
            'shareCount' => '分享的次数',
            'shareUser' => '分享的人数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getA()
    {
        return $this->hasOne(Article::className(), ['id' => 'aid']);
    }
}