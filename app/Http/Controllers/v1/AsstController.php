<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/8/8
 * Time: 下午3:03
 */

namespace App\Http\Controllers\v1;


use App\Constants;
use App\ErrorCode;
use App\Jobs\SendEmailAsstPasswordJob;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Model\Admin;
use App\Model\Asst;
use App\Model\BookingDayStatistic;
use App\Model\Car;
use App\Model\Sale;
use App\Model\SaleAsstCompany;
use App\Model\SaleCompany;
use App\Model\User;
use App\StatisticConstant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class AsstController extends Controller
{
    public function createAsst()
    {
        $password = Input::get('password', null);
        $first_name = Input::get('first_name', null);
        $last_name = Input::get('last_name', null);
        $mobile = Input::get('mobile', null);
        $email = Input::get('email', null);
        $country = Input::get('country', null);
        $gender = Input::get('gender', 2);
        $lang = Input::get('lang', 'en');

        if (//is_null($username) ||
            is_null($password) ||
            is_null($first_name) ||
            is_null($last_name) ||
            is_null($mobile) ||
            is_null($email) ||
            is_null($lang) ||
            is_null($country)
        ) {
            return ErrorCode::errorMissingParam();
        }
//        if (empty($username)) {
//            return ErrorCode::errorParam("param username is empty");
//        } else {
//        }
        if (empty($first_name)) {
            return ErrorCode::errorParam("param first_name is empty");
        }

        if (empty($last_name)) {
            return ErrorCode::errorParam("param last_name is empty");
        }

        //验证密码格式是否正确 TODO
        if (!MethodAlgorithm::pwdRegex($password)) {
            return ErrorCode::errorParam("Invalid password");
        } else {
            $pwd = md5($password);
        }
        if ($gender != 1 && $gender != 2) {
            return ErrorCode::errorParam('gender');
        }
        if (empty($mobile)) {
            return ErrorCode::errorParam("Invalid mobile");
        }
        //验证邮箱格式是否正确
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam("Invalid email");
        }

        if (empty($lang)) {
            $lang = "en";
        }
        $user = User::where('email', $email)->where('company_id', 0)->first();
        if (!empty($user)) {
            return ErrorCode::errorRegisteredEmail();
        }
        $admin = Admin::leftjoin("users","users.id",'=','admins.user_id')
            ->where('users.email', $email)->first();
        if (!empty($admin)) {
            return ErrorCode::errorRegisteredEmail();
        }
        $user = User::where('mobile', $mobile)->where('company_id', 0)->first();
        if (!empty($user)) {
            return ErrorCode::errorRegisteredMobile();
        }
        $admin = Admin::leftjoin("users","users.id",'=','admins.user_id')
            ->where('users.mobile', $mobile)->first();
        if (!empty($admin)) {
            return ErrorCode::errorRegisteredMobile();
        }
        $user = \DB::transaction(function () use (
            $first_name, $last_name,
            $lang, $email,
            $mobile, $gender,
            $pwd, $country
        ) {
            $user = User::create(
                array("first_name" => $first_name,
                    "last_name" => $last_name,
                    "company_id" => 0,
                    "username" => md5($email),
                    "mobile" => $mobile,
                    "email" => $email,
                    "gender" => $gender,
                    "lang" => $lang,
                    "password" => $pwd));
            $result = $user->save();
            if (!$result) {
                return ErrorCode::errorRegisteredFailed();
            }
            $asst = Asst::create(array("user_id" => $user->id, "country" => $country));
            $asst->asst_id = "SA" . sprintf("%04d", $asst->id);
            $asst->save();
            $user->asst = $asst;
            return $user;
        });
        $this->dispatch(new SendEmailAsstPasswordJob($email,$password));

        return ErrorCode::success($user,false);
    }


    public function getAllAssts(Request $request)
    {
        $token = $request->user->token;
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        $search = Input::get('search', null);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);

        $saleQuery = Asst::leftjoin(DB::raw("(select count(*) as count , sale_asst_companies.asst_id from sale_asst_companies 
                                            left join companies on sale_asst_companies.company_id=companies.id 
                                            group by sale_asst_companies.asst_id
                                            ) as ac"), 'ac.asst_id', '=', 'assts.asst_id')
            ->leftjoin('users', 'users.id', '=', 'assts.user_id')
            ->where(function ($query) use ($search) {
                if (!is_null($search)) {
                    $query->where('assts.asst_id', 'like', "%$search%")
                        ->orWhere('users.email', 'like', "%$search%")
                        ->orWhere('users.mobile', 'like', "%$search%")
                        ->orWhere('users.last_name', 'like', "%$search%")
                        ->orWhere('users.first_name', 'like', "%$search%");
                }
            });
        $count = $saleQuery->count();
        $sales = $saleQuery
            ->select(
                'assts.asst_id',
                'assts.user_id as user_id',
                'assts.country',
                'users.first_name as first_name',
                'users.last_name as last_name',
                'users.gender',
                'users.email',
                'users.mobile',
                DB::raw("ifnull(ac.count,0) as count"),
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'assts.id', $token, UrlSpell::companyAsstType) . ' as avatar_url', '')
            )
            ->skip($skip)
            ->take($per_page)
            ->get();
        return ErrorCode::success(["total" => $count, "sales" => $sales],false);
    }

    public function getAsstDetail(Request $request, $asstId)
    {
        $token = $request->user->token;
        $asst = Asst::getSaleDetail($asstId, $token);
        return ErrorCode::success($asst,false);
    }


    public function updateAsstInfo($asstId)
    {
        try {
            $param = Input::get('param', null);
            $token = Input::get('token', null);
            $asst = Asst::updateAsstInfo($asstId, $param, $token);
            return ErrorCode::success($asst);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function deleteAsstInfo($asstId)
    {
        DB::transaction(function () use ($asstId) {
            $asst = Asst::where('asst_id', $asstId)->first();
            SaleAsstCompany::where('asst_id', $asstId)->delete();
            User::where('id', $asst->user_id)->delete();
            $asst->delete();
        });
        return ErrorCode::success('success');
    }

    public function getCompanies(Request $request)
    {
        $asstId = $request->user->asst->asst_id;
        $companies = SaleAsstCompany::leftjoin('companies', 'companies.id', '=', 'sale_asst_companies.company_id')
            ->leftjoin(DB::raw("(select count(*) as driver_count , users.company_id 
                                        from drivers left join users on users.id=drivers.user_id 
                                        group by users.company_id) as dc"), 'dc.company_id', '=', 'companies.id')
            ->leftjoin(DB::raw("(select count(*) as customer_count , users.company_id 
                                        from customers left join users on users.id=customers.user_id 
                                        group by users.company_id) as pc"), 'pc.company_id', '=', 'companies.id')
            ->leftjoin(DB::raw("(select count(*) as car_count , cars.company_id 
                                        from cars 
                                        group by cars.company_id) as vc"), 'vc.company_id', '=', 'companies.id')
            ->leftjoin(DB::raw("(select count(*) as rides_count , bookings.company_id 
                                        from bookings
                                        group by bookings.company_id) as bc"), 'bc.company_id', '=', 'companies.id')
            ->leftjoin(DB::raw("(select count(*) as rate_count , offers.company_id 
                                        from offers
                                        group by offers.company_id) as oc"), 'oc.company_id', '=', 'companies.id')
            ->leftjoin(DB::raw("(select sum(bds.total_income) as rev_total , bds.company_id 
                                        from booking_day_statistics as bds
                                        group by bds.company_id) as bdc"), 'bdc.company_id', '=', 'companies.id')
            ->leftjoin("sales","sales.sale_id","=","sale_asst_companies.sale_id")
            ->leftjoin("users","sales.user_id","=","users.id")
            ->where('sale_asst_companies.asst_id', $asstId)
            ->select(
                "companies.id",
                "companies.ccy",
                "companies.name",
                "sale_asst_companies.sale_id",
                "users.first_name",
                "users.last_name",
                DB::raw("ifnull(dc.driver_count,0) as driver_count"),
                DB::raw("ifnull(pc.customer_count,0) as customer_count"),
                DB::raw("ifnull(vc.car_count,0) as car_count"),
                DB::raw("ifnull(oc.rate_count,0) as rate_count"),
                DB::raw("ifnull(bdc.rev_total,0) as rev_total"),
                DB::raw("ifnull(bc.rides_count,0) as rides_count")
            )
            ->get();
        return ErrorCode::success($companies);
    }

    public function updateAsstUserInfo(Request $request)
    {
        try {
            $saleId = $request->user->asst->asst_id;
            $param = Input::get('param', null);
            $token = Input::get('token', null);
            if (isset($param['companies'])) {
                unset($param['companies']);
            }
            $sale = Asst::updateAsstInfo($saleId, $param, $token);
            return ErrorCode::success($sale,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    public function getAsstCompanyState(Request $request)
    {
        $asstId = $request->user->asst->asst_id;
        $type = Input::get('type', StatisticConstant::DEFAULT_TYPE);
        $timestamp = Input::get('timestamp', null);
        $dataCount = Input::get('count', StatisticConstant::DEFAULT_COUNT);
        $seque = Input::get('sequence', StatisticConstant::DEFAULT_SEQUENCE);
        if (!is_numeric($timestamp) || $timestamp < 0) {
            $timestamp = time();
        }

        if (!is_numeric($type) ||
            ($type != StatisticConstant::TYPE_DAY &&
                $type != StatisticConstant::TYPE_WEEK &&
                $type != StatisticConstant::TYPE_MONTH)
        ) {
            $type = StatisticConstant::DEFAULT_TYPE;
        }

        if (!is_numeric($dataCount) || $dataCount < 0) {
            $dataCount = StatisticConstant::DEFAULT_COUNT;
        }

        if (!is_numeric($seque) ||
            ($seque != StatisticConstant::SEQUENCE_DESC &&
                $seque != StatisticConstant::SEQUENCE_ASC)
        ) {
            $seque = StatisticConstant::DEFAULT_SEQUENCE;
        }

        $orderBy = $seque === StatisticConstant::DEFAULT_SEQUENCE ? 'desc' : 'ASC';
        return DB::transaction(function() use ($asstId,$type,$timestamp,$dataCount,$seque,$orderBy){
            $companies = SaleAsstCompany::where('asst_id',$asstId)
                ->select(DB::raw("group_concat(company_id) as array"))
                ->groupBy('asst_id')
                ->first();
            if(is_null($companies)){
                return ErrorCode::successEmptyResult('');
            }
            $companies = explode(',',$companies->array);
            $comCount = count($companies);
            $carCount = Car::whereIn('company_id',$companies)->count();

            if ($type == StatisticConstant::TYPE_DAY) {
                $statsUSD = BookingDayStatistic::leftjoin('companies',"companies.id",'=','booking_day_statistics.company_id')
                    ->whereIn('companies.id', $companies)
                    ->whereRaw("unix_timestamp(booking_day_statistics.stat_date) < {$timestamp}")
                    ->select(
                        DB::raw("sum(booking_day_statistics.total_bookings) as total_bookings"),
                        DB::raw("sum(booking_day_statistics.completed_bookings) as completed_bookings"),
                        DB::raw("sum(booking_day_statistics.on_time) as on_time"),
                        DB::raw("sum(booking_day_statistics.exe_an_count) as exe_an_count"),
                        DB::raw("sum(booking_day_statistics.out_an_count) as out_an_count"),
                        DB::raw("sum(booking_day_statistics.an_count) as an_count"),
                        DB::raw("sum(booking_day_statistics.p2p_count) as p2p_count"),
                        DB::raw("sum(booking_day_statistics.hour_count) as hour_count"),
                        DB::raw("sum(booking_day_statistics.cq_count) as cq_count"),
                        DB::raw("sum(booking_day_statistics.appearance_count) as appearance_count"),
                        DB::raw("sum(booking_day_statistics.professionalism_count) as professionalism_count"),
                        DB::raw("sum(booking_day_statistics.driving_count) as driving_count"),
                        DB::raw("sum(booking_day_statistics.cleanliness_count) as cleanliness_count"),
                        DB::raw("sum(booking_day_statistics.quality_count) as quality_count"),
                        DB::raw("sum(booking_day_statistics.total_est_amount) as total_est_amount"),
                        DB::raw("sum(booking_day_statistics.total_income) as total_income"),
                        DB::raw("sum(booking_day_statistics.total_plate) as total_plate"),
                        DB::raw("sum(booking_day_statistics.total_an_fee) as total_an_fee"),
                        DB::raw("unix_timestamp(min(booking_day_statistics.stat_date)) as date"),
                        'companies.ccy'
                    )
                    ->where('companies.ccy','usd')
                    ->orderBy('booking_day_statistics.stat_date', $orderBy)
                    ->groupBy('booking_day_statistics.stat_day', 'booking_day_statistics.stat_year')
                    ->take($dataCount)
                    ->get();

                $statsEUR = BookingDayStatistic::leftjoin('companies',"companies.id",'=','booking_day_statistics.company_id')
                    ->whereIn('companies.id', $companies)
                    ->whereRaw("unix_timestamp(booking_day_statistics.stat_date) < {$timestamp}")
                    ->select(
                        DB::raw("sum(booking_day_statistics.total_bookings) as total_bookings"),
                        DB::raw("sum(booking_day_statistics.completed_bookings) as completed_bookings"),
                        DB::raw("sum(booking_day_statistics.on_time) as on_time"),
                        DB::raw("sum(booking_day_statistics.exe_an_count) as exe_an_count"),
                        DB::raw("sum(booking_day_statistics.out_an_count) as out_an_count"),
                        DB::raw("sum(booking_day_statistics.an_count) as an_count"),
                        DB::raw("sum(booking_day_statistics.p2p_count) as p2p_count"),
                        DB::raw("sum(booking_day_statistics.hour_count) as hour_count"),
                        DB::raw("sum(booking_day_statistics.cq_count) as cq_count"),
                        DB::raw("sum(booking_day_statistics.appearance_count) as appearance_count"),
                        DB::raw("sum(booking_day_statistics.professionalism_count) as professionalism_count"),
                        DB::raw("sum(booking_day_statistics.driving_count) as driving_count"),
                        DB::raw("sum(booking_day_statistics.cleanliness_count) as cleanliness_count"),
                        DB::raw("sum(booking_day_statistics.quality_count) as quality_count"),
                        DB::raw("sum(booking_day_statistics.total_est_amount) as total_est_amount"),
                        DB::raw("sum(booking_day_statistics.total_income) as total_income"),
                        DB::raw("sum(booking_day_statistics.total_plate) as total_plate"),
                        DB::raw("sum(booking_day_statistics.total_an_fee) as total_an_fee"),
                        DB::raw("unix_timestamp(min(booking_day_statistics.stat_date)) as date"),
                        'companies.ccy'
                    )
                    ->where('companies.ccy','eur')
                    ->orderBy('booking_day_statistics.stat_date', $orderBy)
                    ->groupBy('booking_day_statistics.stat_day', 'booking_day_statistics.stat_year')
                    ->take($dataCount)
                    ->get();

            } else if ($type == StatisticConstant::TYPE_WEEK) {
                $statsUSD = BookingDayStatistic::leftjoin('companies',"companies.id",'=','booking_day_statistics.company_id')
                    ->whereIn('companies.id', $companies)
                    ->whereRaw("unix_timestamp(booking_day_statistics.stat_date) < {$timestamp}")
                    ->select(
                        DB::raw("sum(booking_day_statistics.total_bookings) as total_bookings"),
                        DB::raw("sum(booking_day_statistics.completed_bookings) as completed_bookings"),
                        DB::raw("sum(booking_day_statistics.on_time) as on_time"),
                        DB::raw("sum(booking_day_statistics.exe_an_count) as exe_an_count"),
                        DB::raw("sum(booking_day_statistics.out_an_count) as out_an_count"),
                        DB::raw("sum(booking_day_statistics.an_count) as an_count"),
                        DB::raw("sum(booking_day_statistics.p2p_count) as p2p_count"),
                        DB::raw("sum(booking_day_statistics.hour_count) as hour_count"),
                        DB::raw("sum(booking_day_statistics.cq_count) as cq_count"),
                        DB::raw("sum(booking_day_statistics.appearance_count) as appearance_count"),
                        DB::raw("sum(booking_day_statistics.professionalism_count) as professionalism_count"),
                        DB::raw("sum(booking_day_statistics.driving_count) as driving_count"),
                        DB::raw("sum(booking_day_statistics.cleanliness_count) as cleanliness_count"),
                        DB::raw("sum(booking_day_statistics.quality_count) as quality_count"),
                        DB::raw("sum(booking_day_statistics.total_est_amount) as total_est_amount"),
                        DB::raw("sum(booking_day_statistics.total_income) as total_income"),
                        DB::raw("sum(booking_day_statistics.total_plate) as total_plate"),
                        DB::raw("sum(booking_day_statistics.total_an_fee) as total_an_fee"),
                        DB::raw("unix_timestamp(min(booking_day_statistics.stat_date)) as date"),
                        'companies.ccy'
                    )
                    ->where('companies.ccy','usd')
                    ->orderBy(DB::raw("min(booking_day_statistics.stat_date)"), $orderBy)
                    ->groupBy('booking_day_statistics.stat_week', 'booking_day_statistics.stat_week_year','companies.ccy')
                    ->take($dataCount)
                    ->get();
                $statsEUR = BookingDayStatistic::leftjoin('companies',"companies.id",'=','booking_day_statistics.company_id')
                    ->whereIn('companies.id', $companies)
                    ->whereRaw("unix_timestamp(booking_day_statistics.stat_date) < {$timestamp}")
                    ->select(
                        DB::raw("sum(booking_day_statistics.total_bookings) as total_bookings"),
                        DB::raw("sum(booking_day_statistics.completed_bookings) as completed_bookings"),
                        DB::raw("sum(booking_day_statistics.on_time) as on_time"),
                        DB::raw("sum(booking_day_statistics.exe_an_count) as exe_an_count"),
                        DB::raw("sum(booking_day_statistics.out_an_count) as out_an_count"),
                        DB::raw("sum(booking_day_statistics.an_count) as an_count"),
                        DB::raw("sum(booking_day_statistics.p2p_count) as p2p_count"),
                        DB::raw("sum(booking_day_statistics.hour_count) as hour_count"),
                        DB::raw("sum(booking_day_statistics.cq_count) as cq_count"),
                        DB::raw("sum(booking_day_statistics.appearance_count) as appearance_count"),
                        DB::raw("sum(booking_day_statistics.professionalism_count) as professionalism_count"),
                        DB::raw("sum(booking_day_statistics.driving_count) as driving_count"),
                        DB::raw("sum(booking_day_statistics.cleanliness_count) as cleanliness_count"),
                        DB::raw("sum(booking_day_statistics.quality_count) as quality_count"),
                        DB::raw("sum(booking_day_statistics.total_est_amount) as total_est_amount"),
                        DB::raw("sum(booking_day_statistics.total_income) as total_income"),
                        DB::raw("sum(booking_day_statistics.total_plate) as total_plate"),
                        DB::raw("sum(booking_day_statistics.total_an_fee) as total_an_fee"),
                        DB::raw("unix_timestamp(min(booking_day_statistics.stat_date)) as date"),
                        'companies.ccy'
                    )
                    ->where('companies.ccy','eur')
                    ->orderBy(DB::raw("min(booking_day_statistics.stat_date)"), $orderBy)
                    ->groupBy('booking_day_statistics.stat_week', 'booking_day_statistics.stat_week_year','companies.ccy')
                    ->take($dataCount)
                    ->get();
            } else if ($type == StatisticConstant::TYPE_MONTH) {
                $statsUSD = BookingDayStatistic::leftjoin('companies',"companies.id",'=','booking_day_statistics.company_id')
                    ->whereIn('companies.id', $companies)
                    ->whereRaw("unix_timestamp(booking_day_statistics.stat_date) < {$timestamp}")
                    ->select(
                        DB::raw("sum(booking_day_statistics.total_bookings) as total_bookings"),
                        DB::raw("sum(booking_day_statistics.completed_bookings) as completed_bookings"),
                        DB::raw("sum(booking_day_statistics.on_time) as on_time"),
                        DB::raw("sum(booking_day_statistics.exe_an_count) as exe_an_count"),
                        DB::raw("sum(booking_day_statistics.out_an_count) as out_an_count"),
                        DB::raw("sum(booking_day_statistics.an_count) as an_count"),
                        DB::raw("sum(booking_day_statistics.p2p_count) as p2p_count"),
                        DB::raw("sum(booking_day_statistics.hour_count) as hour_count"),
                        DB::raw("sum(booking_day_statistics.cq_count) as cq_count"),
                        DB::raw("sum(booking_day_statistics.appearance_count) as appearance_count"),
                        DB::raw("sum(booking_day_statistics.professionalism_count) as professionalism_count"),
                        DB::raw("sum(booking_day_statistics.driving_count) as driving_count"),
                        DB::raw("sum(booking_day_statistics.cleanliness_count) as cleanliness_count"),
                        DB::raw("sum(booking_day_statistics.quality_count) as quality_count"),
                        DB::raw("sum(booking_day_statistics.total_est_amount) as total_est_amount"),
                        DB::raw("sum(booking_day_statistics.total_income) as total_income"),
                        DB::raw("sum(booking_day_statistics.total_plate) as total_plate"),
                        DB::raw("sum(booking_day_statistics.total_an_fee) as total_an_fee"),
                        DB::raw("unix_timestamp(min(booking_day_statistics.stat_date)) as date"),
                        'companies.ccy'
                    )
                    ->where('companies.ccy','usd')
                    ->orderBy(DB::raw("min(booking_day_statistics.stat_date)"), $orderBy)
                    ->groupBy('booking_day_statistics.stat_month', 'booking_day_statistics.stat_year','companies.ccy')
                    ->take($dataCount)
                    ->get();
                $statsEUR = BookingDayStatistic::leftjoin('companies',"companies.id",'=','booking_day_statistics.company_id')
                    ->whereIn('companies.id', $companies)
                    ->whereRaw("unix_timestamp(booking_day_statistics.stat_date) < {$timestamp}")
                    ->select(
                        DB::raw("sum(booking_day_statistics.total_bookings) as total_bookings"),
                        DB::raw("sum(booking_day_statistics.completed_bookings) as completed_bookings"),
                        DB::raw("sum(booking_day_statistics.on_time) as on_time"),
                        DB::raw("sum(booking_day_statistics.exe_an_count) as exe_an_count"),
                        DB::raw("sum(booking_day_statistics.out_an_count) as out_an_count"),
                        DB::raw("sum(booking_day_statistics.an_count) as an_count"),
                        DB::raw("sum(booking_day_statistics.p2p_count) as p2p_count"),
                        DB::raw("sum(booking_day_statistics.hour_count) as hour_count"),
                        DB::raw("sum(booking_day_statistics.cq_count) as cq_count"),
                        DB::raw("sum(booking_day_statistics.appearance_count) as appearance_count"),
                        DB::raw("sum(booking_day_statistics.professionalism_count) as professionalism_count"),
                        DB::raw("sum(booking_day_statistics.driving_count) as driving_count"),
                        DB::raw("sum(booking_day_statistics.cleanliness_count) as cleanliness_count"),
                        DB::raw("sum(booking_day_statistics.quality_count) as quality_count"),
                        DB::raw("sum(booking_day_statistics.total_est_amount) as total_est_amount"),
                        DB::raw("sum(booking_day_statistics.total_income) as total_income"),
                        DB::raw("sum(booking_day_statistics.total_plate) as total_plate"),
                        DB::raw("sum(booking_day_statistics.total_an_fee) as total_an_fee"),
                        DB::raw("unix_timestamp(min(booking_day_statistics.stat_date)) as date"),
                        'companies.ccy'
                    )
                    ->where('companies.ccy','eur')
                    ->orderBy(DB::raw("min(booking_day_statistics.stat_date)"), $orderBy)
                    ->groupBy('booking_day_statistics.stat_month', 'booking_day_statistics.stat_year','companies.ccy')
                    ->take($dataCount)
                    ->get();
            }

            return ErrorCode::success(["companies"=>$comCount,"cars"=>$carCount,"statistic"=> ['usd'=>$statsUSD,"eur"=>$statsEUR]]);

        });

    }


    public function resetSalePassword()
    {

    }
}