<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13 0013
 * Time: 10:07
 */

namespace app\services\user;


interface ViewInfoApi
{

    /**
     * 处理消息队列传进来的数据
     * 用户未注册时传递用户信息
     *
     * @param array|string $userInfo
     * @return array
     */
    public function dealUserViewInfo(array $userInfo);

    /**
     * 展示来源渠道信息
     * @return mixed
     */
    public function showAllSourceZone($zone);

    /**
     * 插入新的渠道来源信息
     * @param array $data
     */
    public function newInsertSourceZone(array $data);

    /**
     * 修改来源渠道信息
     * @param array $data
     */
    public function updateSourceZone(array $data);

    /**
     * 删除来源渠道信息
     * @return mixed
     */
    public function deleteSourceZone(array $data);

    /**
     * 处理消息队列传进来的数据
     * 用户注册后传递注册id进来写入表中
     *
     * @param array|string $userInfo
     * @return array
     */
    public function dealRegisteredUserViewInfo(array $userInfo);

    /**
     * 来源渠道筛选
     * @param int $zone
     */
    public function showChannelSource($zone);

    /**
     * 设置聚合表数据信息
     */
    public function setSourceData();

    /**
     * 获取聚合表数据信息
     * @param array $data
     * @return mixed
     */
    public function getSourceData(array $data,$pageSize);

    /**
     * 获取导出数据
     * @param array $data
     * @return mixed
     */
    public function UserSourceDataExport(array $data);


}