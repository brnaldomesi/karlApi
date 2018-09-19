<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProxyAdminPermission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false;

    protected $fillable = [
        'p_admin_id','permission_id'
    ];
}
