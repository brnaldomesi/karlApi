<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/10
 * Time: 下午1:20
 */

namespace App;


class ErrorCode
{
    /**
     * 默认信息前
     */
    const PRE = 1;
    /**
     * 默认信息后
     */
    const AFT = 2;
    /**
     * 替换
     */
    const REP = 3;

    private static $ErrorCode = [
        2000 => [
            "code" => "2000",
            "result" => "",
            "msg" => "success"
        ],
        2100 => [
            "code" => "2100",
            "result" => "",
            "msg" => "result is empty"
        ],
        3000 => [
            "code" => "3000",
            "result" => "Missing required parameters ",
            "msg" => "request missing parameters"
        ],
        3001 => [
            "code" => "3001",
            "result" => "ERROR required parameters: ",
            "msg" => "parameter error"
        ],
        3002 => [
            "code" => "3002",
            "result" => "Can not find ",
            "msg" => "search object not exit"
        ],
        3005 => [
            "code" => "3005",
            "result" => "Required parameters missing token",
            "msg" => "Required parameters missing token"
        ],
        3006 => [
            "code" => "3006",
            "result" => "You do not have access",
            "msg" => "User have no right to do something"
        ],
        3007 => [
            "code" => "3007",
            "result" => "token was expired",
            "msg" => "token was expired"
        ],
        3008 => [
            "code" => "3008",
            "result" => "servers busy retry later",
            "msg" => "servers busy retry later"
        ],
        3100 => [
            "code" => "3100",
            "result" => "Username has be used",
            "msg" => "Username has be used"
        ],
        3101 => [
            "code" => "3101",
            "result" => "Mobile has be used",
            "msg" => "Mobile has be used"
        ],
        3102 => [
            "code" => "3102",
            "result" => "Email has be used",
            "msg" => "Email has be used"
        ],
        3103 => [
            "code" => "3103",
            "result" => "register failed",
            "msg" => "register failed"
        ],
        3104 => [
            "code" => "3104",
            "result" => "proxy admin is active",
            "msg" => "proxy admin is active"
        ],
        3200 => [
            "code" => "3200",
            "result" => "admin unauthorized operation",
            "msg" => "admin unauthorized operation"
        ],
        3201 => [
            "code" => "3201",
            "result" => "this user has be added admin permission",
            "msg" => "this user has be added admin permission"
        ],
        3202 => [
            "code" => "3202",
            "result" => "this user has no permission to do this action",
            "msg" => "this user has no permission to do this action"
        ],
        3300 => [
            "code" => "3300",
            "result" => " already exist",
            "msg" => "date has already exist"
        ],
        3301 => [
            "code" => "3301",
            "result" => " not exist",
            "msg" => "date not exist"
        ],
        3302 => [
            "code" => "3302",
            "result" => " delete error",
            "msg" => "delete date error"
        ],
        3303 => [
            "code" => "3303",
            "result" => "sale not exist",
            "msg" => "sale not exist"
        ],
        3304 => [
            "code" => "3304",
            "result" => " already used",
            "msg" => "It's already used"
        ],
        3400 => [
            "code" => "3400",
            "result" => " date error ",
            "msg" => "date error"
        ],
        3500 => [
            "code" => "3500",
            "result" => "option can not change type",
            "msg" => "option can not change type"
        ],
        3501 => [
            "code" => "3501",
            "result" => "option can not change parent option",
            "msg" => "option can not change parent option"
        ],
        3502 => [
            "code" => "3502",
            "result" => "option can not change company",
            "msg" => "option can not change company"
        ],
        3603 => [
            "code" => "3603",
            "result" => "driver delete error",
            "msg" => "driver delete error"
        ],
        3604 => [
            "code" => "3604",
            "result" => "The customer can't be deleted,because the customer had orders not executed",
            "msg" => "The customer can't be deleted,because the customer had orders not executed"
        ],
        3605 => [
            "code" => "3605",
            "result" => "car delete error",
            "msg" => "car delete error"
        ],
        3700 => [
            "code" => "3700",
            "result" => "this mobile has been used by the other user",
            "msg" => "this mobile has been used by the other user"
        ],
        3701 => [
            "code" => "3701",
            "result" => "this email has been used by the other user",
            "msg" => "this email has been used by the other user"
        ],
        3702 => [
            "code" => "3702",
            "result" => "error password or not exist user",
            "msg" => "error password or not exist user"
        ],
        3703 => [
            "code" => "3703",
            "result" => "password not changed",
            "msg" => "password not changed"
        ],
        3800 => [
            "code" => "3800",
            "result" => "this offer can't provide services",
            "msg" => "this offer can't provide services"
        ],
        3801 => [
            "code" => "3801",
            "result" => "the drivers in the offer can't provide services",
            "msg" => "the drivers in the offer can't provide services"
        ],
        3802 => [
            "code" => "3802",
            "result" => "the cars in the offer can't provide services",
            "msg" => "the cars in the offer can't provide services"
        ],
        3803 => [
            "code" => "3803",
            "result" => "this appoint time in the offer can't provide services",
            "msg" => "company have no offer"
        ],
        3804 => [
            "code" => "3804",
            "result" => "this appoint time in the car can't provide services",
            "msg" => "company offer have no car"
        ],
        3805 => [
            "code" => "3805",
            "result" => "this appoint time in the driver can't provide services",
            "msg" => "company offer have no driver"
        ],
        3806 => [
            "code" => "3806",
            "result" => " event time has been used",
            "msg" => "event time has been used"
        ],
        3807 => [
            "code" => "3807",
            "result" => "driver car not match",
            "msg" => "driver car not match"
        ],
        3808 => [
            "code" => "3808",
            "result" => "there has no offers match this trip",
            "msg" => "there has no offers match this trip"
        ],
        3809 => [
            "code" => "3809",
            "result" => "there has no cars for this trip",
            "msg" => "there has no cars for this trip"
        ],
        3810 => [
            "code" => "3810",
            "result" => "there has no drivers for this trip",
            "msg" => "there has no drivers for this trip"
        ],
        3811 => [
            "code" => "3811",
            "result" => "offer has been removed",
            "msg" => "offer has been removed"
        ],
        3850 => [
            "code" => "3850",
            "result" => "can't edit custom quote booking",
            "msg" => "can't edit custom quote booking"
        ],
        3851 => [
            "code" => "3851",
            "result" => "can't change to custom quote booking",
            "msg" => "can't change to custom quote booking"
        ],
        3900 => [
            "code" => "3900",
            "result" => "this pay method is active can not be deleted",
            "msg" => "this pay method is active can not be deleted"
        ],
        4000 => [
            "code" => "4000",
            "result" => "database error ",
            "msg" => "database error"
        ],
        5000 => [
            "code" => "5000",
            "result" => "company ln setting error",
            "msg" => "company ln setting error"
        ],
        5001 => [
            "code" => "5001",
            "result" => "company an setting is locked",
            "msg" => "company ln setting error"
        ],
        6000 => [
            "code" => "6000",
            "result" => "username/email/mobile or password incorrect",
            "msg" => "username/email/mobile or password incorrect"
        ],
        7000 => [
            "code" => "7000",
            "result" => "order has not been started",
            "msg" => "order has not been started"
        ],
        7001 => [
            "code" => "7001",
            "result" => "order has been finished",
            "msg" => "order has been finished"
        ],
        7002 => [
            "code" => "7002",
            "result" => "statue:",
            "msg" => "trip state error"
        ],
        7003 => [
            "code" => "7003",
            "result" => "trip can't been started now",
            "msg" => "trip can't been started now"
        ],
        7004 => [
            "code" => "7004",
            "result" => "this driver can't change this order price",
            "msg" => "this driver can't change this order price"
        ],
        7005 => [
            "code" => "7005",
            "result" => "this order can't changed after finish trip half an hour.",
            "msg" => "this order can't changed after finish trip half an hour."
        ],
        7006 => [
            "code" => "7006",
            "result" => "this order has been invalid",
            "msg" => "this order has been invalid"
        ],
        7007 => [
            "code" => "7007",
            "result" => "this order is not finished",
            "msg" => "trip not finished"
        ],
        7008 => [
            "code" => "7008",
            "result" => "this order has been canceled",
            "msg" => "trip has been canceled"
        ],
        7009 => [
            "code" => "7009",
            "result" => "you have running trip , can't start other trip",
            "msg" => "you have running trip , can't start other trip"
        ],
        7010 => [
            "code" => "7010",
            "result" => "this trip not settle finished",
            "msg" => "this trip not settle finished"
        ],
        7100 => [
            "code" => "7100",
            "result" => "order has not been finished",
            "msg" => "feedback trip has not been finished"
        ],
        7101 => [
            "code" => "7101",
            "result" => "order has been added",
            "msg" => "trip has add feedback"
        ],
        7200 => [
            "code" => "7200",
            "result" => "trip has been started",
            "msg" => "change booking error"
        ],
        7201 => [
            "code" => "7201",
            "result" => "invoice has been send",
            "msg" => "invoice has send"
        ],
        7202 => [
            "code" => "7202",
            "result" => "this trip may be canceled or not determine",
            "msg" => "this trip may be canceled or not determine"
        ],
        7203 => [
            "code" => "7203",
            "result" => "this trip in settle",
            "msg" => "trip in settle"
        ],
        8000 => [
            "code" => "8000",
            "result" => "file upload failed",
            "msg" => "file upload failed"
        ],
        8001 => [
            "code" => "8001",
            "result" => "Unrecognized file type",
            "msg" => "the type of upload file "
        ],
        8100 => [
            "code" => "8100",
            "result" => "Pay method not support",
            "msg" => "Pay method not support"
        ],
        8500 => [
            "code" => "8500",
            "result" => "airline info get error : ",
            "msg" => "airline get error"
        ],
        8600 =>[
            "code" => "8600",
            "result" => "no exist payment customers",
            "msg" => "no exist payment customers"
        ],
        8601 =>[
            "code" => "8601",
            "result" => "Pay method is not stripe",
            "msg" => "Pay method is not stripe"
        ],
        8602 =>[
            "code" => "8602",
            "result" => "Get stripe customer failed",
            "msg" => "Get stripe customer failed"
        ],
        8603 =>[
            "code" => "8603",
            "result" => "Auth stripe account failed",
            "msg" => "Auth stripe account failed"
        ],

        8800 => [
            "code" => "8800",
            "result" => "select pay error",
            "msg" => "select pay error"
        ],
        8801 => [
            "code" => "8801",
            "result" => "pay failed ",
            "msg" => "pay failed"
        ],
        8802 => [
            "code" => "8802",
            "result" => "auth credit card error",
            "msg" => "auth credit card error"
        ],
        8803 => [
            "code" => "8803",
            "result" => "delete credit card error",
            "msg" => "delete card error"
        ],
        8804 => [
            "code" => "8804",
            "result" => "list credit card error",
            "msg" => "get card list error"
        ],
        8805 => [
            "code" => "8805",
            "result" => " refund fault",
            "msg" => "customer card limit"
        ],
        8806 => [
            "code" => "8806",
            "result" => "this man add more than 10 cards",
            "msg" => "customer card limit"
        ],
        8807 => [
            "code" => "8807",
            "result" => "amount less than $1 can not pay success",
            "msg" => "amount less than $1 can not pay success"
        ],
        8808 => [
            "code" => "8808",
            "result" => "credit card info error",
            "msg" => "credit card info error"
        ],

        8809 =>[
            "code" => "8809",
            "result" => "coupon code error",
            "msg" => "coupon code error"
        ],
        8900=>[
            "code" => "8900",
            "result" => "error  setting",
            "msg" => "error  setting"
        ],
        8901=>[
            "code" => "8901",
            "result" => "error  api key",
            "msg" => "error api key"
        ],
        8902=>[
            "code" => "8902",
            "result" => "error  not set api key",
            "msg" => "error  not set api key"
        ],
        9000 => [
            "code" => "9000",
            "result" => "Error Api Version",
            "msg" => "error api version"
        ],
    ];

