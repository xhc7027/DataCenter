<?php

namespace app\models;

use app\exceptions\TaskException;
use yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task".
 *
 * @property integer $id
 * @property string $appId
 * @property integer $taskType
 * @property string $beginDate
 * @property string $startTime
 * @property string $endTime
 * @property integer $status
 * @property integer $retryCount
 *
 * @property AppQueue $app
 * @property TaskLog[] $taskLogs
 */
class Task extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appId'], 'required'],
            [['taskType', 'status', 'retryCount'], 'integer'],
            [['startTime', 'endTime'], 'safe'],
            [['appId'], 'string', 'max' => 18],
            [['beginDate'], 'string', 'max' => 10],
            [['appId'], 'exist', 'skipOnError' => true, 'targetClass' => AppQueue::className(), 'targetAttribute' => ['appId' => 'appId']],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['page'] = ['beginDate', 'taskType', 'appId'];
        return $scenarios;
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'appId' => '所属公众号',
            'taskType' => '任务类型',
            'beginDate' => '获取数据日期',
            'startTime' => '开始时间',
            'endTime' => '结束时间',
            'status' => '任务执行状态',
            'retryCount' => '任务执行次数',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getApp()
    {
        return $this->hasOne(AppQueue::className(), ['appId' => 'appId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTaskLogs()
    {
        return $this->hasMany(TaskLog::className(), ['tid' => 'id']);
    }

    /**
     * 创建任务并将状态置为就绪
     * @return Task
     * @throws TaskException
     */
    public function create(): Task
    {
        $this->status = 0;
        if ($this->validate() && $this->insert()) {
            $taskLog = new TaskLog(['tid' => $this->id, 'createAt' => date('Y-m-d H:i:s'), 'note' => '创建任务']);
            $taskLog->insert();
            return $this;
        }
        throw new TaskException('插入记录' . json_encode($this) . '到数据库失败' . json_encode($this->errors));
    }

    /**
     * 开始执行任务
     * @return Task
     * @throws TaskException
     */
    public function start(): Task
    {
        if ($this->status !== 0) {
            throw new TaskException('任务运行前状态必须是就绪');
        }

        $this->startTime = date('Y-m-d H:i:s');
        $this->endTime = null;
        $this->status = 1;
        $this->retryCount++;
        if ($this->update()) {
            $taskLog = new TaskLog(['tid' => $this->id, 'createAt' => date('Y-m-d H:i:s'), 'note' => '运行任务']);
            $taskLog->insert();
            try {
                Yii::$app->taskService->synData($this);

                $this->succeed();
            } catch (\Exception $e) {
                Yii::warning('同步任务' . json_encode($this) . '发现错误', __METHOD__);
                $this->fail($e->getMessage());
            }
            return $this;
        }
        throw new TaskException('修改记录' . json_encode($this) . '到数据库失败' . json_encode($this->errors));
    }

    /**
     * 运行结束将状态置为成功
     * @return bool
     */
    public function succeed(): bool
    {
        $this->endTime = date('Y-m-d H:i:s');
        $this->status = 2;
        if ($this->update() === 1) {
            $taskLog = new TaskLog(['tid' => $this->id, 'createAt' => date('Y-m-d H:i:s'), 'note' => '任务完成']);
            return $taskLog->insert();
        }
        return false;
    }

    /**
     * 运行结束将状态置为失败
     * @param null $message
     * @return bool
     */
    public function fail($message = null): bool
    {
        $this->endTime = date('Y-m-d H:i:s');
        $this->status = 3;
        if ($this->update()) {
            $taskLog = new TaskLog(
                [
                    'tid' => $this->id,
                    'createAt' => date('Y-m-d H:i:s'),
                    'note' => '创建失败' . $message
                ]
            );
            return $taskLog->insert();
        }
        return false;
    }

    /**
     * 将失败任务重置为就绪状态
     * @return bool
     */
    public function recover(): bool
    {
        $this->startTime = date('Y-m-d H:i:s');
        $this->endTime = null;
        $this->status = 0;
        if ($this->update()) {
            $taskLog = new TaskLog(['tid' => $this->id, 'createAt' => date('Y-m-d H:i:s'), 'note' => '重置任务']);
            return $taskLog->insert();
        }
        return false;
    }

    /**
     * 任务运行超时状态置为就绪
     * @return bool
     */
    public function overtime(): bool
    {
        $taskLog = new TaskLog(['tid' => $this->id, 'createAt' => date('Y-m-d H:i:s'), 'note' => '超时任务处理']);
        $taskLog->insert();

        $overTime = time() - Yii::$app->params['taskOverTime'];
        $startTime = strtotime($this->startTime);
        if ($this->status == 1 && $startTime < $overTime) {
            return $this->fail();
        }
        return false;
    }

    /**
     * 获取task对象
     *
     * @param $where
     * @return static
     */
    public static function getTaskObject($where)
    {
        return self::findOne($where);
    }
}