<?php

namespace app\models;

/**
 * This is the model class for table "task_log".
 *
 * @property integer $id
 * @property integer $tid
 * @property string $createAt
 * @property string $note
 *
 * @property Task $t
 */
class TaskLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'task_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tid', 'createAt'], 'required'],
            [['tid'], 'integer'],
            [['createAt'], 'safe'],
            [['note'], 'string', 'max' => 255],
            [['tid'], 'exist', 'skipOnError' => true, 'targetClass' => Task::className(), 'targetAttribute' => ['tid' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'tid' => '任务编号',
            'createAt' => '时间',
            'note' => '备注',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getT()
    {
        return $this->hasOne(Task::className(), ['id' => 'tid']);
    }
}