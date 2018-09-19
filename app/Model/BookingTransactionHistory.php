<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BookingTransactionHistory extends Model
{
    
    const PAY_PLATFORM_PAY_PAL = 1;
    const PAY_PLATFORM_CHASE_BANK = 2;
    
    const PAY_SUCCESS = 1;
    const PAY_FAULT = 0;
    
    const REFUND = 1;
    const NO_REFUND = 0;
    
    const REFUND_SUCCESS = 1;
    const REFUND_FAULT = 0;
    
    const CAPTURE_SUCCESS = 1;
    const CAPTURE_FAULT = 0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "booking_id",
        "ccy",
        "pay_auth",
        "pay1_amount",
        "pay1_id",
        "auth_id",
        "pay1_platform",
        "pay1_refund_amount",
        "pay1_refund",
        "pay1_refund_success",
        "pay1_success",
        "capture_id",
        "capture_amount",
        "capture_success",
        'repay1_id',
        'repay1_amount',
        'repay1_success',
        "pay1_auth_id",
        "pay2_amount",
        "pay2_id",
        "pay2_platform",
        "pay2_refund_amount",
        "pay2_refund",
        "pay2_refund_success",
        "pay2_success"

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
