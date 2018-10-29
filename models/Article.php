<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Query;
use Yii;

/**
 * This is the model class for table "article".
 *
 * @property integer $id
 * @property string $appId
 * @property string $refDate
 * @property string $statDate
 * @property integer $intPageReadUser
 * @property integer $intPageReadCount
 * @property integer $oriPageReadUser
 * @property integer $oriPageReadCount
 * @property integer $shareUser
 * @property integer $shareCount
 * @property integer $addToFavUser
 * @property integer $addToFavCount
 * @property integer $targetUser
 *
 * @property ArticleSummary[] $articleSummaries
 * @property ArticleTotal[] $articleTotals
 * @property ArticleUserRead[] $articleUserReads
 * @property ArticleUserReadHour[] $articleUserReadHours
 * @property ArticleUserShare[] $articleUserShares
 */
class Article extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appId', 'refDate'], 'required'],
            [['statDate'], 'safe'],
            [
                ['intPageReadUser', 'intPageReadCount', 'oriPageReadUser', 'oriPageReadCount', 'shareUser',
                    'shareCount', 'addToFavUser', 'addToFavCount', 'targetUser'], 'integer'
            ],
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
            'statDate' => '统计的日期',
            'intPageReadUser' => '图文页的阅读人数',
            'intPageReadCount' => '图文页的阅读次数',
            'oriPageReadUser' => '原文页的阅读人数',
            'oriPageReadCount' => '原文页的阅读次数',
            'shareUser' => '分享的人数',
            'shareCount' => '分享的次数',
            'addToFavUser' => '收藏的人数',
            'addToFavCount' => '收藏的次数',
            'targetUser' => '送达人数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArticleSummaries()
    {
        return $this->hasMany(ArticleSummary::className(), ['aid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArticleTotals()
    {
        return $this->hasMany(ArticleTotal::className(), ['aid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArticleUserReads()
    {
        return $this->hasMany(ArticleUserRead::className(), ['aid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArticleUserReadHours()
    {
        return $this->hasMany(ArticleUserReadHour::className(), ['aid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArticleUserShares()
    {
        return $this->hasMany(ArticleUserShare::className(), ['aid' => 'id']);
    }

    /**
     * 从数据库获取图文数据
     * @param $appId
     * @return array
     */
    public function getArticleDataFromDB($appId)
    {
        $respMsg = new RespMsg();

        //获取最近一个月的发文数据
        $begin_date = date('Y-m-d', strtotime("-31 day"));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $query = new Query();
        $totalArticles = $query->select('article_total.title')
            ->from('article')
            ->innerJoin('article_total', "article.id = article_total.aid and article.appId='" . $appId
                . "' and article.refDate >='" . $begin_date . "' and article.refDate <='" . $end_date . "'")
            ->groupBy('article.refDate')->count();

        //获取上周的图文阅读次数以及分享次数
        $begin_date = date('Y-m-d', strtotime("-8 day"));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $dataArr = Article::find()
            ->select(['sum(intPageReadCount) as article_read_count', 'sum(shareCount) as article_share_count'])
            ->where(['appId' => $appId])
            ->andWhere("refDate between '" . $begin_date . "' and '" . $end_date . "'")
            ->asArray()->one();

        $dataArr['article_release_count'] = $totalArticles;

        $respMsg->return_msg = $dataArr;
        return $respMsg;
    }

    /**
     * 获取三日的图文分析数据
     *
     * @param $appId string 商户的AppId
     * @param $timeArray array 时间的数组
     * @return array
     */
    public static function getImageTextData($appId, $timeArray)
    {
        $key = Yii::$app->params['constant']['cache']['threeIt'] . $appId;
        if ($threeData = Yii::$app->cache->get($key)) {
            return $threeData;
        }
        $dataArr = self::find()->select(['refDate', 'intPageReadCount', 'oriPageReadCount', 'shareCount', 'addToFavCount'])
            ->where(['appId' => $appId])
            ->andFilterWhere(['in', 'refDate', $timeArray])->asArray()->all();
        $arr = [];
        foreach ($dataArr as $v) {
            $arr[$v['refDate']]['intPageReadCount'] = $v['intPageReadCount'];
            $arr[$v['refDate']]['oriPageReadCount'] = $v['oriPageReadCount'];
            $arr[$v['refDate']]['shareCount'] = $v['shareCount'];
            $arr[$v['refDate']]['addToFavCount'] = $v['addToFavCount'];
        }
        $threeData = [];
        $i = 1;
        foreach ($timeArray as $key => $val) {
            if (isset($arr[$val])) {
                $threeData[$i]['intPageReadCount'] = $arr[$val]['intPageReadCount'];
                $threeData[$i]['oriPageReadCount'] = $arr[$val]['oriPageReadCount'];
                $threeData[$i]['shareCount'] = $arr[$val]['shareCount'];
                $threeData[$i]['addToFavCount'] = $arr[$val]['addToFavCount'];
            } else {
                $threeData[$i]['intPageReadCount'] = $threeData[$i]['oriPageReadCount'] = $threeData[$i]['shareCount'] =
                $threeData[$i]['addToFavCount'] = 0;
            }
            $i++;
        }
        Yii::$app->cache->set($key, $threeData, strtotime("tomorrow") - time());
        return $threeData;
    }

    /**
     * 获取昨日的图文数据
     *
     * @param $appId string 商户的AppId
     * @param $refDate string 昨日的日期
     * @return mixed
     */
    public static function getYestImageTextData($appId, $refDate)
    {
        $key = Yii::$app->params['constant']['cache']['yestIt'] . $appId;
        if ($data = Yii::$app->cache->get($key)) {
            return $data;
        }
        $dataArr = self::find()->select(['intPageReadCount', 'oriPageReadCount', 'shareCount', 'addToFavCount'])
            ->where(['appId' => $appId])
            ->andFilterWhere(['=', 'refDate', $refDate])->asArray()->one();
        if (!$dataArr) {
            $data[0]['intPageReadCount'] = $data[0]['oriPageReadCount'] = $data[0]['shareCount'] =
            $data[0]['addToFavCount'] = 0;
        } else {
            $data[0]['intPageReadCount'] = $dataArr['intPageReadCount'];
            $data[0]['oriPageReadCount'] = $dataArr['oriPageReadCount'];
            $data[0]['shareCount'] = $dataArr['shareCount'];
            $data[0]['addToFavCount'] = $dataArr['addToFavCount'];
            Yii::$app->cache->set($key, $data, strtotime("tomorrow") - time());
        }
        return $data;
    }

    /**
     * 获取日期范围内的所有图文信息
     *
     * @param JobChatTimeForm $jobChatTimeModel
     * @param $appId
     * @return array
     */
    public static function getArticleIds(JobChatTimeForm $jobChatTimeModel, $appId)
    {
        return self::find()->select(['id'])->where(['appId' => $appId])
            ->andWhere("refDate between '" . $jobChatTimeModel->startTime . "' and '" . $jobChatTimeModel->endTime . "'")
            ->asArray()->all();
    }
}