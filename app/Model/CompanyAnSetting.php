<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CompanyAnSetting extends Model{

    const COMBINE_ENABLE = 1;
    const COMBINE_DISABLE = 0;
    const LN_ENABLE = 1;
    const LN_DISABLE = 0;
    const GN_ENABLE = 1;
    const GN_DISABLE = 0;

    const AN_UNLOCKED = 0;
    const AN_LOCKED = 1;

    const AN_ABLE_COUNT=10;
    protected $fillable = [
        'company_id',
        'locked',
        'ln',
        'gn',
        'combine'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}