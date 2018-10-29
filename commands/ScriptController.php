<?php

namespace app\commands;

use app\models\AppQueue;
use Idouzi\Commons\QCloud\TencentQueueUtil;
use Idouzi\Commons\HttpUtil;
use Idouzi\Commons\StringUtil;
use Idouzi\Commons\SecurityUtil;
use app\exceptions\MqException;
use app\exceptions\SystemException;
use app\models\Menu;
use app\models\Task;
use app\services\AnalysisService;
use app\services\DataService;
use app\services\TaskService;
use app\services\user\ViewInfoApiImpl;
use app\services\Factory;
use yii;
use Exception;
use yii\console\Controller;
use app\models\dao\UserUvRecordDAO;

/**
 * 数据同步脚本控制器
 * 使用方法：在项目根目录下面执行
 * <code>
 * ./yii script
 * //或者是具体的某个动作
 * ./yii script index
 * </code>
 * @author tianmingxing
 */
class ScriptController extends Controller
{
    /**
     * @var float 记录脚本开始的时间
     */
    private $timeStart = null;

    public function beforeAction($action)
    {
        $this->timeStart = time();
        Yii::info(date('Y-m-d H:i:s', $this->timeStart) . "开始执行脚本", __METHOD__);
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        $timeEnd = time();
        $time = $timeEnd - $this->timeStart;
        Yii::info("{$action->id}脚本执行总耗时：{$time}秒", __METHOD__);
        return parent::afterAction($action, $result);
    }

    /**
     * 默认动作
     */
    public function actionIndex()
    {
        echo "=============================\n";
        echo "你可以在这里创建新的脚本任务，目前所支持的脚本有：\n";
        echo "./yii script/index 缺省处理\n";
        echo "./yii script/create-day-task 创建日同步任务\n";
        echo "./yii script/sync-data 同步数据\n";
        echo "./yii script/deal-menu-data 处理消息队列的菜单数据\n";
        echo "./yii script/handle-article-data 处理消息队列的菜单数据\n";
        echo "./yii script/get-user-info 处理消息队列的菜单数据\n";
        echo "./yii script/get-registered-user-info 处理消息队列的菜单数据\n";
        echo "./yii script/set-source-data 处理消息队列的菜单数据\n";
        echo "./yii script/get-all-wx-id-for-app-queue 将用户AppId和WxId绑定起来\n";
        echo "./yii script/update-authorize-dispatch 用户重新绑定信息脚本\n";
        echo "./yii script/get-authorizer-info 用户授权回调脚本\n";
        echo "=============================\n";
    }

    /**
     * 创建日同步任务<br>
     * 此动作在每天凌晨1点开始执行
     */
    public function actionCreateDayTask()
    {
        $yesterdayStr = date('Y-m-d', strtotime("-1 day"));
        $task = new Task();
        $task->beginDate = $yesterdayStr;
        Yii::$app->taskService->buildTask($task);
    }

    /**
     * 删除七天前的所有任务
     * 此动作在每天凌晨1点开始执行
     */
    public function actionDelTask()
    {
        try {
            TaskService::delBeforeTask();
        } catch (\Exception $e) {
            Yii::warning('执行删除七天前任务失败:' . $e->getMessage());
        }
    }

    /**
     * <p>通过代理平台找微信同步统计数据</p>
     * <p>此动作间隔每10分钟执行一次，仅在08点-23点之间才能真正处理业务</p>
     */
    public function actionSyncData()
    {
        // 判断当前时间是否处在5点-23点之间，如果是则继续
        $hour = date('H');
        if (in_array($hour, Yii::$app->params['executeScriptTime'])) {
            exit;
        }
        $limit = Yii::$app->params['syncDataLimit'];
        //把超时任务（仅处理重试次数小于等于10次的任务）置为失败状态
        $tasks = Task::find()
            ->where(['status' => 1])
            ->limit($limit)
            ->andWhere(['<=', 'retryCount', 10])
            ->all();

        if ($tasks && count($tasks) > 0) {
            foreach ($tasks as $task) {
                $task->overtime();
            }
        }

        //把失败任务置为就绪状态
        $tasks = Task::find()
            ->where(['status' => 3])
            ->limit($limit)
            ->andWhere(['<=', 'retryCount', 10])
            ->all();

        if ($tasks && count($tasks) > 0) {
            foreach ($tasks as $task) {
                $task->recover();
            }
        }

        //执行就绪状态的任务
        $tasks = Task::find()
            ->where(['status' => 0])
            ->limit($limit)
            ->andWhere(['<=', 'retryCount', 3])
            ->all();

        if ($tasks && count($tasks) > 0) {
            foreach ($tasks as $task) {
                $task->start();
            }
        }
    }

