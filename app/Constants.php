<?php

namespace App;

class Constants {
    const MIN_COST = 1;

    const DAY_SECONDS = 86400;
    const HALF_HOUR = 1800;
    const HOUR_SECONDS = 3600;
    const MINUTE = 60;

    const MI_EARTH_R = 3958.75;
    const KM_EARTH_R = 6371;
    const MI_2_KM = 1.609344;
    const KM_2_MI = 0.6213712;
    const EARTH_R = Constants::MI_EARTH_R;

    //booking时,司机和车结束一单,行驶向下一单开始地点时的最小速度
    const MIN_SPEED = 10; //10mi/h


    const DRIVER_PUSH = 1;
    const CUSTOMER_PUSH = 2;

    const PER_PAGE_DEFAULT = 30;
    const PAGE_DEFAULT = 1;

    const ORDER_BY_ASC = 0;
    const ORDER_BY_DESC = 1;

    const UNIT_MI=1;
    const UNIT_KM=2;


    //2016年09月19日17:03:41 根据KAR-287要求改为0.05
    //2016年11月08日10:00:11 根据KAR-463要求改为0.1
    const PLATFORM_SETTLE_TVA = 0.1;
    //2016年09月26日10:07:14 AN平台booking执行公司的分成
    const EXE_COMPANY_TVA = 0.85;

    const OWN_COMPANY_TVA = 0.15;

    const BOOK_FILTER_ALL = 0;    //与我公司执行的订单
    const BOOK_FILTER_OWN = 1;    //我公司的订单及别公司的订单
    const BOOK_FILTER_EXE = 2;    //我公司的订单及我公司帮别人执行的订单
    const BOOK_FILTER_BOOKING_OWN = 3;    //原单
    const BOOK_FILTER_BOOKING_OTHER = 4;    //非原单=A单+B单

    const CURRENCY_RMB = "CNY";
    const CURRENCY_USD = "USD";
}
