<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LnAskRecord extends Model{

    const SECRET_ASK = 1;
    const SECRET_NO = 0;

    const NEEDED = 1;
    const NEEDED_NO = 0;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'car_model_id',
        "secret",
        "needed"
    ];

}