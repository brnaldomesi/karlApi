<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class C2CMatch extends Model
{
    public $timestamps=false;
    public $table="c2c_match";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "from_com_id","to_com_id"
    ];


}
