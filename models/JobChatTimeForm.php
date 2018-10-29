<?php
namespace app\models;

use yii\base\Model;

/**
 * 工作通表单提交验证模型
 *
 * Class JobChatTimeModel
 * @package app\models
 */
class JobChatTimeForm extends Model
{
    /**
     * 查询开始时间
     *
     * @var
     */
    public $startTime;

    /**
     * 查询截止时间
     *
     * @var
     */
    public $endTime;

    /**
     * 验证规则
     * @return array
     */
    public function rules()
    {
        return [
            [
                ['endTime', 'startTime'], 'safe'
            ]
        ];
    }

    /**
     * 重写校验规则，搜索时间范围不能超过七天
     *
     * @param null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        //进行基本的校验
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }

        if ((strtotime($this->endTime) - strtotime($this->startTime)) > 24 * 60 * 60 * 7) {
            $this->addError('endTime','只能查询七天的数据');
            return false;
        }
        return true;
    }

}