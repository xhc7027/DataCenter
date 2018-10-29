<?php

namespace app\services;

use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use app\commons\StringUtil;
use app\exceptions\CurlException;
use app\exceptions\SystemException;
use app\exceptions\TaskException;
use app\models\Api;
use app\models\ApiItem;
use app\models\AppQueue;
use app\models\Article;
use app\models\ArticleSummary;
use app\models\ArticleTotal;
use app\models\ArticleTotalDetails;
use app\models\ArticleUserRead;
use app\models\ArticleUserReadHour;
use app\models\ArticleUserShare;
use app\models\ArticleUserShareHour;
use app\models\Fans;
use app\models\FansItem;
use app\models\Menu;
use app\models\MenuDetail;
use app\models\Message;
use app\models\MessageItem;
use app\models\MessageItemHour;
use app\models\RespMsg;
use app\models\Task;
use Curl\Curl;
use yii;
use yii\db\Query;
use Idouzi\Commons\QCloud\TencentQueueUtil;

/**
 * 所有与任务相关的业务操作
 * @package app\services
 */
class TaskService
{
    /**
     * 每队列中每个公众号分别创建独立的任务
     * @param Task $task
     */
    public function buildTask(Task $task)
    {
        //检查前一天的同步任务是否已经创建过，主要是为了避免重复同步数据
        $beginDateCount = Task::find()->where(['beginDate' => $task->beginDate])->count();
        //如果有传具体所属公众号，则表示仅为它创建任务
        if ($task->appId) {
            $this->executeTask($task, $beginDateCount);
        } else {
            //从队列中获取公众号列表
            $appQueues = (new Query())->from('app_queue');
            foreach ($appQueues->each() as $app) {//分批获取防止内存溢出
                $work = new Task();
                $work->beginDate = $task->beginDate;
                $work->appId = $app['appId'];
                $this->executeTask($work, $beginDateCount);
            }
        }
    }

    /**
     * 发送创建任务到消息队列
     * @param $data
     * @return null
     */
    private function sendTaskToQueue($data)
    {
        if (!isset($data)) {
            return null;
        }
        TencentQueueUtil::sendMessage(Yii::$app->params['queue-dc-executeTask'], json_encode($data));
    }

    /**
     * 处理创建任务数据
     *
     * @param array $data
     * @return bool
     * @internal param array $menuData
     */
    public static function dealTaskToQueue(array $data)
    {
        try {
            if (!isset($data) && !empty($data)) {
                return null;
            }
            $task = new Task(['scenario' => 'page']);
            $task->beginDate = $data['beginDate'];
            $task->appId = $data['appId'];
            $task->taskType = $data['taskType'];
            $task->id = $data['id'];
            $task->start();
        } catch (\Exception $e) {
            Yii::warning('日志' . $e->getMessage(), __METHOD__);
        }
        return false;
    }

    /**
     * 检查是否有重复任务，否则创建任务并立即启动
     * @param Task $task
     * @param int $beginDateCount 小于等于0表示不需要检查是否重复
     */
    private function executeTask(Task $task, int $beginDateCount)
    {
        $data = [];
        $count = 0;
        if ($beginDateCount > 0) {
            $count = Task::find()->where(['appId' => $task->appId, 'beginDate' => $task->beginDate])->count();
        }

        if ($count == 0) {
            $task->taskType = 0;
            try {
                $task->create();
                $data['beginDate'] = $task->beginDate;
                $data['appId'] = $task->appId;
                $data['taskType'] = $task->taskType;
                $data['id'] = $task->id;
                $data['status'] = $task->status;
                $this->sendTaskToQueue($data);
            } catch (TaskException $e) {
                Yii::warning('保存任务失败:' . json_encode($task) . ', 模型校验:' . json_encode($task->errors), __METHOD__);
            }
        } else {
            Yii::trace('appId ' . $task->appId . ' beginDate ' . $task->beginDate . '已经存在', __METHOD__);
        }
    }

