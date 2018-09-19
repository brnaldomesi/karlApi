<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use App\ErrorCode;
use App\Method\UrlSpell;
use DB;
use Illuminate\Database\Eloquent\Model;

class Company extends Model{

    /**
     * @var array
     * rit rate_invalid_time
     */
    protected $fillable = [
        'name','tva',
        'gmt','address',
        'lat','lng',"stripe_acct_id",
        'domain', 'img',
        'email','country','phone1','phone2',
        'tcp','rate','rit','timezone','dst',"ccy",
        'email_host','email_port','email_password'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];

    public static function companyDetail($company_id)
    {
        $result = Company::where('id', $company_id)
            ->select(
                'companies.id', 'companies.name', 'companies.tva', 'companies.gmt',
                'companies.address',
                'companies.lng', 'companies.lat', 'companies.domain',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB(), ""),
                'companies.email', 'companies.phone1', 'companies.phone2','companies.ccy',
                'companies.tcp', 'companies.rate'
            )
            ->first();
        if (empty($result)) {
            return ErrorCode::errorNoObject('company');
        }
        return ErrorCode::success($result);
    }



}