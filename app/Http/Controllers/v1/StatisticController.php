<?php

namespace App\Http\Controllers\v1;


use App\ErrorCode;
use App\Model\Company;
use App\Model\BookingDayStatistic;
use App\StatisticConstant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

/**
 * Mark STAT
 * Class StatisticController
 * @package App\Http\Controllers\v1
 */
class StatisticController extends Controller
{
    public function getCompanyStatistic(Request $request)
    {
        $company_id = $request->user->company_id;
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

        if ($type == StatisticConstant::TYPE_DAY) {
            $stats = BookingDayStatistic::where('company_id', $company_id)
                ->whereRaw("unix_timestamp(stat_date) < {$timestamp}")
                ->select(
                    'company_id',
                    'total_bookings',
                    'completed_bookings',
                    'on_time',
                    'exe_an_count',
                    'out_an_count',
                    'an_count',
                    'p2p_count',
                    'hour_count',
                    'cq_count',
                    'appearance_count',
                    'professionalism_count',
                    'driving_count',
                    'cleanliness_count',
                    'quality_count',
                    'total_est_amount',
                    'total_income',
                    'total_plate',
                    'total_an_fee',
                    DB::raw("unix_timestamp(stat_date) as date")
                )
                ->orderBy('stat_date', $orderBy)
                ->take($dataCount)
                ->get();
        } else if ($type == StatisticConstant::TYPE_WEEK) {
            $stats = BookingDayStatistic::where('company_id', $company_id)
                ->whereRaw("unix_timestamp(stat_date) < {$timestamp}")
                ->select(
                    'company_id',
                    DB::raw("sum(total_bookings) as total_bookings"),
                    DB::raw("sum(completed_bookings) as completed_bookings"),
                    DB::raw("sum(on_time) as on_time"),
                    DB::raw("sum(exe_an_count) as exe_an_count"),
                    DB::raw("sum(out_an_count) as out_an_count"),
                    DB::raw("sum(an_count) as an_count"),
                    DB::raw("sum(p2p_count) as p2p_count"),
                    DB::raw("sum(hour_count) as hour_count"),
                    DB::raw("sum(cq_count) as cq_count"),
                    DB::raw("sum(appearance_count) as appearance_count"),
                    DB::raw("sum(professionalism_count) as professionalism_count"),
                    DB::raw("sum(driving_count) as driving_count"),
                    DB::raw("sum(cleanliness_count) as cleanliness_count"),
                    DB::raw("sum(quality_count) as quality_count"),
                    DB::raw("sum(total_est_amount) as total_est_amount"),
                    DB::raw("sum(total_income) as total_income"),
                    DB::raw("sum(total_plate) as total_plate"),
                    DB::raw("sum(total_an_fee) as total_an_fee"),
                    DB::raw("unix_timestamp(min(stat_date)) as date")
                )
                ->orderBy(DB::raw("min(stat_date)"), $orderBy)
                ->groupBy('company_id', 'stat_week', 'stat_week_year')
                ->take($dataCount)
                ->get();
        } else if ($type == StatisticConstant::TYPE_MONTH) {
            $stats = BookingDayStatistic::where('company_id', $company_id)
                ->whereRaw("unix_timestamp(stat_date) < {$timestamp}")
                ->select(
                    'company_id',
                    DB::raw("sum(total_bookings) as total_bookings"),
                    DB::raw("sum(completed_bookings) as completed_bookings"),
                    DB::raw("sum(on_time) as on_time"),
                    DB::raw("sum(exe_an_count) as exe_an_count"),
                    DB::raw("sum(out_an_count) as out_an_count"),
                    DB::raw("sum(an_count) as an_count"),
                    DB::raw("sum(p2p_count) as p2p_count"),
                    DB::raw("sum(hour_count) as hour_count"),
                    DB::raw("sum(cq_count) as cq_count"),
                    DB::raw("sum(appearance_count) as appearance_count"),
                    DB::raw("sum(professionalism_count) as professionalism_count"),
                    DB::raw("sum(driving_count) as driving_count"),
                    DB::raw("sum(cleanliness_count) as cleanliness_count"),
                    DB::raw("sum(quality_count) as quality_count"),
                    DB::raw("sum(total_est_amount) as total_est_amount"),
                    DB::raw("sum(total_income) as total_income"),
                    DB::raw("sum(total_plate) as total_plate"),
                    DB::raw("sum(total_an_fee) as total_an_fee"),
                    DB::raw("unix_timestamp(min(stat_date)) as date")
                )
                ->orderBy(DB::raw("min(stat_date)"), $orderBy)
                ->groupBy('company_id', 'stat_month', 'stat_year')
                ->take($dataCount)
                ->get();
        }

        if (empty($stats)) {
            return ErrorCode::successEmptyResult('no result for search');
        } else {
            return ErrorCode::success($stats);
        }
    }


