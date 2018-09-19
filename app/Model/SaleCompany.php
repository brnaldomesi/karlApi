<?php

namespace App\Model;

/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/08/08
 * Time: 01:58
 */
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Method\UserMethod;
use DB;
use Illuminate\Database\Eloquent\Model;

class SaleCompany extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'id', 'sale_id', 'company_id'
    ];

}