    public static function getErrorCode($code, $msg = "", $replace = self::PRE)
    {
        $errorCode = self::$ErrorCode[$code];
        switch ($replace) {
            case self::PRE:
                $errorCode['result'] = $msg . $errorCode['result'];
                break;
            case self::AFT:
                $errorCode['result'] = $errorCode['result'] . $msg;
                break;
            case self::REP:
                $errorCode['result'] = $msg;
                break;
            default:
                break;
        }
        unset($errorCode['msg']);
        return $errorCode;
    }

    public static function getErrorCodes()
    {
        return self::$ErrorCode;
    }


    public static function success($result, $needNumberCheck = true)
    {
        $error = self::getErrorCode(2000, $result, self::REP);
        if ($needNumberCheck) {
            return json_encode($error, JSON_NUMERIC_CHECK);
        } else {
            return json_encode($error);
        }
    }

    //结果为空
    public static function successEmptyResult($message)
    {
        $error = self::getErrorCode(2100, $message);
        return json_encode($error);
    }


    //缺少必要参数
    public static function errorMissingParam($param = "")
    {
        $error = self::getErrorCode(3000, $param, self::AFT);
        return json_encode($error);
    }

    //参数错误
    public static function errorParam($msg = '')
    {
        $error = self::getErrorCode(3001, $msg, self::AFT);
        return json_encode($error);
    }

