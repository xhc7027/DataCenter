<?php
namespace app\controllers\filters;

use app\models\RespMsg;
use yii\base\Behavior;
use yii\web\Controller;
use Yii;

/**
 * 向前端发送CSRF
 *
 * Class CsrfAccessFilter
 * @package app\controllers\filters
 */
class CsrfAccessFilter extends Behavior
{
    /**
     * @var array 仅处理此数组中声明的action
     */
    public $actions = [];

    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }



    /**
     * 传递CSRF
     *
     * @param $event
     * @return mixed
     */
    public function beforeAction($event)
    {
        $response = Yii::$app->getResponse();
        $response->headers->set(Yii::$app->params['constant']['cookie']['_csrf'], Yii::$app->request->getCsrfToken());
        return $event->isValid;
    }
}