    public function getPlatformBookingStatistic()
    {
        $companyId = Input::get('company_id', 0);
        $type = Input::get('type', StatisticConstant::DEFAULT_TYPE);
        $timestamp = Input::get('timestamp', null);
        $dataCount = Input::get('count', StatisticConstant::DEFAULT_COUNT);
        $seque = Input::get('sequence', StatisticConstant::DEFAULT_SEQUENCE);
        if (!is_numeric($timestamp) || $timestamp < 0) {
            $timestamp = time();
        }
        if (!is_numeric($companyId) || $companyId < 0) {
            $companyId = 0;
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

        if ($companyId != 0) {
            $company = Company::where('id', $companyId)->select('timezone')->first();
            if (empty($company)) {
                return ErrorCode::errorNotExist('company');
            }
        }

        if ($type == StatisticConstant::TYPE_DAY) {
            $selector = [
                'company_id',
                "total_bookings",
                'completed_bookings',
                'on_time',
                'cancel_count',
                'invalid_count',
                'trouble_count',
                'exe_an_count',
                'out_an_count',
                'an_count',
                'p2p_count',
                'hour_count',
                'cq_count',
                'appearance_count',
                'professionalism_count',
                'driving_count',
                'cleanliness_count',
                'quality_count',
                'total_est_amount',
                'total_income',
                'total_plate',
                'total_an_fee',
                DB::raw("unix_timestamp(stat_date) as date")
            ];

            $stats = BookingDayStatistic::where('company_id', $companyId)
                ->whereRaw("unix_timestamp(stat_date) < {$timestamp}")
                ->select(
                    $selector
                )
                ->orderBy('stat_date', $orderBy)
                ->take($dataCount)
                ->get();

        } else if ($type == StatisticConstant::TYPE_WEEK) {
            $selector = [
                DB::raw("sum(total_bookings) as total_bookings"),
                DB::raw("sum(completed_bookings) as completed_bookings"),
                DB::raw("sum(on_time) as on_time"),
                DB::raw("sum(cancel_count) as cancel_count"),
                DB::raw("sum(invalid_count) as invalid_count"),
                DB::raw("sum(trouble_count) as trouble_count"),
                DB::raw("sum(exe_an_count) as exe_an_count"),
                DB::raw("sum(out_an_count) as out_an_count"),
                DB::raw("sum(an_count) as an_count"),
                DB::raw("sum(p2p_count) as p2p_count"),
                DB::raw("sum(hour_count) as hour_count"),
                DB::raw("sum(cq_count) as cq_count"),
                DB::raw("sum(appearance_count) as appearance_count"),
                DB::raw("sum(professionalism_count) as professionalism_count"),
                DB::raw("sum(driving_count) as driving_count"),
                DB::raw("sum(cleanliness_count) as cleanliness_count"),
                DB::raw("sum(quality_count) as quality_count"),
                DB::raw("sum(total_est_amount) as total_est_amount"),
                DB::raw("sum(total_income) as total_income"),
                DB::raw("sum(total_plate) as total_plate"),
                DB::raw("sum(total_an_fee) as total_an_fee"),
                DB::raw("unix_timestamp(min(stat_date)) as date")
            ];

            $stats = BookingDayStatistic::where('company_id', $companyId)
                ->whereRaw("unix_timestamp(stat_date) < {$timestamp}")
                ->select(
                    $selector
                )
                ->orderBy(DB::raw("min(stat_date)"), $orderBy)
                ->groupBy('company_id', 'stat_week', 'stat_week_year')
                ->take($dataCount)
                ->get();
        } else if ($type == StatisticConstant::TYPE_MONTH) {
            $selector = [
                DB::raw("sum(total_bookings) as total_bookings"),
                DB::raw("sum(completed_bookings) as completed_bookings"),
                DB::raw("sum(on_time) as on_time"),
                DB::raw("sum(cancel_count) as cancel_count"),
                DB::raw("sum(invalid_count) as invalid_count"),
                DB::raw("sum(trouble_count) as trouble_count"),
                DB::raw("sum(exe_an_count) as exe_an_count"),
                DB::raw("sum(out_an_count) as out_an_count"),
                DB::raw("sum(an_count) as an_count"),
                DB::raw("sum(p2p_count) as p2p_count"),
                DB::raw("sum(hour_count) as hour_count"),
                DB::raw("sum(cq_count) as cq_count"),
                DB::raw("sum(appearance_count) as appearance_count"),
                DB::raw("sum(professionalism_count) as professionalism_count"),
                DB::raw("sum(driving_count) as driving_count"),
                DB::raw("sum(cleanliness_count) as cleanliness_count"),
                DB::raw("sum(quality_count) as quality_count"),
                DB::raw("sum(total_est_amount) as total_est_amount"),
                DB::raw("sum(total_income) as total_income"),
                DB::raw("sum(total_plate) as total_plate"),
                DB::raw("sum(total_an_fee) as total_an_fee"),
                DB::raw("unix_timestamp(min(stat_date)) as date")
            ];

            $stats = BookingDayStatistic::where('company_id', $companyId)
                ->whereRaw("unix_timestamp(stat_date) < {$timestamp}")
                ->select(
                    $selector
                )
                ->orderBy(DB::raw("min(stat_date)"), $orderBy)
                ->groupBy('company_id', 'stat_month', 'stat_year')
                ->take($dataCount)
                ->get();
        }

        if (empty($stats)) {
            return ErrorCode::successEmptyResult('no result for search');
        } else {
            return ErrorCode::success($stats);
        }
    }
}