<?php

namespace App;

class QueueName
{
    const SystemCancelCustomQuote = 'cancel-custom-quote';
    const PushBookingCancel = 'push-booking-canceled';
    const PushBookingSuccess = 'push-booking-success';
    const PushBookingChange = 'push-booking-change';
    const PushDriverMsg = 'push-driver-msg';
    const PushCustomerMsg = 'push-customer-msg';
    const EmailAdminPassword = 'email-admin-pwd';
    const EmailDriverPassword = 'email-driver-pwd';
    const EmailSalePassword = 'email-sale-pwd';
    const EmailRestPassword = 'email-reset-pwd';
    const EmailResetPassengerPassword = 'email-reset-passenger-pwd';
    const EmailResetAdminPassword = 'email-reset-admin-pwd';
    const EmailResetDriverPassword = 'email-reset-driver-pwd';
    const EmailResetSalePassword = 'email-reset-sale-pwd';
    const EmailCustomerInvoice = 'email-customer-invoice';
    const EmailCustomerBooking = 'email-customer-booking';
    const EmailCustomerBookingUpdate = 'email-customer-booking-updated';
    const EmailAdminBooking = 'email-admin-booking';
    const EmailAdminBookingSendBack = 'email-admin-booking-send-back';
    const EmailQuoteDetermine = 'email-quote-determine';
    const PayTripSettle = 'trip-settle';
    const CustomerRemoveAllCreditCard = 'customer_rm_all_cards';
    const PayPalPlatformSettle = 'pay-pal-plate-settle';
    const BookingStatistic = 'booking_statistic';
    const BookingFeedback = 'booking_feedback';
    const BookingCreateStatistic = 'booking_create_statistic';
    const SyncCustomerToGroup = 'sync_c2mc';
    const ChkCustomerGroup = 'chk_cus_group';
    const Com2ComMatch = 'com_to_com_match';
    const CarAP = 'car_ask_provide';

    //Mark 注意同步新增常量一定要添加到下面的數組中

    /**
     * @return array
     */
    public static function getQueueList()
    {
        return [
            self::SystemCancelCustomQuote=>60,
            self::PushBookingCancel=>60,
            self::PushBookingSuccess=>60,
            self::PushBookingChange=>60,
            self::PushDriverMsg=>60,
            self::PushCustomerMsg=>60,
            self::EmailAdminPassword=>100,
            self::EmailDriverPassword=>100,
            self::EmailSalePassword=>100,
            self::EmailRestPassword=>100,
            self::EmailResetPassengerPassword=>100,
            self::EmailResetAdminPassword=>100,
            self::EmailResetDriverPassword=>100,
            self::EmailResetSalePassword=>100,
            self::EmailCustomerInvoice=>100,
            self::EmailCustomerBooking=>100,
            self::EmailCustomerBookingUpdate=>100,
            self::EmailAdminBooking=>100,
            self::EmailAdminBookingSendBack=>100,
            self::EmailQuoteDetermine=>100,
            self::PayTripSettle=>200,
            self::CustomerRemoveAllCreditCard=>60,
            self::PayPalPlatformSettle=>200,
            self::BookingStatistic=>60,
            self::BookingFeedback=>60,
            self::BookingCreateStatistic=>200,
            self::SyncCustomerToGroup=>900,
            self::ChkCustomerGroup=>60,
            self::Com2ComMatch=>60,
            self::CarAP=>30,
        ];
    }

}
/*
sudo php artisan queue:listen --queue='cancel-custom-quote' --tries=3 &
sudo php artisan queue:listen --queue='push-booking-canceled' --tries=3 &
sudo php artisan queue:listen --queue='push-booking-success' --tries=3 &
sudo php artisan queue:listen --queue='push-driver-msg' --tries=3 &
sudo php artisan queue:listen --queue='push-customer-msg' --tries=3 &
sudo php artisan queue:listen --queue='email-admin-pwd' --tries=3 &
sudo php artisan queue:listen --queue='email-driver-pwd' --tries=3 &
sudo php artisan queue:listen --queue='email-reset-passenger-pwd' --tries=3 &
sudo php artisan queue:listen --queue='email-reset-admin-pwd' --tries=3 &
sudo php artisan queue:listen --queue='email-reset-driver-pwd' --tries=3 &
sudo php artisan queue:listen --queue='email-customer-invoice' --tries=3 &
sudo php artisan queue:listen --queue='email-customer-booking' --tries=3 &
sudo php artisan queue:listen --queue='email-admin-booking' --tries=3 &
sudo php artisan queue:listen --queue='email-quote-determine' --tries=3 &
sudo php artisan queue:listen --queue='trip-settle' --tries=3 &
sudo php artisan queue:listen --queue='customer_rm_all_cards' --tries=3 &
sudo php artisan queue:listen --queue='booking_create_statistic' --tries=3
sudo php artisan queue:listen --queue='sync_c2mc' --tries=3
*/
