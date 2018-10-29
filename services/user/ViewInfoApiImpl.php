<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12 0012
 * Time: 13:53
 */

namespace app\services\user;

use app\models\QueueVerify;
use Idouzi\Commons\ExcelExportUtil;
use app\exceptions\SystemException;
use app\models\dao\SourceDataRecordDAO;
use app\models\dao\UserUvRecordDAO;
use app\models\SourceZone;
use app\models\SourceDataRecord;
use app\models\dao\SourceZoneDAO;
use app\models\dao\UserRecordDAO;
use app\models\dao\RegisteredRecordDAO;
use app\models\dao\ZoneIdDAO;
use Idouzi\Commons\StringUtil;
use Idouzi\Commons\HttpUtil;
use Yii;
use Idouzi\Commons\SecurityUtil;


/**
 * 用户访问记录服务
 *
 * Class ViewInfoApiImpl
 * @package app\services\user
 */
class ViewInfoApiImpl implements ViewInfoApi
{

    /**
     * 处理消息队列传进来的数据
     * 用户未注册时传递用户信息
     *
     * @param array|string $userInfo
     * @return array
     * @throws SystemException
     */
    public function dealUserViewInfo(array $userInfo)
    {
        $receiptHandles = [];//用于存放后面需要删除的消息队列的key
        $i = 0;
        $insertData = [];//存放批量插入的数据
        $channelSource = [];
        //遍历将消息队列的信息拿出
        foreach ($userInfo as $key => $val) {
            if (!isset($userInfo[$key]->code) || $userInfo[$key]->code !== 0) {
                continue;
            }
            //用数组形式转换成需要的数据
            $data = json_decode($userInfo[$key]->msgBody, true);
            $queueVerify = new QueueVerify();
            if (!$queueVerify->queueVerify($data)) {
                throw new SystemException('消息队里传递信息不全' . json_encode($data));
            }
            $insertData[$i]['dateTime'] = (int)date('Ymd', $data['dateTime']);
            $insertData[$i]['userSign'] = $data['userSign'];//校验数据
            $insertData[$i]['viewUrl'] = $data['viewUrl'];
            $insertData[$i]['userIp'] = $data['userIp'];
            $insertData[$i]['userAgent'] = $data['userAgent'];
            $insertData[$i]['platformSource'] = $data['platformSource'];
            if (!empty($data['refererUrl'])) {
                $channelSource[$i] = $data['refererUrl'];
            } else {
                $insertData[$i]['zoneId'] = 10001;
                $insertData[$i]['zone'] = 0;
                $insertData[$i]['refererUrl'] = '';
                $insertData[$i]['channelSource'] = Yii::$app->params['sourceData']['channelSource'];
            }
            //获取需要删除消息队列内的键值
            $receiptHandles[] = $userInfo[$key]->receiptHandle;
            $i++;
        }

        //初始化信息记录表
        if (!self::dealUserViewInfoData($insertData, $channelSource, $userInfo)) {
            throw new SystemException('批量插入用户访问数据错误');
        }

        if (!$receiptHandles) {
            throw new SystemException('批量插入用户访问数据错误');
        }
        return $receiptHandles;
    }


    /**
     * 初始化用户信息，并记录用户的访问记录
     *
     * @param $insertData
     * @param $channelSource
     * @param $userInfo
     * @return bool
     */
    public static function dealUserViewInfoData($insertData, $channelSource, $userInfo)
    {
        try {
            $dateTime = date('Ymd');
            //重组消息队列内数据，组装成保存在数据库中的数据
            if (!empty($channelSource)) {
                $insertData = self::resetViewInfoData($insertData, $channelSource);
            }
            if (!$insertData) {
                Yii::warning('从消息队列拿到的用户访问数据出错或不存在：' .
                    json_encode($userInfo) . '处理时间：' . date('Y-m-d H:i:s'));
                throw new SystemException('从消息队列拿到的用户访问数据出错或不存在!');
            }

            //判断新建用户信息添加到聚合表中
            foreach ($insertData as $k => $v) {
                if (!self::insertDataToSourceDataRecord($v)) {
                    Yii::warning('聚合表插入初始信息失败');
                    return false;
                }
            }
            //批量插入数据到 UserRecord表 有关pv信息
            if (!self::recordToSourceData($insertData, $dateTime, 'pv')) {
                Yii::warning('批量插入数据到 UserRecord表pv' . $dateTime, __METHOD__);
                return false;
            }
            //处理进入UserUvRecord表的数据并批量插入
            $uniqueData = self::dealUserUvRecord($insertData);
            //批量插入数据到 UserRecord表
            if ($uniqueData) {
                self::recordToSourceData($uniqueData, $dateTime, 'uv');
            }

            return true;

        } catch (\Exception $e) {
            Yii::warning('批量插入用户访问数据错误' . $e->getMessage(), __METHOD__);
        }
    }


