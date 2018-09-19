<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Method\StripeMethod;
use App\Model\CompanyPayMethod;
use Illuminate\Support\Facades\File;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Token;


$app->group(['prefix' => '1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'guest'], function () use ($app) {
// 需要Guest权限的放在这里
    //check guest
    $app->get('version', function () {
        return \App\Version::version;
    });

    $app->get("send/new/created/companies", function () {
        system("php ../artisan new:companies");
    });
    $app->post('companies/easysignup', 'CompaniesController@createCompanyByEasySignUp');
    $app->get('companies/{company_id}/info', 'CompaniesController@getCompanyInfo');
    //check guest
    $app->post('login', 'AuthController@login');
    //check guest
    $app->post('driver/login', 'AuthController@driverLogin');
    //check guest
    $app->post('{company_id}/login', 'AuthController@customerLogin');

    //check guest
    $app->get('cars/categories', 'CarsController@categories');
    //check guest
    $app->get('cars/brands', 'CarsController@brands');
    //check guest
    $app->get('cars/brands/models', 'CarsController@platformAllModels');
    //check guest
    $app->get('cars/brands/{brand_id}/models', 'CarsController@models');
    //check guest
    $app->get('companies/{company_id}/cars/categories', 'CarsController@companyCarCategories');
    //check guest
    $app->post('companies/{id}/customers/register', 'CustomersController@register');

    //unshown guest   use for get company logo
    //unshown guest   use for get car model img
    $app->get('ask/cars/models/{model_id}', 'FilesController@getAskCarModelImage');
    //unshown guest   use for get car model img
    $app->get('cars/models/{model_id}/{sn}', 'FilesController@getCarModelImage');
    //unshown guest   use for custom quote determine.
    $app->get('bookings/{booking_sn}/determine/{key}/{lang}', 'BookingsController@bookingDetermine');

    //unshown guest   use for get company car image
    $app->get('companies/cars/{car_id}/image/{image_sn}', 'FilesController@getCarsImage');

    //check guest
    $app->get('drivers/{driver_id}/avatar', 'FilesController@getDriverAvatar');
    $app->get('sales/{driver_id}/avatar', 'FilesController@getDriverAvatar');
    $app->get('assts/{driver_id}/avatar', 'FilesController@getDriverAvatar');
    //check guest
    $app->get('companies/{id}/offers/availability', 'TripController@checkOfferForTrip');
    $app->get('companies/{id}/has/offers/', 'OffersController@hasOffers');
    $app->get('companies/{id}/coupon/{code}', 'TripController@checkCompanyCouponCode');

    $app->get('service/price', 'StripeController@getSkuInfo');
    $app->get('service/coupon/{code}', 'StripeController@getCouponInfo');
    $app->post('service/pay/order', 'StripeController@payOrder');
    $app->get('companies/logo/{company_name}', 'FilesController@invoiceGetCompanyLogo');

//    $app->get('admins/send/code', 'UsersController@findAdminPassword');
//    $app->post('admins/rest/password', 'UsersController@resetAdminPassword');
//    $app->get('drivers/send/code', 'UsersController@findDriverPassword');
//    $app->post('drivers/rest/password', 'UsersController@resetDriverPassword');
//    $app->get('{company_id}/customers/send/code', 'UsersController@findCustomerPassword');
//    $app->post('{company_id}/customers/rest/password', 'UsersController@resetCustomerPassword');
    $app->post('{company_id}/customer/template/password', 'UsersController@getCustomerTemplatePassword');
    $app->post('driver/template/password', 'UsersController@getDriverTemplatePassword');
    $app->post('dashboard/template/password', 'UsersController@getAdminTemplatePassword');
//    $app->post('sales/template/password', 'UsersController@getSalesTemplatePassword');

    $app->get('companies/{company_id}/disclaimer', 'CompaniesSettingController@getCompanyDisclaimerHtml');

    $app->get('airline/flights/list', "AirlineController@getAirlineAndFlightByLatLng");

});

