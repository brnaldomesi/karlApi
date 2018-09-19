<?php 
namespace App\Model;
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/02/15
 * Time: 08:12
 */
use Illuminate\Database\Eloquent\Model;

class BookingChangeHistory extends Model{

        const ACTION_TYPE_WITHDRAW = 1;
        const ACTION_TYPE_EDIT = 0;

        protected $fillable = [
            "company_id",
            "admin_id",
            "booking_id",
            "booking_info",
            "action_type",
        ];
        
        protected $hidden=[
                'created_at','updated_at'
        ];

}
?>       