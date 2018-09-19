<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Superadmin extends Model{
    protected $fillable = [
        'user_id'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}