//
$app->group(['prefix' => '1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'customer'], function () use ($app) {
    //需要乘客权限的放在这里:

    //check customer
    $app->post('customers/bookings', 'TripController@customerAddBooking');
    //check customer
    $app->get('customer/orders/state', 'OrdersController@customerGetOrderState');
    $app->get('customers/orders/state', 'OrdersController@customerGetOrderState');
    //check customer
    $app->post('customers/avatar', 'FilesController@uploadAvatarFile');
    //check customer
    $app->get('customers/company', 'CompaniesController@getMyCompany');
    //check customer
    $app->patch('customer', 'CustomersController@customerUpdateInfo');
    $app->patch('customers', 'CustomersController@customerUpdateInfo');
    //check customer
    $app->post('companies/orders/feedback', 'OrdersController@addOrderFeedback');
    //check customer
    $app->get('customer/credit_cards', 'PaymentController@getCustomersCreditCard');
    $app->get('customers/credit_cards', 'PaymentController@getCustomersCreditCard');
    //check customer
    $app->post('customer/credit_cards', 'PaymentController@addCustomersCreditCard');
    $app->post('customers/credit_cards', 'PaymentController@addCustomersCreditCard');
    //check customer
    $app->delete('customer/credit_cards/{card_token}', 'PaymentController@deleteCustomerCreditCards');
    $app->delete('customers/credit_cards/{card_token}', 'PaymentController@deleteCustomerCreditCards');
    //check customer
    $app->get('customers/bookings/{booking_id}/invoice', 'PaymentController@customerAskRateInvoice');

    //$app->post  ('customers/device/{device_token}', "CustomersController@updateCustomersDeviceToken");

    $app->get('customers/bookings', 'BookingsController@customersGetBookings');
    $app->get('customers/info', "CustomersController@getCustomerInfoDetail");
});

$app->group(['prefix' => '1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'driver'], function () use ($app) {
    //需要司机权限的放在这里"
    //check driver
//    $app->get   ('drivers/bookings/past',               'BookingsController@driverGetBookingsPast');
    //check driver
//    $app->get   ('drivers/bookings/upcoming',           'BookingsController@driverGetBookingsUpcoming');
    //check driver
    $app->get('drivers/calendars/routine', 'CalendarsController@getDriverRoutine');
    //check driver
    $app->post('drivers/calendars/events', 'CalendarsController@createDriverEvent');

    $app->get('drivers/calendars/events', 'CalendarsController@driversGetEvents');
    $app->get('drivers/calendars/events/counts', 'CalendarsController@driversGetEventCounts');
    //check driver
    $app->delete('drivers/calendars/events/{id}', 'CalendarsController@deleteDriverEvent');
    //check driver
    $app->put('drivers/calendars/routine', 'CalendarsController@putDriverRoutine');
    //check driver
    $app->get('drivers/calendars/events/upcoming', 'CalendarsController@getDriverEventUpcoming');
    //check driver
    $app->post('drivers/avatar', 'FilesController@uploadAvatarFile');
    //check driver
    $app->post('companies/orders/driver/location', 'OrdersController@updateDriverLocation');
    $app->post('drivers/upload/orders/location', 'OrdersController@updateDriverLocation');
    //check driver
    $app->patch('order/state', 'OrdersController@updateOrderState');
    //decline
    $app->patch('drivers/order/state', 'OrdersController@updateOrderState');
    $app->put('drivers/order/state', 'OrdersController@updateOrderState');
    //check driver
    $app->patch('drivers/info', 'DriversController@updateDriverInfoBySelf');
    $app->put('drivers/info', 'DriversController@updateDriverInfoBySelf');
    $app->get('drivers/info', 'DriversController@getDriverInfo');
    //check driver
    $app->post('bookings/{booking_id}/done', 'OrdersController@driverFinishOrder');
    $app->post('drivers/finished/bookings/{booking_id}', 'OrdersController@driverFinishOrder');
    //$app->post  ('drivers/device/{device_token}', "DriversController@updateDriversDeviceToken");

    $app->get('drivers/bookings', 'BookingsController@driversGetBookings');
    $app->get('drivers/trip/bookings', 'BookingsController@driversGetTripBookings');
    $app->get('drivers/bookings/counts', 'BookingsController@driversGetBookingsCounts');
    $app->get('drivers/bookings/{booking_id}', 'BookingsController@driverGetBookingDetail');
    $app->get('drivers/bookings/{booking_id}/airline', "AirlineController@getAirlineArriveStates");


});

