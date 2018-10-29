<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/17 0017
 * Time: 20:07
 */

namespace app\models\dao;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\data\Pagination;
use MongoDB\BSON\ObjectID;

/**
 * SEO聚合表
 *
 * Class SourceDataRecordDAO
 * @package app\models\dao
 */
class SourceDataRecordDAO extends ActiveRecord
{
    /**
     * 表名
     *
     * @var string
     */
    private static $tableName = 'source_data_record';


    /**
     * 根据字段查找增加对应字段数值
     *
     * @param $data
     * @param $filed
     */
    public static function recordToSourceData(array $data, string $filed, int $date)
    {
        Yii::$app->mongodb->getCollection(self::$tableName)->findAndModify(
            [
                'dateTime' => $date,
                'zoneId' => $data['zoneId'],
                'platformSource' => $data['platformSource'],
            ],
            [
                '$inc' => [$filed => 1],
            ],
            ['new' => 1]
        );
    }

    /**
     * 查找聚合表中有没有存在的聚合信息
     *
     * @param $data
     * @return int
     */
    public static function findRecordSourceData(array $data)
    {
        return self::find()->from(self::$tableName)
            ->where(['dateTime' => (int)$data['dateTime'], 'platformSource' => $data['platformSource'],
                'channelSource' => $data['channelSource'], 'zone' => (int)$data['zone']])->count();
    }


    /**
     * 查询效果报告所需要的聚合统计数据
     *
     * @param $edate
     * @param $order
     * @param $orderBy
     * @param $sdate
     * @param $pageSize
     * @return array
     */
    public static function findSourceData($edate, $order, $orderBy, $sdate, $pageSize)
    {
        if (!isset($pageSize)) {
            $pageSize = Yii::$app->params['perPageCount'];
        }
        $col = Yii::$app->mongodb->getCollection(self::$tableName);
        $sourceData = $col->aggregate(
            [
                ['$match' => [
                    'and',
                    ['and', ['>=', 'dateTime', (int)$sdate], ['<=', 'dateTime', (int)$edate]]]
                ],
                ['$group' => ['_id' => 'dateTime'],],
            ]
        );
        //获取分页信息
        $sourceData ? count($sourceData) : 0;
        $page = new Pagination(['totalCount' => $sourceData, 'pageSize' => $pageSize]);
        //判断升降序
        if ($order == 'desc') {
            $order = -1;
        } else {
            $order = 1;
        }

        //查询聚合信息
        $retAry = $col->aggregate(
            [
                ['$match' => [
                    'and',
                    ['between', 'dateTime', (int)$sdate, (int)$edate],
                ],
                ],
                ['$group' => ['_id' => '$dateTime', 'pv' => ['$sum' => '$pv'], 'uv' => ['$sum' => '$uv'], 'registerNum' => ['$sum' => '$registerNum']
                    , 'bindNum' => ['$sum' => '$bindNum'], 'payNum' => ['$sum' => '$payNum'],]
                ],
                ['$sort' => [$orderBy => $order]],
                ['$skip' => $page->offset],
                ['$limit' => $page->limit],

            ]
        );

        return ['list' => $retAry, 'totalPage' => $page->getPageCount()];
    }

    /**
     * 查询分区通道的聚合统计数据
     *
     * @param $data
     * @param $orderBy
     * @param $order
     * @param $pageSize
     * @return array
     */
    public static function findSourceZoneData($data, $orderBy, $order, $pageSize)
    {
        if (!isset($pageSize)) {
            $pageSize = Yii::$app->params['perPageCount'];
        }
        //判断传递过来的字段是否为空
        $platformSource = !empty($data['platformSource']) ? $data['platformSource'] : null;//下来检查
        $channelSource = !empty($data['channelSource']) ? $data['channelSource'] : [];
        //组装查询语句
        $match = [
            'and',
            ['between', 'dateTime', (int)$data['sdate'], (int)$data['edate']],
            ['zone' => (int)$data['zone']],
        ];
        //组装分组语句
        $id['zone'] = '$zone';
        //判断来源渠道的信息判断组装聚合数据查询语句
        if (!empty($channelSource)) {
            //向聚合查询数组中添加in查询
            array_push($match, ['in', 'channelSource', $data['channelSource']]);
            $id['channelSource'] = '$channelSource';
        }
        //判断来源频道数组中添加确认的来源频道
        if ($platformSource) {
            array_push($match, ['platformSource' => $data['platformSource']]);
            $id['platformSource'] = '$platformSource';
        }

        //聚合查询信息
        $col = Yii::$app->mongodb->getCollection(self::$tableName);
        $retAry = $col->aggregate(
            [
                ['$match' => $match,
                ],
                ['$group' => ['_id' => $id, 'spv' => ['$sum' => '$pv'], 'suv' => ['$sum' => '$uv'], 'sregisterNum' => ['$sum' => '$registerNum']
                    , 'sbindNum' => ['$sum' => '$bindNum'], 'spayNum' => ['$sum' => '$payNum'],]]
            ]
        );


        //查询总条数
        $sourceZoneData = self::find()->select(["channelSource", "zone", "platformSource", "dateTime", "pv", "uv", "registerNum", "bindNum", "payNum"])
            ->from(self::$tableName)->where(['between', 'dateTime', (int)$data['sdate'], (int)$data['edate']])->andWhere(['zone' => (int)$data['zone']])
            ->filterWhere(['in', 'channelSource', $channelSource])->andFilterWhere(['platformSource' => $platformSource]);

        //构造分页
        $page = new Pagination(['totalCount' => $sourceZoneData->count(), 'pageSize' => $pageSize]);

        //安排续分组查询
        $data = $sourceZoneData->orderBy("$orderBy $order")
            ->offset($page->offset)->limit($page->limit)
            ->asArray()->all();

        return ['list' => $data, 'sumList' => $retAry, 'totalPage' => $page->getPageCount()];
    }


    /**
     * 查询分区导出的聚合统计数据
     *
     * @param $data
     * @param $orderBy
     * @param $order
     * @param $pageSize
     * @return array|ActiveRecord
     */
    public static function exprotSourceZoneData($data, $orderBy, $order, $pageSize)
    {
        $platformSource = !empty($data['platformSource']) ? $data['platformSource'] : null;
        $channel = !empty($data['channelSource']) ? $data['channelSource'] : [];

        $sourceZoneData = self::find()->select(["channelSource", "zone", "platformSource", "dateTime", "pv", "uv", "registerNum", "bindNum", "payNum"])
            ->from(self::$tableName)->where(['between', 'dateTime', (int)$data['sdate'], (int)$data['edate']])->andWhere(['zone' => (int)$data['zone']])
            ->filterWhere(['platformSource' => $platformSource]);

        if (is_array($channel)) {
            $sourceZoneData = $sourceZoneData->andFilterWhere(['in', 'channelSource', $channel]);
        } else {
            $sourceZoneData = $sourceZoneData->andFilterWhere(['channelSource' => $channel]);
        }


        $page = new Pagination(['totalCount' => $sourceZoneData->count(), 'pageSize' => $pageSize]);


        $data = $sourceZoneData->orderBy("$orderBy $order")
            ->offset($page->offset)->limit($page->limit)
            ->asArray()->all();

        return $data;
    }

    /**
     * 向聚合表插入初始聚合信息
     * @param $data
     * @return mixed
     */
    public static function insertData($data)
    {
        return Yii::$app->mongodb->getCollection(self::$tableName)->insert($data);
    }

}