    public static function errorNoObject($msg = '')
    {
        $error = self::getErrorCode(3002, $msg, self::AFT);
        return json_encode($error);
    }

    public static function missingToken()
    {
        $error = self::getErrorCode(3005);
        return json_encode($error);
    }

    public static function hasNoPermission()
    {
        $error = self::getErrorCode(3006);
        return json_encode($error);
    }

    public static function errorTokenExpired()
    {
        $error = self::getErrorCode(3007);
        return json_encode($error);
    }

    public static function errorFullGout()
    {
        $error = self::getErrorCode(3008);
        return json_encode($error);
    }

    //注册失败,username被占用
    public static function errorRegisteredUsername()
    {
        $error = self::getErrorCode(3100);
        return json_encode($error);
    }

    //注册失败,mobile被占用
    public static function errorRegisteredMobile()
    {
        $error = self::getErrorCode(3101);
        return json_encode($error);
    }

    //注册失败,email被占用
    public static function errorRegisteredEmail()
    {
        $error = self::getErrorCode(3102);
        return json_encode($error);
    }

    //注册失败
    public static function errorRegisteredFailed()
    {
        $error = self::getErrorCode(3103);
        return json_encode($error);
    }
    //注册失败
    public static function errorProxyAdminActive()
    {
        $error = self::getErrorCode(3104);
        return json_encode($error);
    }

