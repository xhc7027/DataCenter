<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "article_user_share_hour".
 *
 * @property integer $id
 * @property integer $ausId
 * @property integer $refHour
 * @property integer $shareScene
 * @property integer $shareCount
 * @property integer $shareUser
 *
 * @property ArticleUserShare $aus
 */
class ArticleUserShareHour extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article_user_share_hour';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ausId', 'refHour', 'shareScene'], 'required'],
            [['ausId', 'refHour', 'shareScene', 'shareCount', 'shareUser'], 'integer'],
            [['ausId'], 'exist', 'skipOnError' => true, 'targetClass' => ArticleUserShare::className(),
                'targetAttribute' => ['ausId' => 'id']
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
            'ausId' => 'Aus ID',
            'refHour' => '数据的小时',
            'shareScene' => '分享的场景',
            'shareCount' => '分享的次数',
            'shareUser' => '分享的人数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAus()
    {
        return $this->hasOne(ArticleUserShare::className(), ['id' => 'ausId']);
    }
}