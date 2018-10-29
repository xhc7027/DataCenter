<?php

namespace app\services;


use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use app\commons\StringUtil;
use app\exceptions\SystemException;
use app\models\ArticleSummary;
use Yii;

class DataService
{
    /**
     * 获取文章对应的
     * @param $data
     * @return bool
     */
    public static function getArticleData($data)
    {
        try {
            if (!$data) {
                return false;
            }
            $guardData = $data;
            $res = [];
            foreach ($data as $supplierId => $mediaIds) {
                //查询用户的appId
                $appId = self::getAppId($supplierId);
                //获取每个商家的总阅读数据
                $res[$supplierId] = self::getSupplierIdAllArticleRead($mediaIds, $appId);
                unset($data[$supplierId]);
            }
            //发送数据到广告系统
            self::sendDataToAd($res);
        } catch (\Exception $e) {
            Yii::warning('处理数据失败' . $e->getMessage(), __METHOD__);
            return $guardData;
        }

        return $data;
    }

    /**
     * 向代理平台获取用户的appId
     */
    public static function getAppId($supplierId)
    {
        $key = Yii::$app->params['constant']['cache']['appId'].$supplierId;

        if ($res = Yii::$app->cache->get($key)) {
            return $res;
        }
        $get = array(
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr(),
            'wxid' => $supplierId
        );//拼装get参数
        $url = Yii::$app->params['serviceDomain']['weiXinApiDomain'] . '/facade/get-app-info?';
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::get($url), true);

        if (isset($resp['return_msg']['return_code']) && $resp['return_msg']['return_code'] == 'SUCCESS') {
            Yii::$app->cache->set(
                $key,
                $resp['return_msg']['return_msg']['appId'],
                Yii::$app->params['constant']['cache']['appId']['time']
            );

            return $resp['return_msg']['return_msg']['appId'];
        }

        Yii::warning('获取用户appId失败' . json_encode($resp));
        return null;
    }

    /**
     * 发送数据到广告中心
     * @param $res
     * @return mixed
     * @throws SystemException
     */
    public static function sendDataToAd($res)
    {
        if (!$res) {
            return true;
        }
        $get = [
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr()
        ];//拼装get参数
        //从广告系统创建广告并返回广告编码
        $url = Yii::$app->params['serviceDomain']['adDomain'] . '/api/receiver-data?';
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['adSignKey']))->generateSign();
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::simplePost($url, $res), true);

        if (isset($resp['return_code']) && $resp['return_code'] === 'SUCCESS') {
            return true;
        }
        Yii::warning('发送广告数据，data=' . json_encode($res), __METHOD__);
        throw new SystemException('广告系统处理数据失败');
    }

    /**
     * 处理同一个商家所有文章之和
     * @param $mediaIds
     * @return int|mixed
     */
    public static function getSupplierIdAllArticleRead($mediaIds, $appId)
    {
        $res = [];
        foreach ($mediaIds as $key => $item) {
            $dayReadData = self::getDayArticleData(
                (int)$item['mediaId'],
                (int)$item['index'],
                date('Y-m-d', strtotime('-1 day')),
                $appId
            );
            if (!$dayReadData || $dayReadData['intPageReadUser'] == 0) {
                continue;
            }
            $res[$key]['mediaId'] = $item['mediaId'];
            $res[$key]['index'] = $item['index'];
            $res[$key]['intPageReadUser'] = $dayReadData['intPageReadUser'];
        }

        return $res;
    }

    /**
     * 获取对应文章的阅读数据
     * @param $msgId
     * @param $msgIndex
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getDayArticleData(int $msgId, int $msgIndex, $date, $appId)
    {
        return ArticleSummary::find()->select(['article_summary.intPageReadUser'])
            ->innerJoin('article', 'article.id=article_summary.aid')
            ->where(['refDate' => $date, 'appId' => $appId])
            ->andWhere(['msgId' => $msgId])
            ->andWhere(['msgIndex' => $msgIndex])
            ->asArray()->one();
    }

}