/*

Created by Pham 3/18/2018
*/
$app->group(['prefix' => '1', 'namespace' => 'App\Http\Controllers\v1'], function () use ($app) {
    $app->get('coupon', 'CouponController@index');
    $app->post('coupon', 'CouponController@create');
    $app->get('coupon/{id}', 'CouponController@get');
    $app->put('coupon/{id}', 'CouponController@update');
    $app->delete('coupon/{id}', 'CouponController@delete');

});

$app->group(['prefix' => '1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'admin'], function () use ($app) {
    //需要admin权限的放在这里:
    //check admin
    $app->post('companies/admin/setting/push/{token}', 'AdminsController@updateAdminDeviceToken');
    $app->post('bookings', 'TripController@adminAddBooking');
    $app->post('companies/bookings', 'TripController@adminAddBooking');
    //check admin
    $app->get('companies/cars/count', 'CarsController@companyCarsCount');
    //check admin
    $app->get('companies/cars', 'CarsController@companyCars');
    //check admin
    $app->post('companies/cars', 'CarsController@newCompanyCar');
    //check admin
    $app->post('companies/cars/{car_id}/info', 'CarsController@updateCompanyCar');
    //check admin
    $app->delete('companies/cars/{car_id}', 'CarsController@removeCompanyCar');
    // $app->get('admins/permissions', 'PermissionsController@adminPermissions'); 暂时不公开 屏蔽之
    //check admin
    $app->get('companies/own', 'CompaniesController@getMyCompany');
    //check admin
    $app->patch('companies/own', 'CompaniesController@updateMyCompany');
    //check admin
    $app->get('companies/options', 'OptionsController@getCompaniesOptions');
    //check admin
    $app->post('companies/options', 'OptionsController@companyAddOptions');
    //check admin
    $app->patch('companies/options/{option_id}', 'OptionsController@companyUpdateOption');
    //check admin
    $app->delete('companies/options/{option_id}', 'OptionsController@companyDeleteOption');
    //check admin
    $app->get('companies/drivers', 'DriversController@companyDrivers');
    //check admin
    $app->post('companies/drivers', 'DriversController@companyAddDriver');
    //check admin
    $app->post('companies/add/admin/as/driver', 'DriversController@companyAddAdminAsDriver');
    //check admin
    $app->post('companies/add/customer/{customer_id}/as/driver', 'DriversController@companyAddCustomerAsDriver');
    //check admin
    $app->patch('companies/drivers/{driver_id}', 'DriversController@updateDriver');
    //check admin
    $app->delete('companies/drivers/{driver_id}', 'DriversController@companyDeleteDriver');
    //check admin
    $app->get('companies/customers', 'CustomersController@companyCustomers');
    //check admin
    $app->post('companies/customers', 'CustomersController@companyAddCustomer');
    //check admin
    $app->get('companies/customers/count', 'CustomersController@companyCustomerCount');
    //Deprecated
    $app->delete('companies/customers/{customer_id}', 'CustomersController@companyDeleteCustomer');
//    check admin
    $app->patch('companies/customers/{customer_id}', 'CustomersController@companyUpdateCustomer');
    //check admin
    $app->post('companies/offers', 'OffersController@companyAddOffer');
    //check admin
    $app->get('companies/offers', 'OffersController@companyGetOffers');
    //check admin
    $app->get('companies/offers/cars', 'CarsController@getAllCarsAndDrivers');
    //check admin
    $app->get('companies/offers/{offer_id}', 'OffersController@companyGetOffer');
    //check admin
    $app->patch('companies/offers/{offer_id}', 'OffersController@companyUpdateOffer');
    //check admin
    $app->delete('companies/offers/{offer_id}', 'OffersController@companyDeleteOffer');
    //check admin
    $app->get('companies/bookings/counts', 'BookingsController@companiesGetBookingCounts');
    //check admin
    $app->put('companies/bookings/{booking_id}', 'ChangeBookingController@updateBookingInfo');
    //check admin
//    $app->delete('companies/bookings/{booking_id}',             'BookingsController@deleteBookings');
    //check admin
    $app->get('companies/orders/state', 'OrdersController@companyGetOrderState');
    //check admin
    $app->get('companies/orders/feedback', 'OrdersController@companyGetFeedback');
    //check admin
    $app->get('companies/cars/{car_id}', 'CarsController@companyCarDetail');
    //check admin
    $app->patch('companies/admins', 'AdminsController@companyUpdateAdminInfo');
    //check admin
    $app->get('companies/admins/{admin_id}', 'AdminsController@getAdmin');
    //check admin
    $app->get('companies/drivers/{driver_id}', 'DriversController@companyDriver');
    //check admin
    $app->get('companies/customers/{customer_id}', 'CustomersController@companyCustomer');
    //check admin
    $app->post('admins/avatar', 'FilesController@uploadAvatarFile');
    $app->post('companies/admins/avatar', 'FilesController@uploadAvatarFile');
    //check admin
    $app->post('companies/drivers/{driver_id}/avatar', 'FilesController@companiesUploadDriverAvatar');
    //check admin
    $app->post('companies/customers/{customer_id}/avatar', 'FilesController@companiesUploadCustomerAvatar');
    //check admin
    $app->post('companies/cars/{car_id}/image', 'FilesController@updateCompanyCarlImg');

    $app->get('companies/bookings', 'BookingsController@companiesGetBookings');

    $app->get('companies/events', 'CalendarsController@companiesGetEvents');
    //check admin
    //==========
    $app->get('calendars/events/upcoming', 'CalendarsController@companyGetUpcomingEvents');
    $app->get('companies/calendars/events/upcoming', 'CalendarsController@companyGetUpcomingEvents');
    //check admin
    //==========
    $app->get('calendars/events/past', 'CalendarsController@companyGetPastEvents');
    $app->get('companies/calendars/events/past', 'CalendarsController@companyGetPastEvents');
    //check admin
    //==========
    $app->post('calendars/events/', 'CalendarsController@companyAddEvent');
    $app->post('companies/calendars/events/', 'CalendarsController@companyAddEvent');
    //==========
    $app->get('calendars/events/', 'CalendarsController@companiesGetEvents');
    $app->get('companies/calendars/events/', 'CalendarsController@companiesGetEvents');
    //==========
    $app->get('calendars/events/counts', 'CalendarsController@companiesGetEventsCounts');
    $app->get('companies/calendars/events/counts', 'CalendarsController@companiesGetEventsCounts');
    //check admin
    $app->delete('calendars/events/{id}', 'CalendarsController@companyDeleteEvents');
    $app->delete('companies/calendars/events/{id}', 'CalendarsController@companyDeleteEvents');
    //check admin
    $app->get('companies/bookings/{booking_id}', 'BookingsController@getBookingDetail');
    //check admin
    $app->post('companies/bookings/{booking_id}/end', 'BookingsController@endBooking');
    //!!!!! admin
    $app->post('companies/order/{booking_id}/fee/modification', 'OrdersController@changeOrderFeeModification');

//    $app->get   ('company/credit_cards',                'PaymentController@getCompanyCreditCard');
//    $app->put   ('company/credit_cards',                'PaymentController@putCompanyCreditCard');
//    $app->delete('company/credit_cards/{card_number}',  'PaymentController@deleteCompanyCreditCards');
    //check admin
    $app->get('companies/payment/methods', 'PaymentController@getCompanyAllPayMethod');
    //check admin
    $app->post('companies/payment/methods', 'PaymentController@addCompanyAllPayMethod');
    //check admin
    $app->put('companies/payment/methods/{method_id}/active', 'PaymentController@updateCompanyPayMethodActive');
    //check admin
    $app->delete('companies/payment/methods/{method_id}', 'PaymentController@deleteCompanyPayMethod');

    //check admin
    $app->post('companies/logo', 'FilesController@uploadCompanyImg');

    //check admin
    $app->get('companies/customers/{customer_id}/credit_cards', 'PaymentController@companyGetCustomersCreditCard');
    //check admin
    $app->post('companies/customers/{customer_id}/credit_cards', 'PaymentController@companyAddCustomersCreditCard');
    //check admin
    $app->delete('companies/customers/{customer_id}/credit_cards/{card_token}', 'PaymentController@companyDeleteCustomerCreditCards');
    //check admin
    $app->get('companies/orders/statistics', 'StatisticController@getCompanyStatistic');
    //check admin
    $app->get('companies/booking/{id}/offer/info', 'OffersController@getBookingOfferInfo');
    //check admin
    $app->get('companies/bookings/{booking_id}/offers/availability', 'ChangeBookingController@checkOfferMatchBooking');
    //check admin
    $app->post('companies/add/customer/quote/', 'TripController@addCustomerQuote');
    //check admin
    $app->get('companies/customer/quote/availability', 'TripController@checkCustomerQuote');

    //check admin
    $app->get('companies/transactions', 'TransactionController@getCompanyTransactions');
    $app->get('companies/bookings/transactions/{bookingId}', 'TransactionController@getCompanyTransactionsBooking');
    //check admin
    $app->get('companies/transactions/bills', 'TransactionController@getCompanyTransactionsBills');
    //check admin
    //==========
    $app->get('companies/send/bookings/{booking_id}/invoice', 'PaymentController@sendInvoiceToCustomer');
    $app->get('companies/bookings/{booking_id}/invoice/info', 'PaymentController@getBookingInvoiceDetail');
    $app->get('companies/bookings/{booking_id}/invoice/html', 'PaymentController@sendInvoiceToCustomerHtml');

    $app->patch('companies/ln/{enable}', 'CompanyAnSettingController@changeCompanyLnSetting');
    $app->patch('companies/gn/{enable}', 'CompanyAnSettingController@changeCompanyGnSetting');
    $app->patch('companies/combine/{enable}', 'CompanyAnSettingController@changeCompanyCombineSetting');
    $app->patch('companies/an/radius', 'CompanyAnSettingController@changeCompanyRadiusSetting');

    $app->post('companies/car/for/ask/{car_model_id}', 'CompanyAnSettingController@addLnCarModelForAsk');
    $app->post('companies/car/for/provide/{car_id}', 'CompanyAnSettingController@addLnCarForProvider');

    $app->delete('companies/car/for/ask/{car_model_id}', 'CompanyAnSettingController@removeLnCarModelForAsk');
    $app->delete('companies/car/for/provide/{car_id}', 'CompanyAnSettingController@removeLnCarForProvider');

    $app->get('companies/an/setting', 'CompanyAnSettingController@getCompanyAnSetting');

    $app->get('companies/bookings/{booking_id}/email/itinerary', 'BookingsController@sendCustomersBooking');

    $app->patch('companies/orders/state', 'TripController@adminChangeBookingStat');

    $app->patch('companies/disclaimer', 'CompaniesSettingController@updateCompanyDisclaimer');

    $app->get("companies/settings", "CompaniesSettingController@getCompanySetting");
    $app->patch("companies/settings", "CompaniesSettingController@updateCompanySettings");

    $app->get("companies/proxy/admin", "CompaniesSettingController@getProxyAdmin");
    $app->put("companies/proxy/admin", "CompaniesSettingController@createProxyAdmin");
    $app->delete("companies/proxy/admin", "CompaniesSettingController@disableProxyAdmin");

    $app->get('companies/disclaimer', 'CompaniesSettingController@getCompanyDisclaimer');
    $app->patch('companies/disclaimer', 'CompaniesSettingController@updateCompanyDisclaimer');

    $app->get('companies/invoices/{booking_id}', 'PaymentController@getBookingInvoiceView');

    $app->patch('companies/orders/{booking_id}/archive', 'OrdersController@bookingOrderArchive');

    $app->post('companies/bookings/{booking_id}/send/back', 'BookingsController@billBSendBack');
    $app->get("companies/payment/exist/customers/{scId}", "CustomersController@checkPaymentExistCustomerId");

    $app->post("companies/bind/stripe", "StripeController@bindStripeAccount");

    $app->get("companies/group/setting", "GroupController@getGroupSetting");
    $app->post("companies/group/setting", "GroupController@addOuterGroup");
    $app->delete("companies/group/setting", "GroupController@removeGroupSetting");
    $app->patch('companies/group/setting', 'GroupController@deleteAndAddGroup');
    $app->get('companies/out/group/check', 'GroupController@checkApiKeyAndGetLists');
    $app->get('companies/out/group/lists', 'GroupController@getOutGroupList');
    $app->get('companies/out/group/member', 'GroupController@getMemberBelong');
    $app->patch('companies/out/group/member', 'GroupController@changeOutMemberList');

    $app->patch('companies/set/{ccy}', 'CompaniesController@setCompanyCcy');

});

