<?php

namespace app\services;

use app\commons\ArrayUtil;
use app\exceptions\TaskException;
use app\models\Article;
use app\models\ArticleTotal;
use app\models\ArticleTotalDetails;
use app\models\Fans;
use app\models\JobChatTimeForm;
use app\models\Menu;
use app\models\Message;
use app\models\RespMsg;
use app\models\Task;
use yii;
use yii\base\Exception;

/**
 * 数据分析处理
 * Class AnalysisService
 * @package app\services
 */
class AnalysisService
{

    /**
     * 获取用户的数据信息
     * @param $appId
     * @return RespMsg
     */
    public function getUserAnalysic($appId)
    {
        $respMsg = new RespMsg();
        //把失败任务状态置为就绪状态。
        $overTimeTasks = Task::find()
            ->where(['status' => 3, 'appId' => $appId])
            ->all();
        foreach ($overTimeTasks as $task) {
            $task->recover(false);
        }
        //获取粉丝数据
        $fansRespMsg = $this->analysisFansData($appId);
        if ($fansRespMsg->return_code == RespMsg::SUCCESS) {
            $respMsg->return_msg['exiting_user'] = $fansRespMsg->return_msg['exiting_user'];
            $respMsg->return_msg['user_summary_list'] = $fansRespMsg->return_msg['user_summary_list'];
        } else {
            $respMsg->return_code = RespMsg::FAIL;
        }
        //获取图文数据
        $articleRespMsg = $this->analysisArticleData($appId);
        if ($articleRespMsg->return_code == RespMsg::SUCCESS) {
            $respMsg->return_msg['article_release_count'] = $articleRespMsg->return_msg['article_release_count'];
            $respMsg->return_msg['article_read_count'] = $articleRespMsg->return_msg['article_read_count'];
            $respMsg->return_msg['article_share_count'] = $articleRespMsg->return_msg['article_share_count'];
        } else {
            $respMsg->return_code = RespMsg::FAIL;
        }
        if ($respMsg->return_code != RespMsg::SUCCESS) {
            $respMsg->return_msg = $fansRespMsg->return_code == RespMsg::FAIL ?
                $fansRespMsg->return_msg : $articleRespMsg->return_msg;
            //获取数据失败，加入工作队列
            try {
                $taskService = new TaskService();
                $taskService->pushAppQueue($appId);
            } catch (TaskException $e) {
                //如果已经在队列中，还没有数据，则重新创建最近一天的任务
                $task = new Task();
                $task->appId = $appId;
                $task->beginDate = date('Y-m-d', strtotime("-1 day"));
                Yii::$app->taskService->buildTask($task);
            }
        }
        Yii::trace('datacenter get user Analysic data = ' . json_encode($respMsg), __METHOD__);

        return $respMsg;
    }

    /**
     * 分析用户的粉丝数据
     * @param $appId
     * @return RespMsg
     */
    public function analysisFansData($appId)
    {
        $respMsg = new RespMsg();

        //判断缓存中是否存在粉丝数据。
        $key = 'app_fans_data_' . $appId;
        try {
            $fansArr = json_decode(Yii::$app->cache->get($key), true);
            if ($fansArr) {
                $respMsg->return_msg = $fansArr;
                return $respMsg;
            }
        } catch (Exception $e) {
            Yii::$app->cache->delete($key);
        }
        //没有缓存数据则读取数据库
        try {
            $fansModel = new Fans();
            $respMsg = $fansModel->getFansDataFromDB($appId);
            if ($respMsg->return_code == RespMsg::SUCCESS) {
                Yii::$app->cache->set($key, json_encode($respMsg->return_msg), 4 * 60 * 60);//缓存4个小时
            }
        } catch (Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '获取粉丝数据统计失败！';
            Yii::warning('获取用户粉丝数据出错：' . $e->getMessage(), __METHOD__);
        }
        return $respMsg;
    }

    /**
     * 获取图文的分析数据
     * @param $appId
     * @return RespMsg
     */
    public function analysisArticleData($appId)
    {
        $respMsg = new RespMsg();

        //判断缓存中是否存在图文数据。
        $key = 'app_article_data_' . $appId;
        try {
            $articleArr = json_decode(Yii::$app->cache->get($key), true);
            if ($articleArr) {
                $respMsg->return_msg = $articleArr;
                //return $respMsg;
            }
        } catch (Exception $e) {
            Yii::$app->cache->delete($key);
        }
        //没有缓存数据则读取数据库
        try {
            $articleModel = new Article();
            $respMsg = $articleModel->getArticleDataFromDB($appId);
            if ($respMsg->return_code == RespMsg::SUCCESS) {
                Yii::$app->cache->set($key, json_encode($respMsg->return_msg), 4 * 60 * 60);
            }
        } catch (Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '获取图文数据统计失败！';
            Yii::warning('获取用户图文数据统计出错：' . $e->getMessage(), __METHOD__);
        }
        return $respMsg;
    }

