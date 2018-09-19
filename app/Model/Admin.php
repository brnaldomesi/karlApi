<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model{

    const UserId = "admins.user_id";

    protected $fillable = [
        'user_id',"web_push_token"
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}