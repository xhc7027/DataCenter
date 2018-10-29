<?php
namespace app\commons;

/**
 * 数组工具类
 *
 * Class ArrayUtil
 * @package app\commons
 */
class ArrayUtil
{
    /**
     * 获取七天，十五天，一个月的初始化数组
     *
     * @param $flag
     * @return array
     */
    public static function getInitArray($flag)
    {
        $array = [];
        while ($flag) {
            $dateFlag = date('Y-m-d', strtotime("-" . $flag . " day"));
            $array['newUser'][$dateFlag] = 0;
            $array['cancelUser'][$dateFlag] = 0;
            $array['netgainUser'][$dateFlag] = 0;
            $array['cumulateUser'][$dateFlag] = 0;
            $flag--;
        }
        return $array;
    }

    /**
     * 获取七天，十五天，一个月的初始化数组
     *
     * @param $flag
     * @return array
     */
    public static function getInitMessageArray($flag)
    {
        $array = [];
        while ($flag) {
            $dateFlag = date('Y-m-d', strtotime("-" . $flag . " day"));
            $array['msgUser'][$dateFlag] = 0;
            $array['msgCount'][$dateFlag] = 0;
            $array['avgMsgCount'][$dateFlag] = 0;
            $array['oneToFive']['sendUserCount'] = 0;
            $array['sixToTen']['sendUserCount'] = 0;
            $array['tenUp']['sendUserCount'] = 0;
            $flag--;
        }
        return $array;
    }

    /**
     * 重组一个数组
     *
     * @param $arr
     * @return mixed
     */
    public static function reformArray($arr)
    {
        $newArr = [];
        foreach ($arr as $value) {
            $newArr[] = $value['id'];
        }
        return $newArr;
    }
}