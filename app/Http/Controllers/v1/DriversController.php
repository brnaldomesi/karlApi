<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Method\UserMethod;
use App\Model\Admin;
use App\Model\Booking;
use App\Model\Calendar;
use App\Model\CalendarEvent;
use App\Model\CalendarRecurringEvent;
use App\Model\Car;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\DriverCar;
use App\Jobs\SendEmailDriverPasswordJob;
use App\Model\OfferDriverCar;
use App\Model\Superadmin;
use App\Model\User;
use App\Model\Company;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\File;

class DriversController extends Controller
{   //获取平台司机
    public function drivers(Request $request)
    {
        $token = $request->user->token;
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $driver = Driver::select('drivers.id as driver_id',
            'drivers.user_id as user_id',
            'drivers.license_number',
            'drivers.hidden_last',
            'drivers.delay_time',
            'users.first_name as first_name',
            'users.last_name as last_name',
            'users.gender',
            'users.email',
            'users.mobile',
            DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'drivers.id', $token, UrlSpell::companyDriverType) . ' as avatar_url', ''),
            'companies.id as company_id',
            'companies.name as companies_name')
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->orderBy('drivers.updated_at', 'desc')
            ->skip($skip)
            ->take($per_page)
            ->get();
        $result = array();
        $result['total'] = Driver::leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->count();
        if (empty($driver)) {
            return ErrorCode::successEmptyResult('there is no drivers');
        } else {
            $result['drivers'] = $driver;
            return ErrorCode::success($result,false);
        }
    }

    //获取公司司机
    public function companyDrivers(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;

        // 判断是否夏时令
        $dst = MethodAlgorithm::checkDstForCompany($company_id);

        $driver = Driver::select('drivers.id as driver_id',
            'drivers.user_id as user_id',
            'drivers.license_number',
            'drivers.hidden_last',
            'drivers.delay_time',
            'users.first_name as first_name',
            'users.last_name as last_name',
            'users.address', 'users.lat', 'users.lng',
            'users.gender',
            'users.email',
            'users.mobile',
            DB::raw("case when " . $dst . " then calendars.dst_routine else calendars.routine end as routine"),
            DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'drivers.id', $token, UrlSpell::companyDriverType) . ' as avatar_url', ''),
            'companies.id as company_id',
            'companies.name as companies_name',
            DB::raw('count(driver_cars.car_id) as car_count')
        )
            ->where('users.company_id', $company_id)
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->leftjoin('driver_cars', 'driver_cars.driver_id', '=', 'drivers.id')
            ->leftjoin('calendars', DB::raw(''), DB::raw(''),
                DB::raw('calendars.owner_id=drivers.id and calendars.type=' . Calendar::DRIVER_TYPE))
            ->orderBy('drivers.updated_at', 'desc')
            ->groupBy('drivers.id')
            ->get();
        if (empty($driver)) {
            return ErrorCode::successEmptyResult('there is no drivers');
        }
        return ErrorCode::success($driver,false);
    }

    //获取司机详情
    public function driver(Request $request, $driver_id)
    {
        $token = $request->get('token');
        $driver = Driver::where('drivers.id', $driver_id)
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->select('drivers.user_id as user_id',
                'drivers.id as driver_id',
                'drivers.license_number',
                'drivers.hidden_last',
                'drivers.delay_time',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.mobile',
                'users.address', 'users.lat', 'users.lng',
                'users.avatar_url',
                'users.gender',
                DB::raw('unix_timestamp(users.updated_at) as updated_at'),
                'companies.id as company_id',
                'companies.name as companies_name')
            ->first();
        if (empty($driver)) {
            return ErrorCode::errorNotExist();
        } else {
            $driver->avatar_url = UrlSpell::getUrlSpell()
                ->spellingAvatarUrl($driver->updated_at, $driver->avatar_url, $token, $driver_id, UrlSpell::driverType);
            $calendar = Calendar::where('owner_id', $driver->driver_id)
                ->where('type', Calendar::DRIVER_TYPE)
                ->first();
            $driver->calendar = $calendar->routine;
            $driver->cars = $this->getDriverAllCars($driver->company_id, $driver_id, $token);
            return ErrorCode::success($driver,false);
        }
    }

    //获取公司司机详情
    public function companyDriver(Request $request, $driver_id)
    {
        $company_id = $request->user->company_id;
        $token = $request->get('token');
        return DB::transaction(function () use ($company_id, $token, $driver_id) {
            $driver = Driver::where('drivers.id', $driver_id)
                ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
                ->leftjoin('admins', 'drivers.user_id', '=', 'admins.user_id')
                ->where('users.company_id', $company_id)
                ->leftjoin('companies', 'users.company_id', "=", "companies.id")
                ->select('drivers.user_id as user_id',
                    'drivers.id as driver_id',
                    'drivers.license_number',
                    'drivers.hidden_last',
                    'drivers.delay_time',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'users.mobile',
                    'users.address', 'users.lat', 'users.lng',
                    'users.avatar_url',
                    'users.gender',
                    DB::raw("if(admins.id is null , 0,1) as is_admin"),
                    DB::raw('unix_timestamp(users.updated_at) as updated_at'),
                    'companies.id as company_id', 'companies.name as companies_name')
                ->first();
            if (empty($driver)) {
                return ErrorCode::errorNotExist('driver');
            } else {
                $driver->avatar_url = UrlSpell::getUrlSpell()
                    ->spellingAvatarUrl($driver->updated_at, $driver->avatar_url, $token, $driver_id, UrlSpell::companyDriverType);

                // 判断是否夏时令
                $dst = MethodAlgorithm::checkDstForDriver($driver_id);

                $calendar = Calendar::where('owner_id', $driver_id)
                    ->where('type', Calendar::DRIVER_TYPE)
                    ->select(
                        'id',
                        'type',
                        'owner_id',
                        DB::raw("case when " . $dst . " then dst_routine else routine end as routine"),
                        'dst',
                        'timezone',
                        'company_id')
                    ->first();
                $driver->calendar = $calendar->routine;
                $driver->cars = $this->getDriverAllCars($driver->company_id, $driver_id, $token);
                return ErrorCode::success($driver,false);
            }
        });
    }

    public function getDriverInfo()
    {
        $token = Input::get('token', null);
        $driver = Driver::leftjoin("users", "drivers.user_id", "=", "users.id")
            ->where('users.token', $token)
            ->select(
                "drivers.id as driver_id",
                "users.id",
                "users.first_name",
                "users.last_name",
                "users.email",
                "users.mobile",
                "users.company_id",
                "users.lng",
                "users.lang",
                "users.lat",
                "users.address",
                "users.gender",
                "users.avatar_url",
                "users.updated_at",
                "drivers.license_number",
                "drivers.hidden_last",
                "drivers.delay_time"
            )
            ->first();
        $driver->avatar_url = UrlSpell::getUrlSpell()
            ->spellingAvatarUrl($driver->updated_at, $driver->avatar_url, $token, '', UrlSpell::mine);
        $driver->token=$token;
        $driver->driver = array(
        [
            "driver_id"=>$driver->driver_id,
            "license_number"=>$driver->license_number,
            "hidden_last"=>$driver->hidden_last,
            "delay_time"=>$driver->delay_time,
        ]
        );
        unset($driver->driver_id);
        unset($driver->license_number);
        unset($driver->hidden_last);
        unset($driver->delay_time);

        return ErrorCode::success($driver,false);
    }

    public function updateDriverInfoBySelf(Request $request)
    {
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        $driver_id = $request->user->driver->id;
        if (is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }

        try {
            $result = $this->updateDriverInfo($driver_id, $param, $token, UrlSpell::driverType);
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    public function updateDriver($driver_id)
    {
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $result = $this->updateDriverInfo($driver_id, $param, $token, UrlSpell::driverType);
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyUpdateDriver(Request $request, $driver_id)
    {
        $company_id = $request->user->company_id;
        $driver = Driver::where('drivers.id', $driver_id)
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->select('users.company_id')
            ->first();
        if ($company_id != $driver->company_id) {
            return (ErrorCode::errorAdminUnauthorizedOperation());
        }
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }

        try {
            $result = $this->updateDriverInfo($driver_id, $param, $token, UrlSpell::companyDriverType);
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function updateDriverInfo($driver_id, $param, $token, $type)
    {
        $result = DB::transaction(function () use ($driver_id, $param, $token, $type) {
            $driver = Driver::where('drivers.id', $driver_id)->first();
            if (empty($driver)) {
                throw new \Exception(ErrorCode::errorNotExist('driver'));
            }
            $param = json_decode($param, true);
            $param['id'] = $driver->user_id;
            $password = isset($param['pwd']) ? $param['pwd'] : null;
            $first_name = isset($param['first_name']) ? $param['first_name'] : null;
            $last_name = isset($param['last_name']) ? $param['last_name'] : null;
            $lang = isset($param['lang']) ? $param['lang'] : null;
            $mobile = isset($param['mobile']) ? $param['mobile'] : null;
            $email = isset($param['email']) ? $param['email'] : null;
            $address = isset($param['address']) ? $param['address'] : null;
            $gender = isset($param['gender']) ? $param['gender'] : null;
            $calendar = isset($param['calendar']) ? $param['calendar'] : null;
            $license_number = isset($param['license_number']) ? $param['license_number'] : '';
            $cars = isset($param['cars']) ? $param['cars'] : null;
            $delay_time = isset($param['delay_time']) ? $param['delay_time'] : null;
            $hidden_last = isset($param['hidden_last']) ? $param['hidden_last'] : null;

            if (
                is_null($password) &&
                is_null($first_name) &&
                is_null($last_name) &&
                is_null($lang) &&
                is_null($mobile) &&
                is_null($email) &&
                is_null($address) &&
                is_null($gender) &&
                is_null($calendar) &&
                is_null($license_number) &&
                is_null($hidden_last) &&
                is_null($cars)
            ) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }
            $user = UserMethod::updateUserInfo($param, false, true);
            if (!is_null($license_number)) {
//                if (empty($license_number)) {
//                    return ErrorCode::errorParam('license_number');
//                }
                $driver->license_number = $license_number;
            }

            if (!is_null($delay_time)) {
                if (!is_numeric($delay_time) || $delay_time < 0) {
                    throw new \Exception(ErrorCode::errorParam('delay_time'));
                } else {
//                    $driver->delay_time = $pre_time;
                    $driver->delay_time = $delay_time;
                }
            }

            if (!is_null($calendar)) {
                if (empty($calendar)) {
                    throw new \Exception(ErrorCode::errorParam('calendar'));
                } else {
                    if (is_array($calendar)) {
                        if (sizeof($calendar) != 7) {
                            throw new \Exception(ErrorCode::errorParam('calender error'));
                        } else {
                            foreach ($calendar as $cal) {
                                if (!preg_match("/[0,1]{48}/", $cal)) {
                                    throw new \Exception(ErrorCode::errorParam('calender error'));
                                }
                            }
                            $driverCalendar = Calendar::where("type", Calendar::DRIVER_TYPE)->where('owner_id', $driver_id)->first();
                            if (empty($driverCalendar)) {
                                throw new \Exception(ErrorCode::errorNotExist('driver calender'));
                            } else {

                                // 判断是否夏时令
                                $dst = MethodAlgorithm::checkDstForDriver($driver_id);

                                if ($dst) {
                                    $driverCalendar->dst_routine = json_encode($calendar);
                                    $driverCalendar->routine = MethodAlgorithm::shiftString($calendar, true);
                                } else {
                                    $driverCalendar->routine = json_encode($calendar);
                                    $driverCalendar->dst_routine = MethodAlgorithm::shiftString($calendar);
                                }

                                $driverCalendar->save();
                            }
                        }
                    } else {
                        throw new \Exception(ErrorCode::errorParam('calendar 2'));
                    }
                }
            }
            if (!is_null($hidden_last)) {
                if (!is_numeric($hidden_last) || ($hidden_last != 0 && $hidden_last != 1)) {
                    throw new \Exception(ErrorCode::errorParam("hidden_last"));
                } else {
                    $driver->hidden_last = $hidden_last;
                }
            }

            if (!is_null($cars)) {
                if (!empty($cars)) {
                    $deleteCars = DriverCar::where('driver_id', $driver_id)->get();
                    if (count($deleteCars) != 0) {
                        if (!DriverCar::where('driver_id', $driver_id)->delete()) {
                            throw new \Exception(ErrorCode::errorDB());
                        }
                    }
                    $carArrays = explode(',', $cars);
                    foreach ($carArrays as $car_id) {
                        $car_info = Car::where([['id', $car_id], ['company_id', $user->company_id]])->first();
                        if (empty($car_info)) {
                            throw new \Exception(ErrorCode::errorNotExist('car ' . $car_id));
                        }
                        DriverCar::create(['car_id' => $car_id, 'driver_id' => $driver->id]);
                    }
                    OfferDriverCar::where('driver_id', $driver_id)->whereNotIn('car_id', $carArrays)->delete();
                } else {
                    DriverCar::where('driver_id', $driver_id)->delete();
                    OfferDriverCar::where('driver_id', $driver_id)->delete();
                }
            }
            $driver->save();
            $driver = $this->getDriver($driver_id, $user->company_id, $token, $type);

            // 判断是否夏时令
            $dst = MethodAlgorithm::checkDstForDriver($driver_id);

            $driverCalendar = Calendar::where('owner_id', $driver_id)->where('type', Calendar::DRIVER_TYPE)
                ->select(DB::raw("case when " . $dst . " then dst_routine else routine end as routine"))
                ->first();

            $driver->calendar = $driverCalendar->routine;
            return $driver;
        });
        return $result;
    }


    //添加司机信息
    public function addDriver()
    {
        $company_id = Input::get('company_id', null);
        $token = Input::get('token', null);
        $param = Input::get('param', null);
        if (is_null($param) || is_null($company_id) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $driver = $this->insertDriver($company_id, json_decode($param, true), $token, UrlSpell::driverType);
            return ErrorCode::success($driver,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    //添加我司司机
    public function companyAddDriver(Request $request)
    {
        $company_id = $request->user->company_id;
        $param = Input::get('param', null);
        $token = Input::get('token', null);

        if (is_null($param) && is_null($token)) {
            return ErrorCode::errorMissingParam();
        }

        $param = json_decode($param, true);
        if (!is_array($param)) {
            return ErrorCode::errorParam('param');
        }

        $pwd = MethodAlgorithm::getRandomPassword();
        $param['password'] = $pwd;

        try {
            $driver = $this->insertDriver($company_id, $param, $token, UrlSpell::companyDriverType);
            //send password email
            $this->dispatch(new SendEmailDriverPasswordJob($driver->email, $company_id, $pwd));
            return ErrorCode::success($driver,false);
        } catch (\Exception $ex) {
//            Log::info('insert info is '.json_encode($param).' error is '.json_encode($ex));
            return $ex->getMessage();
        }
    }

    private function insertDriver($company_id, $param, $token, $type)
    {
        $driver = DB::transaction(function () use ($company_id, $param, $token, $type) {
            $license_number = isset($param['license_number']) ? $param['license_number'] : '';
//            if (empty($license_number)) {
//                throw new \Exception(ErrorCode::errorParam('license_number'));
//            }

            $cars = isset($param['cars']) ? $param['cars'] : null;
            if (is_null($cars)) {
                throw new \Exception(ErrorCode::errorParam('cars'));
            }
            $pre_time = isset($param['delay_time']) ? $param['delay_time'] : null;
            if (!is_numeric($pre_time) || $pre_time < 0) {
                throw new \Exception(ErrorCode::errorParam('delay_time'));
            }

            $hidden_last = isset($param['hidden_last']) ? $param['hidden_last'] : 0;
            if ($hidden_last != 1) {
                $hidden_last = 0;
            }

            $calendar = isset($param['calendar']) ? $param['calendar'] : null;
            if (is_null($calendar)) {
                throw new \Exception(ErrorCode::errorParam('calendar'));
            } else {
                if (is_array($calendar)) {
                    if (sizeof($calendar) != 7) {
                        throw new \Exception(ErrorCode::errorParam('calender error'));
                    } else {
                        foreach ($calendar as $cal) {
                            if (!preg_match("/[0,1]{48}/", $cal)) {
                                throw new \Exception(ErrorCode::errorParam('calender error'));
                            }
                        }
                    }
                } else {
                    throw new \Exception(ErrorCode::errorParam('calendar 2'));
                }
            }


//            $driver = Driver::where('license_number', $license_number)->first();
//            if (!empty($driver)) {
//                throw new \Exception(ErrorCode::errorAlreadyExist('license_number'));
//            }

            $user = UserMethod::insertUserInfo($company_id, $param, false, false, true);
            $driver = Driver::create(["user_id" => $user->id,
                'license_number' => $license_number,
                'hidden_last' => $hidden_last,
                'delay_time' => $pre_time
            ]);
            if (!empty($cars)) {
                $carArrays = explode(',', $cars);
                foreach ($carArrays as $car_id) {
                    $car_info = Car::where([['id', $car_id], ['company_id', $company_id]])->first();
                    if (empty($car_info)) {
                        throw new \Exception(ErrorCode::errorNotExist('car ' . $car_id));
                    }
                    DriverCar::create(['car_id' => $car_id, 'driver_id' => $driver->id]);
                }
            }

            $driver = $this->getDriver($driver->id, $company_id, $token, $type);

            $company = Company::select('id', 'dst')->where('id', $company_id)->first();
            $calendar_dst = $company->dst == null ? 0 : $company->dst;

            // 判断是否夏时令
            $dst = MethodAlgorithm::checkDstForDriver($driver->driver_id);

            if ($dst) {
                $calendars = Calendar::create(['type' => Calendar::DRIVER_TYPE,
                    'owner_id' => $driver->driver_id,
                    'routine' => MethodAlgorithm::shiftString($calendar, true),
                    'dst_routine' => json_encode($calendar),
                    'dst' => $calendar_dst,
                    'company_id' => $company_id]);
            } else {
                $calendars = Calendar::create(['type' => Calendar::DRIVER_TYPE,
                    'owner_id' => $driver->driver_id,
                    'routine' => json_encode($calendar),
                    'dst_routine' => MethodAlgorithm::shiftString($calendar),
                    'dst' => $calendar_dst,
                    'company_id' => $company_id]);
            }

            if (empty($calendars)) {
                throw new \Exception(ErrorCode::errorDB());
            }
            $driver->calender = $calendar;
            return $driver;
        });
        return $driver;
    }

    /**
     * @param $driver_id
     * @param $company_id
     * @param $token
     * @param $type
     * @return mixed
     */
    private function getDriver($driver_id, $company_id, $token, $type)
    {
        $driver = Driver::where('drivers.id', $driver_id)
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->where('users.company_id', $company_id)
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->select('drivers.user_id as user_id',
                'drivers.id as driver_id',
                'drivers.license_number',
                'drivers.hidden_last',
                'drivers.delay_time',
                DB::raw(Driver::AVG_DB),
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.mobile',
                'users.address', 'users.lat', 'users.lng',
                'users.avatar_url',
                'users.gender',
                DB::raw('unix_timestamp(users.updated_at) as updated_at'),
                'companies.id as company_id', 'companies.name as companies_name')
            ->first();
        $driver->avatar_url = UrlSpell::getUrlSpell()
            ->spellingAvatarUrl($driver->updated_at, $driver->avatar_url, $token, $driver_id, $type);
        $driver->cars = $this->getDriverAllCars($company_id, $driver_id, $token);
        return $driver;
    }


    public function deleteDriver($driver_id)
    {
        $driver = Driver::where('drivers.id', $driver_id)
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->first();

        if (empty($driver)) {
            return ErrorCode::errorNotExist('driver');
        }
        try {
            $this->removeDriver($driver_id);
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyDeleteDriver(Request $request, $driver_id)
    {
        $company_id = $request->user->company_id;
        $driver = Driver::where('drivers.id', $driver_id)
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->first();

        if (empty($driver)) {
            return ErrorCode::errorNotExist('driver');
        }

        if ($company_id != $driver->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }

        try {
            $this->removeDriver($driver_id);
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function removeDriver($driver_id)
    {
        DB::transaction(function () use ($driver_id) {
            $bookings = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
                ->where('bookings.driver_id', $driver_id)
                ->where('bookings.appointed_at', '>', DB::raw('now()'))
                ->get();
            if ($bookings->count() > 0) {
                throw new \Exception(ErrorCode::errorDriverDelete($bookings));
            }
//            $event = CalendarEvent::where('re_owner_id',$driver_id)
//                ->where('re_type',' Calendar::DRIVER_TYPE)
//                ->where('start_time','>',DB::raw('now()'))->first();
//            if (!empty($booking) || !empty($event)) {
//                throw new \Exception(ErrorCode::errorDriverDelete());
//            }
            $driver = Driver::where('id', $driver_id)->first();
            $adminCount = Admin::where('user_id', $driver->user_id)->count();
            $superCount = Superadmin::where('user_id', $driver->user_id)->count();
            $customerCount = Customer::where('user_id', $driver->user_id)->count();

            $driver->delete();
            if ($adminCount == 0 && $superCount == 0 && $customerCount == 0) {
                User::where('id', $driver->user_id)->delete();
            }

            DriverCar::where('driver_id', $driver_id)->delete();
            OfferDriverCar::where('driver_id', $driver_id)->delete();
            CalendarRecurringEvent::where([['calendar_recurring_events.owner_id', $driver_id], ['calendar_recurring_events.owner_type', Calendar::DRIVER_TYPE]])->delete();
            Calendar::where([['calendars.owner_id', $driver_id], ['calendars.type', Calendar::DRIVER_TYPE]])->delete();
            CalendarEvent::where([['calendar_events.re_owner_id', $driver_id], ['calendar_events.re_type', Calendar::DRIVER_TYPE]])->delete();
        });
    }

    private function getDriverAllCars($company_id, $driver_id, $token)
    {
        return Car::leftjoin(DB::raw('(select * from driver_cars where driver_cars.driver_id=' . $driver_id . ') as driver_cars'), 'cars.id', '=', 'driver_cars.car_id')
            ->select('cars.id as id',
                'car_brands.name as brand',
                'car_models.name as model',
                'car_categories.name as category',
                'cars.seats_max',
                'cars.bags_max',
                UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token),
                DB::raw('CASE WHEN driver_cars.driver_id = \'' . $driver_id . '\'
    THEN
      1
    ELSE 0
      end                       AS selected', ''),
                'cars.company_id',
                'cars.license_plate',
                'cars.year as year',
                'cars.color as color',
                'cars.description',
                'calendars.routine')
            ->where('calendars.type', Calendar::CAR_TYPE)
            ->where('cars.company_id', $company_id)
            ->leftjoin('car_models', 'cars.car_model_id', '=', 'car_models.id')
            ->leftjoin('car_brands', 'car_models.car_brand_id', '=', 'car_brands.id')
            ->leftjoin('car_categories', 'car_models.car_category_id', '=', 'car_categories.id')
            ->leftjoin('companies', 'cars.company_id', '=', 'companies.id')
            ->leftjoin('calendars', 'calendars.owner_id', '=', 'cars.id')
            ->orderBy('cars.id', 'desc')
            ->get();
    }


    public function updateDriversDeviceToken(Request $request, $device_token)
    {
//        $token = $request->user->token;
        $user_id = $request->user->id;
        $result = Driver::where('user_id', $user_id)->update(['device_token' => $device_token]);
        if ($result) {
            return ErrorCode::success('success');
        } else {
            return ErrorCode::errorDB();
        }
    }


    public function companyAddAdminAsDriver(Request $request)
    {
        $id = $request->user->id;
        $driver = Driver::where('user_id', $id)->first();
        if (!empty($driver)) {
            return ErrorCode::errorAlreadyExist('admin has been a driver');
        }
        $company_id = $request->user->company_id;
        $param = Input::get('param', null);
        $param = json_decode($param, true);
        try {
            $result = DB::transaction(function () use ($id, $company_id, $param) {
                $license_number = isset($param['license_number']) ? $param['license_number'] : '';
                $cars = isset($param['cars']) ? $param['cars'] : null;
                if (is_null($cars)) {
                    throw new \Exception(ErrorCode::errorParam('cars'));
                }
                $pre_time = isset($param['delay_time']) ? $param['delay_time'] : null;
                if (!is_numeric($pre_time) || $pre_time < 0) {
                    throw new \Exception(ErrorCode::errorParam('delay_time'));
                }


                $calendar = isset($param['calendar']) ? $param['calendar'] : null;
                if (is_null($calendar)) {
                    throw new \Exception(ErrorCode::errorParam('calendar'));
                } else {
                    if (is_array($calendar)) {
                        if (sizeof($calendar) != 7) {
                            throw new \Exception(ErrorCode::errorParam('calender error'));
                        } else {
                            foreach ($calendar as $cal) {
                                if (!preg_match("/[0,1]{48}/", $cal)) {
                                    throw new \Exception(ErrorCode::errorParam('calender error'));
                                }
                            }
                        }
                    } else {
                        throw new \Exception(ErrorCode::errorParam('calendar 2'));
                    }
                }


//                $driver = Driver::where('license_number', $license_number)->first();
//                if (!empty($driver)) {
//                    throw new \Exception(ErrorCode::errorAlreadyExist('license_number'));
//                }
                $driver = Driver::create(["user_id" => $id,
                    'license_number' => $license_number,
                    'delay_time' => $pre_time
                ]);
                if (!empty($cars)) {
                    $carArrays = explode(',', $cars);
                    foreach ($carArrays as $car_id) {
                        Log::info('company id ' . $company_id);
                        $car_info = Car::where([['id', $car_id], ['company_id', $company_id]])->first();
                        if (empty($car_info)) {
                            throw new \Exception(ErrorCode::errorNotExist('car ' . $car_id));
                        }
                        DriverCar::create(['car_id' => $car_id, 'driver_id' => $driver->id]);
                    }
                }

                $calendars = Calendar::create(["type" => Calendar::DRIVER_TYPE, "owner_id" => $driver->id,
                    'routine' => json_encode($calendar),
                    'company_id' => $company_id]);
                if (empty($calendars)) {
                    throw new \Exception(ErrorCode::errorDB());
                }
                $driver->calender = $calendar;
                return $driver;
            });
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyAddCustomerAsDriver(Request $request, $customer_id)
    {
        $customer = Customer::where('id', $customer_id)
            ->select('user_id')
            ->first();
        if (empty($customer)) {
            return ErrorCode::errorNotExist('customer');
        }

        $id = $customer->user_id;
        $driver = Driver::where('user_id', $id)->first();
        if (!empty($driver)) {
            return ErrorCode::errorAlreadyExist('customer has been a driver');
        }
        $company_id = $request->user->company_id;
        $user = User::where('id', $id)->select('username', 'email', 'mobile')->first();

        $user = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
            ->where('users.username', $user->username)
            ->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredUsername());
        }
        //再判断admin email
        $user = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
            ->where('users.email', $user->email)
            ->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredEmail());
        }
        //再判断admin mobile
        $user = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
            ->where('users.mobile', $user->mobile)
            ->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredMobile());
        }
        //再判断super admin mobile

        $user = Superadmin::leftjoin('users', 'superadmins.user_id', '=', 'users.id')
            ->where('users.username', $user->username)
            ->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredUsername());
        }
        //再判断super admin email
        $user = Superadmin::leftjoin('users', 'superadmins.user_id', '=', 'users.id')
            ->where('users.email', $user->email)
            ->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredEmail());
        }
        //再判断super admin mobile
        $user = Superadmin::leftjoin('users', 'superadmins.user_id', '=', 'users.id')
            ->where('users.mobile', $user->mobile)
            ->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredMobile());
        }

        $driver = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
            ->where('users.email', $user->email)->first();
        if (!empty($driver)) {
            throw new \Exception(ErrorCode::errorRegisteredEmail());
        }
        $driver = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
            ->where('users.username', $user->username)->first();
        if (!empty($driver)) {
            throw new \Exception(ErrorCode::errorRegisteredUsername());
        }
        $driver = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
            ->where('users.mobile', $user->mobile)->first();
        if (!empty($driver)) {
            throw new \Exception(ErrorCode::errorRegisteredMobile());
        }


        $param = Input::get('param', null);
        $param = json_decode($param, true);
        try {
            $result = DB::transaction(function () use ($id, $company_id, $param) {
                $license_number = isset($param['license_number']) ? $param['license_number'] : '';
                $cars = isset($param['cars']) ? $param['cars'] : null;
                if (is_null($cars)) {
                    throw new \Exception(ErrorCode::errorParam('cars'));
                }
                $pre_time = isset($param['delay_time']) ? $param['delay_time'] : null;
                if (!is_numeric($pre_time) || $pre_time < 0) {
                    throw new \Exception(ErrorCode::errorParam('delay_time'));
                }


                $calendar = isset($param['calendar']) ? $param['calendar'] : null;
                if (is_null($calendar)) {
                    throw new \Exception(ErrorCode::errorParam('calendar'));
                } else {
                    if (is_array($calendar)) {
                        if (sizeof($calendar) != 7) {
                            throw new \Exception(ErrorCode::errorParam('calender error'));
                        } else {
                            foreach ($calendar as $cal) {
                                if (!preg_match("/[0,1]{48}/", $cal)) {
                                    throw new \Exception(ErrorCode::errorParam('calender error'));
                                }
                            }
                        }
                    } else {
                        throw new \Exception(ErrorCode::errorParam('calendar 2'));
                    }
                }


                $driver = Driver::where('license_number', $license_number)->first();
                if (!empty($driver)) {
                    throw new \Exception(ErrorCode::errorAlreadyExist('license_number'));
                }
                $driver = Driver::create(["user_id" => $id,
                    'license_number' => $license_number,
                    'delay_time' => $pre_time
                ]);
                if (!empty($cars)) {
                    $carArrays = explode(',', $cars);
                    foreach ($carArrays as $car_id) {
                        $car_info = Car::where([['id', $car_id], ['company_id', $company_id]])->first();
                        if (empty($car_info)) {
                            throw new \Exception(ErrorCode::errorNotExist('car ' . $car_id));
                        }
                        DriverCar::create(['car_id' => $car_id, 'driver_id' => $driver->id]);
                    }
                }

                $calendars = Calendar::create(["type" => Calendar::DRIVER_TYPE, "owner_id" => $driver->id,
                    'routine' => json_encode($calendar),
                    'company_id' => $company_id]);
                if (empty($calendars)) {
                    throw new \Exception(ErrorCode::errorDB());
                }
                $driver->calender = $calendar;
                return $driver;
            });
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    public function addDriversWithPassword(Request $request)
    {
        $company_id = $request->user->company_id;
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($param) && is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        $param = json_decode($param, true);
        if (!is_array($param)) {
            return ErrorCode::errorParam('param');
        }

        $pwd = MethodAlgorithm::getRandomPassword();
        $param['gender'] = 2;
        $param['address'] = '';
        $param['password'] = $pwd;

        try {
            $driver = $this->insertDriver($company_id, $param, $token, UrlSpell::companyDriverType);
            //send password email
            $this->dispatch(new SendEmailDriverPasswordJob($driver->email, $company_id, $pwd));
            return ErrorCode::success($driver,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}
