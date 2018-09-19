<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Constants;
use App\Model\Bill;
use App\Model\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class TransactionController extends Controller
{

    public function getCompanyTransactionsBooking(Request $request,$bookingId)
    {
        $company_id = $request->user->company_id;
        $transactions = Bill::leftjoin("bookings", "bills.booking_id", "=", "bookings.id")
            ->leftjoin("booking_transaction_histories as bth","bth.booking_id","=","bookings.id")
            ->leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->leftjoin('orders','orders.booking_id','=','bookings.id')
            ->where("bills.booking_id",$bookingId)
            ->where("bookings.company_id",$company_id)
            ->select(
                DB::raw("unix_timestamp(bills.settle_time) as settle_time"),
                DB::raw("unix_timestamp(orders.start_time) as start_time"),
                DB::raw("unix_timestamp(orders.finish_time) as finish_time"),
                DB::raw("unix_timestamp(bookings.appointed_at) as time"),
                DB::raw("bookings.company_id!=bookings.exe_com_id as an"),
                "bills.an_fee",
                "bills.ccy",
                "bills.booking_id",
                "bills.settle_fee",
                "bills.com_income",
                "bills.platform_income",
                "bookings.company_id as own_com_id",
                "bookings.exe_com_id",
                "bookings.d_address",
                "bookings.a_address",
                "bookings.type",
                "orders.archive",
                "bookings.unit",
                "bookings.offer_data",
                "bookings.passenger_names",
                "bookings.coupon_off",
                "own_com.name as own_com_name",
                "exe_com.name as exe_com_name",
                "own_com.rate as plat_rate",
                DB::raw("if(orders.admin_action!=0,bookings.estimate_distance,orders.actual_distance) as distance"),
                DB::raw("if(orders.admin_action!=0,bookings.estimate_time,orders.actual_time) as duration"),
                DB::raw("round(bills.settle_fee/(1+bookings.tva/100)*bookings.tva/100,2) as tax"),
                DB::raw("round(bookings.option_cost/(1+bookings.tva/100),2) as add_ons"),
                DB::raw("round(bookings.base_cost/(1+bookings.tva/100),2) as base_fare"),
                DB::raw("round(bookings.base_cost/(1+bookings.tva/100),2)+round(bookings.option_cost/(1+bookings.tva/100),2) as sub_total"),
                DB::raw("
                    if(round((bills.settle_fee - bookings.base_cost - bookings.option_cost)/(1+bookings.tva/100),2)+bookings.coupon_off > 1,
                    round((bills.settle_fee - bookings.base_cost - bookings.option_cost)/(1+bookings.tva/100),2)+bookings.coupon_off,
                    0)
                        as additional"),

                DB::raw(Constants::EXE_COMPANY_TVA . " as an_rate"),
                "bookings.driver_data",
                "bookings.customer_data"
            )

            ->first();
        return ErrorCode::success($transactions);
    }

    public function getCompanyTransactions(Request $request)
    {
        $company_id = $request->user->company_id;
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        $filter = Input::get('filter', Constants::BOOK_FILTER_ALL);
        $orderBy = Input::get('order_by', Constants::ORDER_BY_ASC);
        $archive = Input::get('archive',Order::ARCHIVE_TYPE_RESTORE);
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($orderBy != Constants::ORDER_BY_ASC || $orderBy != Constants::ORDER_BY_DESC) {
            $orderBy = Constants::ORDER_BY_ASC;
        }
        if ($orderBy == Constants::ORDER_BY_ASC) {
            $orderBy = "ASC";
        } else {
            $orderBy = "desc";
        }

        if(!is_numeric($archive) ||
            ($archive != Order::ARCHIVE_TYPE_RESTORE &&
             $archive != Order::ARCHIVE_TYPE_ARCHIVE)){
            $archive = Order::ARCHIVE_TYPE_RESTORE;
        }

        $start_time = Input::get('start_time', 0);
        $end_time = Input::get('end_time', time());
        $client_info = Input::get('client_info', null);
        $driver_info = Input::get('driver_info', null);
        $search = Input::get('search', null);
        $transactions = Bill::leftjoin("bookings", "bills.booking_id", "=", "bookings.id")
            ->leftjoin("booking_transaction_histories as bth","bth.booking_id","=","bookings.id")
            ->leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->leftjoin('orders','orders.booking_id','=','bookings.id')
            ->select(
                DB::raw("unix_timestamp(bills.settle_time) as settle_time"),
                DB::raw("unix_timestamp(orders.start_time) as start_time"),
                DB::raw("unix_timestamp(orders.finish_time) as finish_time"),
                DB::raw("unix_timestamp(bookings.appointed_at) as time"),
                DB::raw("bookings.company_id!=bookings.exe_com_id as an"),
                "bills.an_fee",
                "bills.ccy",
                "bills.booking_id",
                "bills.settle_fee",
                "bills.com_income",
                "bills.platform_income",
                "bookings.company_id as own_com_id",
                "bookings.exe_com_id",
                "bookings.d_address",
                "bookings.a_address",
                "bookings.type",
                "orders.archive",
                "bookings.unit",
                "bookings.offer_data",
                "bookings.passenger_names",
                "bookings.coupon_off",
                "own_com.name as own_com_name",
                "exe_com.name as exe_com_name",
                "own_com.rate as plat_rate",
                DB::raw("if(orders.admin_action!=0,bookings.estimate_distance,orders.actual_distance) as distance"),
                DB::raw("if(orders.admin_action!=0,bookings.estimate_time,orders.actual_time) as duration"),
                DB::raw("round(bills.settle_fee/(1+bookings.tva/100)*bookings.tva/100,2) as tax"),
                DB::raw("round(bookings.option_cost/(1+bookings.tva/100),2) as add_ons"),
                DB::raw("round(bookings.base_cost/(1+bookings.tva/100),2) as base_fare"),
                DB::raw("round(bookings.base_cost/(1+bookings.tva/100),2)+round(bookings.option_cost/(1+bookings.tva/100),2) as sub_total"),
                DB::raw("
                    if(round((bills.settle_fee - bookings.base_cost - bookings.option_cost)/(1+bookings.tva/100),2)+bookings.coupon_off > 1,
                    round((bills.settle_fee - bookings.base_cost - bookings.option_cost)/(1+bookings.tva/100),2)+bookings.coupon_off,
                    0)
                        as additional"),

                DB::raw(Constants::EXE_COMPANY_TVA . " as an_rate"),
                "bookings.driver_data",
                "bookings.customer_data"
            )->where(function ($query) use ($filter, $company_id) {
                if ($filter == Constants::BOOK_FILTER_OWN) {
                    $query->where('bookings.company_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_EXE) {
                    $query->where('bookings.exe_com_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_BOOKING_OWN) {
                    $query->where('bookings.exe_com_id', $company_id)
                        ->where('bookings.company_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_BOOKING_OTHER) {
                    $query->where(function ($query) use ($company_id) {
                        $query->where('bookings.exe_com_id', $company_id)
                            ->where('bookings.company_id', '!=', $company_id);
                    })
                        ->orWhere(function ($query) use ($company_id) {
                            $query->where('bookings.company_id',  $company_id)
                                ->where('bookings.exe_com_id','!=', $company_id);
                        });
                } else {
                    $query->where('bookings.company_id', $company_id)
                        ->orWhere('bookings.exe_com_id', $company_id);
                }
            })
            ->where("orders.archive",$archive)
            ->whereBetween(DB::raw('UNIX_TIMESTAMP(bills.settle_time)'), [$start_time, $end_time])
            ->where(function ($query) use ($client_info) {
                if ($client_info != null) {
                    $query->where('bookings.customer_data', 'like', "%" . $client_info . "%");
                }
            })
            ->where(function ($query) use ($driver_info) {
                if ($driver_info != null) {
                    $query->where('bookings.driver_data', 'like', "%" . $driver_info . "%");
                }
            })
            ->where(function ($query) use ($search) {
                if ($search != null) {
                    $query->where('bookings.driver_data', 'like', "%" . $search . "%")
                        ->orWhere('bookings.customer_data', 'like', "%" . $search . "%");
                }
            })
            ->orderBy('bills.settle_time', $orderBy)
            ->skip($per_page * ($page - 1))
            ->take($per_page)
            ->get();

        $count = Bill::leftjoin("bookings", "bills.booking_id", "=", "bookings.id")
            ->leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->leftjoin('orders','orders.booking_id','=','bookings.id')
            ->where("orders.archive",$archive)
            ->whereBetween(DB::raw('UNIX_TIMESTAMP(bills.settle_time)'), [$start_time, $end_time])
            ->where(function ($query) use ($filter, $company_id) {
                if ($filter == Constants::BOOK_FILTER_OWN) {
                    $query->where('bookings.company_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_EXE) {
                    $query->where('bookings.exe_com_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_BOOKING_OWN) {
                    $query->where('bookings.exe_com_id', $company_id)
                        ->where('bookings.company_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_BOOKING_OTHER) {
                    $query->where(function ($query) use ($company_id) {
                        $query->where('bookings.exe_com_id', $company_id)
                            ->where('bookings.company_id', '!=', $company_id);
                        })
                        ->orWhere(function ($query) use ($company_id) {
                            $query->where('bookings.company_id',  $company_id)
                                ->where('bookings.exe_com_id','!=', $company_id);
                        });
                } else {
                    $query->where('bookings.company_id', $company_id)
                        ->orWhere('bookings.exe_com_id', $company_id);
                }
            })->where(function ($query) use ($client_info) {
                if ($client_info != null) {
                    $query->where('bookings.customer_data', 'like', "%" . $client_info . "%");
                }
            })
            ->where(function ($query) use ($driver_info) {
                if ($driver_info != null) {
                    $query->where('bookings.driver_data', 'like', "%" . $driver_info . "%");
                }
            })
            ->where(function ($query) use ($search) {
                if ($search != null) {
                    $query->where('bookings.driver_data', 'like', "%" . $search . "%")
                        ->orWhere('bookings.customer_data', 'like', "%" . $search . "%");
                }
            })
            ->count();
        if (empty($transactions)) {
            return ErrorCode::successEmptyResult('No Transaction');
        }
        $result = ['total' => $count, 'transactions' => $transactions];
        return ErrorCode::success($result);
    }


    public function getCompanyTransactionsBills(Request $request)
    {
        $company_id = $request->user->company_id;
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        $start_time = Input::get('start_time', 0);
        $end_time = Input::get('end_time', time());
        $transactions = Bill::leftjoin('bookings', 'bookings.id', '=', 'bills.booking_id')
            ->select('bills.booking_id',
                DB::raw('UNIX_TIMESTAMP(bills.settle_time) as time'),
                'bills.platform_income as outcome',
                "bills.settle_fee as income")
            ->whereBetween(DB::raw('UNIX_TIMESTAMP(bills.settle_time)'), [$start_time, $end_time])
            ->where('bookings.company_id', $company_id)
            ->orderBy('bills.settle_time', 'desc')
            ->skip($per_page * ($page - 1))
            ->take($per_page)
            ->get();
        $count = Bill::leftjoin('bookings', 'bookings.id', '=', 'bills.booking_id')
            ->where('bookings.company_id', '=', $company_id)
            ->whereBetween(DB::raw('UNIX_TIMESTAMP(bills.settle_time)'), [$start_time, $end_time])
            ->count();
        if (empty($transactions)) {
            return ErrorCode::successEmptyResult('No Transaction');
        }
        $result = ['total' => $count, 'transactions' => $transactions];
        return ErrorCode::success($result);
    }

}

