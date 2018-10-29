<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/24 0024
 * Time: 19:07
 */

namespace app\controllers;

use app\services\LoginService;
use Idouzi\Commons\Models\RespMsg;
use yii\web\Controller;
use app\exceptions\SystemException;
use Exception;
use yii\filters\VerbFilter;
use app\controllers\filters\CsrfAccessFilter;
use app\controllers\filters\IllegalRequestFilter;
use Yii;
use app\controllers\filters\SupplierAccessFilter;

/**
 * 后台登录控制器
 *
 * Class LoginController
 * @package app\controllers
 */
class LoginController extends Controller
{
     public $layout = false;
    /**
     * 定义动作之前的行为
     *
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'traceLoggerFilter' => [
                'class' => 'app\controllers\filters\TraceLoggerFilter'
            ],
            'verbs' => [//限制每个动作的请求类型
                'class' => VerbFilter::className(),
                'actions' => [
                    'login' => ['head', 'get', 'post'],
                    'logout' => ['get', 'head', 'post'],
                ],
            ],
            'IllegalAccess' => [//非法请求过滤
                'class' => IllegalRequestFilter::className(),
            ],
            'SupplierAccess' => [//登录状态过滤
                'class' => SupplierAccessFilter::className(),
                'actions' => ['logout']
            ],
            'CsrfAccess' => [
                'class' => CsrfAccessFilter::className(),
                'actions' => ['login']
            ],
        ];
    }


    public function actionLogin()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            $data = Yii::$app->request->post();
            $return = LoginService::loginValidate($data);
            if ($return) {
                $respMsg->return_msg = $return;
                $respMsg->return_code = RespMsg::SUCCESS;
            } else {
                $respMsg->return_code = RespMsg::FAIL;
                $respMsg->return_msg = "登录名或密码错误";
            }
        } catch (SystemException $e) {
            $respMsg->return_msg = $e->getMessage();
        } catch (Exception $e) {
            Yii::warning('：' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = '';
        }
        return $respMsg;
    }


    /**
     * 退出，清空会话登录标识，移出此票据对应的用户记录。
     *
     * @return RespMsg
     */
    public function actionLogout()
    {
        $respMsg = new RespMsg();
        try {
            $respMsg->return_code = RespMsg::SUCCESS;
            $respMsg->return_msg = LoginService::clearUserLoginFlag();
        } catch (\Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $e->getMessage();
        }

        return $respMsg;
    }


    public function actionValidatePhone()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            $data = Yii::$app->request->post('msgCode');
            if ($respMsg->return_msg = LoginService::validatePhone($data)) {
                $respMsg->return_code = RespMsg::SUCCESS;
            } else {
                $respMsg->return_code = RespMsg::FAIL;
                $respMsg->return_msg = "验证码输入错误，请重新输入";
            }
        } catch (SystemException $e) {
            $respMsg->return_msg = $e->getMessage();
        } catch (Exception $e) {
            Yii::warning('：' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = '';
        }
        return $respMsg;
    }
}