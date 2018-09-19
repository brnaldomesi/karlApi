<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    const AdminId = 'admin_permissions.admin_id';
    const PermissionId = 'admin_permissions.permission_id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'admin_id','permission_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at','updated_at'
    ];
}
