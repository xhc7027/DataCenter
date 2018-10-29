<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 11:46
 */

namespace app\controllers;


use Idouzi\Commons\Models\RespMsg;
use app\models\ContactForm;
use app\models\TaskChangeLog;
use app\services\Factory;
use app\models\TaskTxn;
use Yii;
use yii\web\Controller;
use app\exceptions\SystemException;
use Exception;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\controllers\filters\CsrfAccessFilter;
use app\controllers\filters\IllegalRequestFilter;
use app\controllers\filters\SupplierAccessFilter;

/**
 * 用户访问记录控制器
 * SEO
 *
 * Class UserController
 * @package app\controllers
 */
class UserController extends Controller
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
                    'logout' => ['head', 'post'],
                ],
            ],
            'IllegalAccess' => [//非法请求过滤
                'class' => IllegalRequestFilter::className(),
            ],
            'SupplierAccess' => [//登录状态过滤
                'class' => SupplierAccessFilter::className(),
                'actions' => ['source-zone', 'show-channel-source', 'get-source-data', 'user-source-data-export']
            ],
            'CsrfAccess' => [
                'class' => CsrfAccessFilter::className(),
                'actions' => ['login']
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * 插入、修改、删除对应分区的渠道
     */
    public function actionSourceZone()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            if (Yii::$app->request->isGet) {//展示
                $zone = Yii::$app->request->get('zone');
                $respMsg->return_msg = Factory::getViewInfoService()->showAllSourceZone($zone);
            } elseif (Yii::$app->request->isPost) {//插入
                $data = Yii::$app->request->post();
                $respMsg->return_msg = Factory::getViewInfoService()->newInsertSourceZone($data);
            } elseif (Yii::$app->request->isPut) {//修改
                $data = Yii::$app->request->post();
                Yii::warning(json_encode($data));
                $respMsg->return_msg = Factory::getViewInfoService()->updateSourceZone($data);
            } elseif (Yii::$app->request->isDelete) {//删除
                $data = Yii::$app->request->post();
                $respMsg->return_msg = Factory::getViewInfoService()->deleteSourceZone($data);
            }
            $respMsg->return_code = RespMsg::SUCCESS;
        } catch (SystemException $e) {
            $respMsg->return_msg = $e->getMessage();
        } catch (Exception $e) {
            Yii::warning('：' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = '';
        }
        return $respMsg;
    }

    /**
     * 筛选出来源渠道的名称
     */
    public function actionShowChannelSource()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            $zone = Yii::$app->request->get('zone');
            $respMsg->return_msg = ['channelSource' => Factory::getViewInfoService()->showChannelSource((int)$zone)];
            $respMsg->return_code = RespMsg::SUCCESS;
        } catch (SystemException $e) {
            $respMsg->return_msg = $e->getMessage();
        } catch (Exception $e) {
            Yii::warning('：' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = '';
        }
        return $respMsg;
    }


    /**
     * 返回统计表所需要的数据（聚合表）
     *
     */
    public function actionGetSourceData()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            $data = Yii::$app->request->get();
            $respMsg->return_msg = Factory::getViewInfoService()->getSourceData($data, $pageSize = null);
            $respMsg->return_code = RespMsg::SUCCESS;
        } catch (SystemException $e) {
            $respMsg->return_msg = $e->getMessage();
        } catch (Exception $e) {
            Yii::warning('：' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = '';
        }
        return $respMsg;
    }


    /**
     * 发布需求导出
     */
    public function actionUserSourceDataExport()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            $data = Yii::$app->request->get();
            $respMsg->return_msg = Factory::getViewInfoService()->UserSourceDataExport($data);
            $respMsg->return_code = RespMsg::SUCCESS;
        } catch (\Exception $e) {
            Yii::warning('导出统计数据失败，error=' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = $e->getMessage();
            Yii::$app->response->redirect('index');
        }

        return $respMsg;
    }
}