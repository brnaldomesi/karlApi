<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Model\Coupon;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\File;

class CouponController extends Controller
{
    public function index(Request $request) {
        $company_id = Input::get('company_id', null);

        $coupons = Coupon::where('company_id', $company_id)->whereNull('deleted_at')->get();

        if (empty($coupons)) {
            return ErrorCode::successEmptyResult('there is no coupon');
        } else {
            return ErrorCode::success($coupons,false);
        }
    }

    public function get($id) {
        
        $coupon = Coupon::where('id', $id)->get();

        if (empty($coupon)) {
            return ErrorCode::successEmptyResult('there is no coupon detail');
        } else {
            return ErrorCode::success($coupon,false);
        }
    }

    public function update(Request $request, $id) {
        $company_id = Input::get('company_id', null);
        $code = Input::get('code', null);
        $discount_amount = Input::get('discount_amount', null);
        $discount_type = Input::get('discount_type', null);
        $is_onetime = Input::get('is_onetime', null);
        $is_onetime = $is_onetime == false ? 0 : 1;
        $title = Input::get('title', null);
        $starting_date = Input::get('starting_date', null);
        if($starting_date == 0) $starting_date = null;
        $end_date = Input::get('end_date', null);
        if($end_date == 0) $end_date     = null;
        $is_permanent = Input::get('is_permanent', null);
        $is_permanent = $is_permanent == false ? 0 : 1;
        $turn_state = Input::get('turn_state', null);
        $is_stateChange = Input::get('is_stateChange', null);

        if (is_null($code) && is_null($turn_state)) {
            return ErrorCode::errorMissingParam();
        }

        $count = Coupon::where([['code', $code], ['company_id', $company_id], ['id', '<>', $id]])->whereNull('deleted_at')->count();

        if($count > 0)
        {
            return ErrorCode::errorAlreadyExist('Coupon code is');
        }

        if(is_null($is_stateChange)) {

            Coupon::where('id', $id)->update(['code' => $code, 'discount_amount' => $discount_amount, 'discount_type' => $discount_type, 'is_onetime' => $is_onetime, 'title' => $title, 'starting_date' => $starting_date, 'end_date' => $end_date, 'is_permanent' => $is_permanent, 'turn_state' => $turn_state]);
        }
        else {
            Coupon::where('id', $id)->update(['turn_state' => $turn_state]);
        }
        return ErrorCode::success($id,false);
    }

    public function create() {
        $company_id = Input::get('company_id', null);
        $code = Input::get('code', null);
        $discount_amount = Input::get('discount_amount', null);
        $discount_type = Input::get('discount_type', null);
        $is_onetime = Input::get('is_onetime', null);
        $title = Input::get('title', null);
        $starting_date = Input::get('starting_date', null);
        if($starting_date == 0) $starting_date = null;
        $end_date = Input::get('end_date', null);
        if($end_date == 0) $end_date = null;
        $is_permanent = Input::get('is_permanent', null);
        $turn_state = Input::get('turn_state', null);

        if (is_null($code)) {
            return ErrorCode::errorMissingParam();
        }

        $count = Coupon::where([['code', $code], ['company_id', $company_id]])->whereNull('deleted_at')->count();

        if($count > 0)
        {
            return ErrorCode::errorAlreadyExist('Coupon code is');
        }

        $id = Coupon::insertGetId(['company_id' => $company_id, 'code' => $code, 'discount_amount' => $discount_amount, 'discount_type' => $discount_type, 'is_onetime' => $is_onetime, 'title' => $title, 'starting_date' => $starting_date, 'end_date' => $end_date, 'is_permanent' => $is_permanent, 'turn_state' => $turn_state]);
        return ErrorCode::success($id,false);
    }

    public function delete($id) {
        $result = "OK";

        Coupon::where('id', $id)->forceDelete();
        return ErrorCode::success($result,false);
    }
}
