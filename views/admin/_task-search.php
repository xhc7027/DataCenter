<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\TaskSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="task-search">

    <?php $form = ActiveForm::begin([
        'action' => ['task-index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'appId') ?>

    <?= $form->field($model, 'beginDate')->widget('yii\jui\DatePicker', [
        'language' => 'zh-CN',
        'dateFormat' => 'php:Y-m-d',
        'clientOptions' => ['defaultDate' => date('Y-m-d')]])
    ?>

    <?= $form->field($model, 'status')->radioList(['就绪', '运行', '成功', '失败']) ?>


    <div class="form-group">
        <?= Html::submitButton('查询', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>