    /**
     * 处理消息队列传进来的数据
     * 用户注册后传递注册id进来写入表中
     *
     * @param array|string $userInfo
     * @return array
     */
    public function dealRegisteredUserViewInfo(array $userInfo)
    {
        $receiptHandles = [];//用于存放后面需要删除的消息队列的key
        //遍历将消息队列的信息拿出
        foreach ($userInfo as $key => $val) {
            if (!isset($userInfo[$key]->code) || $userInfo[$key]->code !== 0) {
                continue;
            }
            //用数组形式转换成需要的数据
            $data = json_decode($userInfo[$key]->msgBody, true);

            self::recordUserInfoToSourceData($data);

            //获取需要删除消息队列内的键值
            $receiptHandles[] = $userInfo[$key]->receiptHandle;
        }
        return $receiptHandles;

    }


    /**
     * 重组消息队列内数据，组装成保存在数据库中的数据
     *
     * @param $channelSource
     * @return array
     * @internal param $insertData
     */
    private static function resetViewInfoData($insertData, $channelSource)
    {

        $sourceZone = SourceZoneDAO::findSourceZone();
        foreach ($channelSource as $key => &$val) {
            foreach ($sourceZone as $k => &$v) {
                if (!empty($v['refererUrl']) && strpos($val, $v['refererUrl']) !== false) {
                    $insertData[$key]['zoneId'] = $v['zoneId'];
                    $insertData[$key]['zone'] = $v['zone'];
                    $insertData[$key]['refererUrl'] = $v['refererUrl'];
                    $insertData[$key]['channelSource'] = $v['channelSource'];
                    break;
                }
            }
            if (!isset($insertData[$key]['channelSource']) || $insertData[$key]['channelSource'] == '') {
                $insertData[$key]['zoneId'] = 10004;
                $insertData[$key]['zone'] = 0;
                $insertData[$key]['refererUrl'] = $val;
                $insertData[$key]['channelSource'] = "未知";
            }

        }
        return $insertData;
    }


    /**
     * 展示来源渠道信息
     * @param  $zone
     * @return mixed
     */
    public function showAllSourceZone($zone)
    {
        return ['list' => SourceZoneDAO::showAllSourceZone($zone), 'totalPage' => 1];
    }

    /**
     * 插入新的渠道来源信息
     * @param array $data
     * @return bool
     * @throws SystemException
     */
    public function newInsertSourceZone(array $data)
    {
        $model = new SourceZone();
        $model->createTime = date('Ymd');
        $model->zone = (int)$data['zone'];
        $model->refererUrl = $data['refererUrl'];
        $model->channelSource = $data['channelSource'];
        //model校验
        if (!$model->load($data, '') || !$model->validate()) {
            throw new SystemException(current($model->getFirstErrors()));
        }
        //取出model信息
        $data = $model->insertData();
        //插入到表中
        if (!SourceZoneDAO::insertSourceZone($data)) {
            return false;
        }
        return true;

    }

