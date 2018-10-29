<?php

namespace app\models;

use app\commons\ArrayUtil;
use yii\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "message".
 *
 * @property integer $id
 * @property string $appId
 * @property string $refDate
 * @property integer $msgUser
 * @property integer $msgCount
 * @property integer $countInterval
 * @property integer $intPageReadCount
 * @property integer $oriPageReadUser
 *
 * @property MessageItem[] $messageItems
 */
class Message extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appId', 'refDate'], 'required'],
            [['msgUser', 'msgCount', 'countInterval', 'intPageReadCount', 'oriPageReadUser'], 'integer'],
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
            'msgUser' => '上行发送了消息的用户数',
            'msgCount' => '上行发送了消息的消息总数',
            'countInterval' => '当日发送消息量分布的区间',
            'intPageReadCount' => '图文页的阅读次数',
            'oriPageReadUser' => '原文页的阅读人数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessageItems()
    {
        return $this->hasMany(MessageItem::className(), ['mid' => 'id']);
    }

    /**
     * 获取总的图文分析数据
     *
     * @param $appId string 商户的AppId
     * @param $timeArray array 时间数组
     * @return array|null|ActiveRecord
     */
    public static function getMessageData($appId, $timeArray)
    {
        $key = Yii::$app->params['constant']['cache']['threeMsg'] . $appId;
        if ($threeData = Yii::$app->cache->get($key)) {
            return $threeData;
        }
        $dataArr = self::find()->select(['refDate', 'msgUser', 'msgCount', 'countInterval'])->where(['appId' => $appId])
            ->andFilterWhere(['in', 'refDate', $timeArray])->asArray()->all();
        $arr = [];
        foreach ($dataArr as $v) {
            $arr[$v['refDate']]['msgUser'] = $v['msgUser'];
            $arr[$v['refDate']]['msgCount'] = $v['msgCount'];
        }
        $threeData = [];
        $i = 0;
        foreach ($timeArray as $key => $val) {
            if (isset($arr[$val])) {
                $threeData[$i]['msgUser'] = $arr[$val]['msgUser'];
                $threeData[$i]['msgCount'] = $arr[$val]['msgCount'];
                $threeData[$i]['avgMsgCount'] = !(int)$arr[$val]['msgUser'] ? 0 : round($arr[$val]['msgCount'] / $arr[$val]['msgUser']);
            } else {
                $threeData[$i]['msgUser'] = $threeData[$i]['msgCount'] = $threeData[$i]['avgMsgCount'] = 0;
            }
            $i++;
        }
        Yii::$app->cache->set($key, $threeData, strtotime("tomorrow") - time());
        return $threeData;
    }

    /**
     * 工作通，消息分析界面，七天，十五天，一个月的数据
     *
     * @param $appId
     * @return array
     */
    public static function jobChatGetMessageSummary($appId)
    {
        return self::find()->select(['refDate', 'msgUser', 'msgCount', 'countInterval'])
            ->where(['appId' => $appId])
            ->andFilterWhere([
                'between', 'refDate', date('Y-m-d', strtotime("-30 day")), date('Y-m-d', strtotime('-2 day'))
            ])->asArray()->orderBy('refDate ASC')->all();

    }


    /**
     * 获取昨日的消息统计数据
     *
     * @param $appId string 商户的AppId
     * @param $refDate string 昨日的日期
     * @return mixed
     */
    public static function getYestMessageData($appId, $refDate)
    {
        $key = Yii::$app->params['constant']['cache']['yestMsg'] . $appId;
        if ($data = Yii::$app->cache->get($key)) {
            return $data;
        }
        $dataArr = self::find()->select(['msgUser', 'msgCount', 'countInterval'])->where(['appId' => $appId])
            ->andFilterWhere(['=', 'refDate', $refDate])->asArray()->one();

        $data[0]['refDate'] = $refDate;
        if (!$dataArr) {
            $data[0]['msgUser'] = $data[0]['msgCount'] = $data[0]['avgMsgCount'] = $data[0]['countInterval'] = 0;
        } else {
            $data[0]['msgUser'] = $dataArr['msgUser'];
            $data[0]['msgCount'] = $dataArr['msgCount'];
            $data[0]['countInterval'] = $dataArr['countInterval'];
            $data[0]['avgMsgCount'] = !(int)$dataArr['msgUser'] ? 0 : round($dataArr['msgCount'] / $dataArr['msgUser']);
            Yii::$app->cache->set($key, $data, strtotime("tomorrow") - time());
        }
        return $data;
    }

}