    //admin 操作越权
    public static function errorAdminUnauthorizedOperation()
    {
        $error = self::getErrorCode(3200);
        return json_encode($error);
    }

    //admin 操作越权
    public static function errorAdminHasAdd()
    {
        $error = self::getErrorCode(3201);
        return json_encode($error);
    }

    //admin 操作越权
    public static function errorHandleDBUnauthorizedOperation()
    {
        $error = self::getErrorCode(3202);
        return json_encode($error);
    }

    // 数据已存在
    public static function errorAlreadyExist($msg = '')
    {
        $error = self::getErrorCode(3300, $msg);
        return json_encode($error);
    }

    public static function errorAlreadyUsed($msg = '')
    {
        $error = self::getErrorCode(3304, $msg);
        return json_encode($error);
    }

    // 数据不存在
    public static function errorNotExist($msg = '')
    {
        $error = self::getErrorCode(3301, $msg);
        return json_encode($error);
    }

    // 删除数据出错
    public static function errorDeleteDB($msg = '')
    {
        $error = self::getErrorCode(3302, $msg);
        return json_encode($error);
    }
    // sale不存在
    public static function errorNotExistSale()
    {
        $error = self::getErrorCode(3303);
        return json_encode($error);
    }
    public static function errorDateFormat()
    {
        $error = self::getErrorCode(3400);
        return json_encode($error);
    }

    public static function errorOptionTypeChanged()
    {
        $error = self::getErrorCode(3500);
        return json_encode($error);
    }

    public static function errorOptionParentIdChanged()
    {
        $error = self::getErrorCode(3501);
        return json_encode($error);
    }

    public static function errorOptionCompanyIdChanged()
    {
        $error = self::getErrorCode(3502);
        return json_encode($error);
    }