    /**
     * 修改来源渠道信息
     * @param array $data
     * @return bool
     * @throws SystemException
     */
    public function updateSourceZone(array $data)
    {
        $model = new SourceZone();
        $model->zoneId = $data['zoneId'];
        $model->refererUrl = $data['refererUrl'];
        $model->channelSource = $data['channelSource'];
        //model校验
        if (!$model->load($data, '') || !$model->validate()) {
            throw new SystemException(current($model->getFirstErrors()));
        }
        //取出model信息
        $data = $model->updateData();
        //插入到表中
        if (!SourceZoneDAO::updateSourceZone($model->zoneId, $data)) {
            return false;
        }
        return true;

    }

    /**
     * 删除来源渠道信息
     * @param array $data
     * @return mixed
     */
    public function deleteSourceZone(array $data)
    {
        if (!SourceZoneDAO::deleteSourceZone($data)) {
            return false;
        }
        return true;
    }


    /**
     * 过滤用户信息，in查询查出已存在的用户唯一标识
     *
     * @param array $data
     * @return array
     */
    private static function dealUserUvRecord(array $data): array
    {
        $userSign = [];
        $sign = [];
        foreach ($data as $key => &$v) {
            if (isset($v['userSign'])) {
                $userSign[] = $v['userSign'];
            }
        }
        $userSign = array_unique($userSign);
        $dateTime = date('Ymd');
        $result = UserUvRecordDAO::findExistUserSign((int)$dateTime, $userSign);

        foreach ($result as $key => $value) {
            $sign[] = ($value['userSign']);
        }
        $result = array_unique($sign);
        $return = self::resetUserUvRecord($result, $data);
        return $return;
    }

    /**
     * 重新组装数据，将原有的数据进行过滤，过滤出userUvRecord表不存在的userSign信息
     *
     * @param array $uniqueData
     * @param array $insertData
     * @return array
     */
    private static function resetUserUvRecord(array $uniqueData, array $insertData): array
    {
        if (empty($uniqueData)) {
            return $insertData;
        }
        foreach ($uniqueData as $val) {
            foreach ($insertData as $k => $v) {
                if ($val == $v['userSign']) {
                    unset($insertData[$k]);
                    continue;
                }
            }
        }
        return $insertData;
    }


    /**
     * 来源渠道筛选
     * @param int $zone
     * @return mixed
     */
    public function showChannelSource($zone)
    {
        if ($zone == '') {
            return SourceZoneDAO::findAllChannelSource();
        }
        return SourceZoneDAO::findChoiceChannelSource($zone);
    }

    /**
     * 设置聚合表数据信息
     */
    public function setSourceData()
    {
        $dateTime = date('Ymd', strtotime('-1 day'));
        $supplierBlind = self::getSupplierBlindInfo();
        $supplierBlind = array_column($supplierBlind, 'supplierId');
        //查询注册用户对应的注册时间
        $Blind = RegisteredRecordDAO::findSupplierIdByDate($supplierBlind);
        if (!empty($Blind)) {
            foreach ($Blind as $value) {
                $supplierBlindCount = UserUvRecordDAO::findBindCounts(date('Ymd',$value['dateTime']), $supplierBlind);
            }
            //向聚合表插入用户绑定信息
            self::recordToSourceData($supplierBlindCount, $dateTime, 'bindNum');
        }

        $supplierPay = self::getSupplierPayInfo();
        $supplierPay = array_column($supplierPay, 'wxid');
        //查询注册用户对应的注册时间
        $Pay = RegisteredRecordDAO::findSupplierIdByDate($supplierPay);
        if (!empty($Pay)) {
            foreach ($Pay as $value) {
                $supplierBlindCount = UserUvRecordDAO::findPayCounts(date('Ymd',$value['dateTime'], $supplierBlind));
            }
            //向聚合表插入用户付费信息
            self::recordToSourceData($supplierBlindCount, $dateTime, 'bindNum');
        }
    }

