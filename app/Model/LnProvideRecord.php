<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LnProvideRecord extends Model{
    const SECRET_ASK = 1;
    const SECRET_NO = 0;
    const PROVIDED = 1;
    const PROVIDE_NO = 0;
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'car_id',
        "secret",
        "provide"
    ];

}