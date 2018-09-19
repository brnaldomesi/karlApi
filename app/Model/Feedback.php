<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model{
    protected $fillable = [
        'order_id','appearance',
        'professionalism','driving_ability',
        'cleanliness','quality',
        'comment'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}