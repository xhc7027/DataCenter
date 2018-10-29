<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Task */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => '任务列表', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <table id="w0" class="table table-striped table-bordered detail-view">
        <tbody>
        <tr>
            <th>编号</th>
            <td><?= $model->id ?></td>
        </tr>
        <tr>
            <th>所属公众号</th>
            <td><?= $model->appId ?></td>
        </tr>
        <tr>
            <th>任务类型</th>
            <td>
                <?php
                if ($model->taskType === 0) {
                    echo '日任务';
                } else if ($model->taskType === 1) {
                    echo '周任务';
                } else if ($model->taskType === 2) {
                    echo '月任务';
                } else {
                    echo '未知类型';
                }
                ?>
            </td>
        </tr>
        <tr>
            <th>获取数据日期</th>
            <td><?= $model->beginDate ?></td>
        </tr>
        <tr>
            <th>开始时间</th>
            <td><?= $model->startTime ?></td>
        </tr>
        <tr>
            <th>结束时间</th>
            <td><?= $model->endTime ?></td>
        </tr>
        <tr>
            <th>任务状态</th>
            <td>
                <?php
                if ($model->status === 0) {
                    echo '就绪';
                } else if ($model->status === 1) {
                    echo '运行';
                } else if ($model->status === 2) {
                    echo '成功';
                } else if ($model->status === 3) {
                    echo '失败';
                } else {
                    echo '未知类型';
                }
                ?>
            </td>
        </tr>
        <tr>
            <th>任务执行次数</th>
            <td><?= $model->retryCount ?></td>
        </tr>
        </tbody>
    </table>

    <div id="w1" class="grid-view">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>日志编号</th>
                <th>任务编号</th>
                <th>记录时间</th>
                <th>日志内容</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $logs = $model->getTaskLogs()->all();
            foreach ($logs as $log) {

                ?>
                <tr>
                    <td><?= $log->id ?></td>
                    <td><?= $log->tid ?></td>
                    <td><?= $log->createAt ?></td>
                    <td><?= $log->note ?></td>
                </tr>
                <?php
            }

            ?>
            </tbody>
        </table>
    </div>

    <div id="w1" class="grid-view">
        <?php
        foreach ($data as $item => $value) {
            echo '<h3>' . $item . '</h3>';
            foreach ($value as $content) {
                echo json_encode($content);
            }
        }
        ?>
    </div>

</div>