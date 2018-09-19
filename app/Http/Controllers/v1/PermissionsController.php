<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Model\Admin;
use App\Model\AdminPermission;
use App\Model\Permission;
use Illuminate\Support\Facades\DB;

class PermissionsController extends Controller
{

    public function adminPermissions()
    {
        // 对应接口暂时不使用了
        $admin_id = 1;
        $sql = "SELECT permissions.id, permissions.name, permissions.description, CASE WHEN admin_permissions.id = '".$admin_id."' THEN 1 ELSE 0 END selected
                FROM permissions
                LEFT JOIN admin_permissions ON permissions.id = admin_permissions.permission_id
                WHERE (admin_permissions.admin_id = '".$admin_id."' OR admin_permissions.admin_id IS NULL)
                ORDER BY permissions.id";
        $permissions = json_decode(json_encode(DB::select($sql)), true);
        if (empty($permissions))
        {
            return ErrorCode::errorDB();
        }
        return ErrorCode::success($permissions);
    }

    public function permissions($admin_id)
    {
        $admin = Admin::where('id', $admin_id)->first();
        if (empty($admin))
        {
            return ErrorCode::errorParam('No admin');
        }
        $sql = "SELECT permissions.id, permissions.name, permissions.description, CASE WHEN admin_permissions.id = '".$admin_id."' THEN 1 ELSE 0 END selected
                FROM permissions
                LEFT JOIN admin_permissions ON permissions.id = admin_permissions.permission_id
                WHERE (admin_permissions.admin_id = '".$admin_id."' OR admin_permissions.admin_id IS NULL)
                ORDER BY permissions.id";
        $permissions = json_decode(json_encode(DB::select($sql)), true);
        if (empty($permissions))
        {
            return ErrorCode::errorDB();
        }
        return ErrorCode::success($permissions);
    }

    public function addPermission($admin_id, $permission_id)
    {
        $admin = Admin::where('id', $admin_id)->first();
        if (empty($admin))
        {
            return ErrorCode::errorParam('No admin');
        }
        $permission = Permission::where('id', $permission_id)->first();
        if (empty($permission))
        {
            return ErrorCode::errorParam('No permission');
        }
        $admin_permission = AdminPermission::where([['admin_id',$admin_id],['permission_id',$permission_id]])
            ->first();
        if (!empty($admin_permission))
        {
            return ErrorCode::errorAlreadyExist('Permission');
        }
        $create = ['admin_id' => $admin_id,
            'permission_id' => $permission_id
        ];
        $new_admin_permission = AdminPermission::create($create);
        if(empty($new_admin_permission))
        {
            return ErrorCode::errorDB();
        }
        return ErrorCode::success($new_admin_permission);
    }

    public function removePermission($admin_id, $permission_id)
    {
        $admin = Admin::where('id', $admin_id)->first();
        if (empty($admin))
        {
            return ErrorCode::errorParam('No admin');
        }
        $permission = Permission::where('id', $permission_id)->first();
        if (empty($permission))
        {
            return ErrorCode::errorParam('No permission');
        }
        $admin_permission = AdminPermission::where([['admin_id',$admin_id],['permission_id',$permission_id]])
            ->first();
        if (empty($admin_permission))
        {
            return ErrorCode::errorNotExist('Permission');
        }
        if(!$admin_permission->delete())
        {
            return ErrorCode::errorDB();
        }
        return ErrorCode::success('success');
    }

}

