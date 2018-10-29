<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * TaskSearch represents the model behind the search form about `app\models\Task`.
 */
class TaskSearch extends Task
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'taskType', 'status', 'retryCount'], 'integer'],
            [['appId', 'beginDate', 'startTime', 'endTime'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Task::find()->orderBy('ID DESC');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['attributes' => ['']],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'taskType' => $this->taskType,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'status' => $this->status,
            'retryCount' => $this->retryCount,
        ]);

        $query->andFilterWhere(['appId' => $this->appId])
            ->andFilterWhere(['like', 'beginDate', $this->beginDate]);

        return $dataProvider;
    }
}