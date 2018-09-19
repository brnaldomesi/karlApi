<?php 
namespace app\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model {

	use SoftDeletes;

    protected $fillable = [];

    protected $dates = ['deleted_at'];

    public static $rules = [
        // Validation rules
    ];

    // Relationships

}
