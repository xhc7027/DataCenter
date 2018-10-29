<?php

namespace app\exceptions;

use yii;

/**
 * 任务处理抛出的异常
 * @package app\exceptions
 */
class TaskException extends \Exception
{
    /**
     * 抛出异常时并记录错误日志
     * @param string $message
     */
    public function __construct(string $message)
    {
        Yii::error($message, __METHOD__);
        parent::__construct($message);
    }
}
