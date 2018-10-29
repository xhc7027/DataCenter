<?php

namespace app\controllers;

use app\controllers\actions\ErrorAction;
use app\exceptions\TaskException;
use app\models\Api;
use app\models\AppQueue;
use app\models\Article;
use app\models\ContactForm;
use app\models\Fans;
use app\models\LoginForm;
use app\models\Message;
use app\models\Task;
use app\models\TaskChangeLog;
use app\models\TaskSearch;
use app\models\TaskTxn;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class AdminController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [
                    'logout', 'index', 'task-index', 'task-create', 'task-running', 'task-delete',
                ],
                'rules' => [
                    [
                        'actions' => [
                            'logout', 'index', 'task-index', 'task-create', 'task-running', 'task-delete',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
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
        return $this->redirect(['admin/task-index']);
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->renderPartial('login', [
            'model' => $model,
        ]);
    }

    /**
     *
     */
    public function actionPhoneVerify(){

    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionTaskIndex()
    {
        $searchModel = new TaskSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('task-index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 创建任务
     * @return string|\yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionTaskCreate()
    {
        $task = new Task(['scenario' => 'page']);

        if (Yii::$app->request->isPost) {
            $task->load(Yii::$app->request->post());
            if (!$task->beginDate) {
                throw new ForbiddenHttpException('缺少必要参数');
            } else if (strtotime($task->beginDate) > strtotime(date('Y-m-d', strtotime("-1 day")))) {
                throw new ForbiddenHttpException('只能创建昨天及以前日期的同步任务');
            }

            if ($task->appId && !AppQueue::findOne(['appId' => $task->appId])) {
                throw new ForbiddenHttpException('任务所属公众号 ' . $task->appId . ' 不存在队列中');
            }

            Yii::$app->taskService->buildTask($task);

            return $this->redirect(['task-index']);
        } else {
            return $this->render('task-create', [
                'model' => $task,
            ]);
        }
    }

    /**
     * 开始执行任务
     * @param $id
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionTaskRunning($id)
    {
        //判断任务是否存在
        $task = Task::findOne($id);
        if (!$task) {
            throw new ForbiddenHttpException('任务' . $id . '不存在');
        }

        //判断任务是否已经在运行
        $overTime = time() - Yii::$app->params['taskOverTime'];
        $startTime = strtotime($task->startTime);
        if ($task->status == 1 && $startTime > $overTime) {
            throw new ForbiddenHttpException('任务' . $id . '正在执行中...');
        }
        if ($task->status == 2) {
            $task->recover();
        }
        //开始执行任务
        try {
            $task->start();
        } catch (TaskException $e) {
            throw new ForbiddenHttpException('执行任务失败:' . json_encode($task));
        }

        //跳转到任务执行动态页面
        return $this->redirect(['task-index', 'id' => $id]);
    }

    /**
     * 删除任务
     * @param $id
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionTaskDelete($id)
    {
        $task = Task::findOne($id);
        if (!$task) {
            throw new ForbiddenHttpException('任务' . $id . '不存在');
        }
        $task->delete();
        return $this->redirect(['task-index']);
    }

    /**
     * @param $id
     * @return string
     */
    public function actionTaskView($id)
    {
        $model = Task::findOne($id);

        //查询本次任务所同步出来的数据记录
        $data = [
            'api' => Api::find()->where(['appId' => $model->appId, 'refDate' => $model->beginDate])->asArray()->all(),
            'article' => Article::find()->where(['appId' => $model->appId, 'refDate' => $model->beginDate])->asArray()->all(),
            'fans' => Fans::find()->where(['appId' => $model->appId, 'refDate' => $model->beginDate])->asArray()->all(),
            'message' => Message::find()->where(['appId' => $model->appId, 'refDate' => $model->beginDate])->asArray()->all(),
        ];

        return $this->render('task-view', [
            'model' => $model,
            'data' => $data,
        ]);
    }
}
