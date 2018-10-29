<?php

namespace app\exceptions;

use yii;

/**
 * 发起HTTP请求时的错误
 * @package app\exceptions
 */
class CurlException extends \Exception
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
