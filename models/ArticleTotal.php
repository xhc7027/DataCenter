<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "article_total".
 *
 * @property integer $id
 * @property integer $aid
 * @property string $refDate
 * @property string $msgId
 * @property integer $msgIndex
 * @property string $title
 *
 * @property Article $a
 * @property ArticleTotalDetails[] $articleTotalDetails
 */
class ArticleTotal extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article_total';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aid', 'refDate'], 'required'],
            [['aid', 'msgIndex'], 'integer'],
            [['refDate'], 'string', 'max' => 10],
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
            'id' => '编号',
            'aid' => 'Aid',
            'refDate' => '数据的日期',
            'msgId' => '图文消息id',
            'msgIndex' => '消息次序索引',
            'title' => '图文消息的标题',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getA()
    {
        return $this->hasOne(Article::className(), ['id' => 'aid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArticleTotalDetails()
    {
        return $this->hasMany(ArticleTotalDetails::className(), ['atid' => 'id']);
    }

    /**
     * 获取图文标题
     *
     * @param $articleIds
     * @return array|ActiveRecord[]
     */
    public static function getArticleTitles($articleIds)
    {
        $dataArr = self::find()->select(['id', 'title'])->where(['in', 'aid', $articleIds])
            ->asArray()->all();
        return $dataArr;
    }

}