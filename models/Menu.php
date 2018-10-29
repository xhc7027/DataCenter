<?php
namespace app\models;

use yii\db\ActiveRecord;
use Yii;

/**
 * 菜单统计模型
 *
 * @property integer $id
 * @property string $wxId
 * @property string $keyVal
 * @property string $menuName
 * @property integer $clickCount
 * @property integer $clickUser
 * @property string $refDate
 * @property string $appId
 *
 * Class Menu
 * @package app\models
 */
class Menu extends ActiveRecord
{
    /**
     * 表名
     *
     * @return string
     */
    public static function tableName()
    {
        return 'menu';
    }

    /**
     * 属性
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'id',
            'wxId',
            'keyVal',
            'menuName',
            'clickCount',
            'clickUser',
            'refDate',
            'appId',
        ];
    }

    /**
     * 设置默认场景
     *
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['default'] = ['id', 'wxId', 'keyVal', 'menuName', 'clickCount', 'clickUser', 'refDate', 'appId'];
        return $scenarios;
    }

    /**
     * 属性标签
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'wxId' => '商家id',
            'keyVal' => '菜单的key',
            'menuName' => '菜单的名称',
            'clickCount' => '点击次数',
            'clickUser' => '点击人数',
            'refDate' => '统计日期',
            'appId' => '公众号的appId',
        ];
    }

    /**
     * 获取昨日的菜单统计的数据
     *
     * @param $appId
     * @param $refDate
     * @return mixed
     */
    public static function getYestMenuData($appId, $refDate)
    {
        $key = Yii::$app->params['constant']['cache']['yestMenu'] . $appId;
        if ($data = Yii::$app->cache->get($key)) {
            return $data;
        }
        $dataArr = self::find()->select(['sum(clickUser) clickUser', 'sum(clickCount) clickCount'])
            ->where(['appId' => $appId, 'refDate' => $refDate])->asArray()->all();
        if (!$dataArr[0]['clickCount']) {
            $data[0]['clickCount'] = $data[0]['clickUser'] = $data[0]['avgClickCount'] = 0;
        } else {
            $data[0]['clickUser'] = $dataArr[0]['clickUser'];
            $data[0]['clickCount'] = $dataArr[0]['clickCount'];
            $data[0]['avgClickCount'] = !(int)$dataArr[0]['clickUser'] ? 0 :
                round($dataArr[0]['clickCount'] / $dataArr[0]['clickUser']);
            Yii::$app->cache->set($key, $data, strtotime("tomorrow") - time());
        }
        return $data;
    }

    /**
     * 工作通获取关键指标菜单统计数据
     *
     * @param $appId
     * @param $timeArray
     * @return array|mixed
     */
    public static function getMenuData($appId, $timeArray)
    {
        $dataArr = self::find()->select(['refDate', 'sum(clickUser) clickUser', 'sum(clickCount) clickCount'])
            ->where(['appId' => $appId])
            ->andFilterWhere(['in', 'refDate', $timeArray])->groupby(['refDate'])->asArray()->all();
        $arr = [];
        foreach ($dataArr as $v) {
            $arr[$v['refDate']]['clickUser'] = $v['clickUser'];
            $arr[$v['refDate']]['clickCount'] = $v['clickCount'];
        }
        $fourData = [];
        $i = 0;
        foreach ($timeArray as $key => $val) {
            if (isset($arr[$val])) {
                $fourData[$i]['clickUser'] = $arr[$val]['clickUser'];
                $fourData[$i]['clickCount'] = $arr[$val]['clickCount'];
                $fourData[$i]['avgClickCount'] = !(int)$arr[$val]['clickUser'] ? 0 :
                //保留一位小数
                sprintf("%.1f", $arr[$val]['clickCount'] / $arr[$val]['clickUser']);
            } else {
                $fourData[$i]['clickUser'] = $fourData[$i]['clickCount'] = $fourData[$i]['avgClickCount'] = 0;
            }
            $i++;
        }
        return $fourData;
    }

    /**
     * 工作通，消息分析界面，七天，十五天，一个月的数据
     *
     * @param $appId
     * @param $refDate
     * @return array
     */
    public static function jobChatGetMenuSummary($appId, $refDate)
    {
        return self::find()->select(['menuName', 'sum(clickUser) clickUser', 'sum(clickCount) clickCount'])
            ->where(['appId' => $appId])
            ->andFilterWhere([
                'between', 'refDate', $refDate, date('Y-m-d', strtotime('-1 day'))
            ])->groupBy("keyVal,menuName")->asArray()->orderBy('refDate ASC')->all();

    }

    /**
     * 获取菜单统计的对象
     *
     * @param $where
     * @return static
     */
    public static function getMenuObject($where)
    {
        return self::findOne($where);
    }
}