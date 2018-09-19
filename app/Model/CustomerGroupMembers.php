<?php
namespace App\Model;
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/05/10
 * Time: 02:31
 */
use Illuminate\Database\Eloquent\Model;

class CustomerGroupMembers extends Model
{
    public $timestamps=false;
    protected $fillable = [
        "group_id",
        "customer_id",
        "customer_id_code",
    ];

    protected $hidden = [

    ];

}

?>