    public static function errorDriverDelete($bookings)
    {
        $error = self::getErrorCode(3603, $bookings, self::REP);
        return json_encode($error);
    }

    public static function errorCustomerDelete()
    {
        $error = self::getErrorCode(3604);
        return json_encode($error);
    }

    public static function errorCarDelete($bookings)
    {
        $error = self::getErrorCode(3605, $bookings, self::REP);
        return json_encode($error);
    }

    public static function errorMobileUserData()
    {
        $error = self::getErrorCode(3700);
        return json_encode($error);
    }

    public static function errorEmailUserData()
    {
        $error = self::getErrorCode(3701);
        return json_encode($error);
    }

    public static function errorChangePassword()
    {
        $error = self::getErrorCode(3702);
        return json_encode($error);
    }

    public static function errorPasswordNotChanged()
    {
        $error = self::getErrorCode(3703);
        return json_encode($error);
    }

    public static function errorOfferUse()
    {
        $error = self::getErrorCode(3800);
        return json_encode($error);
    }

    public static function errorOfferUseDriver()
    {
        $error = self::getErrorCode(3801);
        return json_encode($error);
    }

    public static function errorOfferUseCar()
    {
        $error = self::getErrorCode(3802);
        return json_encode($error);
    }

    public static function errorOfferUseAppointedTime()
    {
        $error = self::getErrorCode(3803);
        return json_encode($error);
    }

    public static function errorCarUseAppointedTime()
    {
        $error = self::getErrorCode(3804);
        return json_encode($error);
    }

    public static function errorDriverUseAppointedTime()
    {
        $error = self::getErrorCode(3805);
        return json_encode($error);
    }

    public static function errorAddEventTimeHasBeenUsed($type)
    {
        $error = self::getErrorCode(3806, $type);
        return json_encode($error);
    }

    public static function errorOfferDriverCar()
    {
        $error = self::getErrorCode(3807);
        return json_encode($error);
    }


    public static function errorOffersHasNoOffer()
    {
        $error = self::getErrorCode(3808);
        return json_encode($error);
    }

    public static function errorOffersHasNoCars()
    {
        $error = self::getErrorCode(3809);
        return json_encode($error);
    }

    public static function errorOffersHasNoDrivers()
    {
        $error = self::getErrorCode(3810);
        return json_encode($error);
    }

    public static function errorOfferHaveBeenRemoved()
    {
        $error = self::getErrorCode(3811);
        return json_encode($error);
    }

    public static function errorToEditCustomBooking()
    {
        $error = self::getErrorCode(3850);
        return json_encode($error);
    }

    public static function errorChangeToCustomBooking()
    {
        $error = self::getErrorCode(3851);
        return json_encode($error);
    }

    public static function errorPayMethodInActive()
    {
        $error = self::getErrorCode(3900);
        return json_encode($error);
    }

    //数据库操作错误
    public static function errorDB($msg = '')
    {
        $error = self::getErrorCode(4000, $msg, self::AFT);
        return json_encode($error);
    }


    public static function errorCompanyAnSettingLnError()
    {
        $error = self::getErrorCode(5000);
        return json_encode($error);
    }
    public static function errorCompanyAnSettingIsLocked()
    {
        $error = self::getErrorCode(5001);
        return json_encode($error);
    }

    public static function errorLogin()
    {
        $error = self::getErrorCode(6000);
        return json_encode($error);
    }

    public static function errorOrderTripHasNotBeenStarted()
    {
        $error = self::getErrorCode(7000);
        return json_encode($error);
    }

    public static function errorOrderTripHasBeenFinished()
    {
        $error = self::getErrorCode(7001);
        return json_encode($error);
    }

    public static function errorOrderTripState($statue)
    {
        $error = self::getErrorCode(7002, $statue, self::AFT);
        return json_encode($error);
    }

    public static function errorOrderTripStart()
    {
        $error = self::getErrorCode(7003);
        return json_encode($error);
    }

    public static function errorDriverToChangeOrderPrice()
    {
        $error = self::getErrorCode(7004);
        return json_encode($error);
    }

    public static function errorDriverToChangeOverTime()
    {
        $error = self::getErrorCode(7005);
        return json_encode($error);
    }

