<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "article_summary".
 * @property integer $id
 * @property integer $aid
 * @property string $msgId
 * @property integer $msgIndex
 * @property string $title
 * @property integer $intPageReadUser
 * @property integer $intPageReadCount
 * @property integer $oriPageReadUser
 * @property integer $oriPageReadCount
 * @property integer $shareUser
 * @property integer $shareCount
 * @property integer $addToFavUser
 * @property integer $addToFavCount
 * @property Article $a
 */
class ArticleSummary extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article_summary';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aid'], 'required'],
            [['aid', 'msgIndex', 'intPageReadUser', 'intPageReadCount', 'oriPageReadUser', 'oriPageReadCount',
                'shareUser', 'shareCount', 'addToFavUser', 'addToFavCount'], 'integer'],
            [['msgId'], 'string', 'max' => 15],
            [['title'], 'string', 'max' => 125],
            [['aid'], 'exist', 'skipOnError' => true, 'targetClass' => Article::className(),
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
            'msgId' => '图文消息id',
            'msgIndex' => '消息次序索引',
            'title' => '图文消息的标题',
            'intPageReadUser' => '图文页的阅读人数',
            'intPageReadCount' => '图文页的阅读次数',
            'oriPageReadUser' => '原文页的阅读人数',
            'oriPageReadCount' => '原文页的阅读次数',
            'shareUser' => '分享的人数',
            'shareCount' => '分享的次数',
            'addToFavUser' => '收藏的人数',
            'addToFavCount' => '收藏的次数',
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