    /**
     * 将指定公众号加入同步数据的队列中，在队列中的公众号会纳入每天的同步任务中。
     *
     * 如果公众号不在队列中则将其插入，如果存在则抛一个任务异常。
     *
     * @param string $appId
     * @param string $supplierId
     * @return bool
     * @throws TaskException
     */
    public function pushAppQueue(string $appId, string $supplierId = '')
    {
        //1.查询此公众号是否在队列中
        $appQueue = AppQueue::findOne($appId);
        //1.1如果不在队列中则将其插入
        if (!$appQueue) {
            $appQueue = new AppQueue();
            $appQueue->appId = $appId;
            $appQueue->entryTime = date('Y-m-d H:i:s');
            $appQueue->supplierId = $supplierId;

            $appQueue->insert();
        } else {
            throw new TaskException('此公众号' . $appId . '已经存在于队列中');
        }

        $beginDateList = Yii::$app->dateTimeUtil->daysFromNow(strtotime('-30 day'));

        //2.1逐一创建任务并不马上执行，等待同步脚本来执行
        foreach ($beginDateList as $beginDate) {
            $task = new Task();
            $task->appId = $appId;
            $task->taskType = 0;
            $task->beginDate = $beginDate;
            $task->create();
        }
        return true;
    }

    /**
     * 从代理平台请求数据并写到数据库<br>
     *
     * 接口回来的数据已经包含了微信数据统计中按天所能获取到的所有数据
     * @param $task
     * @throws CurlException
     * @throws TaskException
     */
    public function synData($task)
    {
        //从代理平台请求获取数据
        $url = Yii::$app->params['serviceDomain']['weiXinApiDomain'] . '/facade/data-statistics';
        $params = [
            'appId' => $task->appId,
            'beginDate' => $task->beginDate,
            'endDate' => $task->beginDate,
            'timestamp' => time(),
        ];
        $sign = (new SecurityUtil($params, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $params['sign'] = $sign;

        //调用接口
        $curl = new Curl();
        $curl->get($url, $params);
        $error = $curl->error;
        $respMsg = json_decode($curl->response);
        Yii::trace($curl->response, __METHOD__);
        $curl->close();
        if ($error) {
            throw new CurlException('调用外部接口发现错误:' . json_encode($curl->response));
        }

        //从整体数据中获取各个接口的数据，再调用对应的方法进行单独处理
        if ($respMsg->return_code != RespMsg::SUCCESS) {
            throw new CurlException('从代理平台请求数据错误 ' . $respMsg->return_msg);
        }

        $data = $respMsg->return_msg;

        //按照每个接口分别取出数据再调用对应的方法进行处理
        $weiXinDataApi = Yii::$app->params['weiXinDataApi'];
        foreach ($weiXinDataApi as $item => $value) {
            $method = strtolower($value['type']);

            //调用对应处理方法
            if (isset($data->$method) && $data->$method && !empty($data->$method->list)) {
                //判断此分接口数据是否获取正确
                if (isset($data->$method->error)) {
                    throw new TaskException('处理类型为' . $method . '的数据时发现错误:' . $data->$method->error);
                }

                if (!$this->$method($data->$method->list, $task)) {
                    throw new TaskException('处理类型为' . $method . '的数据时发现错误');
                }
            } else {
                Yii::trace('接口中没有发现 ' . $method . ' 类型数据', __METHOD__);
            }
        }
    }

    /**
     * 处理用户增减数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUserSummary($data, $task)
    {
        try {
            $fans = Fans::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$fans) {
                $fans = new Fans();
                $fans->appId = $task->appId;
                $fans->refDate = $task->beginDate;
            }
            $fans->newUser = 0;
            $fans->cancelUser = 0;
            $fans->netgainUser = 0;
            foreach ($data as $datum) {
                $fans->newUser += $datum->new_user;
                $fans->cancelUser += $datum->cancel_user;
            }
            $fans->netgainUser = $fans->newUser - $fans->cancelUser;
            $fans->save();

            //记录用户的渠道详细数据
            foreach ($data as $datum) {
                $fansItem = FansItem::findOne(['fid' => $fans->id, 'userSource' => $datum->user_source]);
                if (!$fansItem) {
                    $fansItem = new FansItem();
                    $fansItem->fid = $fans->id;
                    $fansItem->userSource = $datum->user_source;
                }
                $fansItem->newUser = $datum->new_user;
                $fansItem->cancelUser = $datum->cancel_user;
                $fansItem->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理用户增减数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取累计用户数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUserCumulate($data, $task)
    {
        try {
            $fans = Fans::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$fans) {
                $fans = new Fans();
                $fans->appId = $task->appId;
                $fans->refDate = $task->beginDate;
            }

            foreach ($data as $datum) {
                $fans->cumulateUser = $datum->cumulate_user;
                $fans->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取累计用户数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取图文统计数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUserRead($data, $task)
    {
        try {
            $article = Article::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$article) {
                $article = new Article();
                $article->appId = $task->appId;
                $article->refDate = $task->beginDate;
            }
            $article->intPageReadUser = $article->intPageReadCount = $article->oriPageReadUser = $article->shareUser =
            $article->oriPageReadCount = $article->shareCount = $article->addToFavUser = $article->addToFavCount = 0;
            foreach ($data as $datum) {
                $article->intPageReadUser += $datum->int_page_read_user;
                $article->intPageReadCount += $datum->int_page_read_count;
                $article->oriPageReadUser += $datum->ori_page_read_user;
                $article->oriPageReadCount += $datum->ori_page_read_count;
                $article->shareUser += $datum->share_user;
                $article->shareCount += $datum->share_count;
                $article->addToFavUser += $datum->add_to_fav_user;
                $article->addToFavCount += $datum->add_to_fav_count;
            }
            $article->save();

            foreach ($data as $datum) {
                $articleUserRead = ArticleUserRead::findOne(['aid' => $task->appId, 'userSource' => $task->beginDate]);
                if (!$articleUserRead) {
                    $articleUserRead = new ArticleUserRead();
                    $articleUserRead->aid = $article->id;
                    $articleUserRead->userSource = $datum->user_source;
                    $articleUserRead->intPageReadUser = $datum->int_page_read_user;
                    $articleUserRead->intPageReadCount = $datum->int_page_read_count;
                    $articleUserRead->oriPageReadUser = $datum->ori_page_read_user;
                    $articleUserRead->oriPageReadCount = $datum->ori_page_read_count;
                    $articleUserRead->shareUser = $datum->share_user;
                    $articleUserRead->shareCount = $datum->share_count;
                    $articleUserRead->addToFavUser = $datum->add_to_fav_user;
                    $articleUserRead->addToFavCount = $datum->add_to_fav_count;
                }
                $articleUserRead->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取图文统计数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取图文群发总数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getArticleTotal($data, $task)
    {
        try {
            $article = Article::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$article) {
                $article = new Article();
                $article->appId = $task->appId;
                $article->refDate = $task->beginDate;
                $article->save();
            }

            foreach ($data as $datum) {
                $index = strpos($datum->msgid, '_');
                $msgId = substr($datum->msgid, 0, $index);
                $msgIndex = substr($datum->msgid, $index + 1, strlen($datum->msgid));

                $articleTotal = ArticleTotal::findOne(
                    ['aid' => $article->id, 'refDate' => $datum->ref_date, 'msgId' => $msgId, 'msgIndex' => $msgIndex]
                );
                if (!$articleTotal) {
                    $articleTotal = new ArticleTotal();
                    $articleTotal->aid = $article->id;
                    $articleTotal->refDate = $datum->ref_date;
                    $articleTotal->msgId = $msgId;
                    $articleTotal->msgIndex = $msgIndex;
                }
                $articleTotal->title = $datum->title;
                $articleTotal->save();

                $details = $datum->details;
                foreach ($details as $detail) {
                    $articleTotalDetails = ArticleTotalDetails::findOne(
                        ['atId' => $articleTotal->id, 'statDate' => $detail->stat_date]
                    );
                    if (!$articleTotalDetails) {
                        $articleTotalDetails = new ArticleTotalDetails();
                        $articleTotalDetails->atId = $articleTotal->id;
                        $articleTotalDetails->statDate = $detail->stat_date;
                    }
                    $articleTotalDetails->targetUser = $detail->target_user;
                    $articleTotalDetails->intPageReadUser = $detail->int_page_read_user;
                    $articleTotalDetails->intPageReadCount = $detail->int_page_read_count;
                    $articleTotalDetails->oriPageReadUser = $detail->ori_page_read_user;
                    $articleTotalDetails->oriPageReadCount = $detail->ori_page_read_count;
                    $articleTotalDetails->shareUser = $detail->share_user;
                    $articleTotalDetails->shareCount = $detail->share_count;
                    $articleTotalDetails->addToFavUser = $detail->add_to_fav_user;
                    $articleTotalDetails->addToFavCount = $detail->add_to_fav_count;
                    $articleTotalDetails->intPageFromSessionReadUser = $detail->int_page_from_session_read_user;
                    $articleTotalDetails->intPageFromSessionReadCount = $detail->int_page_from_session_read_count;
                    $articleTotalDetails->intPageFromHistMsgReadUser = $detail->int_page_from_hist_msg_read_user;
                    $articleTotalDetails->intPageFromHistMsgReadCount = $detail->int_page_from_hist_msg_read_count;
                    $articleTotalDetails->intPageFromFeedReadUser = $detail->int_page_from_feed_read_user;
                    $articleTotalDetails->intPageFromFeedReadCount = $detail->int_page_from_feed_read_count;
                    $articleTotalDetails->intPageFromFriendsReadUser = $detail->int_page_from_friends_read_user;
                    $articleTotalDetails->intPageFromFriendsReadCount = $detail->int_page_from_friends_read_count;
                    $articleTotalDetails->intPageFromOtherReadUser = $detail->int_page_from_other_read_user;
                    $articleTotalDetails->intPageFromOtherReadCount = $detail->int_page_from_other_read_count;
                    $articleTotalDetails->feedShareFromSessionUser = $detail->feed_share_from_session_user;
                    $articleTotalDetails->feedShareFromSessionCnt = $detail->feed_share_from_session_cnt;
                    $articleTotalDetails->feedShareFromFeedUser = $detail->feed_share_from_feed_user;
                    $articleTotalDetails->feedShareFromFeedCnt = $detail->feed_share_from_feed_cnt;
                    $articleTotalDetails->feedShareFromOtherUser = $detail->feed_share_from_other_user;
                    $articleTotalDetails->feedShareFromOtherCnt = $detail->feed_share_from_other_cnt;
                    $articleTotalDetails->save();
                }
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取图文群发总数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取图文群发每日数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getArticleSummary($data, $task)
    {
        try {
            $article = Article::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$article) {
                $article = new Article();
                $article->appId = $task->appId;
                $article->refDate = $task->beginDate;
                $article->save();
            }

            foreach ($data as $datum) {
                $index = strpos($datum->msgid, '_');
                $msgId = substr($datum->msgid, 0, $index);
                $msgIndex = substr($datum->msgid, $index + 1, strlen($datum->msgid));

                $articleSummary = ArticleSummary::findOne(
                    ['aid' => $article->id, 'msgId' => $msgId, 'msgIndex' => $msgIndex]
                );
                if (!$articleSummary) {
                    $articleSummary = new ArticleSummary();
                    $articleSummary->aid = $article->id;
                    $articleSummary->msgId = $msgId;
                    $articleSummary->msgIndex = $msgIndex;
                }
                $articleSummary->title = $datum->title;
                $articleSummary->intPageReadUser = $datum->int_page_read_user;
                $articleSummary->intPageReadCount = $datum->int_page_read_count;
                $articleSummary->oriPageReadUser = $datum->ori_page_read_user;
                $articleSummary->oriPageReadCount = $datum->ori_page_read_count;
                $articleSummary->shareUser = $datum->share_user;
                $articleSummary->shareCount = $datum->share_count;
                $articleSummary->addToFavUser = $datum->add_to_fav_user;
                $articleSummary->addToFavCount = $datum->add_to_fav_count;
                $articleSummary->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取图文群发每日数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取图文统计分时数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUserReadHour($data, $task)
    {
        try {
            $article = Article::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$article) {
                $article = new Article();
                $article->appId = $task->appId;
                $article->refDate = $task->beginDate;
                $article->save();
            }

            foreach ($data as $datum) {
                $userReadHour = ArticleUserReadHour::findOne(
                    [
                        'aid' => $article->id, 'refDate' => $datum->ref_date,
                        'refHour' => $datum->ref_hour, 'userSource' => $datum->user_source
                    ]
                );
                if (!$userReadHour) {
                    $userReadHour = new ArticleUserReadHour();
                    $userReadHour->aid = $article->id;
                    $userReadHour->refDate = $datum->ref_date;
                    $userReadHour->refHour = $datum->ref_hour;
                    $userReadHour->userSource = $datum->user_source;
                }
                $userReadHour->intPageReadUser = $datum->int_page_read_user;
                $userReadHour->intPageReadCount = $datum->int_page_read_count;
                $userReadHour->oriPageReadUser = $datum->ori_page_read_user;
                $userReadHour->oriPageReadCount = $datum->ori_page_read_count;
                $userReadHour->shareUser = $datum->share_user;
                $userReadHour->shareCount = $datum->share_count;
                $userReadHour->addToFavUser = $datum->add_to_fav_user;
                $userReadHour->addToFavCount = $datum->add_to_fav_count;
                $userReadHour->totalOnlineTime = $datum->total_online_time;
                $userReadHour->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取图文统计分时数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取图文分享转发数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUserShare($data, $task)
    {
        try {
            $article = Article::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$article) {
                $article = new Article();
                $article->appId = $task->appId;
                $article->refDate = $task->beginDate;
                $article->save();
            }

            foreach ($data as $datum) {
                $userShare = ArticleUserShare::findOne(['aid' => $article->id, 'shareScene' => $datum->share_scene]);
                if (!$userShare) {
                    $userShare = new ArticleUserShare();
                    $userShare->aid = $article->id;
                    $userShare->shareScene = $datum->share_scene;
                }
                $userShare->shareCount = $datum->share_count;
                $userShare->shareUser = $datum->share_user;
                $userShare->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取图文分享转发数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取图文分享转发分时数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUserShareHour($data, $task)
    {
        try {
            $article = Article::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$article) {
                $article = new Article();
                $article->appId = $task->appId;
                $article->refDate = $task->beginDate;
                $article->save();
            }

            foreach ($data as $datum) {
                $userShare = ArticleUserShare::findOne(['aid' => $article->id, 'shareScene' => $datum->share_scene]);
                if (!$userShare) {
                    $userShare = new ArticleUserShare();
                    $userShare->aid = $article->id;
                    $userShare->shareScene = $datum->share_scene;
                    $userShare->save();
                }

                $userShareHour = ArticleUserShareHour::findOne(
                    ['ausId' => $userShare->id, 'refHour' => $datum->ref_hour, 'shareScene' => $datum->share_scene]
                );
                if (!$userShareHour) {
                    $userShareHour = new ArticleUserShareHour();
                    $userShareHour->ausId = $userShare->id;
                    $userShareHour->refHour = $datum->ref_hour;
                    $userShareHour->shareScene = $datum->share_scene;
                }
                $userShareHour->shareCount = $datum->share_count;
                $userShareHour->shareUser = $datum->share_user;
                $userShareHour->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取图文分享转发分时数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取接口分析数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getInterfaceSummary($data, $task)
    {
        try {
            foreach ($data as $datum) {
                $api = Api::findOne(
                    [
                        'appId' => $task->appId,
                        'refDate' => $datum->ref_date,
                    ]
                );
                if (!$api) {
                    $api = new Api();
                    $api->appId = $task->appId;
                    $api->refDate = $datum->ref_date;
                }
                $api->callbackCount = $datum->callback_count;
                $api->failCount = $datum->fail_count;
                $api->totalTimeCost = $datum->total_time_cost;
                $api->maxTimeCost = $datum->max_time_cost;
                if ($api->totalTimeCost > 0 && $api->callbackCount > 0) {
                    $api->avgTimeCost = $api->totalTimeCost / $api->callbackCount;
                }
                $api->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取接口分析数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取接口分析分时数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getInterfaceSummaryHour($data, $task)
    {
        try {
            //查询父接口数据
            $api = Api::findOne(
                [
                    'appId' => $task->appId,
                    'refDate' => $task->beginDate,
                ]
            );
            if (!$api) {
                $api = new Api();
                $api->appId = $task->appId;
                $api->refDate = $task->beginDate;
                $api->save();
            }

            foreach ($data as $datum) {
                $apiItem = ApiItem::findOne(
                    [
                        'aid' => $api->id,
                        'refHour' => $datum->ref_hour,
                    ]
                );
                if (!$apiItem) {
                    $apiItem = new ApiItem();
                    $apiItem->aid = $api->id;
                    $apiItem->refHour = $datum->ref_hour;
                }
                $apiItem->callbackCount = $datum->callback_count;
                $apiItem->failCount = $datum->fail_count;
                $apiItem->totalTimeCost = $datum->total_time_cost;
                $apiItem->maxTimeCost = $datum->max_time_cost;
                if ($apiItem->totalTimeCost > 0 && $apiItem->callbackCount > 0) {
                    $apiItem->avgTimeCost = $apiItem->totalTimeCost / $apiItem->callbackCount;
                }
                $apiItem->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取接口分析分时数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取消息发送概况数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUpstreamMsg($data, $task)
    {
        try {
            $message = Message::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$message) {
                $message = new Message();
                $message->appId = $task->appId;
                $message->refDate = $task->beginDate;
                $message->save();
            }
            $message->msgUser = $message->msgCount = 0;
            foreach ($data as $datum) {
                $messageItem = MessageItem::findOne(['mid' => $message->id, 'msgType' => $datum->msg_type]);
                if (!$messageItem) {
                    $messageItem = new MessageItem();
                    $messageItem->mid = $message->id;
                    $messageItem->msgType = $datum->msg_type;
                }
                $messageItem->msgUser = $datum->msg_user;
                $messageItem->msgCount = $datum->msg_count;
                $messageItem->save();
                $message->msgUser += $datum->msg_user;
                $message->msgCount += $datum->msg_count;
            }
            $message->save();
        } catch (\Exception $e) {
            Yii::warning('处理获取消息发送概况数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 获取消息分送分时数据
     * @param null|array $data
     * @param Task $task
     * @return boolean
     */
    private function getUpstreamMsgHour($data, $task)
    {
        try {
            $message = Message::findOne(['appId' => $task->appId, 'refDate' => $task->beginDate]);
            if (!$message) {
                $message = new Message();
                $message->appId = $task->appId;
                $message->refDate = $task->beginDate;
                $message->save();
            }

            foreach ($data as $datum) {
                $messageItem = MessageItem::findOne(['mid' => $message->id, 'msgType' => $datum->msg_type]);
                if (!$messageItem) {
                    $messageItem = new MessageItem();
                    $messageItem->mid = $message->id;
                    $messageItem->msgType = $datum->msg_type;
                    $messageItem->save();
                }

                $itemHour = MessageItemHour::findOne(
                    ['miId' => $messageItem->id, 'msgType' => $datum->msg_type, 'refHour' => $datum->ref_hour]
                );
                if (!$itemHour) {
                    $itemHour = new MessageItemHour();
                    $itemHour->miId = $messageItem->id;
                    $itemHour->msgType = $datum->msg_type;
                    $itemHour->refHour = $datum->ref_hour;
                }
                $itemHour->msgUser = $datum->msg_user;
                $itemHour->msgCount = $datum->msg_count;
                $itemHour->save();
            }
        } catch (\Exception $e) {
            Yii::warning('处理获取消息分送分时数据出错 ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * 处理菜单数据
     *
     * @param array $menuData
     * @return bool
     */
    public static function dealMenuData(array $menuData)
    {
        try {
            $where = [
                'wxId' => $menuData['wxId'], 'keyVal' => $menuData['keyVal'], 'menuName' => $menuData['menuName'],
                'openId' => $menuData['openId']
            ];
            //判断当天该用户是否存在点击记录
            $menuDetailCount = MenuDetail::find()
                ->where($where)
                ->andFilterWhere(['>=', 'createTime', strtotime('today')])
                ->andFilterWhere(['<', 'createTime', strtotime('tomorrow')])
                ->count();
            $menuDetailModel = new MenuDetail();
            $menuDetailModel->load($menuData, '');
            if ($menuDetailModel->insert()) {
                return self::insertMenuData($where, $menuData, $menuDetailCount);
            }
        } catch (\Exception $e) {
            Yii::warning('日志' . $e->getMessage(), __METHOD__);
        }
        return false;
    }

    /**
     * 插入菜单统计数据
     *
     * @param $where
     * @param $menuData
     * @param $menuDetailCount
     * @return bool|false|int
     */
    private static function insertMenuData($where, $menuData, $menuDetailCount)
    {
        unset($where['openId']);
        $refDate = date('Y-m-d', $menuData['createTime']);
        $where['refDate'] = $refDate;
        $menu = Menu::getMenuObject($where);
        if (!$menu) {
            $menu = new Menu();
            $menu->load($menuData, '');
            $menu->clickCount = 1;
            $menu->clickUser = 1;
            $menu->refDate = $refDate;
            return $menu->insert();
        } else {
            if ($menuDetailCount) {
                $menu->clickCount++;
            } else {
                $menu->clickCount++;
                $menu->clickUser++;
            }
            return $menu->update();
        }
    }

    /**
     * 删除七天前的所有任务
     * 此动作在每天凌晨1点开始执行
     */
    public static function delBeforeTask()
    {
        $time = date('Y-m-d', strtotime(Yii::$app->params['delTaskTime']));
        Task::deleteAll("endTime < :time", [':time' => $time]);
    }


    /**
     * 用户授权回调业务开始
     * @param $data
     * @return bool
     */
    public function authorizeDispatch($data)
    {
        //todo 删掉之前的换绑新绑逻辑
        try {
            if ($data['data']['InfoType'] == 'unauthorized')//解绑
            {
                if (!$this->deleteAppQueue('appId', $data['AuthorizerAppid'])) {
                    return false;
                }
            } elseif ($data['InfoType'] == 'updateauthorized')//换绑
            {
                if (AppQueue::find()->where(['appId' => $data['AuthorizerAppid']])->count()) {
                    return true;
                }
                // TODO : 添加判断失败的返回值
                $supplierId = $this->authorizeUpdate($data['AuthorizerAppid']);
                if (!$supplierId || !$this->deleteAppQueue('supplierId', $supplierId['wxId'])) {
                    return false;
                }
            } elseif ($data['InfoType'] == 'authorized')//新绑
            {
                if (!$this->pushAppQueue($data['AuthorizerAppid'])) {
                    return false;
                }
            } else {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            Yii::warning('更改用户绑定信息失败' . $e->getMessage(), __METHOD__);
        }

    }

    /**
     * 删除AppQueue中的AppId
     * @param $type
     * @param $appId
     * @return array|void
     * @internal param $data
     * @internal param $appId
     */
    public function deleteAppQueue($type, $appId)
    {
        $exist = AppQueue::find()->where(['supplierId' => $appId])->count();
        if (!$exist) {
            return true;
        }
        //删除对应取消绑定的appId
        $return = (new AppQueue())->deleteInfo($type, $appId);
        if (!$return) {
            Yii::warning('删除数据失败，失败类型：' . $type . '失败数据' . json_encode($appId));
            return [];
        }
        return $return;
    }

    /**
     * 用户换绑用APPid获取用户supplierId
     * @param $appId
     * @return bool
     */
    public function authorizeUpdate($appId)
    {
        $url = Yii::$app->params['serviceDomain']['weiXinApiDomain'] . '/facade/get-supplier-id';
        $params = [
            'appId' => $appId,
            'type' => 'string',
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr(16),
        ];
        $sign = (new SecurityUtil($params, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $params['sign'] = $sign;

        $resp = json_decode(HttpUtil::get($url, http_build_query($params)), true);
        if ($resp['return_msg']['return_code'] && $resp['return_msg']['return_code'] === 'SUCCESS') {
            return $resp['return_msg']['return_msg'];
        }
        return false;
    }

    /**
     * 更新商城用户授权信息
     *
     * @param $data
     * @return bool
     */
    public function updateMallAuthorInfo($data)
    {
        $url = Yii::$app->params['serviceDomain']['mallDomain'] . '/api/update-mall-author-info?';
        $get = [
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr(),
        ];
        $sign = (new SecurityUtil($get, Yii::$app->params['signKey']['mallSignKey']))->generateSign();
        $get['sign'] = $sign;
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::post($url, ['data' => $data]), true);
        if ($resp['return_msg']['return_code'] && $resp['return_msg']['return_code'] === 'SUCCESS') {
            return $resp['return_msg']['return_msg'];
        }
        return false;
    }

    /**
     * 重组获取的AppId数组结构
     *
     * @param $appId
     * @return array
     */
    public static function resetArray($appId)
    {
        $data = [];
        if (!$appId) {
            return [];
        }

        foreach ($appId as $key => $value) {
            foreach ($value as $k => $v) {
                $data[] = $v;
            }
        }
        return $data;
    }

}