$app->group(['prefix' => '1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'super'], function () use ($app) {
    //需要SA权限的放在这里:
//    $app->get   ('cars',            'CarsController@cars');
    $app->get('bookings/statistics', 'StatisticController@getPlatformBookingStatistic');

    //!!!!! super
    $app->post('cars', 'CarsController@newCar');
    //check super
    $app->get('cars/count', 'CarsController@carsCount');
    //!!!!! super
    $app->post('cars/{car_id}/info', 'CarsController@updateCar');
    //check super
    $app->delete('cars/{car_id}', 'CarsController@removeCar');
    //check super
    $app->get('admins/{admin_id}/permissions', 'PermissionsController@permissions');
    //check super
    $app->post('admins/{admin_id}/permissions/{permission_id}', 'PermissionsController@addPermission');
    //check super
    $app->delete('admins/{admin_id}/permissions/{permission_id}', 'PermissionsController@removePermission');
    //check super
    $app->get('companies', 'CompaniesController@companies');
    //check super
    $app->patch('companies/{company_id}', 'CompaniesController@updateCompany');
    //check super
    $app->get('options', 'OptionsController@getAllOptions');
    //!!!!! super
    $app->post('options', 'OptionsController@addOptions');
    //!!!!! super
    $app->patch('options/{option_id}', 'OptionsController@updateOption');
    //!!!!! super
    $app->delete('options/{option_id}', 'OptionsController@deleteOption');
    //!!!!! super
    $app->post('companies/{company_id}/admins', 'AdminsController@createAdmin');
    //check super
    $app->post('users/{user_id}/admins', 'AdminsController@addAdminRole');
    //check super
    $app->get('admins', 'AdminsController@admins');
    //check super
    $app->delete('admins/{admin_id}', 'AdminsController@deleteAdminRole');
    //!!!!! super
    $app->patch('admins/{admin_id}', 'AdminsController@updateAdminInfo');
    //check super
    $app->get('drivers', 'DriversController@drivers');
    //check super
    $app->post('drivers', 'DriversController@addDriver');
    //check super
    $app->patch('drivers/{driver_id}', 'DriversController@updateDriver');
    //check super
    $app->delete('drivers/{driver_id}', 'DriversController@deleteDriver');
    //check super
    $app->get('customers', 'CustomersController@customers');
    //check super
    $app->post('customers', 'CustomersController@addCustomer');
    //!!!!! super
    $app->patch('customers/{customer_id}', 'CustomersController@updateCustomer');
    //!!!!! super
//    $app->delete('customers/{customer_id}', 'CustomersController@deleteCustomer');
    //check super
    $app->get('customers/count', 'CustomersController@customerCount');
    //check super
    $app->get('offers', 'OffersController@getOffers');
    //!!!!! super
    $app->post('offers', 'OffersController@addOffer');
    //check super
    $app->get('offers/{offer_id}', 'OffersController@getOffer');
    //!!!!! super
    $app->patch('offers/{offer_id}', 'OffersController@updateOffer');
    //check super
    $app->delete('offers/{offer_id}', 'OffersController@deleteOffer');
    //check super
    $app->get('bookings', 'BookingsController@getBookings');
//    $app->get   ('cars/{car_id}',           'CarsController@carDetail');
    //check super
    $app->get('customers/{customer_id}', 'CustomersController@customer');
    //check super
    $app->get('admins/{admin_id}', 'AdminsController@getAdmin');
    //check super
    $app->get('drivers/{driver_id}', 'DriversController@driver');
    //check super
    $app->post('superadmins/avatar', 'FilesController@uploadAvatarFile');
    //!!!!! super
    $app->post('drivers/{driver_id}/avatar', 'FilesController@uploadDriverAvatar');
    //!!!!! super
    $app->post('customers/{customer_id}/avatar', 'FilesController@uploadCustomerAvatar');
    //!!!!! super
    $app->post('admins/{admin_id}/avatar', 'FilesController@uploadAdminAvatar');
    //!!!!! super
    //unshown super
    $app->post('cars/models/{car_model_id}/image', 'FilesController@uploadCarModelImage');
    $app->post('cars/models/images/{image_id}', 'FilesController@replaceCarModelImage');
    //unshown super
    $app->delete('cars/models/image/{model_image_id}', 'FilesController@deleteCarModelImage');
    //check super
    $app->post('companies', 'CompaniesController@createAllNewCompany');

    $app->get('car/models', 'SuperManageCarController@getAllCarModelAndImageOfPlatform');
    $app->post('car/models', 'SuperManageCarController@addNewCarModel');
    $app->patch('car/models/{model_id}', 'SuperManageCarController@updateCarModel');
    $app->get('car/categories', 'SuperManageCarController@getCarCategories');
    $app->post('car/categories', 'SuperManageCarController@addCarCategories');
    $app->patch('car/categories/{id}', 'SuperManageCarController@editCarCategories');
    $app->get('car/brands', 'SuperManageCarController@getCarBrand');
    $app->post('car/brands', 'SuperManageCarController@addCarBrand');
    $app->patch('car/brands/{id}', 'SuperManageCarController@editCarBrand');

    $app->patch('companies/{company_id}/rate', 'CompaniesController@updateCompanyRate');
    $app->get('companies/{company_id}/app/url', 'CompaniesController@getCompaniesAppUrl');
    $app->patch('companies/{company_id}/app', 'CompaniesController@updateCompanyAppSetting');

    $app->get('companies/{company_id}/push/config', 'CompaniesController@getCompanyPushConfig');
    $app->post('companies/{company_id}/push/config', 'CompaniesController@addCompanyPushConfig');
    $app->patch('companies/{config_id}/push/config', 'CompaniesController@updateCompanyPushConfig');

    $app->get('companies/{company_id}/details', 'CompaniesController@getCompanyDetails');

    $app->get('companies/info', 'CompaniesController@getCompanyByName');
    $app->patch('companies/{company_id}/an/locked', 'CompanyAnSettingController@setCompanyAnLocked');
    $app->patch('update/android/app/{company_id}', "AnnexController@updateCustomerAppVersion");
    $app->patch('update/android/app/{company_id}', "AnnexController@updateDriverAppVersion");

    $app->post('assts', 'AsstController@createAsst');
    $app->get('assts', 'AsstController@getAllAssts');
    $app->get('assts/{asstId}', 'AsstController@getAsstDetail');
    $app->patch('assts/{asstId}', 'AsstController@updateAsstInfo');
    $app->delete('assts/{saleId}', 'AsstController@deleteAsstInfo');

    $app->post('sales', 'SaleController@createSale');
    $app->get('sales', 'SaleController@getAllSale');
    $app->get('sales/{saleId}', 'SaleController@getSaleDetail');
    $app->patch('sales/{saleId}', 'SaleController@updateSaleInfo');
    $app->delete('sales/{saleId}', 'SaleController@deleteSaleInfo');

    $app->get('rate/rules', 'RatePlanController@getRateRules');
    $app->patch('rate/rules', 'RatePlanController@updateRateRules');

    $app->get('com/rate', 'ComRateRulesController@getComRateRules');
    $app->patch('com/rate', 'ComRateRulesController@updateComRateRules');

    $app->get("sa/companies/bookings", "GodBookingsController@superAdminBookings");


});

