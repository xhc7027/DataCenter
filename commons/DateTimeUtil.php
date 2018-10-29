<?php

namespace app\commons;

/**
 * 常用日期、时间公共操作方法
 * @package app\commons
 */
class DateTimeUtil
{
    public function daysFromNow(int $beforeTime)
    {
        $dates[0] = date('Y-m-d', $beforeTime);
        $i = 1;
        $nowDate = date('Y-m-d');
        $beforeDate = date('Y-m-d', strtotime("+{$i} day", $beforeTime));
        while ($nowDate != $beforeDate) {
            $dates[$i] = $beforeDate;
            $i++;
            $beforeDate = date('Y-m-d', strtotime("+{$i} day", $beforeTime));
        }
        return $dates;
    }
}