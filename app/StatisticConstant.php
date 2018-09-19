<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2016/12/16
 * Time: 下午4:23
 */

namespace App;

/**
 * Mark STAT
 * Class StatisticConstant
 * @package app
 */
class StatisticConstant
{
    const TYPE_DAY=0;
    const TYPE_WEEK=1;
    const TYPE_MONTH=2;

    const SEQUENCE_DESC=0;
    const SEQUENCE_ASC=1;

    const DEFAULT_TYPE = self::TYPE_DAY;
    const DEFAULT_COUNT = 3;
    const DEFAULT_SEQUENCE = self::SEQUENCE_DESC;
}