$app->group(['prefix' => '/1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'sale'], function () use ($app) {
    $app->get('sale/companies', 'SaleController@getCompanies');
    $app->patch('sale/info', 'SaleController@updateSaleUserInfo');
    $app->get('sale/companies/stats', 'SaleController@getSaleCompanyState');

    $app->get("sale/companies/bookings", "GodBookingsController@saleBookings");
});
$app->group(['prefix' => '/1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'asst'], function () use ($app) {
    $app->get('asst/companies', 'AsstController@getCompanies');
    $app->patch('asst/info', 'AsstController@updateAsstUserInfo');
    $app->get('asst/companies/stats', 'AsstController@getAsstCompanyState');
    $app->get("asst/companies/bookings", "GodBookingsController@asstBookings");
});

$app->group(['prefix' => '/1', 'namespace' => 'App\Http\Controllers\v1', 'middleware' => 'login'], function () use ($app) {
    //需要login权限的放在这里:
    //check login
    $app->post('users/device/{device_token}', "UsersController@updateUsersDeviceToken");
    //check login
    $app->get('users/avatar', 'FilesController@getAvatar');
    //check login
    $app->get('companies/{company_id}', 'CompaniesController@companyDetail');
    //check login
    $app->post('users/change/password', 'UsersController@changePassword');
    //check login
    $app->get('customers/{customer_id}/avatar', 'FilesController@getCustomerAvatar');
    //check login
    $app->get('admins/{admin_id}/avatar', 'FilesController@getAdminAvatar');
    //check guest
    $app->post('logout', 'AuthController@logout');
});

