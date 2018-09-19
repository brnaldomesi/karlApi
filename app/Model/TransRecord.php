<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TransRecord extends Model
{
    const TYPE_SUCCESS = 1;
    const TYPE_STOP = 2;
    const TYPE_WAIT = 0;
    const TYPE_FAULT = -1;
    protected $fillable = [
        'charge_id',
        'trans_id',
        'trans_balance_id',
        "company_id",
        'available_on',
        'booking_id',
        'trans_type',
        'trans_amount',
        'trans_ccy',
    ];
}