    public static function errorOrderHasExpired()
    {
        $error = self::getErrorCode(7006);
        return json_encode($error);
    }

    public static function errorGetInvoiceOrderNotFinished()
    {
        $error = self::getErrorCode(7007);
        return json_encode($error);
    }

    public static function errorOrderHasBeenCanceled()
    {
        $error = self::getErrorCode(7008);
        return json_encode($error);
    }

    public static function errorDriverHasRunningOrder()
    {
        $error = self::getErrorCode(7009);
        return json_encode($error);
    }
    public static function errorTripNotSettleFinished()
    {
        $error = self::getErrorCode(7010);
        return json_encode($error);
    }

    public static function errorOrderFeedBack()
    {
        $error = self::getErrorCode(7100);
        return json_encode($error);
    }

    public static function errorOrderFeedBackHasAdded()
    {
        $error = self::getErrorCode(7101);
        return json_encode($error);
    }

    public static function errorOrderHasBeenComplied()
    {
        $error = self::getErrorCode(7200);
        return json_encode($error);
    }

    public static function errorOrderInvoiceHasBeenSend()
    {
        $error = self::getErrorCode(7201);
        return json_encode($error);
    }

    public static function errorOrderStateError()
    {
        $error = self::getErrorCode(7202);
        return json_encode($error);
    }

    public static function errorTripInSettle()
    {
        $error = self::getErrorCode(7203);
        return json_encode($error);
    }

    public static function errorFileUpload()
    {
        $error = self::getErrorCode(8000);
        return json_encode($error);
    }

    public static function errorFileType()
    {
        $error = self::getErrorCode(8001);
        return json_encode($error);
    }
    public static function errorPayMethod()
    {
        $error = self::getErrorCode(8100);
        return json_encode($error);
    }

    public static function errorAirline($message="")
    {
        $error = self::getErrorCode(8500,$message , self::AFT);
        return json_encode($error);
    }

    public static function errorSelectPayMethodCantUse()
    {
        $error = self::getErrorCode(8800);
        return json_encode($error);
    }

    public static function errorPayFailedWith($payMethod)
    {
        $error = self::getErrorCode(8801, $payMethod);
        return json_encode($error);
    }

    public static function errorCreateCreditCard($payMethod)
    {
        $error = self::getErrorCode(8802, $payMethod);
        return json_encode($error);
    }

    public static function errorPayPalDeleteCreditCard()
    {
        $error = self::getErrorCode(8803);
        return json_encode($error);
    }

    public static function errorPayPalListCreditCard()
    {
        $error = self::getErrorCode(8804);
        return json_encode($error);
    }

    public static function errorTooManyCreditCard()
    {
        $error = self::getErrorCode(8806);
        return json_encode($error);
    }

    public static function errorRefundFault($payMethod)
    {
        $error = self::getErrorCode(8805, $payMethod);
        return json_encode($error);
    }

    public static function errorLessAmountFault()
    {
        $error = self::getErrorCode(8807);
        return json_encode($error);
    }
    public static function errorCreditCardCheck()
    {
        $error = self::getErrorCode(8808);
        return json_encode($error);
    }

    public static function errorApiVersion()
    {
        $error = self::getErrorCode(9000);
        return json_encode($error);
    }

    public static function errorCouponCode()
    {
        $error = self::getErrorCode(8809);
        return json_encode($error);
    }
    public static function errorGetExistCustomer()
    {
        $error = self::getErrorCode(8600);
        return json_encode($error);
    }
    public static function errorPayMethodStripe()
    {
        $error = self::getErrorCode(8601);
        return json_encode($error);
    }
    public static function errorGetStripeCustomer()
    {
        $error = self::getErrorCode(8602);
        return json_encode($error);
    }

    public static function errorAuthStripeAccount()
    {
        $error = self::getErrorCode(8603);
        return json_encode($error);
    }

    public static function errorOuterGroupSetting()
    {
        $error = self::getErrorCode(8900);
        return json_encode($error);
    }
    public static function errorOutGroupApiKey()
    {
        $error = self::getErrorCode(8901);
        return json_encode($error);
    }
    public static function errorOutGroupNotSetApiKey()
    {
        $error = self::getErrorCode(8902);
        return json_encode($error);
    }
}