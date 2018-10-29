<?php

namespace app\controllers\filters;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * <p>非法请求拦截</p>
 *
 * <p>1、请求频率过高的访问者进行频率控制</p>
 *
 * @package app\controllers\filters
 */
class IllegalRequestFilter extends Behavior
{
    /**
     * 记录沉默时间
     */
    const SILENCE_TIME = 'silence_time';
    /**
     * 记录每次请求时间毫秒值
     */
    const REQUEST_TIME_FLOAT = 'request_time_float';

    /**
     * @var array 不处理此数组中声明的action
     */
    public $actions = [];

    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * <p>请求前置拦截</p>
     *
     * @param \yii\base\ActionEvent $event
     * @throws \yii\web\ForbiddenHttpException
     * @return boolean
     */
    public function beforeAction($event)
    {

        $actionId = $event->action->id;
        if (!in_array($actionId, $this->actions)) {
            //判断沉默状态
            $silenceTime = Yii::$app->session->get(static::SILENCE_TIME);
            if ($silenceTime && bcsub(time(), $silenceTime) <= Yii::$app->params['requestSilenceTime']) {//在沉默期
                $event->isValid = false;
                throw new ForbiddenHttpException('请等待5秒后再重试。');
            } else {//不在沉默期则进行频次判断
                Yii::$app->session->remove(static::SILENCE_TIME);//清空沉默值

                //获取本次请求时间
                $requestTimeFloatNow = microtime(true) * 1000;
                //获取上一次请求时间
                $requestTimeFloatLast = Yii::$app->session->get(static::REQUEST_TIME_FLOAT);
                if ($requestTimeFloatLast) {
                    //判断两次请求间隔是否少于阀值
                    if (bcsub($requestTimeFloatNow, $requestTimeFloatLast) <= Yii::$app->params['requestLimitTime']) {
                        Yii::$app->session->set(static::SILENCE_TIME, time());
                        $event->isValid = false;
                        throw new ForbiddenHttpException('你的请求频率过快，请于5秒后重试。');
                    } else {
                        Yii::$app->session->set(static::REQUEST_TIME_FLOAT, $requestTimeFloatNow);
                    }
                } else {
                    Yii::$app->session->set(static::REQUEST_TIME_FLOAT, $requestTimeFloatNow);
                }
            }
        }

        return $event->isValid;
    }

}