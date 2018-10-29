<?php

namespace app\behaviors;

use app\commons\SecurityUtil;
use yii\base\Behavior;
use yii\base\Controller;
use yii\base\InvalidParamException;
use yii;

class AccessFilter extends Behavior
{
    /**
     * @var array 仅过滤此数组中声明的action
     */
    public $actions = [];

    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * <p>请求前置拦截</p>
     * <p>
     * 判断接口签名是否正确
     * </p>
     * @param \yii\base\ActionEvent $event
     * @return boolean
     */
    public function beforeAction($event)
    {
        $dataArr = Yii::$app->request->get();
        try {
            (new SecurityUtil($dataArr, Yii::$app->params['publicKeys']['data-center']))->signVerification();
        } catch (InvalidParamException $e) {
            $event->isValid = false;
            Yii::warning('签名失败！[' . $e->getMessage() . ']', __METHOD__);
        }
        return $event->isValid;
    }

}