    /**
     *  获取消息队列信息处理Task任务
     */
    public function actionDealTaskToQueue()
    {
        try {
            //每次从消息队列获取16条数据处理，单次最大16条
            $queue = TencentQueueUtil::batchReceiveMessage(Yii::$app->params['queue-dc-executeTask'], 16);
            if (!$queue) {
                return;
            }
            $receiptHandles = [];//用于存放后面需要批量删除的消息队列的key
            foreach ($queue as $key => $val) {
                if (!isset($queue[$key]->code) || $queue[$key]->code !== 0) {
                    continue;
                }
                //只有处理成功才会将该消息队列的key记录
                if (TaskService::DealTaskToQueue(json_decode($queue[$key]->msgBody, true))) {
                    $receiptHandles[] = $queue[$key]->receiptHandle;
                }
            }
            if ($receiptHandles) {
                TencentQueueUtil::batchDeleteMessage(
                    Yii::$app->params['queue-dc-executeTask'], $receiptHandles
                );
            }
        } catch (MqException $e) {
            Yii::warning("处理消息队列里的菜单数据失败：" . $e->getMessage(), __METHOD__);
        } catch (Exception $e) {
            Yii::warning("系统异常：" . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * 处理消息队列的菜单数据
     */
    public function actionDealMenuData()
    {
        try {
            //每次从消息队列获取16条数据处理，单次最大16条
            $queue = TencentQueueUtil::batchReceiveMessage(Yii::$app->params['queue-msg-menuData'], 16);
            if (!$queue) {
                return;
            }
            $receiptHandles = [];//用于存放后面需要批量删除的消息队列的key
            foreach ($queue as $key => $val) {
                if (!isset($queue[$key]->code) || $queue[$key]->code !== 0) {
                    continue;
                }
                //只有处理成功才会将该消息队列的key记录
                if (TaskService::dealMenuData(json_decode($queue[$key]->msgBody, true))) {
                    $receiptHandles[] = $queue[$key]->receiptHandle;
                }
            }
            if ($receiptHandles) {
                TencentQueueUtil::batchDeleteMessage(
                    Yii::$app->params['queue-msg-menuData'], $receiptHandles
                );
            }
        } catch (MqException $e) {
            Yii::warning("处理消息队列里的菜单数据失败：" . $e->getMessage(), __METHOD__);
        } catch (Exception $e) {
            Yii::warning("系统异常：" . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * 处理需要获取每天文章数据
     */
    public function actionHandleArticleData()
    {
        try {
            $model = TencentQueueUtil::receiveMessage(Yii::$app->params['queue-dataCenter-getArticleRead']);

            if (isset($model->code) && $model->code === 0) {

                $msg = $model->msgBody;
                //选择业务实现
                if ($data = DataService::getArticleData(json_decode($msg, true))) {
                    //删除队列消息
                    TencentQueueUtil::deleteMessage(
                        Yii::$app->params['queue-dataCenter-getArticleRead'],
                        $model->receiptHandle
                    );
                    //存入队列
                    TencentQueueUtil::sendMessage(
                        Yii::$app->params['queue-dataCenter-getArticleRead'],
                        json_encode($data)
                    );
                    return true;
                }
                //删除队列消息
                TencentQueueUtil::deleteMessage(
                    Yii::$app->params['queue-dataCenter-getArticleRead'],
                    $model->receiptHandle
                );
            }
        } catch (Exception $e) {
            Yii::warning("商获取对应文章数据错误,error：" . $e->getMessage(), __METHOD__);
            throw new SystemException('商获取对应文章数据错误,error=' . $e->getMessage());
        }

        return true;
    }

    /**
     * 接受消息队列信息
     * 用户未注册的信息
     */
    public function actionGetUserInfo()
    {
        try {
            $queueName = Yii::$app->params['queue-dc-originView'];
            $queue = TencentQueueUtil::batchReceiveMessage($queueName, 16);
            if (!$queue) {
                return;
            }
            //处理消息队列内的信息
            $receiptHandles = Factory::getViewInfoService()->dealUserViewInfo($queue);//校验数据

            //将处理过的消息队列的键删除
            if ($receiptHandles) {
                TencentQueueUtil::batchDeleteMessage($queueName, $receiptHandles);
            }
        } catch (\Exception $e) {
            Yii::warning('批量插入用户访问数据错误' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * 接受消息队列信息
     * 用户已注册处的信息
     */
    public function actionGetRegisteredUserInfo()
    {
        try {
            $queueName = Yii::$app->params['queue-dc-registeredView'];
            $queue = TencentQueueUtil::batchReceiveMessage($queueName, 16);
            if (!$queue) {
                return;
            }
            //处理消息队列内的信息
            $receiptHandles = Factory::getViewInfoService()->dealRegisteredUserViewInfo($queue);

            //将处理过的消息队列的键删除
            if ($receiptHandles) {
                TencentQueueUtil::batchDeleteMessage($queueName, $receiptHandles);
            }
        } catch (\Exception $e) {
            Yii::warning('批量插入用户访问数据错误' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * 返回统计表所需要的数据（聚合表）
     *
     */
    public function actionSetSourceData()
    {
        try {
            Factory::getViewInfoService()->setSourceData();
        } catch (Exception $e) {
            Yii::warning('将用户绑定付费写入seo数据聚合表出错：' . $e->getMessage(), __METHOD__);

        }
    }

    /**
     * 用户授权回调脚本
     */
    public function actionGetAuthorizerInfo()
    {
        $queueName = Yii::$app->params['queue-dc-authorizerInfo'];
        try {
            $queue = TencentQueueUtil::receiveMessage($queueName);
            if (!$queue) {
                return;
            }
            if (!isset($queue->code) || $queue->code !== 0) {
                TencentQueueUtil::deleteMessage($queueName, $queue->receiptHandle);
                return;
            }
            $data = json_decode($queue->msgBody, true);
            if ($data['type'] == '解绑') {
                if (!(new TaskService)->authorizeDispatch($data)) {
                    Yii::warning('接收到的数据有误' . json_encode($data));
                }
            }
            TencentQueueUtil::deleteMessage($queueName, $queue->receiptHandle);
        } catch (\Exception $e) {
            Yii::warning('不断发送公众号换绑事务消息失败' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * 将用户AppId和WxId绑定起来
     */
    public function actionGetAllWxIdForAppQueue()
    {
        $appId = AppQueue::getAppId();
        $appId = TaskService::resetArray($appId);
        $data = ['appIds' => $appId];
        $get = [
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr()
        ];
        $url = Yii::$app->params['serviceDomain']['weiXinApiDomain'] . '/facade/get-supplier-ids?';
        $sign = (new SecurityUtil($get, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $get['sign'] = $sign;
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::post($url, $data), true);
        if (isset($resp['return_msg']['return_code']) && $resp['return_msg']['return_code'] === 'SUCCESS') {
            $data = $resp['return_msg']['return_msg'];
            foreach ($data as $key => $value) {
                if (!$value['wxId'] || !$value['appId']) {
                    continue;
                }
                if (!AppQueue::updateAllSupplierId($value)) {
                    Yii::warning('更新用户Id失败，用户信息：' . $value);
                    continue;
                }
            }
        }
    }


}