    /**
     * 获取概况页面的数据
     *
     * @param $appId string 商户的AppId
     * @return RespMsg
     */
    public static function getSurveyData($appId)
    {
        $date = date('Y-m-d', strtotime('-1 day'));

        //获取用户分析的数据
        $data['users'] = Fans::getYestAnalysisData($appId, $date)[0];

        //获取图文分析的数据
        $data['articles'] = Article::getYestImageTextData($appId, $date)[0];

        //获取图文分析的数据
        $data['menus'] = Menu::getYestMenuData($appId, $date)[0];

        //获取消息分析的数据
        $data['messages'] = Message::getYestMessageData($appId, $date)[0];
        return $data;
    }

    /**
     * 获取概况页面的数据
     *
     * @param $appId string 商户的AppId
     * @return RespMsg
     */
    public static function getUserAnalysisData($appId)
    {
        //获取昨日的数据
        $userYestAnalysis = Fans::getYestAnalysisData($appId, date('Y-m-d', strtotime('-1 day')));

        //获取时间维度部分的数据
        $jobChatGetFansSummary = Fans::jobChatGetFansSummary($appId)??[];
        $jobChatGetFansSummary = array_merge($jobChatGetFansSummary, $userYestAnalysis);
        $data['timeDimension'] = self::assembleTimeDimension($jobChatGetFansSummary);

        //获取三日的数据
        $userThreeAnalysis = Fans::getAnalysisData($appId, Yii::$app->params['timeArr']);
        unset($userYestAnalysis[0]['refDate']);
        $userAnalysisData = array_merge($userYestAnalysis, $userThreeAnalysis);

        //组装关键指标的各种数据
        $data['keyIndex'] = self::assembleKeyIndex($userAnalysisData);
        return $data;
    }

    /**
     * 获取图文页面关键指标的数据
     *
     * @param $appId string 商户的AppId
     * @return array
     */
    public static function getImageTextData($appId)
    {
        //获取昨日的数据
        $itYestAnalysis = Article::getYestImageTextData($appId, date('Y-m-d', strtotime('-1 day')));

        //获取三日的数据
        $imageTextThreeAnalysis = Article::getImageTextData($appId, Yii::$app->params['timeArr']);
        $imageTextAnalysisData = array_merge($itYestAnalysis, $imageTextThreeAnalysis);

        $data['keyIndex'] = self::assembleKeyIndex($imageTextAnalysisData);
        $jobChatTimeModel = new JobChatTimeForm();
        $jobChatTimeModel->endTime = date("Y-m-d", strtotime("-1 day"));
        $jobChatTimeModel->startTime = date("Y-m-d", strtotime("-8 day"));
        $data['imageTexts'] = self::getImageTexts($appId, $jobChatTimeModel);
        return $data;
    }

    /**
     * 获取单图文信息的数据
     *
     * @param $appId
     * @param JobChatTimeForm $jobChatTimeModel
     * @return array
     */
    public static function getImageTexts($appId, JobChatTimeForm $jobChatTimeModel)
    {
        //获取发布文章的id
        $articleIds = Article::getArticleIds($jobChatTimeModel, $appId);
        if (!$articleIds) return [];

        //重组文章id做in查询
        $reformArray = ArrayUtil::reformArray($articleIds);

        //获取文章的已经id
        $articleTitles = ArticleTotal::getArticleTitles($reformArray);

        //将文章的id
        $data = ArticleTotalDetails::getImageTexts($articleTitles, $jobChatTimeModel);
        return $data;
    }

    /**
     * 获取消息统计的数据
     *
     * @param $appId
     * @return array
     */
    public static function getMenuData($appId)
    {
        $key = Yii::$app->params['constant']['cache']['menu'] . $appId;
        if ($menuData = Yii::$app->cache->get($key)) {
            return $menuData;
        }

        //获取四日的消息统计数据
        $menuFourAnalysis = Menu::getMenuData($appId, array_merge([date('Y-m-d', strtotime('-1 day'))], Yii::$app->params['timeArr']));

        //获取七天十五天三十天的数据
        $sevenData = Menu::jobChatGetMenuSummary($appId, date('Y-m-d', strtotime('-7 day')))??[];
        $fifteenData = Menu::jobChatGetMenuSummary($appId, date('Y-m-d', strtotime('-15 day')))??[];
        $thirtyData = Menu::jobChatGetMenuSummary($appId, date('Y-m-d', strtotime('-30 day')))??[];
        $sevenData = self::calculateMenuAvgClick($sevenData);
        $fifteenData = self::calculateMenuAvgClick($fifteenData);
        $thirtyData = self::calculateMenuAvgClick($thirtyData);
        $data['timeDimension'] = [
            'sevenData' => $sevenData, 'fifteenData' => $fifteenData, 'thirtyData' => $thirtyData
        ];
        $params = ['user' => 'clickUser', 'count' => 'clickCount', 'avgMsgCount' => 'avgClickCount'];
        $data['keyIndex'] = self::calculateMessageRate($menuFourAnalysis, $params);
        Yii::$app->cache->set($key, $data, strtotime("tomorrow") - time());
        return $data;
    }

