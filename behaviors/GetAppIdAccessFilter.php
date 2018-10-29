<?php
namespace app\behaviors;

use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use app\commons\StringUtil;
use app\models\RespMsg;
use yii\base\Behavior;
use yii\base\Controller;
use Yii;
use yii\base\Exception;

/**
 * 工作通请求数据，去代理平台获取appId的过滤器
 *
 * Class ModulesAccessFilter
 * @package app\behaviors
 */
class GetAppIdAccessFilter extends Behavior
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
     * 获取商家的AppId
     * </p>
     * @param \yii\base\ActionEvent $event
     * @return boolean
     */
    public function beforeAction($event)
    {
        try {
            if (!in_array($event->action->id, $this->actions)) {
                return $event->isValid;
            }
            $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
            $event->isValid = false;
            if (!$supplierId = Yii::$app->request->get('supplierId')) {
                $respMsg->return_msg = '商家id不能为空';
                echo $respMsg->toJsonStr();
                return $event->isValid;
            }
            $event->isValid = $this->getAppInfo($supplierId);
        } catch (Exception $e) {
            $respMsg->return_msg = $e->getMessage();
            echo $respMsg->toJsonStr();
        }
        return $event->isValid;
    }

    /**
     * 通过商家id获取公众号信息
     *
     * @param $supplierId
     * @return bool
     * @throws CurlException
     * @throws Exception
     */
    private function getAppInfo($supplierId)
    {
        $get = ['timestamp' => time(), 'wxid' => $supplierId, 'state' => StringUtil::genRandomStr(16)];
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $appInfo = HttpUtil::get(Yii::$app->params['serviceDomain']['weiXinApiDomain'] . '/facade/get-app-info',
            http_build_query($get));
        if ($appInfo->return_code == RespMsg::FAIL) {
            throw new Exception('请求错误，请重试');
        }
        //获取返回的信息
        $appInfo = json_decode(json_encode($appInfo->return_msg), true);

        if ($appInfo['return_code'] == RespMsg::FAIL) {
            Yii::warning("工作通请求获取AppId：" . json_encode($appInfo['return_msg']), __METHOD__);
            throw new Exception('客官，去爱豆子首页绑定公众号吧！');
        }

        if (!$appInfo['return_msg']['appId']) {
            throw new Exception('客官，去爱豆子首页绑定公众号哦！');
        }
        Yii::$app->session->set(Yii::$app->params['constant']['session']['appId'] . $supplierId, $appInfo['return_msg']['appId']);
        return true;
    }

}