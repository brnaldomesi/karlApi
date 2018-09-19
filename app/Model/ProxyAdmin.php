<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProxyAdmin extends Model{
    public $timestamps=false;
    protected $fillable = [
        'id',
        'company_id',
        'username',
        'password',
        'token',
        'expire_time',
    ];


    private static $URL=[
        '1/companies/add/admin/as/driver' ,
        '1/companies/admins',
        '1/users/change/password',
        '1/admins/avatar',
        '1/admins/setting/push/{token}',
        '1/companies/admins/avatar'
    ];

    public static function urlNotAllow($url)
    {
        return in_array($url,self::$URL);
    }
}