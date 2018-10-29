<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "article_total_details".
 *
 * @property integer $id
 * @property integer $atId
 * @property string $statDate
 * @property integer $targetUser
 * @property integer $intPageReadUser
 * @property integer $intPageReadCount
 * @property integer $oriPageReadUser
 * @property integer $oriPageReadCount
 * @property integer $shareUser
 * @property integer $shareCount
 * @property integer $addToFavUser
 * @property integer $addToFavCount
 * @property integer $intPageFromSessionReadUser
 * @property integer $intPageFromSessionReadCount
 * @property integer $intPageFromHistMsgReadUser
 * @property integer $intPageFromHistMsgReadCount
 * @property integer $intPageFromFeedReadUser
 * @property integer $intPageFromFeedReadCount
 * @property integer $intPageFromFriendsReadUser
 * @property integer $intPageFromFriendsReadCount
 * @property integer $intPageFromOtherReadUser
 * @property integer $intPageFromOtherReadCount
 * @property integer $feedShareFromSessionUser
 * @property integer $feedShareFromSessionCnt
 * @property integer $feedShareFromFeedUser
 * @property integer $feedShareFromFeedCnt
 * @property integer $feedShareFromOtherUser
 * @property integer $feedShareFromOtherCnt
 */
class ArticleTotalDetails extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article_total_details';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['atId', 'statDate'], 'required'],
            [['atId', 'targetUser', 'intPageReadUser', 'intPageReadCount', 'oriPageReadUser', 'oriPageReadCount',
                'shareUser', 'shareCount', 'addToFavUser', 'addToFavCount', 'intPageFromSessionReadUser',
                'intPageFromSessionReadCount', 'intPageFromHistMsgReadUser', 'intPageFromHistMsgReadCount',
                'intPageFromFeedReadUser', 'intPageFromFeedReadCount', 'intPageFromFriendsReadUser',
                'intPageFromFriendsReadCount', 'intPageFromOtherReadUser', 'intPageFromOtherReadCount',
                'feedShareFromSessionUser', 'feedShareFromSessionCnt', 'feedShareFromFeedUser',
                'feedShareFromFeedCnt', 'feedShareFromOtherUser', 'feedShareFromOtherCnt'], 'integer'],
            [['statDate'], 'string', 'max' => 10],
            [['atId'], 'exist', 'skipOnError' => true, 'targetClass' => ArticleTotal::className(),
                'targetAttribute' => ['atId' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'atId' => 'AtId',
            'statDate' => '统计的日期',
            'targetUser' => '送达人数',
            'intPageReadUser' => '图文页的阅读人数',
            'intPageReadCount' => '图文页的阅读次数',
            'oriPageReadUser' => '原文页的阅读人数',
            'oriPageReadCount' => '原文页的阅读次数',
            'shareUser' => '分享的人数',
            'shareCount' => '分享的次数',
            'addToFavUser' => '收藏的人数',
            'addToFavCount' => '收藏的次数',
            'intPageFromSessionReadUser' => '公众号会话阅读人数',
            'intPageFromSessionReadCount' => '公众号会话阅读次数',
            'intPageFromHistMsgReadUser' => '历史消息页阅读人数',
            'intPageFromHistMsgReadCount' => '历史消息页阅读次数',
            'intPageFromFeedReadUser' => '朋友圈阅读人数',
            'intPageFromFeedReadCount' => '朋友圈阅读次数',
            'intPageFromFriendsReadUser' => '好友转发阅读人数',
            'intPageFromFriendsReadCount' => '好友转发阅读次数',
            'intPageFromOtherReadUser' => '其他场景阅读人数',
            'intPageFromOtherReadCount' => '其他场景阅读次数',
            'feedShareFromSessionUser' => '公众号会话转发朋友圈人数',
            'feedShareFromSessionCnt' => '公众号会话转发朋友圈次数',
            'feedShareFromFeedUser' => '朋友圈转发朋友圈人数',
            'feedShareFromFeedCnt' => '朋友圈转发朋友圈次数',
            'feedShareFromOtherUser' => '其他场景转发朋友圈人数',
            'feedShareFromOtherCnt' => '其他场景转发朋友圈次数',
        ];
    }

    /**
     * 获取多图文数据
     *
     * @param $articleTitles
     * @param JobChatTimeForm $jobChatTimeModel
     * @return array
     */
    public static function getImageTexts($articleTitles, JobChatTimeForm $jobChatTimeModel)
    {
        if (!$articleTitles) return [];
        foreach ($articleTitles as $key => $val) {
            $dataArr = self::find()
                ->select(
                    [
                        'sum(intPageFromSessionReadUser) as sessionReadUser',
                        'sum(intPageFromSessionReadCount) as sessionReadCount',
                        'sum(intPageFromHistMsgReadUser) as histMsgReadUser',
                        'sum(intPageFromHistMsgReadCount) as histMsgReadCount',
                        'sum(intPageFromFriendsReadUser) as friendsReadUser',
                        'sum(intPageFromFriendsReadCount) as friendsReadCount',
                        'sum(intPageFromOtherReadUser) as otherReadUser',
                        'sum(intPageFromOtherReadCount) as otherReadCount',
                    ])
                ->where(['atId' => $val['id']])
                ->andWhere("statDate between '" . $jobChatTimeModel->startTime .
                    "' and '" . $jobChatTimeModel->endTime . "'")
                ->asArray()->all();
            $articleTitles[$key]['dataInfo'] = $dataArr[0];

        }
        return $articleTitles;
    }


}