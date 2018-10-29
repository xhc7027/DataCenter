<?php

namespace app\controllers\filters;

use Idouzi\Commons\TraceUtil;
use yii\base\Behavior;
use yii\web\Controller;

/**
 * 专用来记录业务日志，并不对业务进行拦截
 *
 * @package app\controllers\filters
 */
class TraceLoggerFilter extends Behavior
{
    private $startLogger;

    /**
     * @return array
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
            Controller::EVENT_AFTER_ACTION => 'afterAction',
        ];
    }

    /**
     * @param \yii\base\ActionEvent $event
     * @return bool
     */
    public function beforeAction($event)
    {
        $this->startLogger = TraceUtil::startLogger();
        return $event->isValid;
    }

    /**
     * @param \yii\base\ActionEvent $event
     * @return bool
     */
    public function afterAction($event)
    {
        TraceUtil::init($event->action->id);
        TraceUtil::endLogger('action',
            $event->action->controller->id . '/' . $event->action->id,
            $this->startLogger
        );

        return $event->isValid;
    }
}