$app->group(['prefix' => 'app', 'namespace' => 'App\Http\Controllers\v1'], function () use ($app) {
    $app->get("company/{id}/{plat}", "AnnexController@getCompaniesApp");
    $app->get('check/update/driver/{plate}/', "AnnexController@checkDriverAppVersion");
    $app->get('check/update/customer/{company_id}/{plate}', "AnnexController@checkCustomerAppVersion");
});
$app->get("imgs/{pName}/{fileName}", "v1\ImageResController@getImage");
$app->get("doc/swagger", function () {

    return response(File::get(storage_path('assets/swagger.json')));
});


$app->group(["prefix" => "tesft", 'namespace' => 'App\Http\Controllers\v1'], function () use ($app) {

    $app->get("/booking/email/{bookingId}/{lang}", "TestController@bookingEmail");
    $app->get("/booking/own/email/{bookingId}/{lang}", "TestController@bookingAnCompanyA");
    $app->get("/booking/exe/email/{bookingId}/{lang}", "TestController@bookingAnCompanyB");

    $app->get("/booking/invoice/{bookingId}/{lang}", "TestController@sendBookingInvoice");

    $app->get("/reset/admin/password/{lang}", "TestController@resetAdminPwd");
    $app->get("/new/admin/password/{lang}", "TestController@newAdminPwd");

    $app->get("/reset/driver/password/{lang}", "TestController@resetDriverPwd");
    $app->get("/new/driver/password/{lang}", "TestController@newDriverPwd");

    $app->get("/reset/sale/password/{lang}", "TestController@resetSalePwd");
    $app->get("/new/sale/password/{lang}", "TestController@newSale");

    $app->get("/reset/asst/password/{lang}", "TestController@resetAsstPwd");
    $app->get("/new/asst/password/{lang}", "TestController@newAsst");

    $app->get("/reset/client/password/{lang}", "TestController@resetClientPwd");
    $app->get("/new/client/password/{lang}", "TestController@newClientPwd");

    $app->get("/customer/booking/{bookingId}/{lang}", "TestController@getCustomerBooking");
    $app->get("/push/notice", "TestController@pushNotice");
    $app->get("/coupon/{code}", "TestController@testCoupon");
    $app->get("/send/back/{bookingId}/{lang}", "TestController@sendBackBookings");

    $app->get("/add/new/sale", "TestController@addNewStripeClient");

    $app->get("/get/stripe", "TestController@testStripe");

});

/*
$app->get('/', function () {
    echo "dsdsdsd";
});*/