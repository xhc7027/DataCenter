<?php

use yii\helpers\Html;
use yii\jui\DatePicker;
use yii\widgets\ActiveForm;

$this->title = '创建任务';
$this->params['breadcrumbs'][] = ['label' => '任务列表', 'url' => ['admin/task-index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'action' => ['admin/task-create'],
        'method' => 'post',
    ]); ?>
    <?= $form->field($model, 'taskType')->radioList(['日任务', '周任务', '月任务']) ?>

    <?= $form->field($model, 'beginDate')->widget(DatePicker::className(), [
        'name' => 'beginDate',
        'language' => 'zh-CN',
        'dateFormat' => 'php:Y-m-d',
        'clientOptions' => ['defaultDate' => date('Y-m-d', strtotime("-1 day"))]
    ]) ?>

    <?= $form->field($model, 'appId')->textInput(['maxlength' => true]) ?>
    <div class="form-group">
        <?= Html::submitButton('提交', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>