    /**
     * 获取聚合表所需要的数据
     * @param array $data
     * @param $pageSize
     * @return mixed|void channelSource   来源渠道
     * channelSource   来源渠道
     * edate           结束时间
     * order           排序规则
     * orderBy         排序字段
     * page            分页数
     * platformSource  来源渠道
     * sdate           开始时间
     * zone            所属分区
     */
    public function getSourceData(array $data, $pageSize)
    {

        if (!isset($data['channelSource']) && !isset($data['platformSource']) && !isset($data['zone'])) {
            $edate = $data['edate'];
            $order = $data['order'];
            $orderBy = $data['orderBy'];
            $sdate = $data['sdate'];
            $return = SourceDataRecordDAO::findSourceData($edate, $order, $orderBy, $sdate, $pageSize);
            $list = $return['list'];
            $list = self::countPercentOfData($list);

            return ['list' => $list, 'totalPage' => $return['totalPage']];
        }
        $order = $data['order'];
        $orderBy = $data['orderBy'];
        $return = SourceDataRecordDAO::findSourceZoneData($data, $orderBy, $order, $pageSize);
        $list = $return['list'];
        $list = self::countPercentOfData($list);
        return ['list' => $list, 'sumList' => $return['sumList'], 'totalPage' => $return['totalPage']];

    }

    /**
     * 用户访问信息数据导出
     *
     * @param array $data
     * @param $pageSize
     * @return array
     */
    private static function exportSourceData(array $data, $pageSize)
    {
        if (!isset($data['channelSource']) && !isset($data['platformSource']) && !isset($data['zone'])) {
            $edate = $data['edate'];
            $order = $data['order'];
            $orderBy = $data['orderBy'];
            $sdate = $data['sdate'];
            $return = SourceDataRecordDAO::findSourceData($edate, $order, $orderBy, $sdate, $pageSize);
            $list = $return['list'];
            $list = self::countPercentOfData($list);
            return ['list' => $list, 'totalPage' => $return['totalPage'], 'flag' => 'sum'];
        }
        if (isset($data['channelSource']) && strpos($data['channelSource'], ',') != null) {
            $data['channelSource'] = explode(',', $data['channelSource']);
        }
        $order = $data['order'];
        $orderBy = $data['orderBy'];
        $return = SourceDataRecordDAO::exprotSourceZoneData($data, $orderBy, $order, $pageSize);
        $list = self::countPercentOfData($return);
        return ['list' => $list];
    }


    /**
     * 组装个环节转换率
     *
     * @param $list
     * @return mixed
     */
    private static function countPercentOfData($list)//UV为O的判断
    {
        foreach ($list as $key => $val) {
            if ($val['uv'] == 0) {
                $list[$key]['registerNumPercent'] = 0;
                $list[$key]['bindNumPercent'] = 0;
                $list[$key]['payNumPercent'] = 0;
            } else {
                $list[$key]['registerNumPercent'] = $val['registerNum'] / $val['uv'];
                $list[$key]['bindNumPercent'] = $val['bindNum'] / $val['uv'];
                $list[$key]['payNumPercent'] = $val['payNum'] / $val['uv'];
            }
        }
        return $list;
    }


    /**
     * 聚合表记录数据
     *
     * @param $data
     * @param $dateTime
     * @param $filed
     * @return bool|mixed
     */
    private static function recordToSourceData(array $data, string $dateTime, string $filed)
    {
        $SourceData = [];
        foreach ($data as $key => $val) {
            $SourceData['zoneId'] = $val['zoneId'];
            $SourceData['platformSource'] = $val['platformSource'];
            SourceDataRecordDAO::recordToSourceData($SourceData, $filed, (int)$dateTime);
        }
        if ($filed == 'pv') {
            return UserRecordDAO::batchInsert($dateTime, $data);
        } elseif ($filed == 'uv') {
            return UserUvRecordDAO::batchInsert($dateTime, $data);
        } else {
            return true;
        }
    }

