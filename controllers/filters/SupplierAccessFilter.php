<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25 0025
 * Time: 15:40
 */

namespace app\controllers\filters;

use app\models\RespMsg;
use Yii;
use yii\base\Behavior;
use yii\base\Controller;
use Idouzi\Commons\StringUtil;
use Idouzi\Commons\SecurityUtil;
use Idouzi\Commons\HttpUtil;


/**
 * 用户登录校验器
 * 校验用户登录状态
 *
 * Class SupplierAccessFilter
 * @package app\controllers\filters
 */
class SupplierAccessFilter extends Behavior
{
    const GUEST = 'GUEST';

    /**
     * @var array 如果验证不正确，需要跳转的action
     */
    public $actions;

    /**
     * @return array
     */
    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }


    /**
     * 校验的方法
     *
     * @param $event
     * @return bool
     */
    public function beforeAction($event)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => self::GUEST]);

        try {
            $action = $event->action->id;
            if (!in_array($action, $this->actions)) {
                $event->isValid = true;
                return $event->isValid;
            }

            $t = Yii::$app->session->get(Yii::$app->params['session']['nickName']);
            //如果cookie中的认证不存在
            if (!$t) {
                Yii::$app->session->destroy();
                $this->notLoginReturn($respMsg, $action);
                return $event->isValid = false;
            }
            return $event->isValid = true;
        } catch (\Exception $e) {
            Yii::warning('校验用户信息出错：' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = self::GUEST;
            $this->notLoginReturn($action, $respMsg);
            return $event->isValid = false;
        }
    }


    /**
     * 当没有登录时，返回根据不同的action做出不同的选择
     *
     * @param RespMsg $respMsg 需要返回的数据
     */
    private function notLoginReturn(RespMsg $respMsg, $action)
    {
        //Yii::$app->response->redirect(Yii::$app->params['accessFilter']['SupplierAccessFilter']);
        echo $respMsg->toJsonStr();
    }
}