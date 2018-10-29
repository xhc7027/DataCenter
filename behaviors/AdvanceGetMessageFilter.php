<?php
namespace app\behaviors;

use app\models\Task;
use yii\base\Behavior;
use yii\base\Controller;
use Yii;

/**
 * 需要提前执行获取数据的过滤器
 * 工作通请求数据时有可能该公众号前一天的数据没有跑回来
 * 这时候就提前将该公众号的数据拉去回来
 *
 *
 * Class AdvanceGetMessageFilter
 * @package app\behaviors
 */
class AdvanceGetMessageFilter extends Behavior
{
    /**
     * 仅过滤次数组声明的action
     *
     * @var array
     */
    public $actions = [];

    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * 请求前置拦截--判断是否需要及时拉去该公众号的数据回来
     *
     * @param $event
     * @return mixed
     */
    public function beforeAction($event)
    {
        if (!in_array($event->action->id, $this->actions)) {
            return $event->isValid;
        }
        //及时拉取数据的时间只能在某个时间段
        $hour = date('H');
        if (in_array($hour, Yii::$app->params['executeScriptTime'])) {
            return $event->isValid;
        }
        $key = Yii::$app->params['constant']['session']['appId'] . Yii::$app->request->get('supplierId');
        $appId = Yii::$app->session->get($key);
        $where = ['beginDate' => date('Y-m-d', strtotime('-1 day')), 'appId' => $appId, 'status' => [0, 3]];
        //判断该公众号的数据是否拉取，没有则及时拉取
        if (!$taskInfo = Task::getTaskObject($where)) {
            Yii::warning("不需要拉取", __METHOD__);
            return $event->isValid;
        }
        try {
            Yii::warning("拉取中", __METHOD__);
            $taskInfo->status = 0;
            $taskInfo->start();
        } catch (\Exception $e) {
            Yii::warning("提前及时同步数据失败" . $e->getMessage(), __METHOD__);
        }
        return $event->isValid;
    }
}