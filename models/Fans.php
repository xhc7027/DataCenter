<?php

namespace app\models;

use app\commons\ArrayUtil;
use yii\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "fans".
 *
 * @property integer $id
 * @property string $appId
 * @property string $refDate
 * @property integer $newUser
 * @property integer $cancelUser
 * @property integer $netgainUser
 * @property integer $cumulateUser
 *
 * @property FansItem[] $fansItems
 */
class Fans extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fans';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appId', 'refDate'], 'required'],
            [['newUser', 'cancelUser', 'netgainUser', 'cumulateUser'], 'integer'],
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
            'id' => '编号',
            'appId' => '公众号AppId',
            'refDate' => '数据的日期',
            'newUser' => '新增的用户数量',
            'cancelUser' => '取消关注的用户数量',
            'netgainUser' => '净增用户数量',
            'cumulateUser' => '总用户量',
        ];
    }

    /**
     * 从数据库获取现有粉丝数和累计粉丝数
     * @param $appId
     * @return RespMsg
     */
    public function getFansDataFromDB($appId)
    {
        $respMsg = new RespMsg();
        $yesterdayStr = date('Y-m-d', strtotime("-1 day"));
        $cumulateFans = Fans::find()->select('cumulateUser')
            ->where(['appId' => $appId, 'refDate' => $yesterdayStr])
            ->scalar();//现有粉丝
        $user_summary_list = $this->getFansSummaryFromDB($appId);

        if (empty($cumulateFans)) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '没有找到公众号：' . $appId . '的粉丝数据';
        } else {
            $respMsg->return_msg['exiting_user'] = $cumulateFans;
            $respMsg->return_msg['user_summary_list'] = $user_summary_list;
        }

        return $respMsg;
    }


    /**
     * 从数据库获取粉丝增减数据
     * @param $appId
     * @return array
     */
    private function getFansSummaryFromDB($appId)
    {
        $yesterday_data = ['new_user' => 0, 'cancel_user' => 0, 'net_user' => 0];//昨天的粉丝增减情况
        $seven_data = ['new_user' => 0, 'cancel_user' => 0, 'net_user' => 0];//最近7天的粉丝增减情况
        $thirty_data = ['new_user' => 0, 'cancel_user' => 0, 'net_user' => 0];//最近一个月的粉丝增减情况
        $begin_date = date('Y-m-d', strtotime("-30 day"));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $fansArr = Fans::find()->select(['refDate', 'newUser', 'cancelUser', 'netgainUser'])
            ->where(['appId' => $appId])
            ->andFilterWhere(['between', 'refDate', $begin_date, $end_date])
            ->asArray()
            ->orderBy('refDate ASC')
            ->all();
        if ($fansArr) {
            foreach ($fansArr as $value) {
                //获取昨天的粉丝趋势
                if ($value['refDate'] == date('Y-m-d', strtotime("-1 day"))) {
                    $yesterday_data['new_user'] = $value['newUser'];
                    $yesterday_data['cancel_user'] = $value['cancelUser'];
                    $yesterday_data['net_user'] = $value['netgainUser'];
                }
                //获取最近7天的粉丝趋势
                if ($value['refDate'] >= date('Y-m-d', strtotime("-7 day"))) {
                    $seven_data['new_user'] += $value['newUser'];
                    $seven_data['cancel_user'] += $value['cancelUser'];
                    $seven_data['net_user'] += $value['netgainUser'];
                }
                //获取最近30天的粉丝趋势
                if ($value['refDate'] >= date('Y-m-d', strtotime("-30 day"))) {
                    $thirty_data['new_user'] += $value['newUser'];
                    $thirty_data['cancel_user'] += $value['cancelUser'];
                    $thirty_data['net_user'] += $value['netgainUser'];
                }
            }

        }
        //拼接最终1,7,30天的数据
        $dataArr[] = $yesterday_data;
        $dataArr[] = $seven_data;
        $dataArr[] = $thirty_data;
        return $dataArr;
    }

    /**
     * 获取某几天的用户分析数据
     *
     * @param $appId string 商户的AppId
     * @param $timeArray array 日期的数组
     * @return array
     */
    public static function getAnalysisData($appId, $timeArray)
    {
        $key = Yii::$app->params['constant']['cache']['threeUser'] . $appId;
        if ($threeData = Yii::$app->cache->get($key)) {
            return $threeData;
        }
        $userThreeAnalysis = self::find()->select(['refDate', 'newUser', 'cancelUser', 'netgainUser', 'cumulateUser'])
            ->where(['appId' => $appId])
            ->andFilterWhere(['in', 'refDate', $timeArray])->asArray()->all();
        $arr = [];
        //将查出的数据以时间为键重放在一个数组里
        foreach ($userThreeAnalysis as $v) {
            $arr[$v['refDate']]['newUser'] = $v['newUser'];
            $arr[$v['refDate']]['cancelUser'] = $v['cancelUser'];
            $arr[$v['refDate']]['netgainUser'] = $v['netgainUser'];
            $arr[$v['refDate']]['cumulateUser'] = $v['cumulateUser'];
        }
        $threeData = [];
        $i = 0;
        //组装三天的数据
        foreach ($timeArray as $key => $val) {
            if (isset($arr[$val])) {
                $threeData[$i]['newUser'] = $arr[$val]['newUser'];
                $threeData[$i]['cancelUser'] = $arr[$val]['cancelUser'];
                $threeData[$i]['netgainUser'] = $arr[$val]['netgainUser'];
                $threeData[$i]['cumulateUser'] = $arr[$val]['cumulateUser'];
            } else {
                $threeData[$i]['newUser'] = $threeData[$i]['cancelUser'] = $threeData[$i]['netgainUser'] =
                $threeData[$i]['cumulateUser'] = 0;
            }
            $i++;
        }
        Yii::$app->cache->set($key, $threeData, strtotime("tomorrow") - time());
        return $threeData;
    }

    /**
     * 获取昨天的用户分析数据
     *
     * @param $appId string 商户的AppId
     * @param $refDate string 昨日的日期
     * @return array
     */
    public static function getYestAnalysisData($appId, $refDate)
    {
        $key = Yii::$app->params['constant']['cache']['yestUser'] . $appId;
        if ($data = Yii::$app->cache->get($key)) {
            return $data;
        }
        $userYestAnalysis = self::find()->select(['refDate', 'newUser', 'cancelUser', 'netgainUser', 'cumulateUser'])
            ->where(['appId' => $appId])
            ->andFilterWhere(['=', 'refDate', $refDate])->asArray()->one();
        $data[0]['refDate'] = $refDate;
        if (!$userYestAnalysis) {
            $data[0]['newUser'] = $data[0]['cancelUser'] = $data[0]['netgainUser'] = $data[0]['cumulateUser'] = 0;
        } else {
            $data[0]['newUser'] = $userYestAnalysis['newUser'];
            $data[0]['cancelUser'] = $userYestAnalysis['cancelUser'];
            $data[0]['netgainUser'] = $userYestAnalysis['netgainUser'];
            $data[0]['cumulateUser'] = $userYestAnalysis['cumulateUser'];
            Yii::$app->cache->set($key, $data, strtotime("tomorrow") - time());
        }
        return $data;

    }

    /**
     * 工作通，用户分析界面，七天，十五天，一个月的数据
     *
     * @param $appId string 商户的AppId
     * @return array
     */
    public static function jobChatGetFansSummary($appId)
    {
        return Fans::find()->select(['refDate', 'newUser', 'cancelUser', 'netgainUser', 'cumulateUser'])
            ->where(['appId' => $appId])
            ->andFilterWhere([
                'between', 'refDate', date('Y-m-d', strtotime("-30 day")), date('Y-m-d', strtotime('-2 day'))
            ])->asArray()->orderBy('refDate ASC')->all();

    }

}