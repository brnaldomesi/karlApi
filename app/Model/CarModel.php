<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    //表中没有created_at 和 updated_at 两个字段的时候添加此行代码,不然调用save方法会报错
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'car_brand_id','car_category_id','name','seats_max','bags_max','sort'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
