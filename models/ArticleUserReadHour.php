<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "article_user_read_hour".
 *
 * @property integer $id
 * @property integer $aid
 * @property string $refDate
 * @property integer $refHour
 * @property integer $userSource
 * @property integer $intPageReadUser
 * @property integer $intPageReadCount
 * @property integer $oriPageReadUser
 * @property integer $oriPageReadCount
 * @property integer $shareUser
 * @property integer $shareCount
 * @property integer $addToFavUser
 * @property integer $addToFavCount
 * @property integer $totalOnlineTime
 *
 * @property Article $a
 */
class ArticleUserReadHour extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article_user_read_hour';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aid', 'refDate', 'refHour', 'userSource'], 'required'],
            [['aid', 'refHour', 'userSource', 'intPageReadUser', 'intPageReadCount', 'oriPageReadUser', 'oriPageReadCount',
                'shareUser', 'shareCount', 'addToFavUser', 'addToFavCount', 'totalOnlineTime'], 'integer'
            ],
            [['refDate'], 'string', 'max' => 10],
            [['aid'], 'exist', 'skipOnError' => true, 'targetClass' => Article::className(), 'targetAttribute' => ['aid' => 'id']],
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
            'refDate' => '数据的日期',
            'refHour' => '数据的小时',
            'userSource' => '代表用户从哪里进入来阅读该图文',
            'intPageReadUser' => '图文页的阅读人数',
            'intPageReadCount' => '图文页的阅读次数',
            'oriPageReadUser' => '原文页的阅读人数',
            'oriPageReadCount' => '原文页的阅读次数',
            'shareUser' => '分享的人数',
            'shareCount' => '分享的次数',
            'addToFavUser' => '收藏的人数',
            'addToFavCount' => '收藏的次数',
            'totalOnlineTime' => '累计在线时间',
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