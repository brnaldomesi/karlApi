<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/6/9
 * Time: 下午3:38
 */

namespace App\Method;


use App\Constants;
use App\ErrorCode;
use App\Model\Company;
use DB;

class CompanyMethod
{
    public static function companies($page,$per_page)
    {
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        $companies = Company::leftjoin(
            DB::raw("(select count(*) as count,company_id 
            from cars group by cars.company_id) as cars "),
            'cars.company_id', '=', 'companies.id')
            ->leftjoin(
                DB::raw("(select count(*) as count,company_id 
            from offers group by offers.company_id) as offers "),
                'offers.company_id', '=', 'companies.id'
            )
            ->leftjoin(
                DB::raw("(select count(*) as count,company_id 
            from options group by options.company_id) as options "),
                'options.company_id', '=', 'companies.id'
            )
            ->leftjoin(
                DB::raw("(select count(*) as count,users.company_id 
            from drivers  left join users on users.id=drivers.user_id group by users.company_id) as drivers "),
                'drivers.company_id', '=', 'companies.id'
            )
            ->leftjoin(
                DB::raw("(select count(*) as count,users.company_id 
            from customers left join users on users.id=customers.user_id  group by users.company_id) as customers "),
                'customers.company_id', '=', 'companies.id'
            )
            ->leftjoin('sale_companies','sale_companies.company_id','=','companies.id')
            ->leftjoin('sales','sale_companies.sale_id','=','sales.sale_id')
            ->leftjoin('users','sales.user_id','=','users.id')
            ->orderBy('companies.updated_at', 'desc')
            ->skip($per_page * ($page - 1))
            ->take($per_page)
            ->select(
                'companies.id', 'companies.name', 'companies.tva', 'companies.gmt',
                'companies.address', 'companies.lng', 'companies.lat', 'companies.email',
                'companies.phone1', 'companies.phone2','companies.ccy',
                'companies.domain',
                'sale_companies.sale_id',
                DB::raw("concat(users.first_name,' ',users.last_name) as sale_name"),
                DB::raw('if(sale_companies.sale_id is null,0,1) as sale_connect'),
                DB::raw('ifnull(cars.count,0) as car_count'),
                DB::raw('ifnull(drivers.count,0) as driver_count'),
                DB::raw('ifnull(customers.count,0) as customer_count'),
                DB::raw('ifnull(options.count,0) as option_count'),
                DB::raw('ifnull(offers.count,0) as offer_count'),
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB(), "")
            )
            ->get();
        if (empty($companies)) {
            return ErrorCode::successEmptyResult('No companies');
        }
        $count = Company::all()->count();
        $result = ['total' => $count, 'companies' => $companies];
        return ErrorCode::success($result);
    }

}