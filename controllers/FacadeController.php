<?php

namespace app\controllers;

use app\behaviors\AccessFilter;
use app\behaviors\AdvanceGetMessageFilter;
use app\behaviors\GetAppIdAccessFilter;
use app\models\JobChatTimeForm;
use app\models\RespMsg;
use app\services\AnalysisService;
use app\services\TaskService;
use yii\base\Controller;
use yii\filters\VerbFilter;
use yii\base\Exception;
use yii;

/**
 * 统一对外交互接口控制器
 * @package app\controllers
 */
class FacadeController extends Controller
{
    /**
     * @var bool 关闭CSRF验证
     */
    public $enableCsrfValidation = false;

    /**
     * 在每个Action执行前后作业务处理
     * @return array
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'app-basic-data' => ['get'],
                ],
            ],
            'access' => [
                'class' => AccessFilter::className(),
                'actions' => [
                    'app-basic-data', 'get-survey-data', 'get-user-analysis-data', 'get-image-text-data',
                    'get-message-data', 'get-image-texts', 'get-menu-data', 'get-mall-author-info'
                ]
            ],
            'GetAppIdAccessFilter' => [ //需要获取appId的权限过滤
                'class' => GetAppIdAccessFilter::className(),
                'actions' => [
                    'get-survey-data', 'get-user-analysis-data', 'get-image-text-data', 'get-message-data',
                    'get-image-texts', 'get-menu-data'
                ]
            ],
            'AdvanceGetMessageFilter' => [//工作通需要及时拉取数据的过滤器，因为默认进入首页，所以只用在首页接口判断
                'class' => AdvanceGetMessageFilter::className(),
                'actions' => [
                    'get-survey-data',
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ]
        ];
    }


    /**
     * 获取公众号的基本数据信息
     * @return mixed
     */
    public function actionAppBasicData()
    {
        $respMsg = new RespMsg();
        $appId = Yii::$app->request->get('appId');
        $dataService = new AnalysisService();
        $dataRespMsg = $dataService->getUserAnalysic($appId);
        if ($dataRespMsg->return_code == RespMsg::SUCCESS) {
            $respMsg->return_msg = $dataRespMsg->return_msg;
        } else {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $dataRespMsg->return_msg;
        }

        return $respMsg->toJsonStr();
    }

    /**
     * 获取概况页面的
     *
     * @return RespMsg
     */
    public function actionGetSurveyData()
    {
        $respMsg = new RespMsg();
        try {
            $key = Yii::$app->params['constant']['session']['appId'] . Yii::$app->request->get('supplierId');
            $respMsg->return_msg = AnalysisService::getSurveyData(Yii::$app->session->get($key));
        } catch (Exception $e) {
            Yii::warning('获取概况页面的数据异常：' . $e->getMessage(), __METHOD__);
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '获取数据失败，请重试';
        }
        return $respMsg;
    }

    /**
     * 获取用户分析页面的数据
     *
     * @return RespMsg
     */
    public function actionGetUserAnalysisData()
    {
        $respMsg = new RespMsg();
        try {
            $key = Yii::$app->params['constant']['session']['appId'] . Yii::$app->request->get('supplierId');
            $respMsg->return_msg = AnalysisService::getUserAnalysisData(Yii::$app->session->get($key));
        } catch (Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $e->getMessage();
        }
        return $respMsg;
    }

    /**
     * 获取图文分析页面的数据
     *
     * @return RespMsg
     */
    public function actionGetImageTextData()
    {
        $respMsg = new RespMsg();
        try {
            $key = Yii::$app->params['constant']['session']['appId'] . Yii::$app->request->get('supplierId');
            $respMsg->return_msg = AnalysisService::getImageTextData(Yii::$app->session->get($key));
        } catch (Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $e->getMessage();
        }
        return $respMsg;
    }

    /**
     * 图文分析界面，单图文分析接口
     *
     * @return RespMsg
     */
    public function actionGetImageTexts()
    {
        $respMsg = new RespMsg();
        try {
            $jobChatTimeModel = new JobChatTimeForm();
            if (!$jobChatTimeModel->load(Yii::$app->request->get(), '') || !$jobChatTimeModel->validate()) {
                throw new Exception(current($jobChatTimeModel->getFirstErrors()));
            }
            $key = Yii::$app->params['constant']['session']['appId'] . Yii::$app->request->get('supplierId');
            $respMsg->return_msg = AnalysisService::getImageTexts(Yii::$app->session->get($key), $jobChatTimeModel);
        } catch (Exception $e) {
            Yii::warning('工作通获取单图文参数不合法：' . $e->getMessage(), __METHOD__);
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $e->getMessage();
        }
        return $respMsg;
    }

    /**
     * 获取消息分析页面的数据
     *
     * @return RespMsg
     */
    public function actionGetMessageData()
    {
        $respMsg = new RespMsg();
        try {
            $key = Yii::$app->params['constant']['session']['appId'] . Yii::$app->request->get('supplierId');
            $respMsg->return_msg = AnalysisService::getMessageData(Yii::$app->session->get($key));
        } catch (Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $e->getMessage();
        }
        return $respMsg;
    }

    /**
     * 获取菜单分析页面的数据
     *
     * @return RespMsg
     */
    public function actionGetMenuData()
    {
        $respMsg = new RespMsg();
        try {
            $key = Yii::$app->params['constant']['session']['appId'] . Yii::$app->request->get('supplierId');
            $respMsg->return_msg = AnalysisService::getMenuData(Yii::$app->session->get($key));
        } catch (Exception $e) {
            Yii::warning("工作通获取菜单统计数据失败" . $e->getMessage(), __METHOD__);
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '获取菜单统计数据失败';
        }
        return $respMsg;
    }


    /**
     *
     */
    public function actionGetMallAuthorInfo()
    {
        $respMsg = new RespMsg();
        try {
            $reAppId = Yii::$app->request->post('reAppId');
            $authorInfo = Yii::$app->request->post('authorInfo');
            if (!isset($reAppId) || !TaskService::deleteAppQueue('appId', $reAppId)) {
                Yii::error('用户AppId在AppQueue表中删除失败' . json_encode($reAppId));
            }
            if (!isset($authorInfo['appId']) || !TaskService::pushAppQueue($authorInfo['appId'])) {
                Yii::error('用户AppId在AppQueue表中插入失败' . json_encode($reAppId) . '用户换绑信息为:' . json_encode($authorInfo));
            }
        } catch (Exception $e) {
            Yii::warning("工作通获取菜单统计数据失败" . $e->getMessage(), __METHOD__);
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '接口执行失败';
        }
    }
}