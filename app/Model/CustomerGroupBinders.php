<?php

namespace App\Model;

/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/05/10
 * Time: 02:31
 */
use Illuminate\Database\Eloquent\Model;

class CustomerGroupBinders extends Model
{
    const TYPE_MAIL_CHIMP=1;

    const SORT_RIDES_COUNT =1;
    const SORT_COST_TOTAL =2;

    const STATE_INIT=0;
    const STATE_FINISH=1;
    const STATE_FAIL=-1;
    protected $fillable = [
        "company_id",
        "type",
        "sort",
        "state",
        "outer_key",
    ];

    protected $hidden = [
        "created_at", "updated_at"
    ];

}

?>