    /**
     * 计算前七天，前十五天，前三十天的平均点击量
     *
     * @param $data
     * @return mixed
     */
    private static function calculateMenuAvgClick($data)
    {
        foreach ($data as $key => $val) {
            $data[$key]['avgClickCount'] = !$data[$key]['clickUser'] ? 0 :
                round($data[$key]['clickCount'] / $data[$key]['clickUser']);
        }
        return $data;
    }


    /**
     * 获取消息统计的数据
     *
     * @param $appId
     * @return array
     */
    public static function getMessageData($appId)
    {
        //获取昨日的消息统计的数据
        $msgYestAnalysis = Message::getYestMessageData($appId, date('Y-m-d', strtotime('-1 day')));

        //获取三日的消息统计数据
        $megThreeAnalysis = Message::getMessageData($appId, Yii::$app->params['timeArr']);
        $messageAnalysis = array_merge($msgYestAnalysis, $megThreeAnalysis);

        $jobChatGetMessageSummary = Message::jobChatGetMessageSummary($appId)??[];
        $messageSummary = array_merge($jobChatGetMessageSummary, $msgYestAnalysis);
        $data = self::assembleMessageTimeDimension($messageSummary);
        $params = ['user' => 'msgUser', 'count' => 'msgCount', 'avgMsgCount' => 'avgMsgCount'];
        $data['keyIndex'] = self::calculateMessageRate($messageAnalysis, $params);

        return $data;
    }

    /**
     * 计算消息分析的概率
     *
     * @param $messageAnalysis
     * @param $params
     * @return array
     */
    private static function calculateMessageRate($messageAnalysis, $params)
    {
        $calculateRate = [];
        $calculateRate[$params['user']][] = (int)$messageAnalysis[0][$params['user']];
        $calculateRate[$params['count']][] = (int)$messageAnalysis[0][$params['count']];
        $calculateRate[$params['avgMsgCount']][] = (int)$messageAnalysis[0][$params['avgMsgCount']];
        for ($i = 1; $i <= 3; $i++) {
            $calculateRate[$params['user']][$i] = self::calculateRate(
                $messageAnalysis[0][$params['user']], $messageAnalysis[$i][$params['user']]
            );
            $calculateRate[$params['count']][$i] = self::calculateRate(
                $messageAnalysis[0][$params['count']], $messageAnalysis[$i][$params['count']]
            );
            $messageAnalysis[$i][$params['avgMsgCount']] = $messageAnalysis[$i][$params['user']] ?
                $messageAnalysis[$i][$params['count']] / $messageAnalysis[$i][$params['user']] : 0;
            $calculateRate[$params['avgMsgCount']][$i] = self::calculateRate(
                $messageAnalysis[0][$params['avgMsgCount']], $messageAnalysis[$i][$params['avgMsgCount']]
            );;
        }
        return $calculateRate;
    }

    /**
     * 计算用户概率的
     *
     * @param $data
     * @return array
     */
    private static function assembleKeyIndex($data)
    {
        $calculateRate = [];
        $i = $j = 0;
        foreach ($data as $key => $val) {
            foreach ($val as $k => $v) {
                if ($j <= 3) {
                    $calculateRate[$k][$i] = (int)$data[$i][$k];
                } else {
                    $calculateRate[$k][$i] = self::calculateRate($data[0][$k], $data[$i][$k]);
                }
                $j++;
            }
            $i++;
        }
        return $calculateRate;
    }

    /**
     * 计算概率
     *
     * @param $num
     * @param $number
     * @return float
     */
    private static function calculateRate($num, $number)
    {
        return !(int)$number ? $num * 100 : round(($num - $number) / $number * 100, 1);
    }

    /**
     * 组装时间维度的数据
     *
     * @param $data
     * @return array
     */
    private static function assembleTimeDimension($data)
    {
        $sevenData = ArrayUtil::getInitArray(7);
        $fifteenData = ArrayUtil::getInitArray(15);
        $thirtyData = ArrayUtil::getInitArray(30);
        if ($data) {
            foreach ($data as $value) {
                //获取最近7天的粉丝趋势
                if (isset($sevenData['newUser'][$value['refDate']])) {
                    $sevenData = self::assembleUserSummary($sevenData, $value);
                }
                //获取最近15天的粉丝趋势
                if (isset($fifteenData['newUser'][$value['refDate']])) {
                    $fifteenData = self::assembleUserSummary($fifteenData, $value);
                }
                //获取最近30天的粉丝趋势
                if (isset($thirtyData['newUser'][$value['refDate']])) {
                    $thirtyData = self::assembleUserSummary($thirtyData, $value);
                }
            }
        }
        //返回最终7,15,30天的数据
        return ['sevenData' => $sevenData, 'fifteenData' => $fifteenData, 'thirtyData' => $thirtyData];
    }

