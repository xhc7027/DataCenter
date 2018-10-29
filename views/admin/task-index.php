<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '任务列表';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= Html::a('创建任务', ['admin/task-create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php echo $this->render('_task-search', ['model' => $searchModel]); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'beginDate',
            [
                'class' => 'yii\grid\DataColumn',
                'attribute' => 'taskType',
                'value' => function ($model, $key, $index, $column) {
                    $type = [
                        '0' => '日任务',
                        '1' => '周任务',
                        '2' => '月任务 ',
                    ];
                    if (isset($model->taskType))
                        return $type[$model->taskType];
                    return null;
                }
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'attribute' => 'appId',
                'label' => '任务所属人',
                'value' => function ($model, $key, $index, $column) {
                    if ($model->appId) {
                        return $model->appId;
                    }
                    return '全部';
                }
            ],
            'startTime',
            'endTime',
            'retryCount',
            [
                'class' => 'yii\grid\DataColumn',
                'format' => 'html',
                'attribute' => 'status',
                'value' => function ($model, $key, $index, $column) {
                    switch ($model->status) {
                        case 0:
                            return Html::img('/images/disabled.png');
                            break;
                        case 1:
                            return Html::img('/images/anime.gif');
                            break;
                        case 2:
                            return Html::img('/images/success.png');
                            break;
                        case 3:
                            return Html::img('/images/failed.png');
                            break;
                        default:
                            return '非法类型';
                    }
                }
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{exec} {view}',
                'buttons' => [
                    'exec' => function ($url, $model, $key) {
                        $label = '执行';
                        if ($model->status !== 0) {
                            $label = '重新执行';
                        }
                        return Html::a($label, '/admin/task-running?id=' . $model->id);
                    },
                    'view' => function ($url, $model, $key) {
                        return Html::a('任务详情', '/admin/task-view?id=' . $model->id);
                    },
                ]
            ],
        ],
    ]); ?>
</div>