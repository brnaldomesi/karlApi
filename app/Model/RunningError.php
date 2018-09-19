<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RunningError extends Model{
    const STATE_SUCCESS = 0;
    const STATE_FAULT = 1;

    const TYPE_BOOKING = "Booking";
    const TYPE_EMAIL = "Email";
    const TYPE_PAY_PAY_PAYOUTS = "Payouts";
    const TYPE_PAY_PAY_LIST_CREDIT = "List credit card";
    const TYPE_PAY_PAY_CREATE_CREDIT = "Create credit card";
    const TYPE_PAY_PAY_DELETE_CREDIT = "Delete credit card";
    const TYPE_PAY_PAY_CHECK_CREDIT = "Check credit card";
    const TYPE_PAY = "Pay";
    const TYPE_PAY_REFUND_PAY = "Refund";
    const TYPE_PAY_CAPTURE_PAY = "Capture";
    const TYPE_NOTIFICATION = "Ionic notification";
    const TYPE_COMPANY_PAY_INFO = "company pay info";

    protected $fillable = [
        'error_state','error_type',
        'error_content'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];


    public static function recordRunningError($error_state,$error_type,$error_content)
    {
        RunningError::create([
            'error_state'=>$error_state,
            'error_type'=>$error_type,
            'error_content'=>$error_content
        ]);
    }
}