    /**
     * 组装用户分析统计的数据
     *
     * @param $data
     * @param $value
     * @return mixed
     */
    private static function assembleUserSummary($data, $value)
    {
        $data['newUser'][$value['refDate']] = (int)$value['newUser'];
        $data['cancelUser'][$value['refDate']] = (int)$value['cancelUser'];
        $data['netgainUser'][$value['refDate']] = (int)$value['netgainUser'];
        $data['cumulateUser'][$value['refDate']] = (int)$value['cumulateUser'];
        return $data;
    }

    /**
     * 组装休息分析界面的时间维度数据
     *
     * @param $messageSummary
     * @return array
     */
    private static function assembleMessageTimeDimension($messageSummary)
    {
        $sevenData = ArrayUtil::getInitMessageArray(7);
        $fifteenData = ArrayUtil::getInitMessageArray(15);
        $thirtyData = ArrayUtil::getInitMessageArray(30);
        if ($messageSummary) {
            foreach ($messageSummary as $value) {
                //获取最近7天的粉丝趋势
                $avgMsgCount = !(int)$value['msgUser'] ? 0 : round($value['msgCount'] / $value['msgUser'], 1);
                if (isset($sevenData['msgUser'][$value['refDate']])) {
                    $sevenData = self::assembleMessageSummary($sevenData, $value, $avgMsgCount);
                }
                //获取最近15天的粉丝趋势
                if (isset($fifteenData['msgUser'][$value['refDate']])) {
                    $fifteenData = self::assembleMessageSummary($fifteenData, $value, $avgMsgCount);
                }
                //获取最近30天的粉丝趋势
                if (isset($thirtyData['msgUser'][$value['refDate']])) {
                    $thirtyData = self::assembleMessageSummary($thirtyData, $value, $avgMsgCount);
                }
            }
        }

        $sevenData = self::assembleDistributeRate($sevenData);
        $fifteenData = self::assembleDistributeRate($fifteenData);
        $thirtyData = self::assembleDistributeRate($thirtyData);


        //返回最终7,15,30天的数据
        return [
            'timeDimension' => [
                'sevenData' => $sevenData, 'fifteenData' => $fifteenData, 'thirtyData' => $thirtyData
            ],
        ];
    }

    /**
     * 组装消息统计的数据
     *
     * @param $data
     * @param $value
     * @param $avgMsgCount
     * @return mixed
     */
    private static function assembleMessageSummary($data, $value, $avgMsgCount)
    {
        $data['msgUser'][$value['refDate']] = (int)$value['msgUser'];
        $data['msgCount'][$value['refDate']] = (int)$value['msgCount'];
        $data['avgMsgCount'][$value['refDate']] = $avgMsgCount;
        switch ($value['countInterval']) {
            case 0:
                $data['oneToFive']['sendUserCount'] += (int)$value['msgUser'];
                break;
            case 1:
                $data['oneToFive']['sendUserCount'] += (int)$value['msgUser'];
                break;
            case 2:
                $data['sixToTen']['sendUserCount'] += (int)$value['msgUser'];
                break;
            case 3:
                $data['tenUp']['sendUserCount'] += (int)$value['msgUser'];
                break;
        }
        return $data;
    }

    /**
     * 计算消息分布的概率
     *
     * @param $data
     * @return mixed
     */
    private static function assembleDistributeRate($data)
    {
        $dataCount = $data['oneToFive']['sendUserCount'] + $data['sixToTen']['sendUserCount'] +
            $data['tenUp']['sendUserCount'];
        $data['oneToFive']['sendRate'] = self::calculateDistributeRate($dataCount, $data['oneToFive']['sendUserCount']);
        $data['sixToTen']['sendRate'] = self::calculateDistributeRate($dataCount, $data['sixToTen']['sendUserCount']);
        $data['tenUp']['sendRate'] = self::calculateDistributeRate($dataCount, $data['tenUp']['sendUserCount']);
        return $data;
    }

    /**
     * 计算分布的概率
     *
     * @param $dataCount
     * @param $count
     * @return int|string
     */
    private static function calculateDistributeRate($dataCount, $count)
    {
        return !$dataCount ? 0 : sprintf("%.2f", $count / $dataCount * 100);
    }

}