    /**
     * 记录用户信息到聚合表
     *
     * @param $data
     * @return bool|mixed
     */
    private static function recordUserInfoToSourceData(array $data)
    {
        $dateTime = $data['dateTime'] = date('Ymd', $data['dateTime']);
        $updateUserInfo = UserUvRecordDAO::updateRegisteredUserInfo(
            $dateTime, $data['supplierId'], $data['userSign']);
        if (!RegisteredRecordDAO::insertData($data)) {
            Yii::warning('插入用户注册时间失败：' . $data['supplierId'] .
                '用户userSign：' . $data['userSign']);
        }

        if ($updateUserInfo === false) {
            Yii::warning('userUvRecord表插入用户Id：' . $data['supplierId'] .
                '用户userSign：' . $data['userSign'] . '不存在！！！');
        }
        $uniqueData = UserUvRecordDAO::findExistSupplierId($dateTime, $data['supplierId']);

        return self::recordToSourceData($uniqueData, $dateTime, 'registerNum');
    }


    /**
     * 根据时间查询用户绑定信息
     *
     * @return array
     */
    private static function getSupplierBlindInfo(): array
    {
        $get = [
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr()
        ];//拼装get参数
        $post = [
            'sdate' => date('Y-m-d', strtotime('-1 day')),
            'edate' => date('Y-m-d'),
        ];
        //爱豆子大后台拉取数据
        $url = Yii::$app->params['domains']['security'] . '/api/get-supplier-blind-info.html?';
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['publicKeys']['security']))->generateSign();
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::simplePost($url, $post), true);
        if ($resp && $resp['return_code'] === 'SUCCESS') {
            return $resp['return_msg'];
        }

        Yii::warning('查询用户绑定信息失败,error=' . json_encode($resp), __METHOD__);
        return [];
    }

    /**
     * 根据时间查询用户付费信息
     *
     * @return array
     * @internal param $post
     */
    private static function getSupplierPayInfo(): array
    {
        $get = [
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr()
        ];//拼装get参数
        $post = [
            'sdate' => (int)strtotime(date('Ymd', strtotime('-1 day'))),
            'edate' => (int)strtotime(date('Ymd')),
        ];

        //调用商城接口赠送用户优惠券
        $url = Yii::$app->params['domains']['mall'] . '/api/get-supplier-pay-info?';
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['publicKeys']['mall']))->generateSign();
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::simplePost($url, $post), true);
        if (isset($resp['return_code']) && $resp['return_code'] === 'SUCCESS') {
            return $resp['return_msg'];
        }
        Yii::warning('查询用户付费信息失败，error=' . json_encode($resp), __METHOD__);
        return [];
    }


    /**
     * seo聚合表数据导出
     *
     * @param array $data
     * @return mixed|void
     * @throws SystemException
     */
    public function UserSourceDataExport(array $data)
    {
        $exportData = self::exportSourceData($data, $pageSize = 4000);
        if (!$exportData['list']) {
            throw new SystemException('查询日期暂无数据，请重新选择');
        }
        if (isset($exportData['flag']) && $exportData['flag'] == 'sum') {
            return self::createSumSourceDataExcel($exportData);
        }
        return self::createUserSourceDataExcel($exportData);
    }


    /**
     * 合计数据导出
     *
     * @param $data
     * @return bool
     */
    public static function createSumSourceDataExcel(array $data)
    {
        $cellHeadArr = [
            '_id' => '日期',
            'pv' => '展现量',
            'uv' => 'uv',
            'registerNum' => '注册用户数',
            'registerNumPercent' => '注册转化率',
            'bindNum' => '绑定公众号用户数',
            'bindNumPercent' => '绑定率',
            'payNum' => '付费用户数',
            'payNumPercent' => '付费率',
        ];
        $excelExport = new ExcelExportUtil('统计列表', $cellHeadArr);
        //组装要导出的数据
        $exportData = self::collatingSumSourceDataExcel($data);
        //echo json_encode($exportData);die;
        $excelExport->setExcelData($exportData);
        $excelExport->doExportToBrowser('seo统计' . date('YmdHis'));
        return true;
    }


    /**
     * 合计数据导出组装
     *
     * @param $data
     * @return array
     */
    public static function collatingSumSourceDataExcel(array $data)
    {
        $res = [];
        foreach ($data['list'] as $k => $v) {
            $res[$k]['_id'] = $v['_id'];
            $res[$k]['pv'] = $v['pv'] ?? ' ';
            $res[$k]['uv'] = $v['uv'] ?? ' ';
            $res[$k]['registerNum'] = $v['registerNum'] ?? ' ';
            $res[$k]['bindNum'] = $v['bindNum'] ?? ' ';
            $res[$k]['payNum'] = $v['payNum'] ?? ' ';
            $res[$k]['registerNumPercent'] = number_format($v['registerNumPercent'], 4) * 100 . '%' ?? ' ';
            $res[$k]['bindNumPercent'] = number_format($v['bindNumPercent'], 4) * 100 . '%' ?? ' ';
            $res[$k]['payNumPercent'] = number_format($v['payNumPercent'], 4) * 100 . '%' ?? ' ';

        }

        return $res;
    }

    /**
     * 分区数据导出
     *
     * @param array $data
     * @return bool
     */
    public static function createUserSourceDataExcel(array $data)
    {
        $cellHeadArr = [
            'dateTime' => '日期',
            'channelSource' => '来源渠道',
            'platformSource' => '来源频道',
            'zone' => '分区标识',
            'pv' => '展现量',
            'uv' => 'uv',
            'registerNum' => '注册用户数',
            'registerNumPercent' => '注册转化率',
            'bindNum' => '绑定公众号用户数',
            'bindNumPercent' => '绑定率',
            'payNum' => '付费用户数',
            'payNumPercent' => '付费率',
        ];
        $excelExport = new ExcelExportUtil('统计列表', $cellHeadArr);
        //组装要导出的数据
        $exportData = self::collatingUserSourceDataExcel($data);
        $excelExport->setExcelData($exportData);
        $excelExport->doExportToBrowser('seo统计' . date('YmdHis'));
        return true;
    }

    /**
     * 分区数据导出组装
     *
     * @param array $data
     * @return array
     */
    private static function collatingUserSourceDataExcel(array $data)
    {
        $res = [];
        foreach ($data['list'] as $k => $v) {//_id代表用户ID
            $res[$k]['dateTime'] = $v['dateTime'];
            $res[$k]['channelSource'] = $v['channelSource'] ?? ' ';
            $res[$k]['platformSource'] = $v['platformSource'] ?? ' ';
            $res[$k]['zone'] = $v['zone'] ?? ' ';
            $res[$k]['pv'] = $v['pv'] ?? ' ';
            $res[$k]['uv'] = $v['uv'] ?? ' ';
            $res[$k]['registerNum'] = $v['registerNum'] ?? ' ';
            $res[$k]['bindNum'] = $v['bindNum'] ?? ' ';
            $res[$k]['payNum'] = $v['payNum'] ?? ' ';
            $res[$k]['registerNumPercent'] = number_format($v['registerNumPercent'], 4) * 100 . '%' ?? ' ';
            $res[$k]['bindNumPercent'] = number_format($v['bindNumPercent'], 4) * 100 . '%' ?? ' ';
            $res[$k]['payNumPercent'] = number_format($v['payNumPercent'], 4) * 100 . '%' ?? ' ';

        }

        return $res;
    }


    /**
     * 插入新的聚合统计到聚合表
     *
     * @param $data
     * @return bool
     * @throws SystemException
     */
    private static function insertDataToSourceDataRecord(array $data)
    {
        $queueVerify = new QueueVerify();
        if (!$queueVerify->SourceDataVerify($data)) {
            throw new SystemException('聚合表查找数据不全缺少相关字段' . json_encode($data));
        }
        $count = SourceDataRecordDAO::findRecordSourceData($data);//传一模型校验
        if ($count == 0) {
            $model = new SourceDataRecord();
            $model->dateTime = (int)$data['dateTime'];
            $model->zone = $data['zone'];
            $model->platformSource = $data['platformSource'];
            $model->channelSource = $data['channelSource'];
            $model->zoneId = $data['zoneId'];
            //model校验
            if (!$model->load($data, '') || !$model->validate()) {
                throw new SystemException(current($model->getFirstErrors()));
            }
            //取出model信息
            $data = $model->insertData();
            if (!SourceDataRecordDAO::insertData($data)) {
                return false;
            }
        }
        return true;
    }

}