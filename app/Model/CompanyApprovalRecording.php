<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CompanyApprovalRecording extends Model{

    const APPROVAL_STATE_PASS = 0;
    const APPROVAL_STATE_WAITING_PAY = 1;

    protected $fillable = [
        'company_id',
        'approval_state',
        'order_id',
        'pay_id'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}