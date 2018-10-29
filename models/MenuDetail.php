<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * 菜单统计详情模型
 *
 * @property integer $id
 * @property string $wxId
 * @property string $keyVal
 * @property string $menuName
 * @property string $createTime
 * @property string $openId
 *
 * Class MenuDetail
 * @package app\models
 */
class MenuDetail extends ActiveRecord
{
    /**
     * 表名
     *
     * @return string
     */
    public static function tableName()
    {
        return 'menu_detail';
    }

    /**
     * 属性名
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'id',
            'wxId',
            'keyVal',
            'menuName',
            'createTime',
            'openId',
        ];
    }

    /**
     * 设置场景
     *
     * @return array
     */
    public function scenarios()
    {
        $scenarios =  parent::scenarios();
        $scenarios['default'] = ['id', 'wxId', 'keyVal', 'menuName', 'createTime', 'openId',];
        return $scenarios;
    }

    /**
     * 属性标签
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'wxId' => '商家id',
            'keyVal' => '菜单的key',
            'menuName' => '菜单的名称',
            'createTime' => '点击时间',
            'openId' => '点击人',
        ];
    }

}