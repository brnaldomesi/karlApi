<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Model\Booking;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class GodBookingsController extends Controller
{
    public function saleBookings(Request $request)
    {
        $saleId = $request->user->sale->sale_id;
        $comName = Input::get("com_name", null);
        $startTime = Input::get("start_time", null);
        $endTime = Input::get("end_time", null);
        $customerName = Input::get("customer");
        $driverName = Input::get("driver", null);
        $exeComName = Input::get("exe_com_name", null);
        $per_page = Input::get("per_page",Constants::PER_PAGE_DEFAULT);
        $page = Input::get("page",Constants::PAGE_DEFAULT);

        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $bookingSql = Booking::leftjoin("companies as own_com", "own_com.id", "=", "bookings.company_id")
            ->leftjoin("companies as exe_com", "exe_com.id", "=", "bookings.exe_com_id")
            ->leftjoin("orders", "orders.booking_id", "=", "bookings.id")
            ->whereRaw("bookings.company_id in (select company_id from sale_companies where sale_id='{$saleId}')")
            ->where(function ($query) use($comName){
                if(!empty($comName)){
                    $query->where("own_com.name","like","%{$comName}%");
                }
            })->where(function ($query) use($exeComName){
                if(!empty($exeComName)){
                    $query->where("exe_com.name","like","%{$exeComName}%");
                }
            })->where(function ($query) use($driverName){
                if(!empty($driverName)){
                    $query->where("bookings.driver_data","like","%{$driverName}%");
                }
            })->where(function ($query) use($customerName){
                if(!empty($customerName)){
                    $query->where("bookings.customer_data","like","%{$customerName}%");
                }
            })->where(function ($query) use($startTime,$endTime){
                if(is_numeric($startTime) && $startTime>0){
                    $query->whereRow("unix_timestamp(bookings.appointed_at) > {$startTime}");
                }
                if(is_numeric($endTime) && $endTime > 0){
                    $query->whereRow("unix_timestamp(bookings.appointed_at) < {$endTime}");
                }
            });
        $count = $bookingSql->count();
        $bookings = $bookingSql->select(
                "bookings.d_address",
                "bookings.a_address",
                "bookings.estimate_time",
                "bookings.estimate_distance",
                "bookings.total_cost",
                "bookings.customer_data",
                "bookings.driver_data",
                "bookings.offer_data",
                "bookings.option_data",
                "bookings.passenger_count",
                "bookings.bags_count",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at"),
                DB::raw("if(bookings.company_id=bookings.exe_com_id,0,1) as an"),
                "own_com.name",
                "exe_com.name"
            )
            ->skip($skip)
            ->take($per_page)
            ->orderBy("bookings.created_at","desc")
            ->get();

        return ErrorCode::success(["total"=>$count,"bookings"=>$bookings]);
    }
    public function asstBookings(Request $request)
    {
        $asstId = $request->user->asst->asst_id;
        $comName = Input::get("com_name", null);
        $startTime = Input::get("start_time", null);
        $endTime = Input::get("end_time", null);
        $customerName = Input::get("customer");
        $driverName = Input::get("driver", null);
        $exeComName = Input::get("exe_com_name", null);
        $per_page = Input::get("per_page",Constants::PER_PAGE_DEFAULT);
        $page = Input::get("page",Constants::PAGE_DEFAULT);

        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $bookingSql = Booking::leftjoin("companies as own_com", "own_com.id", "=", "bookings.company_id")
            ->leftjoin("companies as exe_com", "exe_com.id", "=", "bookings.exe_com_id")
            ->leftjoin("orders", "orders.booking_id", "=", "bookings.id")
            ->whereRaw("bookings.company_id in (select company_id from sale_asst_companies where asst_id='{$asstId}')")
            ->where(function ($query) use($comName){
                if(!empty($comName)){
                    $query->where("own_com.name","like","%{$comName}%");
                }
            })->where(function ($query) use($exeComName){
                if(!empty($exeComName)){
                    $query->where("exe_com.name","like","%{$exeComName}%");
                }
            })->where(function ($query) use($driverName){
                if(!empty($driverName)){
                    $query->where("bookings.driver_data","like","%{$driverName}%");
                }
            })->where(function ($query) use($customerName){
                if(!empty($customerName)){
                    $query->where("bookings.customer_data","like","%{$customerName}%");
                }
            })->where(function ($query) use($startTime,$endTime){
                if(is_numeric($startTime) && $startTime>0){
                    $query->whereRow("unix_timestamp(bookings.appointed_at) > {$startTime}");
                }
                if(is_numeric($endTime) && $endTime > 0){
                    $query->whereRow("unix_timestamp(bookings.appointed_at) < {$endTime}");
                }
            });
        $count = $bookingSql->count();
        $bookings = $bookingSql->select(
                "bookings.d_address",
                "bookings.a_address",
                "bookings.estimate_time",
                "bookings.estimate_distance",
                "bookings.total_cost",
                "bookings.customer_data",
                "bookings.driver_data",
                "bookings.offer_data",
                "bookings.option_data",
                "bookings.passenger_count",
                "bookings.bags_count",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at"),
                DB::raw("if(bookings.company_id=bookings.exe_com_id,0,1) as an"),
                "own_com.name",
                "exe_com.name"
            )
            ->skip($skip)
            ->take($per_page)
            ->orderBy("bookings.created_at","desc")
            ->get();

        return ErrorCode::success(["total"=>$count,"bookings"=>$bookings]);
    }

    public function superAdminBookings()
    {
        $comName = Input::get("com_name", null);
        $startTime = Input::get("start_time", null);
        $endTime = Input::get("end_time", null);
        $customerName = Input::get("customer");
        $driverName = Input::get("driver", null);
        $exeComName = Input::get("exe_com_name", null);
        $per_page = Input::get("per_page",Constants::PER_PAGE_DEFAULT);
        $page = Input::get("page",Constants::PAGE_DEFAULT);

        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $bookingSql = Booking::leftjoin("companies as own_com", "own_com.id", "=", "bookings.company_id")
            ->leftjoin("companies as exe_com", "exe_com.id", "=", "bookings.exe_com_id")
            ->leftjoin("orders", "orders.booking_id", "=", "bookings.id")
            ->where(function ($query) use($comName){
                if(!empty($comName)){
                    $query->where("own_com.name","like","%{$comName}%");
                }
            })->where(function ($query) use($exeComName){
                if(!empty($exeComName)){
                    $query->where("exe_com.name","like","%{$exeComName}%");
                }
            })->where(function ($query) use($driverName){
                if(!empty($driverName)){
                    $query->where("bookings.driver_data","like","%{$driverName}%");
                }
            })->where(function ($query) use($customerName){
                if(!empty($customerName)){
                    $query->where("bookings.customer_data","like","%{$customerName}%");
                }
            })->where(function ($query) use($startTime,$endTime){
                if(is_numeric($startTime) && $startTime>0){
                    $query->whereRow("unix_timestamp(bookings.appointed_at) > {$startTime}");
                }
                if(is_numeric($endTime) && $endTime > 0){
                    $query->whereRow("unix_timestamp(bookings.appointed_at) < {$endTime}");
                }
            });
        $count = $bookingSql->count();
        $bookings = $bookingSql->select(
            "bookings.d_address",
            "bookings.a_address",
            "bookings.estimate_time",
            "bookings.estimate_distance",
            "bookings.total_cost",
            "bookings.customer_data",
            "bookings.driver_data",
            "bookings.offer_data",
            "bookings.option_data",
            "bookings.passenger_count",
            "bookings.bags_count",
            DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at"),
            DB::raw("if(bookings.company_id=bookings.exe_com_id,0,1) as an"),
            "own_com.name",
            "exe_com.name"
        )
            ->skip($skip)
            ->take($per_page)
            ->orderBy("bookings.created_at","desc")
            ->get();

        return ErrorCode::success(["total"=>$count,"bookings"=>$bookings]);
    }

}
