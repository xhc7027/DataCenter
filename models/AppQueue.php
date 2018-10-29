<?php

namespace app\models;

use yii\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "app_queue".
 *
 * @property string $appId
 * @property string $entryTime
 * @property string supplierId
 */
class AppQueue extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_queue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appId'], 'required'],
            [['entryTime','supplierId'], 'safe'],
            [['appId','supplierId'], 'string', 'max' => 18],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'appId' => '公众号AppId',
            'entryTime' => '加入同步队列时间',
            'supplierId' => '用户supplierId',
        ];
    }

    /**
     * 删除AppQueue中信息
     *
     * @param $filed
     * @param $appId
     * @return int
     */
    public function deleteInfo($filed, $appId)
    {
        return self::deleteAll([$filed => $appId]);
    }

    /**
     * 根据supplierId为空的获取用户AppId
     *
     * @return array|ActiveRecord[]
     */
    public function getAppId()
    {
        return self::find()->select(['appId'])->where(['supplierId'=> null])->limit(1000)->asArray()->all();
    }

    /**
     * 更新用户SupplierId
     *
     * @param $data
     * @return int
     */
    public function updateAllSupplierId($data)
    {
        return self::updateAll(['supplierId' => $data['wxId']], ['appId' => $data['appId']]);
    }
}
