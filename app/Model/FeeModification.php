<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FeeModification extends Model{
    const DRIVER_TYPE = 1;
    const ADMIN_TYPE = 2;
    
    const ACTIVE = 1;
    const DIS_ACTIVE = 0;
    protected $fillable = [
        'order_id','delta_fee','delta_tax',
        'comment','modifier_id','modifier_type','active'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}